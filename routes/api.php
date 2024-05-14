<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::get('/pacientes', function (Request $request) {
    return DB::table('pacientes')->get();
});

Route::get('/pacientes/{id}', function ($id) {
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

Route::post('/pacientes', function (Request $request) {
    try {
        DB::table('pacientes')->insert($request->FormData);

        return response()->json(['message' => 'Paciente criado com sucesso'], 201);
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao criar paciente', 'error' => $e->getMessage()], 500);
    }
});

Route::put('/pacientes/{id}', function (Request $request, $id) {
    try {
        // Update the patient record
        DB::table('pacientes')->where('id', $id)->update($request->FormData);

        return response()->json(['message' => 'Paciente atualizado com sucesso'], 200);
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao atualizar paciente', 'error' => $e->getMessage()], 500);
    }
});

Route::delete('/pacientes/{id}', function ($id) {
    try {
        DB::table('pacientes')->where('id', $id)->delete();

        return response()->json(['message' => 'Paciente deletado com sucesso'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao deletar paciente', 'error' => $e->getMessage()], 500);
    }
});

Route::get('/prontuarios', function (Request $request) {
return  DB::table('prontuarios')
        ->join('pacientes', 'prontuarios.id_paciente', '=', 'pacientes.id')
        ->leftJoin('evolucao_prontuario', function ($join) {
            $join->on('prontuarios.id', '=', 'evolucao_prontuario.id_prontuario')
                ->where('evolucao_prontuario.data_atendimento', function ($query) {
                    $query->select(DB::raw('max(data_atendimento)'))
                        ->from('evolucao_prontuario')
                        ->whereColumn('prontuarios.id', '=', 'evolucao_prontuario.id_prontuario');
                });
        })
        ->select('prontuarios.*', 'pacientes.nome as nome_paciente', DB::raw('(SELECT count(*) FROM evolucao_prontuario WHERE evolucao_prontuario.id_prontuario = prontuarios.id) as quantidade_evolucoes'))
        ->groupBy('prontuarios.id', 'prontuarios.id_paciente', 'pacientes.nome')
        ->get();
});

Route::get('/prontuarios/{id}', function ($id) {
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

Route::post('/prontuarios', function (Request $request) {
    try {


        DB::table('prontuarios')->insert($request->FormData);

        return response()->json(['message' => 'Prontuario criado com sucesso'], 201);
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao criar prontuario', 'error' => $e->getMessage()], 500);
    }
});

Route::put('/prontuarios/{id}', function (Request $request, $id) {
    try {
        // Update the patient record
        DB::table('prontuarios')->where('id', $id)->update($request->FormData);

        return response()->json(['message' => 'Prontuario atualizado com sucesso'], 200);
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao atualizar prontuario', 'error' => $e->getMessage()], 500);
    }
});

Route::get('/evolucao/{id}', function ($id) {
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


Route::post('/evolucao', function (Request $request) {
    try {
        DB::table('evolucao_prontuario')->insert($request->FormData);

        return response()->json(['message' => 'Evolução criado com sucesso'], 201);
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao criar evolução', 'error' => $e->getMessage()], 500);
    }
});

Route::get('/agendamentos', function (Request $request) {
    return DB::table('agendamentos')
        ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
        ->select('agendamentos.*', 'pacientes.nome as nome_paciente', DB::raw('CASE WHEN CONCAT(agendamentos.data, " ", agendamentos.hora) <= NOW() THEN "Realizado" ELSE "Em aberto" END AS status'))
        ->get();
});

Route::get('/agendamentos/proximo', function (Request $request) {
    $agendamento = DB::table('agendamentos')
        ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
        ->select('agendamentos.*', 'pacientes.nome as nome_paciente')
        ->where('agendamentos.data', '>=', DB::raw('CURDATE()'))
        ->where(function ($query) {
            $query->where('agendamentos.data', '>', DB::raw('CURDATE()'))
                ->orWhere(function ($query) {
                    $query->where('agendamentos.data', '=', DB::raw('CURDATE()'))
                        ->where('agendamentos.hora', '>', DB::raw('CURTIME()'));
                });
        })
        ->orderBy('agendamentos.data')
        ->orderBy('agendamentos.hora')
        ->first();

    return $agendamento;
});


Route::get('/agendamentos/hoje', function (Request $request) {
    $agendamentos = DB::table('agendamentos')
        ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
        ->select('agendamentos.*', 'pacientes.nome as nome_paciente', DB::raw('CASE WHEN CONCAT(agendamentos.data, " ", agendamentos.hora) <= NOW() THEN "Realizado" ELSE "Em aberto" END AS status'))
        ->whereDate('agendamentos.data', '=', DB::raw('CURDATE()'))
        ->orderByRaw('agendamentos.hora ASC')
        ->get();

    return $agendamentos;
});




Route::post('/agendamentos', function (Request $request) {
    try {
        DB::table('agendamentos')->insert($request->FormData);

        return response()->json(['message' => 'Agendamento criado com sucesso'], 201);
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao criar agendamento', 'error' => $e->getMessage()], 500);
    }
});


Route::get('/home', function (Request $request) {
    try {
        $proxAgendamento = DB::table('agendamentos')
        ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
        ->select('agendamentos.*', 'pacientes.nome as nome_paciente')
        ->where('agendamentos.data', '>=', DB::raw('CURDATE()'))
        ->where(function ($query) {
            $query->where('agendamentos.data', '>', DB::raw('CURDATE()'))
                ->orWhere(function ($query) {
                    $query->where('agendamentos.data', '=', DB::raw('CURDATE()'))
                        ->where('agendamentos.hora', '>', DB::raw('CURTIME()'));
                });
        })
        ->orderBy('agendamentos.data')
        ->orderBy('agendamentos.hora')
        ->first();

        $agendamentos = DB::table('agendamentos')
        ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
        ->select('agendamentos.*', 'pacientes.nome as nome_paciente', DB::raw('CASE WHEN CONCAT(agendamentos.data, " ", agendamentos.hora) <= NOW() THEN "Realizado" ELSE "Em aberto" END AS status'))
        ->whereDate('agendamentos.data', '=', DB::raw('CURDATE()'))
        ->orderByRaw('agendamentos.hora ASC')
        ->get();

        return [$proxAgendamento, $agendamentos];
    } catch (ValidationException $e) {
        return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao criar agendamento', 'error' => $e->getMessage()], 500);
    }
});