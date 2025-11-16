<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Agendamento;

Route::prefix('agendamentos')->group(function () {
    // Listar agendamentos
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
                'pagamentos.observacao'
            );

        if ($pacienteId) $query->where('agendamentos.id_paciente', $pacienteId);
        if ($statusFilter !== 'Todos') $query->where('agendamentos.status', $statusFilter);

        return $query->get();
    });

    // Próximo agendamento
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

    // Agendamentos de hoje
    Route::get('/hoje', function (Request $request) {
        $agendamentos = DB::table('agendamentos')
            ->join('pacientes', 'agendamentos.id_paciente', '=', 'pacientes.id')
            ->select('agendamentos.*', 'pacientes.nome as nome_paciente')
            ->whereDate('agendamentos.data', '=', DB::raw('CURDATE()'))
            ->orderByRaw('agendamentos.hora ASC')
            ->get();

        return $agendamentos;
    });

    // Atualizar status automaticamente (para agendamentos vencidos)
    Route::post('/auto-update-status', function () {
        try {
            $agendamentosVencidos = Agendamento::where('alterado_manualmente', false)
                ->where('status', 'agendado')
                ->whereRaw('CONCAT(data, " ", IFNULL(hora, "00:00:00")) < NOW()')
                ->get();

            $atualizados = 0;
            foreach ($agendamentosVencidos as $agendamento) {
                if ($agendamento->marcarComoRealizadoAutomaticamente()) {
                    $atualizados++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$atualizados} agendamentos atualizados automaticamente para 'realizado'",
                'atualizados' => $atualizados
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar status automaticamente',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Alterar status manualmente
    Route::put('/{id}/status', function (Request $request, $id) {
        try {
            $agendamento = Agendamento::findOrFail($id);
            $novoStatus = $request->input('status');
            $observacoes = $request->input('observacoes');
            $usuario = $request->input('usuario', 'sistema');

            $resultado = $agendamento->alterarStatusManualmente($novoStatus, $observacoes, $usuario);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status alterado com sucesso',
                    'agendamento' => $agendamento->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao alterar status'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status',
                'error' => $e->getMessage()
            ], 500);
        }
    });


    // Marcar como realizado
    Route::put('/{id}/realizado', function (Request $request, $id) {
        try {
            $agendamento = Agendamento::findOrFail($id);
            $observacoes = $request->input('observacoes');
            $usuario = $request->input('usuario', 'sistema');

            $resultado = $agendamento->marcarComoRealizado($observacoes, $usuario);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Agendamento marcado como realizado',
                    'agendamento' => $agendamento->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Falha ao marcar como realizado'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar como realizado',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Marcar falta
    Route::put('/{id}/faltou', function (Request $request, $id) {
        try {
            $agendamento = Agendamento::findOrFail($id);
            $observacoes = $request->input('observacoes', 'Paciente não compareceu');
            $usuario = $request->input('usuario', 'sistema');

            $resultado = $agendamento->marcarComoFaltou($observacoes, $usuario);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Falta registrada com sucesso',
                    'agendamento' => $agendamento->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Falha ao registrar falta'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar falta',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Cancelar agendamento
    Route::put('/{id}/cancelar', function (Request $request, $id) {
        try {
            $agendamento = Agendamento::findOrFail($id);
            $motivo = $request->input('motivo', 'Agendamento cancelado');
            $usuario = $request->input('usuario', 'sistema');

            $resultado = $agendamento->cancelar($motivo, $usuario);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Agendamento cancelado com sucesso',
                    'agendamento' => $agendamento->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Falha ao cancelar agendamento'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar agendamento',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Criar agendamento
    Route::post('/', function (Request $request) {
        try {
            DB::beginTransaction();

            // Criar agendamento com status inicial
            $dadosAgendamento = $request->all();
            $dadosAgendamento['status'] = 'agendado';
            $dadosAgendamento['alterado_manualmente'] = false;

            $agendamentoId = DB::table('agendamentos')->insertGetId($dadosAgendamento);

            // Criar pagamento pendente automaticamente
            $agendamento = DB::table('agendamentos')->where('id', $agendamentoId)->first();
            $pacienteNome = DB::table('pacientes')->where('id', $agendamento->id_paciente)->value('nome');

            DB::table('pagamentos')->insert([
                'agendamento_id' => $agendamentoId,
                'paciente_id' => $agendamento->id_paciente,
                'descricao' => "Consulta agendada para {$pacienteNome} em {$agendamento->data} às {$agendamento->hora}",
                'tipo' => 'consulta',
                'valor_consulta' => 100.00,
                'status_pagamento' => 'pendente'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Agendamento criado com sucesso',
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

    // Atualizar agendamento
    Route::put('/{id}', function (Request $request, $id) {
        try {
            $dados = $request->all();

            // Remover campos que não devem ser alterados diretamente
            unset($dados['status'], $dados['alterado_manualmente'], $dados['data_status_alterado']);

            DB::table('agendamentos')->where('id', $id)->update($dados);

            return response()->json(['message' => 'Agendamento atualizado com sucesso'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar agendamento', 'error' => $e->getMessage()], 500);
        }
    });

    // Deletar agendamento
    Route::delete('/{id}', function ($id) {
        try {
            DB::table('agendamentos')->where('id', $id)->delete();

            return response()->json(['message' => 'Agendamento deletado com sucesso'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao deletar agendamento', 'error' => $e->getMessage()], 500);
        }
    });
});
