<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tratamento extends Model
{
    use HasFactory;

    protected $table = 'tratamentos';

    protected $fillable = [
        'paciente_id',
        'prontuario_id',
        'data_inicio',
        'data_fim_prevista',
        'data_alta_real',
        'status',
        'motivo_alta',
        'total_sessoes_previstas',
        'total_sessoes_realizadas',
        'objetivo_tratamento',
        'observacoes_finais'
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim_prevista' => 'date',
        'data_alta_real' => 'date',
        'total_sessoes_previstas' => 'integer',
        'total_sessoes_realizadas' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class, 'prontuario_id');
    }

    public function sessoesFisioterapia(): HasMany
    {
        return $this->hasMany(SessaoFisioterapia::class, 'tratamento_id');
    }

    /**
     * Scopes
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeConcluidos($query)
    {
        return $query->where('status', 'concluido');
    }

    public function scopeEmAndamento($query)
    {
        return $query->whereIn('status', ['ativo', 'pausado']);
    }

    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_inicio', [$dataInicio, $dataFim]);
    }

    /**
     * Accessors
     */
    public function getDuracaoTratamentoAttribute()
    {
        if ($this->data_alta_real) {
            return $this->data_inicio->diffInDays($this->data_alta_real);
        }

        return $this->data_inicio->diffInDays(now());
    }

    public function getProgressoAttribute()
    {
        if ($this->total_sessoes_previstas > 0) {
            return ($this->total_sessoes_realizadas / $this->total_sessoes_previstas) * 100;
        }

        return 0;
    }

    public function getStatusFormatadoAttribute()
    {
        $statusMap = [
            'ativo' => 'Ativo',
            'concluido' => 'Concluído',
            'interrompido' => 'Interrompido',
            'pausado' => 'Pausado'
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    /**
     * Métodos de análise
     */
    public function atualizarSessoesRealizadas()
    {
        $this->total_sessoes_realizadas = $this->sessoesFisioterapia()
            ->where('status', 'realizada')
            ->count();

        $this->save();

        return $this->total_sessoes_realizadas;
    }

    public function calcularTempoMedioSessoes()
    {
        return $this->sessoesFisioterapia()
            ->where('status', 'realizada')
            ->whereNotNull('duracao_minutos')
            ->avg('duracao_minutos');
    }

    public function finalizarTratamento($motivo = null)
    {
        $this->status = 'concluido';
        $this->data_alta_real = now()->toDateString();
        $this->motivo_alta = $motivo;
        $this->atualizarSessoesRealizadas();

        return $this->save();
    }
}
