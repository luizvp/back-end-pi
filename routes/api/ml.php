<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\MLService;

Route::prefix('ml')->group(function () {
    // Health check da API ML
    Route::get('/health', function () {
        $mlService = new MLService();
        $health = $mlService->checkHealth();

        return response()->json([
            'ml_api_status' => $health ? 'online' : 'offline',
            'ml_api_data' => $health,
            'timestamp' => now()
        ]);
    });

    // Informações do modelo ML
    Route::get('/model-info', function () {
        $mlService = new MLService();
        $modelInfo = $mlService->getModelInfo();

        return response()->json([
            'model_info' => $modelInfo,
            'timestamp' => now()
        ]);
    });

    // Predizer falta para um paciente específico
    Route::post('/predict-falta/{pacienteId}', function (Request $request, $pacienteId) {
        try {
            $mlService = new MLService();
            $dataAgendamento = $request->input('data_agendamento');

            $resultado = $mlService->predictFalta($pacienteId, $dataAgendamento);

            return response()->json([
                'success' => true,
                'prediction' => $resultado,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro na predição ML',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Predizer faltas para múltiplos pacientes
    Route::post('/predict-batch', function (Request $request) {
        try {
            $mlService = new MLService();
            $pacienteIds = $request->input('paciente_ids', []);
            $dataAgendamento = $request->input('data_agendamento');

            if (empty($pacienteIds)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Lista de pacientes vazia'
                ], 400);
            }

            $resultados = $mlService->predictBatch($pacienteIds, $dataAgendamento);

            return response()->json([
                'success' => true,
                'predictions' => $resultados,
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro na predição batch ML',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Analisar agendamentos de amanhã
    Route::get('/analyze-tomorrow', function () {
        try {
            $mlService = new MLService();
            $previsoes = $mlService->analisarAgendamentosAmanha();

            return response()->json([
                'success' => true,
                'date' => now()->addDay()->toDateString(),
                'total_agendamentos' => count($previsoes),
                'predictions' => $previsoes,
                'high_risk_count' => collect($previsoes)->where('previsao.nivel_risco', 'alto')->count(),
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro na análise de agendamentos',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Obter pacientes com alto risco de falta
    Route::get('/high-risk-patients', function (Request $request) {
        try {
            $mlService = new MLService();
            $limite = $request->input('limite', 0.6);
            $pacientes = $mlService->getPacientesAltoRisco($limite);

            return response()->json([
                'success' => true,
                'limit_threshold' => $limite,
                'total_high_risk' => $pacientes->count(),
                'patients' => $pacientes->map(function ($previsao) {
                    return [
                        'paciente_id' => $previsao->paciente_id,
                        'paciente_nome' => $previsao->paciente->nome ?? 'N/A',
                        'probabilidade_falta' => $previsao->valor_previsao,
                        'confianca' => $previsao->confianca,
                        'data_previsao' => $previsao->data_previsao,
                        'acao_recomendada' => $previsao->acao_recomendada,
                        'nivel_risco' => $previsao->nivel_risco
                    ];
                }),
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao obter pacientes de alto risco',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Obter estatísticas das previsões ML
    Route::get('/statistics', function (Request $request) {
        try {
            $dias = $request->input('days', 30);
            $dataInicio = now()->subDays($dias);

            // Estatísticas gerais
            $totalPrevisoes = \App\Models\PrevisaoMl::where('data_calculo', '>=', $dataInicio)->count();
            $previsoesPorTipo = \App\Models\PrevisaoMl::where('data_calculo', '>=', $dataInicio)
                ->selectRaw('tipo_previsao, COUNT(*) as total')
                ->groupBy('tipo_previsao')
                ->get();

            // Distribuição por nível de risco
            $previsoesFaltas = \App\Models\PrevisaoMl::where('data_calculo', '>=', $dataInicio)
                ->where('tipo_previsao', 'probabilidade_falta')
                ->get();

            $distribuicaoRisco = [
                'baixo' => $previsoesFaltas->where('valor_previsao', '<', 0.3)->count(),
                'medio' => $previsoesFaltas->whereBetween('valor_previsao', [0.3, 0.6])->count(),
                'alto' => $previsoesFaltas->where('valor_previsao', '>=', 0.6)->count()
            ];

            // Acurácia aproximada (baseada em execuções)
            $acoesExecutadas = \App\Models\PrevisaoMl::where('data_calculo', '>=', $dataInicio)
                ->where('executada', true)
                ->count();

            return response()->json([
                'success' => true,
                'period_days' => $dias,
                'statistics' => [
                    'total_predictions' => $totalPrevisoes,
                    'predictions_by_type' => $previsoesPorTipo,
                    'risk_distribution' => $distribuicaoRisco,
                    'actions_executed' => $acoesExecutadas,
                    'execution_rate' => $totalPrevisoes > 0 ? round(($acoesExecutadas / $totalPrevisoes) * 100, 1) : 0
                ],
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao obter estatísticas ML',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Marcar ação como executada
    Route::put('/mark-executed/{previsaoId}', function (Request $request, $previsaoId) {
        try {
            $previsao = \App\Models\PrevisaoMl::find($previsaoId);

            if (!$previsao) {
                return response()->json([
                    'success' => false,
                    'error' => 'Previsão não encontrada'
                ], 404);
            }

            $observacoes = $request->input('observacoes', 'Ação executada manualmente');
            $previsao->marcarComoExecutada($observacoes);

            return response()->json([
                'success' => true,
                'message' => 'Ação marcada como executada',
                'previsao' => $previsao
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao marcar ação como executada',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Dashboard ML resumido
    Route::get('/dashboard', function () {
        try {
            $mlService = new MLService();

            // Status da API ML
            $apiStatus = $mlService->checkHealth();

            // Pacientes de alto risco hoje
            $altoRiscoHoje = $mlService->getPacientesAltoRisco(0.6);

            // Previsões dos próximos agendamentos
            $previsoes = $mlService->analisarAgendamentosAmanha();

            // Estatísticas rápidas
            $stats = [
                'total_previsoes_mes' => \App\Models\PrevisaoMl::where('data_calculo', '>=', now()->startOfMonth())->count(),
                'alto_risco_count' => $altoRiscoHoje->count(),
                'agendamentos_amanha' => count($previsoes),
                'acoes_pendentes' => \App\Models\PrevisaoMl::where('executada', false)
                    ->where('data_previsao', '>=', now())
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'ml_api_status' => $apiStatus ? 'online' : 'offline',
                'statistics' => $stats,
                'high_risk_patients' => $altoRiscoHoje->take(5), // Top 5
                'tomorrow_predictions' => collect($previsoes)->take(10), // Top 10
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro no dashboard ML',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // ===== NOVAS ROTAS DE PREVISÃO DE DEMANDA =====

    // Predizer demanda para um período
    Route::post('/predict-demand', function (Request $request) {
        try {
            $mlService = new MLService();

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $diagnostico = $request->input('diagnostico');

            if (!$startDate || !$endDate) {
                return response()->json([
                    'success' => false,
                    'error' => 'start_date e end_date são obrigatórios'
                ], 400);
            }

            $resultado = $mlService->predictDemand($startDate, $endDate, $diagnostico);

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro na predição de demanda',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Obter tendências de demanda
    Route::get('/demand-trends', function (Request $request) {
        try {
            $mlService = new MLService();
            $periodDays = $request->input('days', 90);

            $resultado = $mlService->getDemandTrends($periodDays);

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao obter tendências de demanda',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Obter demanda por diagnóstico
    Route::get('/demand-by-diagnosis', function (Request $request) {
        try {
            $mlService = new MLService();
            $periodDays = $request->input('days', 30);

            $resultado = $mlService->getDemandByDiagnosis($periodDays);

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao obter demanda por diagnóstico',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Obter análise de sazonalidade
    Route::get('/seasonal-analysis', function () {
        try {
            $mlService = new MLService();

            $resultado = $mlService->getSeasonalAnalysis();

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro na análise sazonal',
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Dashboard específico de demanda
    Route::get('/demand-dashboard', function (Request $request) {
        try {
            $mlService = new MLService();
            $periodDays = $request->input('days', 30);

            // Previsão para próximos 30 dias
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addDays($periodDays)->format('Y-m-d');

            $demandPrediction = $mlService->predictDemand($startDate, $endDate);
            $demandTrends = $mlService->getDemandTrends($periodDays);
            $demandByDiagnosis = $mlService->getDemandByDiagnosis($periodDays);
            $seasonalAnalysis = $mlService->getSeasonalAnalysis();

            return response()->json([
                'success' => true,
                'period_days' => $periodDays,
                'demand_prediction' => $demandPrediction,
                'demand_trends' => $demandTrends,
                'demand_by_diagnosis' => $demandByDiagnosis,
                'seasonal_analysis' => $seasonalAnalysis,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro no dashboard de demanda',
                'message' => $e->getMessage()
            ], 500);
        }
    });
});
