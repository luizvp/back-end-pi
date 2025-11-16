<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessaoFisioterapia extends Model
{
    use HasFactory;

    protected $table = 'sessoes_fisioterapia';

    protected $fillable = [
        'agendamento_id',
        'paciente_id',
        'prontuario_id',
        'tratamento_id',
        'data_sessao',
        'hora_inicio',
        'hora_fim',
        'duracao_minutos',
        'status',
        'tipo_sessao',
        'observacoes_sessao',
        'evolucao_paciente',
        'equipamentos_utilizados',
        'exercicios_realizados'
    ];

    protected $casts = [
        'data_sessao' => 'date',
        'hora_inicio' => 'datetime:H:i',
        'hora_fim' => 'datetime:H:i',
        'duracao_minutos' => 'integer',
        'equipamentos_utilizados' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class, 'prontuario_id');
    }

    public function tratamento(): BelongsTo
    {
        return $this->belongsTo(Tratamento::class, 'tratamento_id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'sessao_id');
    }

    public function dadosIot(): HasMany
    {
        return $this->hasMany(DadoIot::class, 'sessao_id');
    }

    /**
     * Scopes
     */
    public function scopeRealizadas($query)
    {
        return $query->where('status', 'realizada');
    }

    public function scopeAgendadas($query)
    {
        return $query->where('status', 'agendada');
    }

    public function scopeCanceladas($query)
    {
        return $query->where('status', 'cancelada');
    }

    public function scopeFaltas($query)
    {
        return $query->where('status', 'faltou');
    }

    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_sessao', [$dataInicio, $dataFim]);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_sessao', $tipo);
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data_sessao', today());
    }

    /**
     * Accessors
     */
    public function getStatusFormatadoAttribute()
    {
        $statusMap = [
            'agendada' => 'Agendada',
            'realizada' => 'Realizada',
            'cancelada' => 'Cancelada',
            'faltou' => 'Faltou'
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    public function getTipoSessaoFormatadoAttribute()
    {
        $tipoMap = [
            'avaliacao' => 'Avaliação',
            'tratamento' => 'Tratamento',
            'reavaliacao' => 'Reavaliação'
        ];

        return $tipoMap[$this->tipo_sessao] ?? $this->tipo_sessao;
    }

    public function getDataHoraFormatadaAttribute()
    {
        $dataFormatada = $this->data_sessao->format('d/m/Y');
        $horaFormatada = $this->hora_inicio ? $this->hora_inicio->format('H:i') : '';

        return $horaFormatada ? "{$dataFormatada} às {$horaFormatada}" : $dataFormatada;
    }

    /**
     * Métodos utilitários
     */
    public function calcularDuracao()
    {
        if ($this->hora_inicio && $this->hora_fim) {
            $inicio = $this->hora_inicio;
            $fim = $this->hora_fim;

            $this->duracao_minutos = $inicio->diffInMinutes($fim);
            $this->save();

            return $this->duracao_minutos;
        }

        return null;
    }

    public function marcarComoRealizada($observacoes = null, $evolucao = null)
    {
        $this->status = 'realizada';

        if ($observacoes) {
            $this->observacoes_sessao = $observacoes;
        }

        if ($evolucao) {
            $this->evolucao_paciente = $evolucao;
        }

        // Atualizar contador no tratamento
        if ($this->tratamento) {
            $this->tratamento->atualizarSessoesRealizadas();
        }

        return $this->save();
    }

    public function adicionarEquipamento($equipamentoId)
    {
        $equipamentos = $this->equipamentos_utilizados ?? [];

        if (!in_array($equipamentoId, $equipamentos)) {
            $equipamentos[] = $equipamentoId;
            $this->equipamentos_utilizados = $equipamentos;
            $this->save();
        }

        return $this;
    }

    public function getEquipamentosNomes()
    {
        if (!$this->equipamentos_utilizados) {
            return [];
        }

        return Equipamento::whereIn('id', $this->equipamentos_utilizados)
            ->pluck('nome')
            ->toArray();
    }
}
