<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all appointment IDs
        $agendamentoIds = DB::table('agendamentos')->pluck('id', 'id_paciente')->toArray();

        // Get all patient IDs
        $pacienteIds = DB::table('pacientes')->pluck('id')->toArray();

        // Payment methods
        $formasPagamento = [
            'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro', 'PIX', 'Transferência Bancária',
            'Boleto', 'Cheque', 'Convênio'
        ];

        // Payment types
        $tiposPagamento = ['consulta', 'produto', 'outro'];

        // Payment status
        $statusPagamento = ['pendente', 'pago', 'cancelado'];

        // Payment descriptions for products
        $descricoesProdutos = [
            'Venda de órtese para {parte_corpo}',
            'Venda de bandagem para {parte_corpo}',
            'Venda de equipamento para exercícios em casa',
            'Venda de bola terapêutica',
            'Venda de faixa elástica',
            'Venda de tens portátil',
            'Venda de almofada ortopédica',
            'Venda de palmilha ortopédica',
            'Venda de colar cervical',
            'Venda de muleta',
            'Venda de bengala',
            'Venda de andador',
            'Venda de tipoia'
        ];

        // Body parts for product descriptions
        $partesCorpo = [
            'joelho', 'tornozelo', 'punho', 'cotovelo', 'ombro', 'coluna lombar',
            'coluna cervical', 'quadril', 'mão', 'pé'
        ];

        // Payment descriptions for other types
        $descricoesOutros = [
            'Avaliação postural',
            'Avaliação funcional',
            'Avaliação ergonômica',
            'Sessão domiciliar',
            'Pacote de sessões',
            'Relatório médico',
            'Atestado',
            'Teleconsulta',
            'Orientação domiciliar',
            'Consulta de retorno'
        ];

        // Generate payments
        $pagamentos = [];

        // 1. Generate payments for appointments (80% of appointments)
        $agendamentosComPagamento = array_slice($agendamentoIds, 0, (int)(count($agendamentoIds) * 0.8), true);

        foreach ($agendamentosComPagamento as $pacienteId => $agendamentoId) {
            // Get appointment date
            $dataAgendamento = DB::table('agendamentos')
                ->where('id', $agendamentoId)
                ->value('data');

            // Convert to Carbon instance
            $dataAgendamentoCarbonObj = Carbon::parse($dataAgendamento);

            // Generate payment date (same day or up to 7 days after appointment)
            $dataPagamento = $dataAgendamentoCarbonObj->copy()->addDays(rand(0, 7))->format('Y-m-d H:i:s');

            // Generate payment status (70% paid, 20% pending, 10% canceled)
            $randomStatus = rand(1, 100);
            $status = $randomStatus <= 70 ? 'pago' : ($randomStatus <= 90 ? 'pendente' : 'cancelado');

            // Generate payment method (only for paid payments)
            $formaPagamento = $status === 'pago' ? $formasPagamento[array_rand($formasPagamento)] : null;

            // Generate payment amount (between 80 and 200)
            $valorConsulta = rand(80, 200) + (rand(0, 99) / 100);

            // Get patient name
            $nomePaciente = DB::table('pacientes')
                ->where('id', $pacienteId)
                ->value('nome');

            // Generate description
            $descricao = "Consulta agendada para {$nomePaciente} em {$dataAgendamento}";

            // Create payment
            $pagamentos[] = [
                'agendamento_id' => $agendamentoId,
                'paciente_id' => $pacienteId,
                'descricao' => $descricao,
                'tipo' => 'consulta',
                'valor_consulta' => $valorConsulta,
                'forma_pagamento' => $formaPagamento,
                'status_pagamento' => $status,
                'data_pagamento' => $status === 'pago' ? $dataPagamento : null,
                'observacao' => $status === 'cancelado' ? 'Pagamento cancelado devido a ' . ($this->randomChance(50) ? 'cancelamento da consulta' : 'reagendamento') : null,
                'created_at' => $dataAgendamentoCarbonObj->copy()->subDays(rand(0, 3))->format('Y-m-d H:i:s'),
                'updated_at' => $status !== 'pendente' ? $dataPagamento : $dataAgendamentoCarbonObj->copy()->format('Y-m-d H:i:s')
            ];
        }

        // 2. Generate product payments (not linked to appointments)
        $numProdutoPagamentos = 50;

        for ($i = 1; $i <= $numProdutoPagamentos; $i++) {
            // Random patient
            $pacienteId = $pacienteIds[array_rand($pacienteIds)];

            // Get patient name
            $nomePaciente = DB::table('pacientes')
                ->where('id', $pacienteId)
                ->value('nome');

            // Generate payment date (between 1 and 180 days ago)
            $dataPagamento = Carbon::now()->subDays(rand(1, 180))->format('Y-m-d H:i:s');

            // Generate payment status (80% paid, 15% pending, 5% canceled)
            $randomStatus = rand(1, 100);
            $status = $randomStatus <= 80 ? 'pago' : ($randomStatus <= 95 ? 'pendente' : 'cancelado');

            // Generate payment method (only for paid payments)
            $formaPagamento = $status === 'pago' ? $formasPagamento[array_rand($formasPagamento)] : null;

            // Generate payment amount (between 30 and 300)
            $valorConsulta = rand(30, 300) + (rand(0, 99) / 100);

            // Generate description
            $descricaoBase = $descricoesProdutos[array_rand($descricoesProdutos)];
            $parteCorpo = $partesCorpo[array_rand($partesCorpo)];
            $descricao = str_replace('{parte_corpo}', $parteCorpo, $descricaoBase) . ' para ' . $nomePaciente;

            // Create payment
            $pagamentos[] = [
                'agendamento_id' => null,
                'paciente_id' => $pacienteId,
                'descricao' => $descricao,
                'tipo' => 'produto',
                'valor_consulta' => $valorConsulta,
                'forma_pagamento' => $formaPagamento,
                'status_pagamento' => $status,
                'data_pagamento' => $status === 'pago' ? $dataPagamento : null,
                'observacao' => null,
                'created_at' => Carbon::parse($dataPagamento)->subHours(rand(1, 24))->format('Y-m-d H:i:s'),
                'updated_at' => $dataPagamento
            ];
        }

        // 3. Generate other payments (not linked to appointments)
        $numOutrosPagamentos = 30;

        for ($i = 1; $i <= $numOutrosPagamentos; $i++) {
            // Random patient
            $pacienteId = $pacienteIds[array_rand($pacienteIds)];

            // Get patient name
            $nomePaciente = DB::table('pacientes')
                ->where('id', $pacienteId)
                ->value('nome');

            // Generate payment date (between 1 and 180 days ago)
            $dataPagamento = Carbon::now()->subDays(rand(1, 180))->format('Y-m-d H:i:s');

            // Generate payment status (80% paid, 15% pending, 5% canceled)
            $randomStatus = rand(1, 100);
            $status = $randomStatus <= 80 ? 'pago' : ($randomStatus <= 95 ? 'pendente' : 'cancelado');

            // Generate payment method (only for paid payments)
            $formaPagamento = $status === 'pago' ? $formasPagamento[array_rand($formasPagamento)] : null;

            // Generate payment amount (between 50 and 250)
            $valorConsulta = rand(50, 250) + (rand(0, 99) / 100);

            // Generate description
            $descricao = $descricoesOutros[array_rand($descricoesOutros)] . ' para ' . $nomePaciente;

            // Create payment
            $pagamentos[] = [
                'agendamento_id' => null,
                'paciente_id' => $pacienteId,
                'descricao' => $descricao,
                'tipo' => 'outro',
                'valor_consulta' => $valorConsulta,
                'forma_pagamento' => $formaPagamento,
                'status_pagamento' => $status,
                'data_pagamento' => $status === 'pago' ? $dataPagamento : null,
                'observacao' => null,
                'created_at' => Carbon::parse($dataPagamento)->subHours(rand(1, 24))->format('Y-m-d H:i:s'),
                'updated_at' => $dataPagamento
            ];
        }

        // Insert data into the pagamentos table
        DB::table('pagamentos')->insert($pagamentos);
    }

    /**
     * Helper function to generate random chance
     */
    private function randomChance($percentageChance)
    {
        return rand(1, 100) <= $percentageChance;
    }
}
