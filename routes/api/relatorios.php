<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

Route::prefix('relatorios')->group(function () {
    // Financial report by payment method
    Route::get('/pagamentos', function (Request $request) {
        $inicio = $request->query('inicio');
        $fim = $request->query('fim');
        $tipo = $request->query('tipo'); // New parameter for filtering by payment type

        // Defaults to previous month if not provided
        $inicio = $inicio ?? Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $fim = $fim ?? Carbon::now()->subMonth()->endOfMonth()->toDateString();

        $query = DB::table('pagamentos')
            ->where('status_pagamento', 'pago')
            ->whereBetween('data_pagamento', [$inicio, $fim]);

        // Filter by payment type if specified
        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        // Group by both payment method and type
        $pagamentos = $query->select(
                'forma_pagamento',
                'tipo',
                DB::raw('SUM(valor_consulta) as total_pago'),
                DB::raw('COUNT(*) as quantidade')
            )
            ->groupBy('forma_pagamento', 'tipo')
            ->get();

        // Calculate totals by type
        $totaisPorTipo = $pagamentos->groupBy('tipo')
            ->map(function ($items, $tipo) {
                return [
                    'tipo' => $tipo,
                    'total' => $items->sum('total_pago'),
                    'quantidade' => $items->sum('quantidade')
                ];
            })
            ->values();

        return response()->json([
            'inicio' => $inicio,
            'fim' => $fim,
            'totais' => $pagamentos,
            'totais_por_tipo' => $totaisPorTipo,
            'total_geral' => $pagamentos->sum('total_pago')
        ]);
    });

    // Report of payments by type (consulta, produto, outro)
    Route::get('/pagamentos-por-tipo', function (Request $request) {
        $inicio = $request->query('inicio');
        $fim = $request->query('fim');

        // Defaults to current month if not provided
        $inicio = $inicio ?? Carbon::now()->startOfMonth()->toDateString();
        $fim = $fim ?? Carbon::now()->endOfMonth()->toDateString();

        $pagamentos = DB::table('pagamentos')
            ->join('pacientes', 'pagamentos.paciente_id', '=', 'pacientes.id')
            ->leftJoin('agendamentos', 'pagamentos.agendamento_id', '=', 'agendamentos.id')
            ->select(
                'pagamentos.id',
                'pagamentos.tipo',
                'pagamentos.descricao',
                'pagamentos.valor_consulta',
                'pagamentos.status_pagamento',
                'pagamentos.data_pagamento',
                'pacientes.nome as nome_paciente'
            )
            ->whereBetween('pagamentos.criado_em', [$inicio, $fim])
            ->orderBy('pagamentos.tipo')
            ->orderBy('pagamentos.criado_em', 'desc')
            ->get();

        // Group by type
        $porTipo = $pagamentos->groupBy('tipo')
            ->map(function ($items, $tipo) {
                return [
                    'tipo' => $tipo,
                    'quantidade' => $items->count(),
                    'total' => $items->sum('valor_consulta'),
                    'pagos' => $items->where('status_pagamento', 'pago')->count(),
                    'pendentes' => $items->where('status_pagamento', 'pendente')->count(),
                    'valor_pago' => $items->where('status_pagamento', 'pago')->sum('valor_consulta'),
                    'valor_pendente' => $items->where('status_pagamento', 'pendente')->sum('valor_consulta')
                ];
            })
            ->values();

        return response()->json([
            'inicio' => $inicio,
            'fim' => $fim,
            'pagamentos' => $pagamentos,
            'resumo_por_tipo' => $porTipo,
            'total_geral' => $pagamentos->sum('valor_consulta'),
            'total_pago' => $pagamentos->where('status_pagamento', 'pago')->sum('valor_consulta'),
            'total_pendente' => $pagamentos->where('status_pagamento', 'pendente')->sum('valor_consulta')
        ]);
    });
});
