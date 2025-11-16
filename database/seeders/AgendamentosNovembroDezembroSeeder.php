<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgendamentosNovembroDezembroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all patient IDs with their birth dates for age calculation
        $pacientes = DB::table('pacientes')->select('id', 'data_nascimento')->get();

        if ($pacientes->isEmpty()) {
            echo "❌ Nenhum paciente encontrado! Execute o PacientesSeeder primeiro.\n";
            return;
        }

        // Define time slots for appointments
        $horasConsulta = [
            '08:00:00', '08:30:00', '09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00',
            '13:00:00', '13:30:00', '14:00:00', '14:30:00', '15:00:00', '15:30:00', '16:00:00', '16:30:00',
            '17:00:00', '17:30:00', '18:00:00', '18:30:00', '19:00:00', '19:30:00'
        ];

        // Feriados brasileiros em novembro/dezembro 2025
        $feriados = [
            '2025-11-15', // Proclamação da República
            '2025-12-25', // Natal
            '2025-12-31', // Véspera de Ano Novo (considerado feriado para clínicas)
        ];

        // Get the highest existing ID to continue sequence
        $maxId = DB::table('agendamentos')->max('id') ?? 0;
        $contador = $maxId + 1;

        $agendamentos = [];
        $agendamentoKeys = [];

        echo "🗓️ Gerando agendamentos para novembro-dezembro 2025...\n";

        // Gerar agendamentos de 1º novembro a 31 dezembro 2025
        $dataInicio = Carbon::create(2025, 11, 1); // 1º novembro 2025
        $dataFim = Carbon::create(2025, 12, 31);   // 31 dezembro 2025

        for ($data = $dataInicio->copy(); $data <= $dataFim; $data->addDay()) {
            echo "📅 Processando: " . $data->format('d/m/Y') . "\n";

            // Determinar fator sazonal
            $fatorSazonal = $this->calcularFatorSazonal($data);

            // Verificar se é feriado
            $isFeriado = in_array($data->format('Y-m-d'), $feriados);

            // Calcular número de agendamentos baseado em padrões
            $numAgendamentosDia = $this->calcularNumAgendamentos($data, $fatorSazonal, $isFeriado);

            for ($i = 0; $i < $numAgendamentosDia; $i++) {
                $paciente = $pacientes->random();
                $hora = $horasConsulta[array_rand($horasConsulta)];

                // Evitar agendamentos duplicados no mesmo dia/hora
                $key = $data->format('Y-m-d') . '_' . $hora;
                if (isset($agendamentoKeys[$key])) {
                    continue;
                }
                $agendamentoKeys[$key] = true;

                // Calcular idade do paciente
                $idade = $paciente->data_nascimento ?
                    Carbon::parse($paciente->data_nascimento)->age : 40;

                // Determinar status baseado em características realistas
                $status = $this->determinarStatus($data, $hora, $idade, $paciente->id, $fatorSazonal, $isFeriado);

                // Criar timestamp baseado na data do agendamento
                $createdAt = $data->copy()->subDays(rand(1, 7))->format('Y-m-d H:i:s');
                $updatedAt = $status != 'agendado' ? $data->format('Y-m-d H:i:s') : $createdAt;

                $agendamentos[] = [
                    'id' => $contador++,
                    'id_paciente' => $paciente->id,
                    'data' => $data->format('Y-m-d'),
                    'hora' => $hora,
                    'status' => $status,
                    'observacoes' => $this->gerarObservacoes($status, $data, $isFeriado),
                    'compareceu' => in_array($status, ['realizado']) ? 1 :
                                  (in_array($status, ['faltou']) ? 0 : null),
                    'alterado_manualmente' => rand(0, 10) < 2 ? 1 : 0, // 20% alterados manualmente
                    'data_status_alterado' => $status != 'agendado' ? $data->format('Y-m-d H:i:s') : null,
                    'alterado_por' => $status != 'agendado' ? 'sistema' : null,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt
                ];
            }
        }

        if (empty($agendamentos)) {
            echo "❌ Nenhum agendamento foi gerado!\n";
            return;
        }

        // Insert data in batches to avoid memory issues
        echo "💾 Inserindo " . count($agendamentos) . " agendamentos no banco...\n";

        $chunks = array_chunk($agendamentos, 100);
        foreach ($chunks as $index => $chunk) {
            DB::table('agendamentos')->insert($chunk);
            echo "✅ Batch " . ($index + 1) . "/" . count($chunks) . " inserido\n";
        }

        echo "🎉 Sucesso! Gerados " . count($agendamentos) . " agendamentos para novembro-dezembro 2025!\n";

        // Estatísticas finais
        $totalNovembro = collect($agendamentos)->filter(function($ag) {
            return substr($ag['data'], 5, 2) === '11';
        })->count();

        $totalDezembro = collect($agendamentos)->filter(function($ag) {
            return substr($ag['data'], 5, 2) === '12';
        })->count();

        echo "📊 Novembro 2025: {$totalNovembro} agendamentos\n";
        echo "📊 Dezembro 2025: {$totalDezembro} agendamentos\n";
    }

    /**
     * Calcular fator sazonal baseado no mês e semana
     */
    private function calcularFatorSazonal($data)
    {
        $mes = $data->month;
        $semanaDoMes = ceil($data->day / 7);

        if ($mes == 11) {
            // Novembro: padrão normal com leve redução no final
            return $semanaDoMes <= 3 ? 1.0 : 0.9;
        } elseif ($mes == 12) {
            // Dezembro: redução progressiva por férias
            switch ($semanaDoMes) {
                case 1: return 0.95; // Primeira semana ainda normal
                case 2: return 0.85; // Segunda semana começa a reduzir
                case 3: return 0.70; // Terceira semana (férias escolares)
                case 4: return 0.50; // Quarta semana (Natal)
                case 5: return 0.30; // Quinta semana (fim de ano)
                default: return 0.50;
            }
        }

        return 1.0;
    }

    /**
     * Calcular número de agendamentos por dia
     */
    private function calcularNumAgendamentos($data, $fatorSazonal, $isFeriado)
    {
        if ($isFeriado) {
            return rand(0, 2); // Feriados: quase nada
        }

        $isWeekend = $data->isWeekend();

        if ($isWeekend) {
            $base = rand(2, 5); // Final de semana: sempre baixo
        } else {
            $base = rand(8, 15); // Dias úteis: movimento normal
        }

        // Aplicar fator sazonal
        $comSazonalidade = intval($base * $fatorSazonal);

        // Garantir pelo menos 1 agendamento em dias úteis (exceto feriados)
        if (!$isWeekend && !$isFeriado && $comSazonalidade < 1) {
            $comSazonalidade = 1;
        }

        return $comSazonalidade;
    }

    /**
     * Determinar status baseado em características realistas + sazonalidade
     */
    private function determinarStatus($data, $hora, $idade, $pacienteId, $fatorSazonal, $isFeriado)
    {
        // Se é agendamento futuro (depois de hoje), sempre 'agendado'
        if ($data->isAfter(Carbon::now())) {
            return 'agendado';
        }

        // Base probability of missing (15%)
        $probFalta = 0.15;

        // Sazonalidade aumenta faltas em dezembro
        if ($data->month == 12) {
            $probFalta += 0.08; // Dezembro: +8% faltas por viagens/férias
        }

        // Feriados aumentam muito as faltas
        if ($isFeriado) {
            $probFalta += 0.25; // +25% em feriados
        }

        // Idade influencia faltas
        if ($idade > 70) {
            $probFalta += 0.08; // Idosos faltam mais
        } elseif ($idade < 25) {
            $probFalta += 0.05; // Jovens faltam mais
        } elseif ($idade >= 30 && $idade <= 50) {
            $probFalta -= 0.03; // Adultos faltam menos
        }

        // Dia da semana influencia
        if ($data->dayOfWeek == Carbon::MONDAY) {
            $probFalta += 0.08; // Segunda-feira
        } elseif ($data->dayOfWeek == Carbon::FRIDAY) {
            $probFalta += 0.05; // Sexta-feira
        } elseif ($data->isWeekend()) {
            $probFalta += 0.12; // Final de semana
        }

        // Horário influencia
        $horaNum = intval(substr($hora, 0, 2));
        if ($horaNum <= 8) {
            $probFalta += 0.06; // Muito cedo
        } elseif ($horaNum >= 18) {
            $probFalta += 0.04; // Muito tarde
        }

        // Dezembro: horários tardios têm mais faltas (escurece cedo)
        if ($data->month == 12 && $horaNum >= 17) {
            $probFalta += 0.08;
        }

        // Padrão individual do paciente (baseado no ID)
        $padrãoPaciente = ($pacienteId % 10) / 10; // 0.0 a 0.9
        if ($padrãoPaciente > 0.7) {
            $probFalta += 0.10; // Pacientes com padrão de faltas
        } elseif ($padrãoPaciente < 0.3) {
            $probFalta -= 0.05; // Pacientes muito pontuais
        }

        // Vésperas de feriado
        $amanha = $data->copy()->addDay();
        if (in_array($amanha->format('Y-m-d'), ['2025-11-15', '2025-12-25', '2025-12-31', '2026-01-01'])) {
            $probFalta += 0.10; // Véspera de feriado
        }

        // Probabilidade de cancelamento (menor que falta)
        $probCancelamento = 0.08;

        // Em dezembro, mais cancelamentos por viagens
        if ($data->month == 12) {
            $probCancelamento += 0.04;
        }

        // Determinar resultado
        $random = mt_rand() / mt_getrandmax();

        if ($random < $probFalta) {
            return 'faltou';
        } elseif ($random < ($probFalta + $probCancelamento)) {
            return 'cancelado';
        } else {
            return 'realizado';
        }
    }

    /**
     * Gerar observações baseadas no status e contexto sazonal
     */
    private function gerarObservacoes($status, $data, $isFeriado)
    {
        $observacoes = [
            'realizado' => [
                'Paciente compareceu no horário',
                'Sessão realizada com sucesso',
                'Paciente pontual',
                'Tratamento aplicado conforme planejado',
                null // Alguns sem observação
            ],
            'faltou' => [
                'Paciente não compareceu',
                'Ausência sem justificativa',
                'Não atendeu ligações de lembrete',
                'Falta não justificada'
            ],
            'cancelado' => [
                'Paciente cancelou por motivo pessoal',
                'Cancelamento por problema de saúde',
                'Reagendado a pedido do paciente',
                'Cancelamento de última hora',
                'Paciente teve compromisso imprevisto'
            ]
        ];

        // Observações específicas para dezembro
        if ($data->month == 12) {
            if ($status == 'faltou') {
                $observacoes['faltou'] = array_merge($observacoes['faltou'], [
                    'Paciente viajou para as férias',
                    'Ausência por compromissos natalinos',
                    'Não compareceu por conta das férias'
                ]);
            } elseif ($status == 'cancelado') {
                $observacoes['cancelado'] = array_merge($observacoes['cancelado'], [
                    'Cancelamento por viagem de férias',
                    'Reagendado para após as festas',
                    'Cancelou por compromissos familiares',
                    'Solicitou reagendamento para janeiro'
                ]);
            }
        }

        // Observações para feriados
        if ($isFeriado && in_array($status, ['faltou', 'cancelado'])) {
            return $status == 'faltou' ?
                'Não compareceu por conta do feriado' :
                'Cancelou devido ao feriado';
        }

        $opcoes = $observacoes[$status] ?? [null];
        return $opcoes[array_rand($opcoes)];
    }
}
