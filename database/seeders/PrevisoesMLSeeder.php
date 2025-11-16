<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\MLService;
use App\Models\Agendamento;
use App\Models\PrevisaoMl;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrevisoesMLSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🧠 Gerando previsões ML para o dashboard...\n";

        // Limpar tabela
        DB::table('previsoes_ml')->truncate();

        $mlService = new MLService();
        $contador = 0;

        // Pegar agendamentos futuros para predições
        $agendamentosFuturos = Agendamento::where('data', '>', Carbon::now())
            ->where('status', 'agendado')
            ->limit(50) // Limitar para não sobrecarregar
            ->get();

        echo "Processando {$agendamentosFuturos->count()} agendamentos futuros...\n";

        foreach ($agendamentosFuturos as $agendamento) {
            try {
                // Gerar predição usando o MLService (que já tem fallback)
                $resultado = $mlService->predictFalta($agendamento->id_paciente, $agendamento->data);
                $contador++;

                if ($contador % 10 == 0) {
                    echo "Processados: {$contador}\n";
                }
            } catch (\Exception $e) {
                echo "Erro ao processar agendamento {$agendamento->id}: {$e->getMessage()}\n";
            }
        }

        // Gerar também algumas previsões históricas para estatísticas
        echo "Gerando previsões históricas para estatísticas...\n";

        $agendamentosPassados = Agendamento::where('data', '>=', Carbon::now()->subDays(30))
            ->where('data', '<=', Carbon::now())
            ->whereIn('status', ['realizado', 'faltou', 'cancelado'])
            ->limit(100)
            ->get();

        foreach ($agendamentosPassados as $agendamento) {
            try {
                // Criar previsão histórica baseada no resultado real
                $probabilidadeReal = ($agendamento->status == 'faltou') ? 0.8 : 0.2;
                $probabilidadeReal += (mt_rand(-20, 20) / 100); // Adicionar variação
                $probabilidadeReal = max(0.05, min(0.95, $probabilidadeReal));

                $nivelRisco = $probabilidadeReal > 0.6 ? 'alto' :
                             ($probabilidadeReal > 0.3 ? 'medio' : 'baixo');

                PrevisaoMl::create([
                    'paciente_id' => $agendamento->id_paciente,
                    'tipo_previsao' => 'probabilidade_falta',
                    'valor_previsao' => $probabilidadeReal,
                    'confianca' => mt_rand(70, 95) / 100,
                    'data_previsao' => $agendamento->data,
                    'data_calculo' => $agendamento->data,
                    'modelo_utilizado' => 'Historical_Fallback_v1.0',
                    'parametros_entrada' => [
                        'status_real' => $agendamento->status,
                        'data' => $agendamento->data,
                        'hora' => $agendamento->hora,
                        'nivel_risco' => $nivelRisco
                    ],
                    'acao_recomendada' => $nivelRisco == 'alto' ?
                        'Confirmar presença por telefone' : 'Monitoramento padrão',
                    'executada' => mt_rand(0, 1) == 1
                ]);

                $contador++;
            } catch (\Exception $e) {
                echo "Erro ao criar previsão histórica: {$e->getMessage()}\n";
            }
        }

        echo "✅ Total de previsões geradas: {$contador}\n";
        echo "📊 Verificando dados finais...\n";

        $total = PrevisaoMl::count();
        $altoRisco = PrevisaoMl::where('valor_previsao', '>=', 0.6)->count();
        $medioRisco = PrevisaoMl::whereBetween('valor_previsao', [0.3, 0.6])->count();
        $baixoRisco = PrevisaoMl::where('valor_previsao', '<', 0.3)->count();
        $executadas = PrevisaoMl::where('executada', true)->count();

        echo "📈 ESTATÍSTICAS FINAIS:\n";
        echo "   Total: {$total}\n";
        echo "   Alto risco: {$altoRisco}\n";
        echo "   Médio risco: {$medioRisco}\n";
        echo "   Baixo risco: {$baixoRisco}\n";
        echo "   Executadas: {$executadas}\n";
        echo "🎉 Dashboard ML pronto para uso!\n";
    }
}
