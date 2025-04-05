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
        // Get all patient IDs
        $pacienteIds = DB::table('pacientes')->pluck('id')->toArray();

        // Generate 200 appointments with varied data
        $agendamentos = [];

        // Define time slots for appointments
        $horasConsulta = [
            '08:00:00', '08:30:00', '09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00',
            '13:00:00', '13:30:00', '14:00:00', '14:30:00', '15:00:00', '15:30:00', '16:00:00', '16:30:00',
            '17:00:00', '17:30:00', '18:00:00', '18:30:00', '19:00:00', '19:30:00'
        ];

        // Generate past appointments (60% of total)
        $pastAppointmentsCount = (int)(200 * 0.6);

        for ($i = 1; $i <= $pastAppointmentsCount; $i++) {
            // Random patient
            $pacienteId = $pacienteIds[array_rand($pacienteIds)];

            // Random date in the past (between 1 and 180 days ago)
            $data = Carbon::now()->subDays(rand(1, 180))->format('Y-m-d');

            // Random time slot
            $hora = $horasConsulta[array_rand($horasConsulta)];

            // Create appointment
            $agendamentos[] = [
                'id_paciente' => $pacienteId,
                'data' => $data,
                'hora' => $hora
            ];
        }

        // Generate future appointments (40% of total)
        $futureAppointmentsCount = 200 - $pastAppointmentsCount;

        for ($i = 1; $i <= $futureAppointmentsCount; $i++) {
            // Random patient
            $pacienteId = $pacienteIds[array_rand($pacienteIds)];

            // Random date in the future (between 1 and 60 days from now)
            $data = Carbon::now()->addDays(rand(1, 60))->format('Y-m-d');

            // Random time slot
            $hora = $horasConsulta[array_rand($horasConsulta)];

            // Create appointment
            $agendamentos[] = [
                'id_paciente' => $pacienteId,
                'data' => $data,
                'hora' => $hora
            ];
        }

        // Ensure we don't have duplicate appointments (same date, time and patient)
        $uniqueAgendamentos = [];
        $dateTimePatientMap = [];

        foreach ($agendamentos as $agendamento) {
            $key = $agendamento['data'] . $agendamento['hora'] . $agendamento['id_paciente'];

            if (!isset($dateTimePatientMap[$key])) {
                $dateTimePatientMap[$key] = true;
                $uniqueAgendamentos[] = $agendamento;
            }
        }

        // Insert data into the agendamentos table
        DB::table('agendamentos')->insert($uniqueAgendamentos);
    }
}
