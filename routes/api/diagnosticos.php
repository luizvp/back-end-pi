<?php

use Illuminate\Support\Facades\Route;
use App\Models\DiagnosticoPadronizado;

/*
|--------------------------------------------------------------------------
| Diagnósticos API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('diagnosticos')->group(function () {

    // Listar todos os diagnósticos
    Route::get('/', function () {
        try {
            $diagnosticos = DiagnosticoPadronizado::orderBy('categoria')
                ->orderBy('codigo_cid')
                ->get();

            return response()->json($diagnosticos);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar diagnósticos',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Buscar por categoria
    Route::get('/categoria/{categoria}', function ($categoria) {
        try {
            $diagnosticos = DiagnosticoPadronizado::where('categoria', $categoria)
                ->orderBy('codigo_cid')
                ->get();

            return response()->json($diagnosticos);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar diagnósticos por categoria',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Buscar por ID
    Route::get('/{id}', function ($id) {
        try {
            $diagnostico = DiagnosticoPadronizado::find($id);

            if (!$diagnostico) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diagnóstico não encontrado'
                ], 404);
            }

            return response()->json($diagnostico);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar diagnóstico',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Buscar categorias únicas
    Route::get('/categorias/list', function () {
        try {
            $categorias = DiagnosticoPadronizado::distinct()
                ->orderBy('categoria')
                ->pluck('categoria');

            return response()->json($categorias);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar categorias',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});
