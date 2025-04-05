<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array of users with varied data
        $usuarios = [
            // Admin users
            [
                'usuario' => 'admin',
                'senha' => Hash::make('admin123'),
                'admin' => 1
            ],
            [
                'usuario' => 'diretor',
                'senha' => Hash::make('diretor123'),
                'admin' => 1
            ],
            [
                'usuario' => 'gerente',
                'senha' => Hash::make('gerente123'),
                'admin' => 1
            ],

            // Regular users (staff)
            [
                'usuario' => 'recepcionista',
                'senha' => Hash::make('recep123'),
                'admin' => 0
            ],
            [
                'usuario' => 'fisioterapeuta1',
                'senha' => Hash::make('fisio123'),
                'admin' => 0
            ],
            [
                'usuario' => 'fisioterapeuta2',
                'senha' => Hash::make('fisio456'),
                'admin' => 0
            ],
            [
                'usuario' => 'atendente',
                'senha' => Hash::make('atend123'),
                'admin' => 0
            ],
            [
                'usuario' => 'secretaria',
                'senha' => Hash::make('secre123'),
                'admin' => 0
            ],
            [
                'usuario' => 'auxiliar',
                'senha' => Hash::make('aux123'),
                'admin' => 0
            ],
            [
                'usuario' => 'estagiario',
                'senha' => Hash::make('estag123'),
                'admin' => 0
            ],
        ];

        // Generate 20 more random users
        $firstNames = ['joao', 'maria', 'pedro', 'ana', 'carlos', 'lucia', 'roberto', 'patricia',
                      'fernando', 'amanda', 'ricardo', 'camila', 'bruno', 'juliana', 'lucas', 'mariana'];

        $roles = ['fisio', 'atend', 'recep', 'aux', 'estag', 'secre', 'prof'];

        for ($i = 1; $i <= 20; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $role = $roles[array_rand($roles)];
            $number = rand(1, 999);

            $username = $firstName . '.' . $role . $number;
            $password = $role . $number . '!' . ucfirst($firstName);

            $usuarios[] = [
                'usuario' => $username,
                'senha' => Hash::make($password),
                'admin' => rand(0, 10) > 8 ? 1 : 0 // 20% chance of being admin
            ];
        }

        // Insert data into the usuarios table
        DB::table('usuarios')->insert($usuarios);
    }
}
