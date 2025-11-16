<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prontuario extends Model
{
    use HasFactory;

    protected $table = 'prontuarios';

    protected $fillable = [
        'id_paciente',
        'diagnostico_cid_id',
        'historia_clinica',
        'queixa_principal',
        'habitos_vida',
        'hma',
        'hmp',
        'antecedentes_pessoais',
        'antecedentes_familiares',
        'tratamentos_realizados',
        'deambulando',
        'internado',
        'deambulando_apoio',
        'orientado',
        'cadeira_rodas',
        'exames_complementares',
        'usa_medicamentos',
        'realizou_cirurgia',
        'inspecao_palpacao',
        'semiotica',
        'testes_especificos',
        'avaliacao_dor',
        'objetivos_tratamento',
        'recursos_terapeuticos',
        'plano_tratamento',
        'diagnostico_clinico',
        'diagnostico_fisioterapeutico',
        'status_tratamento',
        'data_alta',
        'motivo_alta'
    ];

    protected $casts = [
        'deambulando' => 'boolean',
        'internado' => 'boolean',
        'deambulando_apoio' => 'boolean',
        'orientado' => 'boolean',
        'cadeira_rodas' => 'boolean',
        'data_criacao' => 'datetime',
        'data_alta' => 'date',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function diagnosticoPadronizado(): BelongsTo
    {
        return $this->belongsTo(DiagnosticoPadronizado::class, 'diagnostico_cid_id');
    }

    public function evolucoes(): HasMany
    {
        return $this->hasMany(EvolucaoProntuario::class, 'id_prontuario');
    }

    public function tratamentos(): HasMany
    {
        return $this->hasMany(Tratamento::class, 'prontuario_id');
    }

    public function sessoesFisioterapia(): HasMany
    {
        return $this->hasMany(SessaoFisioterapia::class, 'prontuario_id');
    }

    /**
     * Scopes
     */
    public function scopeAtivos($query)
    {
        return $query->where('status_tratamento', 'ativo');
    }

    public function scopeConcluidos($query)
    {
        return $query->where('status_tratamento', 'concluido');
    }

    public function scopeInterrompidos($query)
    {
        return $query->where('status_tratamento', 'interrompido');
    }

    public function scopePausados($query)
    {
        return $query->where('status_tratamento', 'pausado');
    }

    public function scopePorDiagnostico($query, $diagnosticoId)
    {
        return $query->where('diagnostico_cid_id', $diagnosticoId);
    }

    public function scopePorCategoriaDiagnostico($query, $categoria)
    {
        return $query->whereHas('diagnosticoPadronizado', function($q) use ($categoria) {
            $q->where('categoria', $categoria);
        });
    }

    /**
     * Accessors
     */
    public function getStatusTratamentoFormatadoAttribute()
    {
        $statusMap = [
            'ativo' => 'Ativo',
            'concluido' => 'Concluído',
            'interrompido' => 'Interrompido',
            'pausado' => 'Pausado'
        ];

        return $statusMap[$this->status_tratamento] ?? $this->status_tratamento;
    }

    public function getDiagnosticoCompletoAttribute()
    {
        if ($this->diagnosticoPadronizado) {
            return $this->diagnosticoPadronizado->codigo_com_descricao;
        }

        return $this->diagnostico_clinico ?? 'Não definido';
    }

    public function getTempoTratamentoAttribute()
    {
        if ($this->data_alta) {
            return $this->data_criacao->diffInDays($this->data_alta);
        }

        return $this->data_criacao->diffInDays(now());
    }

    /**
     * Métodos utilitários
     */
    public function finalizarTratamento($motivo = null)
    {
        $this->status_tratamento = 'concluido';
        $this->data_alta = now()->toDateString();

        if ($motivo) {
            $this->motivo_alta = $motivo;
        }

        // Finalizar tratamentos ativos relacionados
        $this->tratamentos()->where('status', 'ativo')->update([
            'status' => 'concluido',
            'data_alta_real' => now()->toDateString(),
            'motivo_alta' => $motivo
        ]);

        return $this->save();
    }

    public function pausarTratamento($motivo = null)
    {
        $this->status_tratamento = 'pausado';

        if ($motivo) {
            $this->motivo_alta = $motivo;
        }

        // Pausar tratamentos ativos relacionados
        $this->tratamentos()->where('status', 'ativo')->update([
            'status' => 'pausado'
        ]);

        return $this->save();
    }

    public function reativarTratamento()
    {
        $this->status_tratamento = 'ativo';
        $this->data_alta = null;
        $this->motivo_alta = null;

        // Reativar tratamentos pausados
        $this->tratamentos()->where('status', 'pausado')->update([
            'status' => 'ativo'
        ]);

        return $this->save();
    }

    public function adicionarEvolucao($descricao, $dataAtendimento = null)
    {
        return $this->evolucoes()->create([
            'data_atendimento' => $dataAtendimento ?? now()->toDateString(),
            'descricao_evolucao' => $descricao
        ]);
    }

    public function getTotalSessoes()
    {
        return $this->sessoesFisioterapia()->count();
    }

    public function getSessoesRealizadas()
    {
        return $this->sessoesFisioterapia()->where('status', 'realizada')->count();
    }

    public function getTaxaComparecimento()
    {
        $total = $this->sessoesFisioterapia()->count();
        $realizadas = $this->getSessoesRealizadas();

        return $total > 0 ? ($realizadas / $total) * 100 : 0;
    }

    /**
     * Métodos estáticos para relatórios
     */
    public static function getEstatisticasPorDiagnostico($dias = 30)
    {
        $dataInicio = now()->subDays($dias);

        return static::with('diagnosticoPadronizado')
            ->where('data_criacao', '>=', $dataInicio)
            ->selectRaw('
                diagnostico_cid_id,
                COUNT(*) as total_casos,
                AVG(DATEDIFF(COALESCE(data_alta, NOW()), data_criacao)) as duracao_media,
                SUM(CASE WHEN status_tratamento = "concluido" THEN 1 ELSE 0 END) as concluidos
            ')
            ->groupBy('diagnostico_cid_id')
            ->orderBy('total_casos', 'desc')
            ->get();
    }

    public static function getDistribuicaoStatus()
    {
        return static::selectRaw('
            status_tratamento,
            COUNT(*) as quantidade
        ')
        ->groupBy('status_tratamento')
        ->get()
        ->pluck('quantidade', 'status_tratamento')
        ->toArray();
    }
}
