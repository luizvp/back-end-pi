<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrevisaoMl;
use App\Models\Agendamento;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrevisoesMLAtuaisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🔄 Gerando previsões ML com DATAS ATUAIS para o dashboard...\n";

        $contador = 0;

        // 1. PREVISÕES PARA ESTE MÊS (data_calculo recente)
        echo "📅 Gerando previsões para OUTUBRO/2025...\n";

        $pacientes = Paciente::limit(30)->get();

        foreach ($pacientes as $paciente) {
            for ($diasAtras = 30; $diasAtras >= 1; $diasAtras--) {
                if (mt_rand(1, 3) != 1) continue; // 33% chance cada dia

                $dataCalculo = Carbon::now()->subDays($diasAtras);
                $dataPrevisao = $dataCalculo->copy()->addDays(mt_rand(1, 7));

                $probabilidade = mt_rand(5, 95) / 100;
                $nivelRisco = $probabilidade > 0.6 ? 'alto' :
                             ($probabilidade > 0.3 ? 'medio' : 'baixo');

                PrevisaoMl::create([
                    'paciente_id' => $paciente->id,
                    'tipo_previsao' => 'probabilidade_falta',
                    'valor_previsao' => $probabilidade,
                    'confianca' => mt_rand(70, 95) / 100,
                    'data_previsao' => $dataPrevisao,
                    'data_calculo' => $dataCalculo,
                    'modelo_utilizado' => 'Dashboard_Current_v1.0',
                    'parametros_entrada' => [
                        'dashboard_seed' => true,
                        'data_geracao' => $dataCalculo->format('Y-m-d'),
                        'nivel_risco' => $nivelRisco
                    ],
                    'acao_recomendada' => $nivelRisco == 'alto' ?
                        'Ligar para confirmar presença' : 'Monitoramento padrão',
                    'executada' => mt_rand(0, 1) == 1
                ]);

                $contador++;
            }
        }

        // 2. PREVISÕES PARA HOJE E AMANHÃ (alto risco hoje)
        echo "🎯 Gerando previsões de ALTO RISCO para HOJE...\n";

        $hoje = Carbon::today();
        $amanha = Carbon::tomorrow();

        // Alto risco HOJE
        for ($i = 0; $i < 8; $i++) {
            $paciente = $pacientes->random();

            PrevisaoMl::create([
                'paciente_id' => $paciente->id,
                'tipo_previsao' => 'probabilidade_falta',
                'valor_previsao' => mt_rand(60, 95) / 100, // Garantir alto risco
                'confianca' => mt_rand(80, 95) / 100,
                'data_previsao' => $hoje,
                'data_calculo' => $hoje->copy()->subHours(mt_rand(1, 12)),
                'modelo_utilizado' => 'HighRisk_Today_v1.0',
                'parametros_entrada' => [
                    'alert_type' => 'high_risk_today',
                    'urgency' => 'high'
                ],
                'acao_recomendada' => 'URGENTE: Ligar para confirmar presença HOJE',
                'executada' => mt_rand(0, 2) == 0 // 33% executadas
            ]);

            $contador++;
        }

        // 3. AÇÕES PENDENTES (não executadas, futuras)
        echo "⏳ Gerando AÇÕES PENDENTES (futuras)...\n";

        for ($diasFuturos = 1; $diasFuturos <= 10; $diasFuturos++) {
            $dataFutura = Carbon::now()->addDays($diasFuturos);

            for ($i = 0; $i < 3; $i++) {
                $paciente = $pacientes->random();
                $probabilidade = mt_rand(40, 90) / 100;

                PrevisaoMl::create([
                    'paciente_id' => $paciente->id,
                    'tipo_previsao' => 'probabilidade_falta',
                    'valor_previsao' => $probabilidade,
                    'confianca' => mt_rand(75, 90) / 100,
                    'data_previsao' => $dataFutura,
                    'data_calculo' => Carbon::now()->subHours(mt_rand(1, 24)),
                    'modelo_utilizado' => 'Future_Actions_v1.0',
                    'parametros_entrada' => [
                        'action_pending' => true,
                        'target_date' => $dataFutura->format('Y-m-d')
                    ],
                    'acao_recomendada' => $probabilidade > 0.6 ?
                        'Ligar 1 dia antes' : 'SMS de lembrete',
                    'executada' => false // TODAS PENDENTES
                ]);

                $contador++;
            }
        }

        echo "✅ Total de previsões ATUAIS geradas: {$contador}\n";

        // Verificar estatísticas finais
        echo "📊 VERIFICAÇÃO FINAL:\n";

        $totalGeral = PrevisaoMl::count();
        $esteMsg = PrevisaoMl::where('data_calculo', '>=', Carbon::now()->startOfMonth())->count();
        $altoRiscoHoje = PrevisaoMl::where('data_previsao', Carbon::today())
            ->where('valor_previsao', '>=', 0.6)->count();
        $acoesPendentes = PrevisaoMl::where('executada', false)
            ->where('data_previsao', '>=', Carbon::now())->count();

        echo "   📈 Total geral: {$totalGeral}\n";
        echo "   📅 Este mês: {$esteMsg}\n";
        echo "   🚨 Alto risco HOJE: {$altoRiscoHoje}\n";
        echo "   ⏳ Ações pendentes: {$acoesPendentes}\n";
        echo "🎉 Dashboard ML deve estar funcionando PERFEITAMENTE agora!\n";
    }
}
