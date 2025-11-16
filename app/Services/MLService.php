<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PrevisaoMl;
use App\Models\Agendamento;
use App\Models\Paciente;
use Carbon\Carbon;

class MLService
{
    private $mlApiUrl;
    private $timeout;

    public function __construct()
    {
        $this->mlApiUrl = env('ML_API_URL', 'http://localhost:5000');
        $this->timeout = env('ML_API_TIMEOUT', 30);
    }

    /**
     * Verificar se a API ML está funcionando
     */
    public function checkHealth()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->mlApiUrl . '/health');

            return $response->successful() ? $response->json() : false;
        } catch (\Exception $e) {
            Log::error('ML API Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Predizer probabilidade de falta para um paciente específico
     */
    public function predictFalta($pacienteId, $dataAgendamento = null)
    {
        try {
            // Buscar dados do paciente
            $paciente = Paciente::find($pacienteId);
            if (!$paciente) {
                throw new \Exception("Paciente não encontrado: {$pacienteId}");
            }

            // Calcular histórico de faltas
            $historicoFaltas = $this->calcularHistoricoFaltas($pacienteId);

            // Determinar dia da semana do agendamento
            $diaSemana = $dataAgendamento ?
                Carbon::parse($dataAgendamento)->dayOfWeek :
                Carbon::now()->dayOfWeek;

            // Calcular dias desde última consulta
            $diasDesdeUltima = $this->calcularDiasDesdeUltima($pacienteId);

            // Buscar diagnóstico mais recente
            $diagnosticoCid = $this->obterDiagnosticoRecente($pacienteId);

            // Preparar dados para a API
            $payload = [
                'paciente_id' => $pacienteId,
                'idade' => $this->calcularIdade($paciente->data_nascimento),
                'historico_faltas' => $historicoFaltas,
                'dia_semana' => $diaSemana,
                'diagnostico_cid' => $diagnosticoCid,
                'dias_desde_ultima' => $diasDesdeUltima
            ];

            Log::info('Enviando dados para ML API', $payload);

            // Fazer requisição para a API Python
            $response = Http::timeout($this->timeout)
                ->post($this->mlApiUrl . '/predict-falta', $payload);

            if (!$response->successful()) {
                throw new \Exception('ML API Error: ' . $response->body());
            }

            $resultado = $response->json();

            // Salvar previsão no banco
            $this->salvarPrevisao($resultado, $pacienteId, $dataAgendamento);

            return $resultado;

        } catch (\Exception $e) {
            Log::error('Erro na predição ML: ' . $e->getMessage());

            // Retornar predição fallback
            return $this->fallbackPrediction($pacienteId);
        }
    }

    /**
     * Predizer para múltiplos pacientes (batch)
     */
    public function predictBatch($pacienteIds, $dataAgendamento = null)
    {
        try {
            $pacientes = [];

            foreach ($pacienteIds as $pacienteId) {
                $paciente = Paciente::find($pacienteId);
                if (!$paciente) continue;

                $pacientes[] = [
                    'paciente_id' => $pacienteId,
                    'idade' => $this->calcularIdade($paciente->data_nascimento),
                    'historico_faltas' => $this->calcularHistoricoFaltas($pacienteId),
                    'dia_semana' => $dataAgendamento ?
                        Carbon::parse($dataAgendamento)->dayOfWeek :
                        Carbon::now()->dayOfWeek,
                    'diagnostico_cid' => $this->obterDiagnosticoRecente($pacienteId),
                    'dias_desde_ultima' => $this->calcularDiasDesdeUltima($pacienteId)
                ];
            }

            $response = Http::timeout($this->timeout)
                ->post($this->mlApiUrl . '/predict-batch', ['pacientes' => $pacientes]);

            if (!$response->successful()) {
                throw new \Exception('ML API Batch Error: ' . $response->body());
            }

            $resultados = $response->json();

            // Salvar todas as previsões
            foreach ($resultados['predictions'] as $resultado) {
                $this->salvarPrevisao($resultado, $resultado['paciente_id'], $dataAgendamento);
            }

            return $resultados;

        } catch (\Exception $e) {
            Log::error('Erro na predição batch ML: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obter informações sobre o modelo ML
     */
    public function getModelInfo()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->mlApiUrl . '/model-info');

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('Erro ao obter info do modelo ML: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcular histórico de faltas do paciente (últimos 90 dias)
     */
    private function calcularHistoricoFaltas($pacienteId)
    {
        $dataInicio = Carbon::now()->subDays(90);

        $totalAgendamentos = Agendamento::where('id_paciente', $pacienteId)
            ->where('data', '>=', $dataInicio)
            ->whereIn('status', ['realizado', 'faltou'])
            ->count();

        if ($totalAgendamentos == 0) {
            return 0.0; // Nenhum histórico
        }

        $totalFaltas = Agendamento::where('id_paciente', $pacienteId)
            ->where('data', '>=', $dataInicio)
            ->where('status', 'faltou')
            ->count();

        return $totalFaltas / $totalAgendamentos;
    }

    /**
     * Calcular dias desde a última consulta
     */
    private function calcularDiasDesdeUltima($pacienteId)
    {
        $ultimaConsulta = Agendamento::where('id_paciente', $pacienteId)
            ->where('status', 'realizado')
            ->orderBy('data', 'desc')
            ->first();

        if (!$ultimaConsulta) {
            return 365; // Nunca veio
        }

        return Carbon::parse($ultimaConsulta->data)->diffInDays(Carbon::now());
    }

    /**
     * Obter diagnóstico mais recente do paciente
     */
    private function obterDiagnosticoRecente($pacienteId)
    {
        $prontuario = \DB::table('prontuarios')
            ->join('diagnosticos_padronizados', 'prontuarios.diagnostico_cid_id', '=', 'diagnosticos_padronizados.id')
            ->where('prontuarios.id_paciente', $pacienteId)
            ->orderBy('prontuarios.data_criacao', 'desc')
            ->select('diagnosticos_padronizados.codigo_cid')
            ->first();

        return $prontuario ? $prontuario->codigo_cid : 'M79.3'; // Default
    }

    /**
     * Calcular idade do paciente
     */
    private function calcularIdade($dataNascimento)
    {
        if (!$dataNascimento) {
            return 40; // Idade padrão
        }

        return Carbon::parse($dataNascimento)->age;
    }

    /**
     * Salvar previsão no banco de dados
     */
    private function salvarPrevisao($resultado, $pacienteId, $dataAgendamento = null)
    {
        try {
            PrevisaoMl::create([
                'paciente_id' => $pacienteId,
                'tipo_previsao' => 'probabilidade_falta',
                'valor_previsao' => $resultado['probabilidade_falta'],
                'confianca' => $resultado['confianca'] ?? null,
                'data_previsao' => $dataAgendamento ? Carbon::parse($dataAgendamento) : Carbon::tomorrow(),
                'modelo_utilizado' => 'RandomForest_v1.0',
                'parametros_entrada' => [
                    'idade' => $resultado['idade'] ?? null,
                    'historico_faltas' => $resultado['historico_faltas'] ?? null,
                    'dia_semana' => $resultado['dia_semana'] ?? null,
                    'fatores_risco' => $resultado['fatores_risco'] ?? []
                ],
                'acao_recomendada' => $resultado['acao_recomendada'] ?? 'Monitoramento padrão',
                'executada' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar previsão ML: ' . $e->getMessage());
        }
    }

    /**
     * Predição fallback quando a API ML não está disponível
     */
    private function fallbackPrediction($pacienteId)
    {
        $historicoFaltas = $this->calcularHistoricoFaltas($pacienteId);

        // Cálculo heurístico simples
        $probabilidade = 0.15 + ($historicoFaltas * 0.4);
        $probabilidade = min($probabilidade, 0.9);

        return [
            'paciente_id' => $pacienteId,
            'probabilidade_falta' => $probabilidade,
            'confianca' => 0.5,
            'fatores_risco' => $historicoFaltas > 0.3 ? ['Histórico de faltas elevado'] : [],
            'nivel_risco' => $probabilidade > 0.5 ? 'alto' : ($probabilidade > 0.3 ? 'medio' : 'baixo'),
            'acao_recomendada' => $probabilidade > 0.5 ?
                'Ligar para confirmar presença' : 'Monitoramento padrão',
            'timestamp' => Carbon::now()->toISOString(),
            'fonte' => 'fallback'
        ];
    }

    /**
     * Analisar agendamentos de amanhã e gerar previsões
     */
    public function analisarAgendamentosAmanha()
    {
        $amanha = Carbon::tomorrow();

        $agendamentos = Agendamento::where('data', $amanha->toDateString())
            ->where('status', 'agendado')
            ->get();

        $previsoes = [];

        foreach ($agendamentos as $agendamento) {
            $previsao = $this->predictFalta($agendamento->id_paciente, $amanha);
            $previsoes[] = [
                'agendamento_id' => $agendamento->id,
                'paciente_nome' => $agendamento->paciente->nome ?? 'N/A',
                'hora' => $agendamento->hora,
                'previsao' => $previsao
            ];
        }

        return $previsoes;
    }

    /**
     * Obter pacientes com alto risco de falta
     */
    public function getPacientesAltoRisco($limite = 0.6)
    {
        return PrevisaoMl::with('paciente')
            ->where('tipo_previsao', 'probabilidade_falta')
            ->where('valor_previsao', '>=', $limite)
            ->where('data_previsao', '>=', Carbon::today())
            ->where('executada', false)
            ->orderBy('valor_previsao', 'desc')
            ->get();
    }

    // ===== NOVOS MÉTODOS DE PREVISÃO DE DEMANDA =====

    /**
     * Predizer demanda de agendamentos para um período
     */
    public function predictDemand($startDate, $endDate, $diagnostico = null)
    {
        try {
            $payload = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'diagnostico' => $diagnostico
            ];

            Log::info('Enviando dados para predição de demanda', $payload);

            $response = Http::timeout($this->timeout)
                ->post($this->mlApiUrl . '/predict-demand', $payload);

            if (!$response->successful()) {
                throw new \Exception('ML API Demand Error: ' . $response->body());
            }

            $resultado = $response->json();

            // Salvar previsão de demanda no banco se necessário
            if ($resultado['success'] ?? false) {
                $this->salvarPrevisaoDemanda($resultado, $startDate, $endDate, $diagnostico);
            }

            return $resultado;

        } catch (\Exception $e) {
            Log::error('Erro na predição de demanda ML: ' . $e->getMessage());

            // Retornar predição fallback
            return $this->fallbackDemandPrediction($startDate, $endDate, $diagnostico);
        }
    }

    /**
     * Obter tendências de demanda
     */
    public function getDemandTrends($periodDays = 90)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->mlApiUrl . '/demand-trends', [
                    'days' => $periodDays
                ]);

            if (!$response->successful()) {
                throw new \Exception('ML API Trends Error: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Erro ao obter tendências de demanda: ' . $e->getMessage());
            return $this->fallbackTrends($periodDays);
        }
    }

    /**
     * Obter demanda por diagnóstico
     */
    public function getDemandByDiagnosis($periodDays = 30)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->mlApiUrl . '/demand-by-diagnosis', [
                    'days' => $periodDays
                ]);

            if (!$response->successful()) {
                throw new \Exception('ML API Diagnosis Error: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Erro ao obter demanda por diagnóstico: ' . $e->getMessage());
            return $this->fallbackDiagnosisDemand($periodDays);
        }
    }

    /**
     * Obter análise de sazonalidade
     */
    public function getSeasonalAnalysis()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->mlApiUrl . '/seasonal-analysis');

            if (!$response->successful()) {
                throw new \Exception('ML API Seasonal Error: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Erro ao obter análise sazonal: ' . $e->getMessage());
            return $this->fallbackSeasonalAnalysis();
        }
    }

    /**
     * Salvar previsão de demanda no banco
     */
    private function salvarPrevisaoDemanda($resultado, $startDate, $endDate, $diagnostico = null)
    {
        try {
            PrevisaoMl::create([
                'paciente_id' => null, // Demanda é geral, não específica por paciente
                'tipo_previsao' => 'demanda_periodo',
                'valor_previsao' => $resultado['predicted_appointments'] ?? 0,
                'confianca' => $resultado['model_confidence'] ?? 0.6,
                'data_previsao' => Carbon::parse($startDate),
                'data_fim_previsao' => Carbon::parse($endDate),
                'modelo_utilizado' => 'DemandPredictor_v1.0',
                'parametros_entrada' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'diagnostico' => $diagnostico,
                    'seasonal_factors' => $resultado['seasonal_factors'] ?? null
                ],
                'acao_recomendada' => $this->getRecommendedActionForDemand($resultado['predicted_appointments'] ?? 0),
                'executada' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar previsão de demanda: ' . $e->getMessage());
        }
    }

    /**
     * Obter ação recomendada baseada na demanda prevista
     */
    private function getRecommendedActionForDemand($predictedAppointments)
    {
        $dailyAvg = $predictedAppointments / 30; // Assumindo período mensal

        if ($dailyAvg > 20) {
            return 'Alto volume previsto - considerar aumentar capacidade de atendimento';
        } elseif ($dailyAvg < 8) {
            return 'Baixo volume previsto - considerar campanhas de marketing ou promoções';
        } else {
            return 'Volume dentro do esperado - manter operação normal';
        }
    }

    /**
     * Predição de demanda fallback
     */
    private function fallbackDemandPrediction($startDate, $endDate, $diagnostico = null)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end) + 1;

        // Estimativa baseada em média histórica
        $avgDailyAppointments = 15;
        $totalPredicted = $avgDailyAppointments * $days;

        // Ajustar por fins de semana (assumindo 20% da capacidade)
        $weekends = 0;
        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            if ($date->isWeekend()) {
                $weekends++;
            }
        }

        $totalPredicted = ($totalPredicted - ($weekends * $avgDailyAppointments)) + ($weekends * $avgDailyAppointments * 0.2);

        // Gerar dados do gráfico de forecast (forecast_data)
        $forecastData = [];
        for ($i = 0; $i < $days; $i++) {
            $currentDate = $start->copy()->addDays($i);

            $baseDaily = $avgDailyAppointments;

            // Variar por dia da semana
            if ($currentDate->isWeekend()) {
                $baseDaily *= 0.3;
            } elseif ($currentDate->dayOfWeek == 1) { // Segunda
                $baseDaily *= 1.2;
            } elseif ($currentDate->dayOfWeek == 2) { // Terça
                $baseDaily *= 1.1;
            }

            // Adicionar variação sazonal suave
            $seasonalFactor = 1 + (sin(($i / $days) * 2 * pi()) * 0.2);
            $predicted = max(2, $baseDaily * $seasonalFactor);

            $forecastData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'predicted' => round($predicted, 1),
                'confidence_upper' => round($predicted * 1.2, 1),
                'confidence_lower' => round($predicted * 0.8, 1)
            ];
        }

        return [
            'success' => true,
            'period' => "$startDate to $endDate",
            'predicted_appointments' => (int)$totalPredicted,
            'daily_average' => round($totalPredicted / $days, 1),
            'forecast_data' => $forecastData, // DADOS PARA O GRÁFICO
            'confidence_interval' => [(int)($totalPredicted * 0.8), (int)($totalPredicted * 1.2)],
            'seasonal_factors' => [
                'weekend_factor' => 0.2,
                'holiday_factor' => 0.1
            ],
            'holiday_impact' => -5,
            'diagnostico_breakdown' => [
                'M79.3' => (int)($totalPredicted * 0.3),
                'M25.5' => (int)($totalPredicted * 0.25),
                'M54.5' => (int)($totalPredicted * 0.2),
                'S72.0' => (int)($totalPredicted * 0.15),
                'M17.9' => (int)($totalPredicted * 0.1)
            ],
            'model_confidence' => 0.6,
            'timestamp' => Carbon::now()->toISOString(),
            'source' => 'fallback'
        ];
    }

    /**
     * Tendências fallback
     */
    private function fallbackTrends($periodDays)
    {
        $historical = [];
        $startDate = Carbon::now()->subDays($periodDays);

        for ($i = 0; $i < $periodDays; $i++) {
            $date = $startDate->copy()->addDays($i);
            $baseAppointments = 15;

            // Variar por dia da semana
            if ($date->isWeekend()) {
                $baseAppointments *= 0.2;
            } elseif ($date->dayOfWeek == 1) { // Segunda
                $baseAppointments *= 1.3;
            } elseif ($date->dayOfWeek == 2) { // Terça
                $baseAppointments *= 1.2;
            }

            // Adicionar ruído
            $appointments = max(0, $baseAppointments + rand(-3, 5));

            $historical[] = [
                'date' => $date->format('Y-m-d'),
                'appointments' => (int)$appointments
            ];
        }

        return [
            'success' => true,
            'historical_data' => $historical,
            'period_days' => $periodDays,
            'average_daily' => 15,
            'trend_direction' => 'stable',
            'seasonality_detected' => true,
            'source' => 'fallback'
        ];
    }

    /**
     * Demanda por diagnóstico fallback
     */
    private function fallbackDiagnosisDemand($periodDays)
    {
        $totalPredicted = 15 * $periodDays;

        $breakdown = [
            'M79.3' => (int)($totalPredicted * 0.3),
            'M25.5' => (int)($totalPredicted * 0.25),
            'M54.5' => (int)($totalPredicted * 0.2),
            'S72.0' => (int)($totalPredicted * 0.15),
            'M17.9' => (int)($totalPredicted * 0.1)
        ];

        // Mapeamento dos códigos CID para nomes completos
        $diagnosticNames = [
            'M79.3' => 'M79.3 - Fibromialgia',
            'M25.5' => 'M25.5 - Artrose',
            'M54.5' => 'M54.5 - Dor lombar',
            'S72.0' => 'S72.0 - Fraturas',
            'M17.9' => 'M17.9 - Gonartrose'
        ];

        $pieData = [];
        foreach ($breakdown as $diagnostico => $count) {
            $pieData[] = [
                'name' => $diagnosticNames[$diagnostico] ?? $diagnostico,
                'value' => $count,
                'percentage' => round(($count / $totalPredicted) * 100, 1)
            ];
        }

        return [
            'success' => true,
            'period_days' => $periodDays,
            'total_appointments' => $totalPredicted,
            'breakdown' => $breakdown,
            'pie_data' => $pieData,
            'timestamp' => Carbon::now()->toISOString(),
            'source' => 'fallback'
        ];
    }

    /**
     * Análise sazonal fallback
     */
    private function fallbackSeasonalAnalysis()
    {
        return [
            'success' => true,
            'seasonal_factors' => [
                'monthly' => [
                    'Janeiro' => 1.2,
                    'Fevereiro' => 1.1,
                    'Março' => 1.0,
                    'Abril' => 0.9,
                    'Maio' => 0.8,
                    'Junho' => 1.1,
                    'Julho' => 1.2,
                    'Agosto' => 1.1,
                    'Setembro' => 0.9,
                    'Outubro' => 0.8,
                    'Novembro' => 0.7,
                    'Dezembro' => 0.6
                ],
                'weekly' => [
                    'Segunda' => 1.3,
                    'Terça' => 1.2,
                    'Quarta' => 1.0,
                    'Quinta' => 0.9,
                    'Sexta' => 0.8,
                    'Sábado' => 0.4,
                    'Domingo' => 0.3
                ]
            ],
            'insights' => [
                'Junho e Julho têm maior demanda (inverno)',
                'Segunda e Terça são os dias de maior procura',
                'Dezembro tem menor demanda (férias)',
                'Feriados reduzem drasticamente os agendamentos'
            ],
            'timestamp' => Carbon::now()->toISOString(),
            'source' => 'fallback'
        ];
    }
}
