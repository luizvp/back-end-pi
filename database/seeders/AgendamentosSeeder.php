<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgendamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all patient IDs with their birth dates for age calculation
        $pacientes = DB::table('pacientes')->select('id', 'data_nascimento')->get();

        // Define time slots for appointments
        $horasConsulta = [
            '08:00:00', '08:30:00', '09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00',
            '13:00:00', '13:30:00', '14:00:00', '14:30:00', '15:00:00', '15:30:00', '16:00:00', '16:30:00',
            '17:00:00', '17:30:00', '18:00:00', '18:30:00', '19:00:00', '19:30:00'
        ];

        $agendamentos = [];
        $contador = 1;

        // Gerar agendamentos dos últimos 120 dias (mais dados para ML)
        for ($daysAgo = 120; $daysAgo >= 1; $daysAgo--) {
            $data = Carbon::now()->subDays($daysAgo);

            // Mais agendamentos nos dias úteis
            $isWeekend = $data->isWeekend();
            $numAgendamentosDia = $isWeekend ? rand(2, 5) : rand(8, 15);

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
                $status = $this->determinarStatus($data, $hora, $idade, $paciente->id);

                $agendamentos[] = [
                    'id' => $contador++,
                    'id_paciente' => $paciente->id,
                    'data' => $data->format('Y-m-d'),
                    'hora' => $hora,
                    'status' => $status,
                    'observacoes' => $this->gerarObservacoes($status),
                    'compareceu' => in_array($status, ['realizado']) ? 1 :
                                  (in_array($status, ['faltou']) ? 0 : null),
                    'alterado_manualmente' => rand(0, 10) < 2 ? 1 : 0, // 20% alterados manualmente
                    'data_status_alterado' => $status != 'agendado' ? $data->format('Y-m-d H:i:s') : null,
                    'alterado_por' => $status != 'agendado' ? 'sistema' : null,
                    'created_at' => $data->format('Y-m-d H:i:s'),
                    'updated_at' => $data->format('Y-m-d H:i:s')
                ];
            }
        }

        // Gerar agendamentos futuros (próximos 30 dias) - todos com status 'agendado'
        for ($daysAhead = 1; $daysAhead <= 30; $daysAhead++) {
            $data = Carbon::now()->addDays($daysAhead);

            // Menos agendamentos nos fins de semana
            $isWeekend = $data->isWeekend();
            $numAgendamentosDia = $isWeekend ? rand(1, 3) : rand(6, 12);

            for ($i = 0; $i < $numAgendamentosDia; $i++) {
                $paciente = $pacientes->random();
                $hora = $horasConsulta[array_rand($horasConsulta)];

                // Evitar duplicatas
                $key = $data->format('Y-m-d') . '_' . $hora;
                if (isset($agendamentoKeys[$key])) {
                    continue;
                }
                $agendamentoKeys[$key] = true;

                $agendamentos[] = [
                    'id' => $contador++,
                    'id_paciente' => $paciente->id,
                    'data' => $data->format('Y-m-d'),
                    'hora' => $hora,
                    'status' => 'agendado',
                    'observacoes' => null,
                    'compareceu' => null,
                    'alterado_manualmente' => 0,
                    'data_status_alterado' => null,
                    'alterado_por' => null,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                ];
            }
        }

        // Truncate table and reset auto-increment
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('agendamentos')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Insert data in batches
        $chunks = array_chunk($agendamentos, 100);
        foreach ($chunks as $chunk) {
            DB::table('agendamentos')->insert($chunk);
        }

        echo "✅ Gerados " . count($agendamentos) . " agendamentos com padrões realistas!\n";
    }

    /**
     * Determinar status baseado em características realistas
     */
    private function determinarStatus($data, $hora, $idade, $pacienteId)
    {
        // Base probability of missing (15%)
        $probFalta = 0.15;

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

        // Padrão individual do paciente (baseado no ID)
        $padrãoPaciente = ($pacienteId % 10) / 10; // 0.0 a 0.9
        if ($padrãoPaciente > 0.7) {
            $probFalta += 0.10; // Pacientes com padrão de faltas
        } elseif ($padrãoPaciente < 0.3) {
            $probFalta -= 0.05; // Pacientes muito pontuais
        }

        // Probabilidade de cancelamento (menor)
        $probCancelamento = 0.08;

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
     * Gerar observações baseadas no status
     */
    private function gerarObservacoes($status)
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

        $opcoes = $observacoes[$status] ?? [null];
        return $opcoes[array_rand($opcoes)];
    }
}
