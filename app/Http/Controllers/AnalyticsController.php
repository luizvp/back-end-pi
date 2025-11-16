<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;
use App\Models\Agendamento;
use App\Models\SessaoFisioterapia;
use App\Models\Tratamento;
use App\Models\Pagamento;
use App\Models\PrevisaoMl;
use App\Models\DadoIot;

class AnalyticsController extends Controller
{
    /**
     * Dashboard principal com métricas gerais
     */
    public function dashboard()
    {
        try {
            $metricas = [
                'pacientes_ativos' => Paciente::ativos()->count(),
                'tratamentos_ativo' => Tratamento::ativos()->count(),
                'sessoes_mes' => SessaoFisioterapia::porPeriodo(now()->startOfMonth(), now())->realizadas()->count(),
                'receita_mes' => Pagamento::pagos()
                    ->whereBetween('data_pagamento', [now()->startOfMonth(), now()])
                    ->sum('valor_total'),
                'taxa_comparecimento' => Agendamento::getTaxaComparecimento(30),
                'previsoes_alto_risco' => PrevisaoMl::getPacientesAltoRisco()->count()
            ];

            // Dados para gráficos
            $graficos = [
                'sessoes_por_dia' => $this->getSessoesPorDia(7),
                'distribuicao_diagnosticos' => $this->getDistribuicaoDiagnosticos(30),
                'evolucao_receita' => $this->getEvolucaoReceita(6),
                'status_tratamentos' => Tratamento::selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')->get()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'metricas' => $metricas,
                    'graficos' => $graficos
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Análises de tratamento
     */
    public function tratamentos()
    {
        try {
            $dados = [
                'tempo_medio_tratamento' => $this->getTempoMedioTratamento(),
                'taxa_sucesso_por_diagnostico' => $this->getTaxaSucessoPorDiagnostico(),
                'tratamentos_longos' => $this->getTratamentosLongos(),
                'eficacia_por_equipamento' => $this->getEficaciaPorEquipamento()
            ];

            return response()->json([
                'success' => true,
                'data' => $dados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar análises de tratamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Previsões e alertas ML
     */
    public function previsoes()
    {
        try {
            $dados = [
                'pacientes_risco_falta' => PrevisaoMl::getPacientesAltoRisco('probabilidade_falta', 0.7),
                'previsoes_demanda' => PrevisaoMl::demandaPeriodo()
                    ->where('data_previsao', '>=', now())
                    ->orderBy('data_previsao')
                    ->take(30)
                    ->get(),
                'resumo_previsoes' => PrevisaoMl::getResumoPrevisoes(30),
                'alertas_equipamentos' => $this->getAlertasEquipamentos()
            ];

            return response()->json([
                'success' => true,
                'data' => $dados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar previsões',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dados IoT em tempo real
     */
    public function iot()
    {
        try {
            $dados = [
                'leituras_hoje' => DadoIot::hoje()->count(),
                'sensores_ativos' => DadoIot::ultimas24h()
                    ->distinct('equipamento_id')
                    ->count(),
                'alertas_criticos' => $this->getAlertasCriticos(),
                'tendencias' => $this->getTendenciasIot()
            ];

            return response()->json([
                'success' => true,
                'data' => $dados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados IoT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Métodos auxiliares privados
    private function getSessoesPorDia($dias = 7)
    {
        return SessaoFisioterapia::selectRaw('DATE(data_sessao) as data, COUNT(*) as total')
            ->where('data_sessao', '>=', now()->subDays($dias))
            ->where('status', 'realizada')
            ->groupBy('data')
            ->orderBy('data')
            ->get();
    }

    private function getDistribuicaoDiagnosticos($dias = 30)
    {
        return Tratamento::join('prontuarios', 'tratamentos.prontuario_id', '=', 'prontuarios.id')
            ->join('diagnosticos_padronizados', 'prontuarios.diagnostico_cid_id', '=', 'diagnosticos_padronizados.id')
            ->where('tratamentos.data_inicio', '>=', now()->subDays($dias))
            ->selectRaw('diagnosticos_padronizados.categoria, COUNT(*) as total')
            ->groupBy('diagnosticos_padronizados.categoria')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function getEvolucaoReceita($meses = 6)
    {
        return Pagamento::selectRaw('DATE_FORMAT(data_pagamento, "%Y-%m") as mes, SUM(valor_total) as receita')
            ->where('status_pagamento', 'pago')
            ->where('data_pagamento', '>=', now()->subMonths($meses))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
    }

    private function getTempoMedioTratamento()
    {
        return Tratamento::concluidos()
            ->whereNotNull('data_alta_real')
            ->selectRaw('AVG(DATEDIFF(data_alta_real, data_inicio)) as tempo_medio')
            ->first()
            ->tempo_medio ?? 0;
    }

    private function getTaxaSucessoPorDiagnostico()
    {
        return Tratamento::join('prontuarios', 'tratamentos.prontuario_id', '=', 'prontuarios.id')
            ->join('diagnosticos_padronizados', 'prontuarios.diagnostico_cid_id', '=', 'diagnosticos_padronizados.id')
            ->selectRaw('
                diagnosticos_padronizados.categoria,
                COUNT(*) as total,
                SUM(CASE WHEN tratamentos.status = "concluido" THEN 1 ELSE 0 END) as concluidos,
                ROUND(SUM(CASE WHEN tratamentos.status = "concluido" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as taxa_sucesso
            ')
            ->groupBy('diagnosticos_padronizados.categoria')
            ->having('total', '>=', 5) // Apenas categorias com pelo menos 5 casos
            ->orderBy('taxa_sucesso', 'desc')
            ->get();
    }

    private function getTratamentosLongos()
    {
        return Tratamento::ativos()
            ->where('data_inicio', '<=', now()->subMonths(6))
            ->with(['paciente', 'prontuario.diagnosticoPadronizado'])
            ->orderBy('data_inicio')
            ->get();
    }

    private function getEficaciaPorEquipamento()
    {
        // Esta seria uma análise complexa baseada nos dados de IoT
        // Por ora, retorna dados mock
        return [
            ['equipamento' => 'Esteira 1', 'utilizacao' => 85, 'eficacia' => 92],
            ['equipamento' => 'Ultrassom', 'utilizacao' => 70, 'eficacia' => 88],
            ['equipamento' => 'Laser', 'utilizacao' => 60, 'eficacia' => 85]
        ];
    }

    private function getAlertasEquipamentos()
    {
        return [
            'manutencao_vencida' => \App\Models\Equipamento::manutencaoVencida()->count(),
            'equipamentos_inativos' => \App\Models\Equipamento::inativos()->count()
        ];
    }

    private function getAlertasCriticos()
    {
        // Simular alertas baseados em IoT
        $alertas = [];

        // Frequência cardíaca alta
        $fcAlta = DadoIot::frequenciaCardiaca()
            ->where('valor', '>', 120)
            ->where('contexto', 'repouso')
            ->ultimas24h()
            ->count();

        if ($fcAlta > 0) {
            $alertas[] = [
                'tipo' => 'Frequência Cardíaca Alta',
                'quantidade' => $fcAlta,
                'nivel' => 'alto'
            ];
        }

        return $alertas;
    }

    private function getTendenciasIot()
    {
        return DadoIot::selectRaw('
            DATE(timestamp) as data,
            tipo_sensor,
            AVG(valor) as valor_medio
        ')
        ->where('timestamp', '>=', now()->subDays(7))
        ->groupBy('data', 'tipo_sensor')
        ->orderBy('data')
        ->get()
        ->groupBy('tipo_sensor');
    }
}
