<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PacientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array of 100 patients with varied data
        $pacientes = [];

        // Brazilian cities
        $cidades = ['São Paulo', 'Rio de Janeiro', 'Belo Horizonte', 'Salvador', 'Fortaleza',
                   'Brasília', 'Curitiba', 'Recife', 'Porto Alegre', 'Manaus', 'Belém',
                   'Goiânia', 'Guarulhos', 'Campinas', 'São Luís', 'Maceió', 'Natal', 'Teresina'];

        // Brazilian neighborhoods
        $bairros = ['Centro', 'Jardim Paulista', 'Copacabana', 'Ipanema', 'Leblon', 'Barra da Tijuca',
                   'Boa Viagem', 'Pituba', 'Aldeota', 'Meireles', 'Asa Sul', 'Asa Norte', 'Batel',
                   'Jardim América', 'Pinheiros', 'Vila Madalena', 'Moema', 'Itaim Bibi', 'Lapa'];

        // Professions
        $profissoes = ['Médico(a)', 'Engenheiro(a)', 'Professor(a)', 'Advogado(a)', 'Contador(a)',
                      'Programador(a)', 'Designer', 'Arquiteto(a)', 'Enfermeiro(a)', 'Psicólogo(a)',
                      'Dentista', 'Farmacêutico(a)', 'Nutricionista', 'Fisioterapeuta', 'Jornalista',
                      'Administrador(a)', 'Economista', 'Vendedor(a)', 'Empresário(a)', 'Estudante'];

        // Marital status
        $estadosCivis = ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)', 'União Estável'];

        // First names
        $nomesPrimeiro = ['Ana', 'João', 'Maria', 'Pedro', 'Juliana', 'Carlos', 'Fernanda', 'Lucas',
                         'Mariana', 'Rafael', 'Camila', 'Bruno', 'Amanda', 'Rodrigo', 'Patrícia',
                         'Gustavo', 'Aline', 'Felipe', 'Daniela', 'Marcelo', 'Luciana', 'Ricardo',
                         'Beatriz', 'Eduardo', 'Larissa', 'Paulo', 'Natália', 'Leonardo', 'Isabela',
                         'Gabriel', 'Letícia', 'Thiago', 'Carolina', 'Vinícius', 'Jéssica', 'Matheus',
                         'Gabriela', 'Diego', 'Vanessa', 'Alexandre', 'Bianca', 'Guilherme', 'Renata',
                         'Henrique', 'Tatiana', 'André', 'Mônica', 'Luiz', 'Débora', 'Fábio'];

        // Last names
        $nomesSobrenome = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves',
                          'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro', 'Martins', 'Carvalho',
                          'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa', 'Rocha',
                          'Dias', 'Nascimento', 'Andrade', 'Moreira', 'Nunes', 'Marques', 'Machado',
                          'Mendes', 'Freitas', 'Cardoso', 'Ramos', 'Gonçalves', 'Santana', 'Teixeira'];

        // Generate 100 patients
        for ($i = 1; $i <= 100; $i++) {
            // Generate random name
            $nome = $nomesPrimeiro[array_rand($nomesPrimeiro)] . ' ' .
                   ($i % 3 == 0 ? $nomesPrimeiro[array_rand($nomesPrimeiro)] . ' ' : '') .
                   $nomesSobrenome[array_rand($nomesSobrenome)] .
                   ($i % 4 == 0 ? ' ' . $nomesSobrenome[array_rand($nomesSobrenome)] : '');

            // Generate random birth date (between 18 and 80 years old)
            $idade = rand(18, 80);
            $dataNascimento = Carbon::now()->subYears($idade)->subDays(rand(0, 365));

            // Generate random CPF (just for demonstration, not valid CPFs)
            $cpf = sprintf('%03d', rand(0, 999)) .
                  sprintf('%03d', rand(0, 999)) .
                  sprintf('%03d', rand(0, 999)) .
                  sprintf('%02d', rand(0, 99));
            $cpf = substr($cpf, 0, 11); // Ensure it's 11 digits

            // Generate random email
            $emailNome = strtolower(explode(' ', $nome)[0]) .
                        ($i % 2 == 0 ? '.' . strtolower(explode(' ', $nome)[count(explode(' ', $nome)) - 1]) : '') .
                        rand(1, 999);
            $dominio = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com.br', 'uol.com.br', 'terra.com.br'];
            $email = $emailNome . '@' . $dominio[array_rand($dominio)];

            // Generate random phone number
            $telefone = '(' . rand(11, 99) . ') ' .
                       (rand(0, 1) ? '9' : '') .
                       rand(1000, 9999) . '-' .
                       rand(1000, 9999);

            // Create timestamp for created_at and updated_at
            $createdAt = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s');
            $updatedAt = rand(0, 1) ? Carbon::parse($createdAt)->addDays(rand(1, 30))->format('Y-m-d H:i:s') : $createdAt;

            $pacientes[] = [
                'nome' => $nome,
                'data_nascimento' => $dataNascimento->format('Y-m-d'),
                'telefone' => $telefone,
                'sexo' => rand(0, 10) > 1 ? (rand(0, 1) ? 'M' : 'F') : 'O',
                'cidade' => $cidades[array_rand($cidades)],
                'bairro' => $bairros[array_rand($bairros)],
                'profissao' => $profissoes[array_rand($profissoes)],
                'endereco_residencial' => 'Rua ' . ucfirst(str_shuffle('abcdefghijklmnopqrstuvwxyz')) . ', ' . rand(1, 9999),
                'endereco_comercial' => rand(0, 1) ? 'Av. ' . ucfirst(str_shuffle('abcdefghijklmnopqrstuvwxyz')) . ', ' . rand(1, 9999) : null,
                'naturalidade' => $cidades[array_rand($cidades)],
                'estado_civil' => $estadosCivis[array_rand($estadosCivis)],
                'cpf' => $cpf,
                'email' => $email,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt
            ];
        }

        // Insert data into the pacientes table
        DB::table('pacientes')->insert($pacientes);
    }
}
