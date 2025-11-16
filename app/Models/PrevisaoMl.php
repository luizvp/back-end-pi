<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrevisaoMl extends Model
{
    use HasFactory;

    protected $table = 'previsoes_ml';
    public $timestamps = false; // Apenas data_calculo

    protected $fillable = [
        'paciente_id',
        'tipo_previsao',
        'valor_previsao',
        'confianca',
        'data_previsao',
        'modelo_utilizado',
        'parametros_entrada',
        'acao_recomendada',
        'executada'
    ];

    protected $casts = [
        'valor_previsao' => 'decimal:4',
        'confianca' => 'decimal:4',
        'data_previsao' => 'date',
        'data_calculo' => 'datetime',
        'parametros_entrada' => 'array',
        'executada' => 'boolean'
    ];

    /**
     * Relacionamentos
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Scopes
     */
    public function scopeProbabilidadeFalta($query)
    {
        return $query->where('tipo_previsao', 'probabilidade_falta');
    }

    public function scopeDemandaPeriodo($query)
    {
        return $query->where('tipo_previsao', 'demanda_periodo');
    }

    public function scopeSucessoTratamento($query)
    {
        return $query->where('tipo_previsao', 'sucesso_tratamento');
    }

    public function scopeNaoExecutadas($query)
    {
        return $query->where('executada', false);
    }

    public function scopeExecutadas($query)
    {
        return $query->where('executada', true);
    }

    public function scopeAltaConfianca($query, $limite = 0.8)
    {
        return $query->where('confianca', '>=', $limite);
    }

    public function scopeBaixaConfianca($query, $limite = 0.6)
    {
        return $query->where('confianca', '<', $limite);
    }

    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_previsao', [$dataInicio, $dataFim]);
    }

    public function scopeRecentes($query, $dias = 7)
    {
        return $query->where('data_calculo', '>=', now()->subDays($dias));
    }

    /**
     * Accessors
     */
    public function getTipoPrevisaoFormatadoAttribute()
    {
        $tipos = [
            'probabilidade_falta' => 'Probabilidade de Falta',
            'demanda_periodo' => 'Previsão de Demanda',
            'sucesso_tratamento' => 'Sucesso do Tratamento'
        ];

        return $tipos[$this->tipo_previsao] ?? $this->tipo_previsao;
    }

    public function getValorPrevisaoPercentualAttribute()
    {
        return round($this->valor_previsao * 100, 1) . '%';
    }

    public function getConfiancaPercentualAttribute()
    {
        return $this->confianca ? round($this->confianca * 100, 1) . '%' : 'N/A';
    }

    public function getNivelRiscoAttribute()
    {
        if ($this->tipo_previsao === 'probabilidade_falta') {
            if ($this->valor_previsao >= 0.8) return 'alto';
            if ($this->valor_previsao >= 0.5) return 'medio';
            return 'baixo';
        }

        if ($this->tipo_previsao === 'sucesso_tratamento') {
            if ($this->valor_previsao >= 0.8) return 'alto';
            if ($this->valor_previsao >= 0.6) return 'medio';
            return 'baixo';
        }

        return 'neutro';
    }

    public function getNivelConfiancaAttribute()
    {
        if (!$this->confianca) return 'desconhecida';

        if ($this->confianca >= 0.9) return 'muito_alta';
        if ($this->confianca >= 0.8) return 'alta';
        if ($this->confianca >= 0.6) return 'media';
        if ($this->confianca >= 0.4) return 'baixa';
        return 'muito_baixa';
    }

    public function getStatusRecomendacaoAttribute()
    {
        if ($this->executada) return 'executada';
        if (!$this->acao_recomendada) return 'sem_acao';

        // Verificar se precisa de ação urgente
        if ($this->tipo_previsao === 'probabilidade_falta' && $this->valor_previsao >= 0.7) {
            return 'urgente';
        }

        return 'pendente';
    }

    /**
     * Métodos utilitários
     */
    public function marcarComoExecutada($observacoes = null)
    {
        $this->executada = true;

        if ($observacoes) {
            $parametros = $this->parametros_entrada ?? [];
            $parametros['observacoes_execucao'] = $observacoes;
            $this->parametros_entrada = $parametros;
        }

        return $this->save();
    }

    public function atualizarAcaoRecomendada($acao)
    {
        $this->acao_recomendada = $acao;
        return $this->save();
    }

    /**
     * Métodos estáticos para análise
     */
    public static function getResumoPrevisoes($dias = 30)
    {
        $dataInicio = now()->subDays($dias);

        return static::where('data_calculo', '>=', $dataInicio)
            ->selectRaw('
                tipo_previsao,
                COUNT(*) as total,
                AVG(valor_previsao) as valor_medio,
                AVG(confianca) as confianca_media,
                SUM(CASE WHEN executada = 1 THEN 1 ELSE 0 END) as executadas
            ')
            ->groupBy('tipo_previsao')
            ->get();
    }

    public static function getPacientesAltoRisco($tipo = 'probabilidade_falta', $limite = 0.7)
    {
        return static::with('paciente')
            ->where('tipo_previsao', $tipo)
            ->where('valor_previsao', '>=', $limite)
            ->where('data_previsao', '>=', now())
            ->where('executada', false)
            ->orderBy('valor_previsao', 'desc')
            ->get();
    }

    public static function getEficaciaModelo($tipoPrevisao, $dias = 90)
    {
        $dataInicio = now()->subDays($dias);

        // Esta função deveria comparar previsões com resultados reais
        // Por enquanto, retorna estatísticas básicas
        return static::where('tipo_previsao', $tipoPrevisao)
            ->where('data_calculo', '>=', $dataInicio)
            ->selectRaw('
                COUNT(*) as total_previsoes,
                AVG(confianca) as confianca_media,
                COUNT(CASE WHEN valor_previsao >= 0.7 THEN 1 END) as previsoes_alto_risco,
                COUNT(CASE WHEN executada = 1 THEN 1 END) as acoes_executadas
            ')
            ->first();
    }

    public static function gerarAlerta($pacienteId, $tipoPrevisao, $valorPrevisao, $parametros = [])
    {
        // Verificar se já existe previsão recente similar
        $previsaoExistente = static::where('paciente_id', $pacienteId)
            ->where('tipo_previsao', $tipoPrevisao)
            ->where('data_calculo', '>=', now()->subHours(24))
            ->first();

        if ($previsaoExistente) {
            return $previsaoExistente;
        }

        // Gerar ação recomendada baseada no tipo e valor
        $acao = static::gerarAcaoRecomendada($tipoPrevisao, $valorPrevisao);

        return static::create([
            'paciente_id' => $pacienteId,
            'tipo_previsao' => $tipoPrevisao,
            'valor_previsao' => $valorPrevisao,
            'data_previsao' => now()->addDays(1),
            'parametros_entrada' => $parametros,
            'acao_recomendada' => $acao,
            'data_calculo' => now()
        ]);
    }

    private static function gerarAcaoRecomendada($tipo, $valor)
    {
        switch ($tipo) {
            case 'probabilidade_falta':
                if ($valor >= 0.8) return 'Enviar lembrete urgente via WhatsApp';
                if ($valor >= 0.6) return 'Enviar lembrete por SMS';
                if ($valor >= 0.4) return 'Ligar para confirmar presença';
                return 'Monitorar paciente';

            case 'sucesso_tratamento':
                if ($valor < 0.4) return 'Reavaliar plano de tratamento';
                if ($valor < 0.6) return 'Intensificar acompanhamento';
                return 'Manter tratamento atual';

            case 'demanda_periodo':
                if ($valor >= 0.8) return 'Considerar aumento de capacidade';
                if ($valor <= 0.3) return 'Revisar estratégia de marketing';
                return 'Monitorar demanda';

            default:
                return 'Analisar resultado';
        }
    }
}
