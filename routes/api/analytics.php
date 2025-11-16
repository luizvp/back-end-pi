<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;

/*
|--------------------------------------------------------------------------
| Analytics API Routes
|--------------------------------------------------------------------------
|
| Rotas para funcionalidades de Analytics avançadas:
| - Dashboard com métricas gerais
| - Análises de tratamento
| - Previsões ML
| - Dados IoT em tempo real
|
*/

Route::prefix('analytics')->group(function () {

    // Dashboard principal
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard'])
        ->name('analytics.dashboard');

    // Análises de tratamento
    Route::get('/tratamentos', [AnalyticsController::class, 'tratamentos'])
        ->name('analytics.tratamentos');

    // Previsões e alertas ML
    Route::get('/previsoes', [AnalyticsController::class, 'previsoes'])
        ->name('analytics.previsoes');

    // Dados IoT em tempo real
    Route::get('/iot', [AnalyticsController::class, 'iot'])
        ->name('analytics.iot');

    // Endpoints específicos para componentes
    Route::prefix('metricas')->group(function () {
        Route::get('/kpis', function () {
            return response()->json([
                'pacientes_total' => \App\Models\Paciente::count(),
                'tratamentos_ativos' => \App\Models\Tratamento::ativos()->count(),
                'sessoes_hoje' => \App\Models\SessaoFisioterapia::hoje()->count(),
                'receita_mes' => \App\Models\Pagamento::pagos()
                    ->whereBetween('data_pagamento', [now()->startOfMonth(), now()])
                    ->sum('valor_total')
            ]);
        });

        Route::get('/alertas', function () {
            return response()->json([
                'faltas_previstas' => \App\Models\PrevisaoMl::getPacientesAltoRisco('probabilidade_falta', 0.7)->count(),
                'equipamentos_manutencao' => \App\Models\Equipamento::manutencaoVencida()->count(),
                'pagamentos_pendentes' => \App\Models\Pagamento::pendentes()->count()
            ]);
        });
    });

    // Endpoint para dados em tempo real (WebSocket simulation)
    Route::get('/realtime', function () {
        return response()->json([
            'timestamp' => now()->toISOString(),
            'sessoes_ativas' => \App\Models\SessaoFisioterapia::hoje()->agendadas()->count(),
            'pacientes_atendidos_hoje' => \App\Models\SessaoFisioterapia::hoje()->realizadas()
                ->distinct('paciente_id')->count(),
            'ultima_atualizacao' => now()->format('H:i:s')
        ]);
    });
});
