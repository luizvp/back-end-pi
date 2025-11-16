<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agendamento extends Model
{
    use HasFactory;

    protected $table = 'agendamentos';

    protected $fillable = [
        'id_paciente',
        'data',
        'hora',
        'status',
        'observacoes',
        'compareceu',
        'alterado_manualmente',
        'data_status_alterado',
        'alterado_por'
    ];

    protected $casts = [
        'data' => 'date',
        'hora' => 'datetime:H:i',
        'compareceu' => 'boolean',
        'alterado_manualmente' => 'boolean',
        'data_status_alterado' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'agendamento_id');
    }

    public function sessaoFisioterapia(): HasOne
    {
        return $this->hasOne(SessaoFisioterapia::class, 'agendamento_id');
    }

    /**
     * Scopes
     */
    public function scopeAgendados($query)
    {
        return $query->where('status', 'agendado');
    }


    public function scopeRealizados($query)
    {
        return $query->where('status', 'realizado');
    }

    public function scopeFaltou($query)
    {
        return $query->where('status', 'faltou');
    }

    public function scopeCancelados($query)
    {
        return $query->where('status', 'cancelado');
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data', today());
    }

    public function scopeAmanha($query)
    {
        return $query->whereDate('data', tomorrow());
    }

    public function scopeEstaSemanaa($query)
    {
        return $query->whereBetween('data', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data', [$dataInicio, $dataFim]);
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'agendado');
    }

    public function scopeFinalizados($query)
    {
        return $query->whereIn('status', ['realizado', 'faltou', 'cancelado']);
    }

    /**
     * Accessors
     */
    public function getStatusFormatadoAttribute()
    {
        $statusMap = [
            'agendado' => 'Agendado',
            'confirmado' => 'Confirmado',
            'realizado' => 'Realizado',
            'faltou' => 'Faltou',
            'cancelado' => 'Cancelado'
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    public function getDataHoraFormatadaAttribute()
    {
        $dataFormatada = $this->data->format('d/m/Y');
        $horaFormatada = $this->hora ? $this->hora->format('H:i') : '';

        return $horaFormatada ? "{$dataFormatada} às {$horaFormatada}" : $dataFormatada;
    }

    public function getStatusCorAttribute()
    {
        $cores = [
            'agendado' => 'warning',
            'confirmado' => 'info',
            'realizado' => 'success',
            'faltou' => 'error',
            'cancelado' => 'default'
        ];

        return $cores[$this->status] ?? 'default';
    }

    public function getTempoRestanteAttribute()
    {
        if ($this->data < now()->toDateString()) {
            return 'Vencido';
        }

        $dataHoraAgendamento = $this->data->setTimeFromTimeString($this->hora ? $this->hora->format('H:i') : '00:00');
        $diferenca = now()->diffInMinutes($dataHoraAgendamento, false);

        if ($diferenca < 0) {
            return 'Vencido';
        } elseif ($diferenca < 60) {
            return "{$diferenca} minutos";
        } elseif ($diferenca < 1440) { // 24 horas
            $horas = floor($diferenca / 60);
            return "{$horas}h";
        } else {
            $dias = floor($diferenca / 1440);
            return "{$dias} dias";
        }
    }

    /**
     * Métodos utilitários com controle de alteração manual
     */
    public function alterarStatusManualmente($novoStatus, $observacoes = null, $usuario = null)
    {
        $this->status = $novoStatus;
        $this->alterado_manualmente = true;
        $this->data_status_alterado = now();
        $this->alterado_por = $usuario;

        if ($observacoes) {
            $this->observacoes = $observacoes;
        }

        // Definir comparecimento baseado no status
        switch ($novoStatus) {
            case 'realizado':
                $this->compareceu = true;
                break;
            case 'faltou':
            case 'cancelado':
                $this->compareceu = false;
                break;
            default:
                $this->compareceu = null;
        }

        return $this->save();
    }


    public function marcarComoRealizado($observacoes = null, $usuario = null)
    {
        $resultado = $this->alterarStatusManualmente('realizado', $observacoes, $usuario);

        // Criar sessão de fisioterapia automaticamente se não existir
        if ($resultado && !$this->sessaoFisioterapia) {
            $this->criarSessaoFisioterapia();
        }

        return $resultado;
    }

    public function marcarComoFaltou($observacoes = null, $usuario = null)
    {
        return $this->alterarStatusManualmente('faltou', $observacoes, $usuario);
    }

    public function cancelar($motivo = null, $usuario = null)
    {
        return $this->alterarStatusManualmente('cancelado', $motivo, $usuario);
    }

    public function marcarComoRealizadoAutomaticamente()
    {
        // Só marca como realizado automaticamente se não foi alterado manualmente
        if (!$this->alterado_manualmente && $this->status === 'agendado') {
            $this->status = 'realizado';
            $this->compareceu = true;
            $this->data_status_alterado = now();

            return $this->save();
        }

        return false;
    }

    public function reagendar($novaData, $novaHora = null, $observacoes = null)
    {
        $this->data = $novaData;

        if ($novaHora) {
            $this->hora = $novaHora;
        }

        $this->status = 'agendado';
        $this->compareceu = null;

        if ($observacoes) {
            $this->observacoes = $observacoes;
        }

        return $this->save();
    }

    public function criarSessaoFisioterapia($tipo = 'tratamento')
    {
        // Buscar o prontuário mais recente do paciente
        $prontuario = $this->paciente->prontuarios()->latest('data_criacao')->first();
        $tratamento = $prontuario ? $prontuario->tratamentos()->where('status', 'ativo')->first() : null;

        return SessaoFisioterapia::create([
            'agendamento_id' => $this->id,
            'paciente_id' => $this->id_paciente,
            'prontuario_id' => $prontuario?->id,
            'tratamento_id' => $tratamento?->id,
            'data_sessao' => $this->data,
            'hora_inicio' => $this->hora,
            'status' => $this->status === 'realizado' ? 'realizada' : 'agendada',
            'tipo_sessao' => $tipo
        ]);
    }

    /**
     * Métodos estáticos para relatórios
     */
    public static function getTaxaComparecimento($dias = 30)
    {
        $dataInicio = now()->subDays($dias);

        $agendamentos = static::where('data', '>=', $dataInicio)
            ->where('data', '<=', now())
            ->whereIn('status', ['realizado', 'faltou'])
            ->count();

        $comparecimentos = static::where('data', '>=', $dataInicio)
            ->where('data', '<=', now())
            ->where('status', 'realizado')
            ->count();

        return $agendamentos > 0 ? ($comparecimentos / $agendamentos) * 100 : 0;
    }

    public static function getDistribuicaoStatus($dias = 30)
    {
        $dataInicio = now()->subDays($dias);

        return static::where('data', '>=', $dataInicio)
            ->selectRaw('
                status,
                COUNT(*) as quantidade
            ')
            ->groupBy('status')
            ->get()
            ->pluck('quantidade', 'status')
            ->toArray();
    }

    public static function getEstatisticasPorPaciente($pacienteId, $dias = 90)
    {
        $dataInicio = now()->subDays($dias);

        return static::where('id_paciente', $pacienteId)
            ->where('data', '>=', $dataInicio)
            ->selectRaw('
                COUNT(*) as total_agendamentos,
                SUM(CASE WHEN status = "realizado" THEN 1 ELSE 0 END) as comparecimentos,
                SUM(CASE WHEN status = "faltou" THEN 1 ELSE 0 END) as faltas,
                SUM(CASE WHEN status = "cancelado" THEN 1 ELSE 0 END) as cancelamentos
            ')
            ->first();
    }

    public static function getProximosAgendamentos($limite = 10)
    {
        return static::with(['paciente'])
            ->where('data', '>=', now())
            ->where('status', 'agendado')
            ->orderBy('data')
            ->orderBy('hora')
            ->limit($limite)
            ->get();
    }

    public static function getAgendamentosAtrasados()
    {
        return static::with(['paciente'])
            ->where('data', '<', now())
            ->where('status', 'agendado')
            ->orderBy('data', 'desc')
            ->get();
    }
}
