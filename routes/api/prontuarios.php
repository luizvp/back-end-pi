<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::prefix('prontuarios')->group(function () {
    Route::get('/', function (Request $request) {
        return DB::table('prontuarios')
            ->join('pacientes', 'prontuarios.id_paciente', '=', 'pacientes.id')
            ->leftJoin('diagnosticos_padronizados', 'prontuarios.diagnostico_cid_id', '=', 'diagnosticos_padronizados.id')
            ->leftJoin('evolucao_prontuario', function ($join) {
                $join->on('prontuarios.id', '=', 'evolucao_prontuario.id_prontuario')
                    ->where('evolucao_prontuario.data_atendimento', function ($query) {
                        $query->select(DB::raw('max(data_atendimento)'))
                            ->from('evolucao_prontuario')
                            ->whereColumn('prontuarios.id', '=', 'evolucao_prontuario.id_prontuario');
                    });
            })
            ->leftJoin('tratamentos', 'prontuarios.id', '=', 'tratamentos.prontuario_id')
            ->select(
                'prontuarios.*',
                'pacientes.nome as nome_paciente',
                'diagnosticos_padronizados.codigo_cid',
                'diagnosticos_padronizados.descricao as diagnostico_descricao',
                'tratamentos.status as status_tratamento',
                'tratamentos.data_alta_real',
                'tratamentos.motivo_alta',
                DB::raw('(SELECT count(*) FROM evolucao_prontuario WHERE evolucao_prontuario.id_prontuario = prontuarios.id) as quantidade_evolucoes')
            )
            ->groupBy(
                'prontuarios.id',
                'prontuarios.id_paciente',
                'pacientes.nome',
                'diagnosticos_padronizados.codigo_cid',
                'diagnosticos_padronizados.descricao',
                'tratamentos.status',
                'tratamentos.data_alta_real',
                'tratamentos.motivo_alta'
            )
            ->get()
            ->map(function ($prontuario) {
                // Estruturar dados para corresponder ao que o frontend espera
                $result = (array) $prontuario;

                if ($prontuario->codigo_cid) {
                    $result['diagnostico_padronizado'] = [
                        'codigo_cid' => $prontuario->codigo_cid,
                        'descricao' => $prontuario->diagnostico_descricao
                    ];
                } else {
                    $result['diagnostico_padronizado'] = null;
                }

                // Remover campos duplicados
                unset($result['codigo_cid'], $result['diagnostico_descricao']);

                return $result;
            });
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
