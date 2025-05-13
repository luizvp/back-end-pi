<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('terminal')->group(function () {
    // Simular conexão com terminal
    Route::post('/connect', function (Request $request) {
        // Simular delay de conexão
        sleep(1);

        return response()->json([
            'connected' => true,
            'device_id' => 'TERM-'.rand(1000, 9999),
            'battery' => rand(50, 100).'%',
        ]);
    });

    // Simular processamento de pagamento
    Route::post('/process', function (Request $request) {
        // Validar dados
        $request->validate([
            'valor' => 'required|numeric',
            'forma_pagamento' => 'required|string',
            'parcelas' => 'nullable|integer|min:1'
        ]);

        // Simular processamento
        sleep(2);

        // 90% de chance de aprovação
        $approved = (rand(1, 100) <= 90);

        return response()->json([
            'status' => $approved ? 'approved' : 'declined',
            'transaction_id' => 'TX'.time().rand(1000, 9999),
            'valor' => $request->valor,
            'forma_pagamento' => $request->forma_pagamento,
            'parcelas' => $request->parcelas ?? 1,
            'message' => $approved ? 'Pagamento aprovado' : 'Cartão recusado',
            'timestamp' => now()->toIso8601String()
        ]);
    });
});
