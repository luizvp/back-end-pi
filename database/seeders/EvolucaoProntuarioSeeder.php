<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EvolucaoProntuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all medical record IDs
        $prontuarioIds = DB::table('prontuarios')->pluck('id')->toArray();

        // Evolution descriptions
        $descricoes = [
            'Paciente relata melhora do quadro álgico. Realizado {tratamento} com boa resposta. Mantido plano terapêutico.',
            'Paciente refere melhora parcial dos sintomas. Realizado {tratamento} com resposta satisfatória. Ajustado plano terapêutico.',
            'Paciente sem alteração do quadro. Realizado {tratamento}. Modificado plano terapêutico.',
            'Paciente relata piora dos sintomas. Realizado {tratamento} com pouca resposta. Reavaliado e modificado plano terapêutico.',
            'Paciente apresenta evolução positiva. Realizado {tratamento} com boa resposta. Mantido plano terapêutico.',
            'Paciente com melhora significativa da amplitude de movimento. Realizado {tratamento}. Mantido plano terapêutico.',
            'Paciente refere redução da dor. Realizado {tratamento} com boa resposta. Mantido plano terapêutico.',
            'Paciente apresenta melhora da funcionalidade. Realizado {tratamento}. Ajustado plano terapêutico para progressão.',
            'Paciente com redução do edema. Realizado {tratamento} com resposta satisfatória. Mantido plano terapêutico.',
            'Paciente relata melhora da qualidade do sono. Realizado {tratamento}. Mantido plano terapêutico.',
            'Paciente refere dificuldade em realizar os exercícios domiciliares. Realizado {tratamento} e reorientação. Mantido plano terapêutico.',
            'Paciente faltou à sessão anterior. Realizado {tratamento} com boa resposta. Mantido plano terapêutico.',
            'Paciente relata evento de dor intensa após atividade física. Realizado {tratamento} com boa resposta. Ajustado plano terapêutico.',
            'Paciente apresenta melhora da força muscular. Realizado {tratamento} com progressão de carga. Mantido plano terapêutico.',
            'Paciente refere melhora da sensibilidade. Realizado {tratamento}. Mantido plano terapêutico.',
            'Paciente com melhora do padrão de marcha. Realizado {tratamento} com boa resposta. Ajustado plano terapêutico para progressão.',
            'Paciente relata retorno às atividades diárias sem dor. Realizado {tratamento}. Mantido plano terapêutico.',
            'Paciente apresenta melhora do equilíbrio. Realizado {tratamento} com progressão. Mantido plano terapêutico.',
            'Paciente refere redução da rigidez articular. Realizado {tratamento} com boa resposta. Mantido plano terapêutico.',
            'Paciente com melhora da coordenação motora. Realizado {tratamento}. Ajustado plano terapêutico para progressão.'
        ];

        // Treatments
        $tratamentos = [
            'cinesioterapia', 'eletroterapia', 'termoterapia', 'crioterapia', 'terapia manual',
            'massoterapia', 'exercícios de fortalecimento', 'alongamento', 'mobilização articular',
            'liberação miofascial', 'treino proprioceptivo', 'treino de equilíbrio', 'treino funcional',
            'exercícios respiratórios', 'técnicas de relaxamento', 'bandagem funcional',
            'exercícios de estabilização', 'treino de marcha', 'exercícios posturais',
            'pilates', 'exercícios de coordenação motora'
        ];

        // Generate evolution records
        $evolucoes = [];

        // For each medical record, generate 3-10 evolution records
        foreach ($prontuarioIds as $prontuarioId) {
            // Get medical record creation date
            $dataCriacaoProntuario = DB::table('prontuarios')
                ->where('id', $prontuarioId)
                ->value('data_criacao');

            // Convert to Carbon instance
            $dataCriacao = Carbon::parse($dataCriacaoProntuario);

            // Generate random number of evolution records (3-10)
            $numEvolucoes = rand(3, 10);

            // Generate evolution records with dates after medical record creation
            for ($i = 1; $i <= $numEvolucoes; $i++) {
                // Generate date (7-14 days after previous date or medical record creation)
                $dataAtendimento = $dataCriacao->copy()->addDays(rand(7, 14))->format('Y-m-d');

                // Update date for next evolution
                $dataCriacao = Carbon::parse($dataAtendimento);

                // Skip if date is in the future
                if ($dataCriacao->isAfter(Carbon::now())) {
                    continue;
                }

                // Generate random description
                $descricaoBase = $descricoes[array_rand($descricoes)];
                $tratamento = $tratamentos[array_rand($tratamentos)];
                $descricao = str_replace('{tratamento}', $tratamento, $descricaoBase);

                // Add some variation to descriptions
                if (rand(0, 100) > 70) {
                    $descricao .= ' ' . $descricoes[array_rand($descricoes)];
                    $descricao = str_replace('{tratamento}', $tratamentos[array_rand($tratamentos)], $descricao);
                }

                // Create evolution record
                $evolucoes[] = [
                    'id_prontuario' => $prontuarioId,
                    'data_atendimento' => $dataAtendimento,
                    'descricao_evolucao' => $descricao
                ];
            }
        }

        // Insert data into the evolucao_prontuario table
        DB::table('evolucao_prontuario')->insert($evolucoes);
    }
}
