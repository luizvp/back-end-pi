<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipamentos = [
            // Equipamentos Cardiovasculares
            [
                'nome' => 'Esteira Ergométrica 1',
                'tipo' => 'cardiovascular',
                'marca' => 'Movement',
                'modelo' => 'RT250',
                'numero_serie' => 'EST001',
                'status' => 'ativo',
                'localizacao' => 'Sala de Cardio',
                'tempo_uso_total' => 0,
                'observacoes' => 'Equipamento para exercícios cardiovasculares'
            ],
            [
                'nome' => 'Bike Ergométrica 2',
                'tipo' => 'cardiovascular',
                'marca' => 'Biotech',
                'modelo' => 'BT300',
                'numero_serie' => 'BIKE002',
                'status' => 'ativo',
                'localizacao' => 'Sala de Cardio',
                'tempo_uso_total' => 0,
                'observacoes' => 'Bicicleta ergométrica para reabilitação'
            ],
            [
                'nome' => 'Elíptico',
                'tipo' => 'cardiovascular',
                'marca' => 'Athletic',
                'modelo' => 'ELP450',
                'numero_serie' => 'ELP003',
                'status' => 'ativo',
                'localizacao' => 'Sala de Cardio',
                'tempo_uso_total' => 0,
                'observacoes' => 'Equipamento de baixo impacto'
            ],

            // Equipamentos de Eletroterapia
            [
                'nome' => 'Ultrassom Terapêutico',
                'tipo' => 'eletroterapia',
                'marca' => 'Ibramed',
                'modelo' => 'Sonic Compact',
                'numero_serie' => 'US004',
                'status' => 'ativo',
                'localizacao' => 'Sala de Procedimentos',
                'tempo_uso_total' => 0,
                'observacoes' => 'Ultrassom para tratamento de lesões'
            ],
            [
                'nome' => 'Eletroestimulador',
                'tipo' => 'eletroterapia',
                'marca' => 'KLD',
                'modelo' => 'Neurodyn',
                'numero_serie' => 'ELE005',
                'status' => 'ativo',
                'localizacao' => 'Sala de Procedimentos',
                'tempo_uso_total' => 0,
                'observacoes' => 'TENS e FES para reabilitação'
            ],
            [
                'nome' => 'Laser Terapêutico',
                'tipo' => 'eletroterapia',
                'marca' => 'DMC',
                'modelo' => 'Theal Therapy',
                'numero_serie' => 'LAS006',
                'status' => 'ativo',
                'localizacao' => 'Sala de Procedimentos',
                'tempo_uso_total' => 0,
                'observacoes' => 'Laser de baixa potência'
            ],

            // Mobiliário e Equipamentos Básicos
            [
                'nome' => 'Maca de Atendimento 1',
                'tipo' => 'mobiliario',
                'marca' => 'Carci',
                'modelo' => 'Standard',
                'numero_serie' => 'MAC007',
                'status' => 'ativo',
                'localizacao' => 'Consultório 1',
                'tempo_uso_total' => 0,
                'observacoes' => 'Maca para atendimentos gerais'
            ],
            [
                'nome' => 'Maca de Atendimento 2',
                'tipo' => 'mobiliario',
                'marca' => 'Carci',
                'modelo' => 'Standard',
                'numero_serie' => 'MAC008',
                'status' => 'ativo',
                'localizacao' => 'Consultório 2',
                'tempo_uso_total' => 0,
                'observacoes' => 'Maca para atendimentos gerais'
            ],
            [
                'nome' => 'Tatame Terapêutico',
                'tipo' => 'mobiliario',
                'marca' => 'EVA Sports',
                'modelo' => 'Professional',
                'numero_serie' => 'TAT009',
                'status' => 'ativo',
                'localizacao' => 'Sala de Exercícios',
                'tempo_uso_total' => 0,
                'observacoes' => 'Área para exercícios de solo'
            ],

            // Equipamentos de Avaliação
            [
                'nome' => 'Balança Digital',
                'tipo' => 'avaliacao',
                'marca' => 'Welmy',
                'modelo' => 'W200',
                'numero_serie' => 'BAL010',
                'status' => 'ativo',
                'localizacao' => 'Sala de Avaliação',
                'tempo_uso_total' => 0,
                'observacoes' => 'Balança antropométrica digital'
            ],
            [
                'nome' => 'Estadiômetro',
                'tipo' => 'avaliacao',
                'marca' => 'Sanny',
                'modelo' => 'ES2020',
                'numero_serie' => 'EST011',
                'status' => 'ativo',
                'localizacao' => 'Sala de Avaliação',
                'tempo_uso_total' => 0,
                'observacoes' => 'Medição de altura'
            ],

            // Sensores IoT
            [
                'nome' => 'Sensor Frequência Cardíaca',
                'tipo' => 'sensor',
                'marca' => 'Polar',
                'modelo' => 'H10',
                'numero_serie' => 'POL012',
                'status' => 'ativo',
                'localizacao' => 'Equipamento Móvel',
                'tempo_uso_total' => 0,
                'observacoes' => 'Monitor de frequência cardíaca Bluetooth'
            ],
            [
                'nome' => 'Oxímetro Digital',
                'tipo' => 'sensor',
                'marca' => 'Bioland',
                'modelo' => 'BL320',
                'numero_serie' => 'OXI013',
                'status' => 'ativo',
                'localizacao' => 'Equipamento Móvel',
                'tempo_uso_total' => 0,
                'observacoes' => 'Medidor de saturação e frequência'
            ],
            [
                'nome' => 'Monitor de Pressão',
                'tipo' => 'sensor',
                'marca' => 'Omron',
                'modelo' => 'HEM-7130',
                'numero_serie' => 'MON014',
                'status' => 'ativo',
                'localizacao' => 'Equipamento Móvel',
                'tempo_uso_total' => 0,
                'observacoes' => 'Pressão arterial digital automático'
            ]
        ];

        foreach ($equipamentos as $equipamento) {
            DB::table('equipamentos')->updateOrInsert(
                ['numero_serie' => $equipamento['numero_serie']],
                [
                    'nome' => $equipamento['nome'],
                    'tipo' => $equipamento['tipo'],
                    'marca' => $equipamento['marca'],
                    'modelo' => $equipamento['modelo'],
                    'status' => $equipamento['status'],
                    'localizacao' => $equipamento['localizacao'],
                    'tempo_uso_total' => $equipamento['tempo_uso_total'],
                    'observacoes' => $equipamento['observacoes'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
