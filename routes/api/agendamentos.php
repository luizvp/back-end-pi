<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::prefix('agendamentos')->group(function () {
    Route::get('/', function (Request $request) {
        $pacienteId = $request->query('paciente_id');
        $statusFilter = $request->query('status', 'Todos');

        $query = DB::table('agendamentos')
            ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
            ->leftJoin('pagamentos', 'agendamentos.id', '=', 'pagamentos.agendamento_id')
            ->select(
                'agendamentos.*',
                'pacientes.nome as nome_paciente',
                'pagamentos.id as pagamento_id',
                'pagamentos.valor_consulta',
                'pagamentos.forma_pagamento',
                'pagamentos.status_pagamento',
                'pagamentos.data_pagamento',
                'pagamentos.observacao',
                DB::raw('CASE WHEN CONCAT(agendamentos.data, " ", agendamentos.hora) <= NOW() THEN "Realizado" ELSE "Em aberto" END AS status')
            );

        if ($pacienteId) $query->where('agendamentos.id_paciente', $pacienteId);
        if ($statusFilter !== 'Todos') $query->having('status', '=', $statusFilter);

        return $query->get();
    });

    Route::get('/proximo', function (Request $request) {
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

    Route::get('/hoje', function (Request $request) {
        $agendamentos = DB::table('agendamentos')
            ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
            ->select('agendamentos.*', 'pacientes.nome as nome_paciente',
                DB::raw('CASE WHEN CONCAT(agendamentos.data, " ", agendamentos.hora) <= NOW() THEN "Realizado" ELSE "Em aberto" END AS status'))
            ->whereDate('agendamentos.data', '=', DB::raw('CURDATE()'))
            ->orderByRaw('agendamentos.hora ASC')
            ->get();

        return $agendamentos;
    });

    Route::post('/', function (Request $request) {
        try {
            // Begin transaction to ensure both operations succeed or fail together
            DB::beginTransaction();

            // Insert the appointment and get its ID
            $agendamentoId = DB::table('agendamentos')->insertGetId($request->all());

            // Get the appointment details to create a meaningful payment description
            $agendamento = DB::table('agendamentos')->where('id', $agendamentoId)->first();
            $pacienteNome = DB::table('pacientes')->where('id', $agendamento->id_paciente)->value('nome');

            // Create a payment record for this appointment
            DB::table('pagamentos')->insert([
                'agendamento_id' => $agendamentoId,
                'paciente_id' => $agendamento->id_paciente,
                'descricao' => "Consulta agendada para {$pacienteNome} em {$agendamento->data} às {$agendamento->hora}",
                'tipo' => 'consulta',
                'valor_consulta' => 100.00, // Default value
                'status_pagamento' => 'pendente'
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Agendamento criado com sucesso. Um pagamento pendente foi gerado automaticamente.',
                'agendamento_id' => $agendamentoId
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao criar agendamento', 'error' => $e->getMessage()], 500);
        }
    });

    Route::put('/{id}', function (Request $request, $id) {
        try {
            DB::table('agendamentos')->where('id', $id)->update($request->all());

            return response()->json(['message' => 'Agendamento atualizado com sucesso'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar agendamento', 'error' => $e->getMessage()], 500);
        }
    });

    Route::delete('/{id}', function ($id) {
        try {
            DB::table('agendamentos')->where('id', $id)->delete();

            return response()->json(['message' => 'Agendamento deletado com sucesso'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao deletar agendamento', 'error' => $e->getMessage()], 500);
        }
    });
});
