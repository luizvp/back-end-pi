<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::prefix('evolucao')->group(function () {
    Route::get('/{id}', function ($id) {
        try {
            $evolucao = DB::table('evolucao_prontuario')->where('id_prontuario', $id)->get();

            if ($evolucao) {
                return response()->json(['evolucao' => $evolucao], 200);
            } else {
                return response()->json(['message' => 'evolucao não encontrado'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao buscar evolucao', 'error' => $e->getMessage()], 500);
        }
    });

    Route::post('/', function (Request $request) {
        try {
            DB::table('evolucao_prontuario')->insert($request->FormData);

            return response()->json(['message' => 'Evolução criado com sucesso'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao criar evolução', 'error' => $e->getMessage()], 500);
        }
    });
});
