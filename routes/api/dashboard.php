<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

Route::prefix('dashboard')->group(function () {
    // Get KPIs for the dashboard
    Route::get('/kpis', function (Request $request) {
        try {
            // Get period from request (default to current month)
            $period = $request->query('period', 'month');

            // Calculate date ranges based on period
            $today = Carbon::today();
            $startDate = null;
            $endDate = $today->copy()->endOfDay();
            $previousStartDate = null;
            $previousEndDate = null;

            switch ($period) {
                case 'week':
                    $startDate = $today->copy()->startOfWeek();
                    $previousStartDate = $startDate->copy()->subWeek();
                    $previousEndDate = $previousStartDate->copy()->endOfWeek();
                    break;
                case 'month':
                    $startDate = $today->copy()->startOfMonth();
                    $previousStartDate = $startDate->copy()->subMonth();
                    $previousEndDate = $previousStartDate->copy()->endOfMonth();
                    break;
                case 'year':
                    $startDate = $today->copy()->startOfYear();
                    $previousStartDate = $startDate->copy()->subYear();
                    $previousEndDate = $previousStartDate->copy()->endOfYear();
                    break;
                default:
                    $startDate = $today->copy()->startOfMonth();
                    $previousStartDate = $startDate->copy()->subMonth();
                    $previousEndDate = $previousStartDate->copy()->endOfMonth();
            }

            // 1. Financial KPIs

            // Current period revenue
            $currentRevenue = DB::table('pagamentos')
                ->where('status_pagamento', 'pago')
                ->whereBetween('data_pagamento', [$startDate, $endDate])
                ->sum('valor_consulta');

            // Previous period revenue
            $previousRevenue = DB::table('pagamentos')
                ->where('status_pagamento', 'pago')
                ->whereBetween('data_pagamento', [$previousStartDate, $previousEndDate])
                ->sum('valor_consulta');

            // Revenue by payment method
            $revenueByMethod = DB::table('pagamentos')
                ->select('forma_pagamento', DB::raw('SUM(valor_consulta) as total'))
                ->where('status_pagamento', 'pago')
                ->whereBetween('data_pagamento', [$startDate, $endDate])
                ->groupBy('forma_pagamento')
                ->get();

            // Revenue by type
            $revenueByType = DB::table('pagamentos')
                ->select('tipo', DB::raw('SUM(valor_consulta) as total'))
                ->where('status_pagamento', 'pago')
                ->whereBetween('data_pagamento', [$startDate, $endDate])
                ->groupBy('tipo')
                ->get();

            // Pending payments
            $pendingPayments = DB::table('pagamentos')
                ->where('status_pagamento', 'pendente')
                ->sum('valor_consulta');

            // Total payments
            $totalPayments = DB::table('pagamentos')
                ->sum('valor_consulta');

            // Default rate (percentage of pending payments)
            $defaultRate = $totalPayments > 0 ? ($pendingPayments / $totalPayments) * 100 : 0;

            // Average ticket
            $paidPaymentsCount = DB::table('pagamentos')
                ->where('status_pagamento', 'pago')
                ->whereBetween('data_pagamento', [$startDate, $endDate])
                ->count();

            $averageTicket = $paidPaymentsCount > 0 ? $currentRevenue / $paidPaymentsCount : 0;

            // 2. Patient KPIs

            // Total active patients
            $totalPatients = DB::table('pacientes')->count();

            // New patients in current period
            $newPatients = DB::table('pacientes')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // New patients in previous period
            $previousNewPatients = DB::table('pacientes')
                ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
                ->count();

            // 3. Appointment KPIs

            // Total appointments in current period
            $totalAppointments = DB::table('agendamentos')
                ->whereBetween('data', [$startDate->toDateString(), $endDate->toDateString()])
                ->count();

            // Completed appointments (agendamentos com data anterior à data atual)
            $completedAppointments = DB::table('agendamentos')
                ->where('data', '<', $today->toDateString())
                ->whereBetween('data', [$startDate->toDateString(), $endDate->toDateString()])
                ->count();

            // Appointment completion rate
            $completionRate = $totalAppointments > 0 ? ($completedAppointments / $totalAppointments) * 100 : 0;

            // Return all KPIs
            return response()->json([
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'previous_start' => $previousStartDate->toDateString(),
                    'previous_end' => $previousEndDate->toDateString(),
                ],
                'financial' => [
                    'current_revenue' => round($currentRevenue, 2),
                    'previous_revenue' => round($previousRevenue, 2),
                    'revenue_change_percentage' => $previousRevenue > 0
                        ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                        : 0,
                    'revenue_by_method' => $revenueByMethod,
                    'revenue_by_type' => $revenueByType,
                    'pending_payments' => round($pendingPayments, 2),
                    'default_rate' => round($defaultRate, 2),
                    'average_ticket' => round($averageTicket, 2),
                ],
                'patients' => [
                    'total_patients' => $totalPatients,
                    'new_patients' => $newPatients,
                    'previous_new_patients' => $previousNewPatients,
                    'patients_change_percentage' => $previousNewPatients > 0
                        ? round((($newPatients - $previousNewPatients) / $previousNewPatients) * 100, 2)
                        : 0,
                ],
                'appointments' => [
                    'total_appointments' => $totalAppointments,
                    'completed_appointments' => $completedAppointments,
                    'completion_rate' => round($completionRate, 2),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar KPIs do dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Get trend data for charts
    Route::get('/trends', function (Request $request) {
        try {
            // Get period from request (default to current month)
            $period = $request->query('period', 'month');
            $metric = $request->query('metric', 'revenue'); // revenue, appointments, patients

            // Calculate date ranges and grouping based on period
            $today = Carbon::today();
            $startDate = null;
            $groupBy = 'day'; // Default grouping

            switch ($period) {
                case 'week':
                    $startDate = $today->copy()->startOfWeek();
                    $groupBy = 'day';
                    break;
                case 'month':
                    $startDate = $today->copy()->startOfMonth();
                    $groupBy = 'day';
                    break;
                case 'year':
                    $startDate = $today->copy()->startOfYear();
                    $groupBy = 'month';
                    break;
                default:
                    $startDate = $today->copy()->startOfMonth();
                    $groupBy = 'day';
            }

            $endDate = $today->copy()->endOfDay();

            // Prepare the result array
            $result = [];

            // Get trend data based on the requested metric
            switch ($metric) {
                case 'revenue':
                    // Revenue trend
                    if ($groupBy === 'day') {
                        $trend = DB::table('pagamentos')
                            ->select(DB::raw('DATE(data_pagamento) as date'), DB::raw('SUM(valor_consulta) as value'))
                            ->where('status_pagamento', 'pago')
                            ->whereBetween('data_pagamento', [$startDate, $endDate])
                            ->groupBy(DB::raw('DATE(data_pagamento)'))
                            ->orderBy('date')
                            ->get();
                    } else {
                        $trend = DB::table('pagamentos')
                            ->select(DB::raw('MONTH(data_pagamento) as month'), DB::raw('SUM(valor_consulta) as value'))
                            ->where('status_pagamento', 'pago')
                            ->whereBetween('data_pagamento', [$startDate, $endDate])
                            ->groupBy(DB::raw('MONTH(data_pagamento)'))
                            ->orderBy('month')
                            ->get();
                    }
                    $result = $trend;
                    break;

                case 'appointments':
                    // Appointments trend
                    if ($groupBy === 'day') {
                        $trend = DB::table('agendamentos')
                            ->select(DB::raw('data as date'), DB::raw('COUNT(*) as value'))
                            ->whereBetween('data', [$startDate->toDateString(), $endDate->toDateString()])
                            ->groupBy('data')
                            ->orderBy('date')
                            ->get();
                    } else {
                        $trend = DB::table('agendamentos')
                            ->select(DB::raw('MONTH(data) as month'), DB::raw('COUNT(*) as value'))
                            ->whereBetween('data', [$startDate->toDateString(), $endDate->toDateString()])
                            ->groupBy(DB::raw('MONTH(data)'))
                            ->orderBy('month')
                            ->get();
                    }
                    $result = $trend;
                    break;

                case 'patients':
                    // New patients trend - using created_at from timestamps
                    if ($groupBy === 'day') {
                        $trend = DB::table('pacientes')
                            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as value'))
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->groupBy(DB::raw('DATE(created_at)'))
                            ->orderBy('date')
                            ->get();
                    } else {
                        $trend = DB::table('pacientes')
                            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as value'))
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->groupBy(DB::raw('MONTH(created_at)'))
                            ->orderBy('month')
                            ->get();
                    }
                    $result = $trend;
                    break;
            }

            return response()->json([
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'metric' => $metric,
                'group_by' => $groupBy,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar dados de tendência',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Get distribution data for charts
    Route::get('/distribution', function (Request $request) {
        try {
            // Get distribution type from request
            $type = $request->query('type', 'payment_method'); // payment_method, appointment_status, payment_type

            // Calculate date ranges
            $today = Carbon::today();
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfDay();

            // Prepare the result array
            $result = [];

            // Get distribution data based on the requested type
            switch ($type) {
                case 'payment_method':
                    // Distribution by payment method
                    $result = DB::table('pagamentos')
                        ->select('forma_pagamento as label', DB::raw('COUNT(*) as value'))
                        ->where('status_pagamento', 'pago')
                        ->whereBetween('data_pagamento', [$startDate, $endDate])
                        ->groupBy('forma_pagamento')
                        ->get();
                    break;

                case 'appointment_status':
                    // Distribution by appointment status (calculado dinamicamente)
                    $futureAppointments = DB::table('agendamentos')
                        ->where('data', '>=', $today->toDateString())
                        ->whereBetween('data', [$startDate->toDateString(), $endDate->toDateString()])
                        ->count();

                    $pastAppointments = DB::table('agendamentos')
                        ->where('data', '<', $today->toDateString())
                        ->whereBetween('data', [$startDate->toDateString(), $endDate->toDateString()])
                        ->count();

                    $result = collect([
                        ['label' => 'Realizado', 'value' => $pastAppointments],
                        ['label' => 'Agendado', 'value' => $futureAppointments]
                    ]);
                    break;

                case 'payment_type':
                    // Distribution by payment type
                    $result = DB::table('pagamentos')
                        ->select('tipo as label', DB::raw('COUNT(*) as value'))
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->groupBy('tipo')
                        ->get();
                    break;
            }

            return response()->json([
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'type' => $type,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar dados de distribuição',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});
