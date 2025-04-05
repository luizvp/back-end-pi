<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::prefix('pacientes')->group(function () {
    Route::get('/', function (Request $request) {
        return DB::table('pacientes')->get();
    });

    Route::get('/{id}', function ($id) {
        try {
            $paciente = DB::table('pacientes')->where('id', $id)->first();

            if ($paciente) {
                return response()->json(['paciente' => $paciente], 200);
            } else {
                return response()->json(['message' => 'Paciente não encontrado'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao buscar paciente', 'error' => $e->getMessage()], 500);
        }
    });

    Route::post('/', function (Request $request) {
        try {
            DB::table('pacientes')->insert($request->FormData);

            return response()->json(['message' => 'Paciente criado com sucesso'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao criar paciente', 'error' => $e->getMessage()], 500);
        }
    });

    Route::put('/{id}', function (Request $request, $id) {
        try {
            DB::table('pacientes')->where('id', $id)->update($request->FormData);

            return response()->json(['message' => 'Paciente atualizado com sucesso'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar paciente', 'error' => $e->getMessage()], 500);
        }
    });

    Route::delete('/{id}', function ($id) {
        try {
            DB::table('pacientes')->where('id', $id)->delete();

            return response()->json(['message' => 'Paciente deletado com sucesso'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao deletar paciente', 'error' => $e->getMessage()], 500);
        }
    });
});
