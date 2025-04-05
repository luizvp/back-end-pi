<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::prefix('pagamentos')->group(function () {
    // Get all payments with optional filters
    Route::get('/', function (Request $request) {
        try {
            $query = DB::table('pagamentos')
                ->join('pacientes', 'pagamentos.paciente_id', '=', 'pacientes.id')
                ->leftJoin('agendamentos', 'pagamentos.agendamento_id', '=', 'agendamentos.id')
                ->select(
                    'pagamentos.*',
                    'pacientes.nome as nome_paciente',
                    'agendamentos.data as data_agendamento',
                    'agendamentos.hora as hora_agendamento'
                );

            // Apply filters
            if ($request->has('tipo')) {
                $query->where('pagamentos.tipo', $request->tipo);
            }

            if ($request->has('paciente_id')) {
                $query->where('pagamentos.paciente_id', $request->paciente_id);
            }

            if ($request->has('status')) {
                $query->where('pagamentos.status_pagamento', $request->status);
            }

            $pagamentos = $query->orderBy('pagamentos.created_at', 'desc')->get();

            return response()->json($pagamentos, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar pagamentos',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Get pending payments
    Route::get('/pendentes', function (Request $request) {
        try {
            // Get pending payments from appointments
            $pagamentosPendentes = DB::table('pagamentos')
                ->join('pacientes', 'pagamentos.paciente_id', '=', 'pacientes.id')
                ->leftJoin('agendamentos', 'pagamentos.agendamento_id', '=', 'agendamentos.id')
                ->select(
                    'pagamentos.id',
                    'pagamentos.agendamento_id',
                    'pagamentos.descricao',
                    'pagamentos.tipo',
                    'pagamentos.valor_consulta',
                    'pagamentos.status_pagamento',
                    'pacientes.nome as nome_paciente',
                    'agendamentos.data as data_agendamento',
                    'agendamentos.hora as hora_agendamento',
                    'pagamentos.valor_consulta as valor_pendente'
                )
                ->where('pagamentos.status_pagamento', '!=', 'pago')
                ->orderBy('agendamentos.data', 'desc')
                ->get();

            return response()->json([
                'pagamentos_pendentes' => $pagamentosPendentes,
                'total_pendente' => $pagamentosPendentes->sum('valor_pendente')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar pagamentos pendentes',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Create a new payment
    Route::post('/', function (Request $request) {
        try {
            DB::table('pagamentos')->insert($request->FormData);

            return response()->json(['message' => 'Pagamento registrado com sucesso'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao registrar pagamento', 'error' => $e->getMessage()], 500);
        }
    });

    // Create a new independent payment (not linked to an appointment)
    Route::post('/independente', function (Request $request) {
        try {
            $pagamentoId = DB::table('pagamentos')->insertGetId([
                'paciente_id' => $request->paciente_id,
                'descricao' => $request->descricao,
                'tipo' => $request->tipo ?? 'produto',
                'valor_consulta' => $request->valor,
                'forma_pagamento' => $request->forma_pagamento,
                'status_pagamento' => $request->status_pagamento ?? 'pendente',
                'data_pagamento' => $request->data_pagamento,
                'observacao' => $request->observacao
            ]);

            return response()->json([
                'message' => 'Pagamento independente criado com sucesso',
                'id' => $pagamentoId
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar pagamento independente',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Update an existing payment
    Route::put('/{id}', function (Request $request, $id) {
        try {
            DB::table('pagamentos')->where('id', $id)->update($request->FormData);

            return response()->json(['message' => 'Pagamento atualizado com sucesso'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar pagamento', 'error' => $e->getMessage()], 500);
        }
    });

    // Delete a payment
    Route::delete('/{id}', function ($id) {
        try {
            // Check if payment exists
            $payment = DB::table('pagamentos')->where('id', $id)->first();
            if (!$payment) {
                return response()->json(['message' => 'Pagamento não encontrado'], 404);
            }

            // Delete the payment
            DB::table('pagamentos')->where('id', $id)->delete();

            return response()->json(['message' => 'Pagamento excluído com sucesso'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao excluir pagamento', 'error' => $e->getMessage()], 500);
        }
    });
});
