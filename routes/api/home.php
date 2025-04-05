<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            ->select('agendamentos.*', 'pacientes.nome as nome_paciente',
                DB::raw('CASE WHEN CONCAT(agendamentos.data, " ", agendamentos.hora) <= NOW() THEN "Realizado" ELSE "Em aberto" END AS status'))
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
