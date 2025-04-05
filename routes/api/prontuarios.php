<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::prefix('prontuarios')->group(function () {
    Route::get('/', function (Request $request) {
        return DB::table('prontuarios')
            ->join('pacientes', 'prontuarios.id_paciente', '=', 'pacientes.id')
            ->leftJoin('evolucao_prontuario', function ($join) {
                $join->on('prontuarios.id', '=', 'evolucao_prontuario.id_prontuario')
                    ->where('evolucao_prontuario.data_atendimento', function ($query) {
                        $query->select(DB::raw('max(data_atendimento)'))
                            ->from('evolucao_prontuario')
                            ->whereColumn('prontuarios.id', '=', 'evolucao_prontuario.id_prontuario');
                    });
            })
            ->select('prontuarios.*', 'pacientes.nome as nome_paciente',
                DB::raw('(SELECT count(*) FROM evolucao_prontuario WHERE evolucao_prontuario.id_prontuario = prontuarios.id) as quantidade_evolucoes'))
            ->groupBy('prontuarios.id', 'prontuarios.id_paciente', 'pacientes.nome')
            ->get();
    });

    Route::get('/{id}', function ($id) {
        try {
            $paciente = DB::table('prontuarios')->where('id', $id)->first();

            if ($paciente) {
                return response()->json(['prontuario' => $paciente], 200);
            } else {
                return response()->json(['message' => 'Prontuario não encontrado'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao buscar prontuario', 'error' => $e->getMessage()], 500);
        }
    });

    Route::post('/', function (Request $request) {
        try {
            DB::table('prontuarios')->insert($request->FormData);

            return response()->json(['message' => 'Prontuario criado com sucesso'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao criar prontuario', 'error' => $e->getMessage()], 500);
        }
    });

    Route::put('/{id}', function (Request $request, $id) {
        try {
            DB::table('prontuarios')->where('id', $id)->update($request->FormData);

            return response()->json(['message' => 'Prontuario atualizado com sucesso'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar prontuario', 'error' => $e->getMessage()], 500);
        }
    });
});
