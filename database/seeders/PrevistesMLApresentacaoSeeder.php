<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrevisaoMl;
use App\Models\Agendamento;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PrevistesMLApresentacaoSeeder extends Seeder
{
    /**
     * Seeder para criar dados ML ricos para apresentação acadêmica
     */
    public function run(): void
    {
        echo "🎓 Criando dados ML para apresentação acadêmica...\n";

        // Limpar previsões existentes (incluindo dados antigos)
        DB::table('previsoes_ml')->truncate();

        // Garantir que sempre criamos dados a partir de HOJE (mesmo se executado em dias diferentes)
        $dataHoje = Carbon::now()->startOfDay();
        $previsoes = [];
        $contadorId = 1;

        echo "📅 Data base para criação dos dados: " . $dataHoje->format('d/m/Y') . "\n";

        // Obter alguns pacientes para usar nos exemplos
        $pacientes = Paciente::take(20)->get();

        // ===== CENÁRIOS DE ALTO RISCO =====
        $cenarios_alto_risco = [
            [
                'probabilidade' => 0.85,
                'fatores' => ['Histórico de 4 faltas nos últimos 30 dias', 'Paciente idoso (78 anos)', 'Agendamento em horário tardio'],
                'acao' => 'URGENTE: Ligar 24h antes para confirmar presença e oferecer reagendamento',
                'contexto' => 'Paciente com padrão histórico de faltas frequentes'
            ],
            [
                'probabilidade' => 0.92,
                'fatores' => ['Última falta há 3 dias', 'Segunda-feira (maior índice de faltas)', 'Chuva prevista para o dia'],
                'acao' => 'Ligar na sexta anterior + SMS lembrete no domingo + confirmação no dia',
                'contexto' => 'Paciente jovem com baixo comprometimento'
            ],
            [
                'probabilidade' => 0.78,
                'fatores' => ['3 reagendamentos no último mês', 'Trabalhador autônomo', 'Horário de pico (9h)'],
                'acao' => 'Oferecer horários alternativos + flexibilidade de reagendamento',
                'contexto' => 'Conflitos frequentes com trabalho'
            ],
            [
                'probabilidade' => 0.73,
                'fatores' => ['Mora longe da clínica (>30km)', 'Transporte público', 'Diagnóstico não urgente'],
                'acao' => 'Considerar telemedicina ou reagendar para horários com menos trânsito',
                'contexto' => 'Dificuldades de locomoção e custo'
            ],
            [
                'probabilidade' => 0.89,
                'fatores' => ['Paciente oncológico em tratamento', 'Baixa imunidade', 'Período de chuvas'],
                'acao' => 'PRIORITÁRIO: Confirmar estado de saúde antes da consulta',
                'contexto' => 'Condições de saúde críticas'
            ]
        ];

        // ===== CENÁRIOS DE MÉDIO RISCO =====
        $cenarios_medio_risco = [
            [
                'probabilidade' => 0.45,
                'fatores' => ['1 falta no último mês', 'Sexta-feira', 'Paciente trabalhador'],
                'acao' => 'SMS de lembrete 24h antes',
                'contexto' => 'Perfil profissional com compromissos'
            ],
            [
                'probabilidade' => 0.52,
                'fatores' => ['Primeiro atendimento', 'Paciente ansioso', 'Horário matinal'],
                'acao' => 'Ligação de boas-vindas + orientações sobre localização',
                'contexto' => 'Ansiedade por primeira consulta'
            ],
            [
                'probabilidade' => 0.38,
                'fatores' => ['Paciente regular', 'Pequeno atraso histórico', 'Meio da semana'],
                'acao' => 'Monitoramento padrão com lembrete automático',
                'contexto' => 'Comportamento previsível e controlado'
            ]
        ];

        // ===== CRIAR PREVISÕES PARA HOJE E PRÓXIMOS 7 DIAS =====
        for ($dia = 0; $dia <= 7; $dia++) {
            $dataAlvo = $dataHoje->copy()->addDays($dia);

            echo "📅 Criando previsões para: " . $dataAlvo->format('d/m/Y') . "\n";

            // Para cada dia, criar 2-4 previsões de alto risco
            $numAltoRisco = $dia === 0 ? 3 : rand(2, 4); // Hoje: 3, outros dias: 2-4

            for ($i = 0; $i < $numAltoRisco; $i++) {
                $paciente = $pacientes->random();
                $cenario = $cenarios_alto_risco[array_rand($cenarios_alto_risco)];

                $previsoes[] = [
                    'id' => $contadorId++,
                    'paciente_id' => $paciente->id,
                    'tipo_previsao' => 'probabilidade_falta',
                    'valor_previsao' => $cenario['probabilidade'],
                    'confianca' => rand(75, 95) / 100,
                    'data_previsao' => $dataAlvo->format('Y-m-d'),
                    'data_calculo' => $dataHoje->format('Y-m-d H:i:s'),
                    'modelo_utilizado' => 'RandomForest_Academico_v1.2',
                    'parametros_entrada' => json_encode([
                        'idade' => Carbon::parse($paciente->data_nascimento)->age,
                        'fatores_risco' => $cenario['fatores'],
                        'contexto_clinico' => $cenario['contexto'],
                        'dia_semana' => $dataAlvo->dayOfWeek,
                        'historico_faltas' => rand(2, 6) / 10
                    ]),
                    'acao_recomendada' => $cenario['acao'],
                    'executada' => $dia < 0 ? (rand(0, 10) > 3) : false // Passado: 70% executadas
                ];
            }

            // Criar algumas previsões de médio risco
            $numMedioRisco = rand(2, 3);
            for ($i = 0; $i < $numMedioRisco; $i++) {
                $paciente = $pacientes->random();
                $cenario = $cenarios_medio_risco[array_rand($cenarios_medio_risco)];

                $previsoes[] = [
                    'id' => $contadorId++,
                    'paciente_id' => $paciente->id,
                    'tipo_previsao' => 'probabilidade_falta',
                    'valor_previsao' => $cenario['probabilidade'],
                    'confianca' => rand(65, 85) / 100,
                    'data_previsao' => $dataAlvo->format('Y-m-d'),
                    'data_calculo' => $dataHoje->format('Y-m-d H:i:s'),
                    'modelo_utilizado' => 'RandomForest_Academico_v1.2',
                    'parametros_entrada' => json_encode([
                        'idade' => Carbon::parse($paciente->data_nascimento)->age,
                        'fatores_risco' => $cenario['fatores'],
                        'contexto_clinico' => $cenario['contexto'],
                        'dia_semana' => $dataAlvo->dayOfWeek,
                        'historico_faltas' => rand(1, 3) / 10
                    ]),
                    'acao_recomendada' => $cenario['acao'],
                    'executada' => $dia < 0 ? (rand(0, 10) > 4) : false
                ];
            }

            // Criar algumas previsões de baixo risco
            $numBaixoRisco = rand(3, 6);
            for ($i = 0; $i < $numBaixoRisco; $i++) {
                $paciente = $pacientes->random();

                $previsoes[] = [
                    'id' => $contadorId++,
                    'paciente_id' => $paciente->id,
                    'tipo_previsao' => 'probabilidade_falta',
                    'valor_previsao' => rand(5, 25) / 100, // 0.05 a 0.25
                    'confianca' => rand(80, 95) / 100,
                    'data_previsao' => $dataAlvo->format('Y-m-d'),
                    'data_calculo' => $dataHoje->format('Y-m-d H:i:s'),
                    'modelo_utilizado' => 'RandomForest_Academico_v1.2',
                    'parametros_entrada' => json_encode([
                        'idade' => Carbon::parse($paciente->data_nascimento)->age,
                        'fatores_risco' => [],
                        'contexto_clinico' => 'Paciente regular com bom histórico',
                        'dia_semana' => $dataAlvo->dayOfWeek,
                        'historico_faltas' => rand(0, 1) / 10
                    ]),
                    'acao_recomendada' => 'Monitoramento padrão - paciente confiável',
                    'executada' => false
                ];
            }
        }

        // ===== CRIAR ALGUMAS PREVISÕES HISTÓRICAS PARA ESTATÍSTICAS =====
        echo "📈 Criando dados históricos para enriquecer estatísticas...\n";

        for ($diasAtras = 1; $diasAtras <= 30; $diasAtras++) {
            $dataHistorica = $dataHoje->copy()->subDays($diasAtras);

            // Criar previsões históricas com resultados "conhecidos"
            $numPrevisoes = rand(5, 12);
            for ($i = 0; $i < $numPrevisoes; $i++) {
                $paciente = $pacientes->random();
                $probabilidade = rand(10, 90) / 100;
                $executada = rand(0, 10) > 2; // 80% executadas

                $nivelRisco = $probabilidade >= 0.6 ? 'alto' :
                             ($probabilidade >= 0.3 ? 'medio' : 'baixo');

                $previsoes[] = [
                    'id' => $contadorId++,
                    'paciente_id' => $paciente->id,
                    'tipo_previsao' => 'probabilidade_falta',
                    'valor_previsao' => $probabilidade,
                    'confianca' => rand(70, 95) / 100,
                    'data_previsao' => $dataHistorica->format('Y-m-d'),
                    'data_calculo' => $dataHistorica->format('Y-m-d H:i:s'),
                    'modelo_utilizado' => 'RandomForest_Academico_v1.1',
                    'parametros_entrada' => json_encode([
                        'idade' => Carbon::parse($paciente->data_nascimento)->age,
                        'nivel_risco_historico' => $nivelRisco,
                        'resultado_real' => $probabilidade > 0.5 ? 'faltou' : 'compareceu'
                    ]),
                    'acao_recomendada' => $nivelRisco == 'alto' ?
                        'Ligação preventiva realizada' : 'Monitoramento padrão',
                    'executada' => $executada
                ];
            }
        }

        // Inserir todas as previsões no banco
        echo "💾 Inserindo " . count($previsoes) . " previsões no banco...\n";

        $chunks = array_chunk($previsoes, 100);
        foreach ($chunks as $index => $chunk) {
            DB::table('previsoes_ml')->insert($chunk);
            echo "✅ Lote " . ($index + 1) . "/" . count($chunks) . " inserido\n";
        }

        // Estatísticas finais
        $total = count($previsoes);
        $hoje = collect($previsoes)->filter(function($p) use ($dataHoje) {
            return $p['data_previsao'] === $dataHoje->format('Y-m-d');
        })->count();

        $altoRiscoHoje = collect($previsoes)->filter(function($p) use ($dataHoje) {
            return $p['data_previsao'] === $dataHoje->format('Y-m-d') && $p['valor_previsao'] >= 0.6;
        })->count();

        echo "\n🎯 DADOS PARA APRESENTAÇÃO CRIADOS:\n";
        echo "   📊 Total de previsões: {$total}\n";
        echo "   📅 Previsões para hoje: {$hoje}\n";
        echo "   ⚠️  Alto risco hoje: {$altoRiscoHoje}\n";
        echo "   📈 Cobertura: 8 dias (hoje + 7 dias futuros)\n";
        echo "   📚 Histórico: 30 dias para estatísticas\n";
        echo "\n🎓 Dashboard pronto para apresentação acadêmica!\n";
    }
}
