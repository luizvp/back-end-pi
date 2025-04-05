<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call seeders in the correct order to handle dependencies
        $this->call([
            // First seed tables without foreign key dependencies
            PacientesSeeder::class,
            UsuariosSeeder::class,

            // Then seed tables with foreign key dependencies
            ProntuariosSeeder::class,
            AgendamentosSeeder::class,

            // Finally seed tables that depend on multiple other tables
            EvolucaoProntuarioSeeder::class,
            PagamentosSeeder::class,
        ]);
    }
}
