<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiagnosticosPadronizadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diagnosticos = [
            // Dores Musculares
            [
                'codigo_cid' => 'M79.1',
                'descricao' => 'Mialgia',
                'categoria' => 'Dor muscular',
                'subcategoria' => 'Distúrbios dos músculos'
            ],
            [
                'codigo_cid' => 'M79.2',
                'descricao' => 'Neuralgia e neurite não especificadas',
                'categoria' => 'Dor neurológica',
                'subcategoria' => 'Distúrbios neurológicos'
            ],
            [
                'codigo_cid' => 'M79.3',
                'descricao' => 'Paniculite não especificada',
                'categoria' => 'Inflamação tecidos moles',
                'subcategoria' => 'Distúrbios de tecidos moles'
            ],

            // Dores Articulares
            [
                'codigo_cid' => 'M25.5',
                'descricao' => 'Dor articular',
                'categoria' => 'Dor articular',
                'subcategoria' => 'Artropatias'
            ],
            [
                'codigo_cid' => 'M25.6',
                'descricao' => 'Rigidez articular não classificada em outra parte',
                'categoria' => 'Rigidez articular',
                'subcategoria' => 'Artropatias'
            ],

            // Dores na Coluna
            [
                'codigo_cid' => 'M54.5',
                'descricao' => 'Dor lombar baixa',
                'categoria' => 'Dorsalgia',
                'subcategoria' => 'Dorsalgia'
            ],
            [
                'codigo_cid' => 'M54.2',
                'descricao' => 'Cervicalgia',
                'categoria' => 'Dorsalgia',
                'subcategoria' => 'Dor cervical'
            ],
            [
                'codigo_cid' => 'M54.6',
                'descricao' => 'Dor na coluna torácica',
                'categoria' => 'Dorsalgia',
                'subcategoria' => 'Dor torácica'
            ],

            // Lesões Traumáticas
            [
                'codigo_cid' => 'S93.4',
                'descricao' => 'Entorse e distensão do tornozelo',
                'categoria' => 'Lesões traumáticas',
                'subcategoria' => 'Lesões do tornozelo'
            ],
            [
                'codigo_cid' => 'S83.5',
                'descricao' => 'Entorse e distensão do joelho',
                'categoria' => 'Lesões traumáticas',
                'subcategoria' => 'Lesões do joelho'
            ],
            [
                'codigo_cid' => 'S43.4',
                'descricao' => 'Entorse e distensão do ombro',
                'categoria' => 'Lesões traumáticas',
                'subcategoria' => 'Lesões do ombro'
            ],

            // Distúrbios do Ombro
            [
                'codigo_cid' => 'M75.3',
                'descricao' => 'Síndrome do impacto do ombro',
                'categoria' => 'Lesões do ombro',
                'subcategoria' => 'Distúrbios de tecidos moles'
            ],
            [
                'codigo_cid' => 'M75.1',
                'descricao' => 'Síndrome do manguito rotador',
                'categoria' => 'Lesões do ombro',
                'subcategoria' => 'Distúrbios de tecidos moles'
            ],

            // Distúrbios Neurológicos
            [
                'codigo_cid' => 'G93.3',
                'descricao' => 'Síndrome pós-viral de fadiga',
                'categoria' => 'Reabilitação neurológica',
                'subcategoria' => 'Síndromes neurológicas'
            ],
            [
                'codigo_cid' => 'G81.9',
                'descricao' => 'Hemiplegia não especificada',
                'categoria' => 'Reabilitação neurológica',
                'subcategoria' => 'Paralisia'
            ],

            // Distúrbios Respiratórios
            [
                'codigo_cid' => 'J44.1',
                'descricao' => 'Doença pulmonar obstrutiva crônica com exacerbação aguda',
                'categoria' => 'Fisioterapia respiratória',
                'subcategoria' => 'DPOC'
            ],

            // Distúrbios Circulatórios
            [
                'codigo_cid' => 'I87.2',
                'descricao' => 'Insuficiência venosa (crônica) (periférica)',
                'categoria' => 'Fisioterapia vascular',
                'subcategoria' => 'Distúrbios circulatórios'
            ],

            // Fibromialgia
            [
                'codigo_cid' => 'M79.7',
                'descricao' => 'Fibromialgia',
                'categoria' => 'Dor crônica',
                'subcategoria' => 'Síndromes dolorosas'
            ],

            // Artrose
            [
                'codigo_cid' => 'M15.9',
                'descricao' => 'Poliartroses não especificadas',
                'categoria' => 'Artrose',
                'subcategoria' => 'Degenerações articulares'
            ],
            [
                'codigo_cid' => 'M17.9',
                'descricao' => 'Gonartrose não especificada',
                'categoria' => 'Artrose',
                'subcategoria' => 'Artrose do joelho'
            ]
        ];

        foreach ($diagnosticos as $diagnostico) {
            DB::table('diagnosticos_padronizados')->updateOrInsert(
                ['codigo_cid' => $diagnostico['codigo_cid']],
                [
                    'descricao' => $diagnostico['descricao'],
                    'categoria' => $diagnostico['categoria'],
                    'subcategoria' => $diagnostico['subcategoria'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
