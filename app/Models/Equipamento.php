<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipamento extends Model
{
    use HasFactory;

    protected $table = 'equipamentos';

    protected $fillable = [
        'nome',
        'tipo',
        'marca',
        'modelo',
        'numero_serie',
        'status',
        'localizacao',
        'tempo_uso_total',
        'ultima_manutencao',
        'proxima_manutencao',
        'observacoes'
    ];

    protected $casts = [
        'tempo_uso_total' => 'integer',
        'ultima_manutencao' => 'date',
        'proxima_manutencao' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function dadosIot(): HasMany
    {
        return $this->hasMany(DadoIot::class, 'equipamento_id');
    }

    /**
     * Scopes
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    public function scopeEmManutencao($query)
    {
        return $query->where('status', 'manutencao');
    }

    public function scopeInativos($query)
    {
        return $query->where('status', 'inativo');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorLocalizacao($query, $localizacao)
    {
        return $query->where('localizacao', $localizacao);
    }

    public function scopeManutencaoVencida($query)
    {
        return $query->where('proxima_manutencao', '<=', now())
                    ->where('status', '!=', 'inativo');
    }

    /**
     * Accessors
     */
    public function getStatusFormatadoAttribute()
    {
        $statusMap = [
            'ativo' => 'Ativo',
            'manutencao' => 'Em Manutenção',
            'inativo' => 'Inativo'
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    public function getTipoFormatadoAttribute()
    {
        $tipoMap = [
            'cardiovascular' => 'Cardiovascular',
            'eletroterapia' => 'Eletroterapia',
            'mobiliario' => 'Mobiliário',
            'avaliacao' => 'Avaliação',
            'sensor' => 'Sensor/IoT'
        ];

        return $tipoMap[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function getTempoUsoFormatadoAttribute()
    {
        $totalMinutos = $this->tempo_uso_total;
        $horas = floor($totalMinutos / 60);
        $minutos = $totalMinutos % 60;

        return "{$horas}h {$minutos}min";
    }

    public function getProximaManutencaoStatusAttribute()
    {
        if (!$this->proxima_manutencao) {
            return 'não_definida';
        }

        $diasRestantes = now()->diffInDays($this->proxima_manutencao, false);

        if ($diasRestantes < 0) {
            return 'vencida';
        } elseif ($diasRestantes <= 7) {
            return 'proxima';
        } else {
            return 'normal';
        }
    }

    /**
     * Métodos utilitários
     */
    public function adicionarTempoUso($minutos)
    {
        $this->tempo_uso_total += $minutos;
        $this->save();

        return $this->tempo_uso_total;
    }

    public function realizarManutencao($observacoes = null, $proximaManutencao = null)
    {
        $this->status = 'ativo';
        $this->ultima_manutencao = now()->toDateString();

        if ($proximaManutencao) {
            $this->proxima_manutencao = $proximaManutencao;
        } else {
            // Agendar próxima manutenção para 6 meses
            $this->proxima_manutencao = now()->addMonths(6)->toDateString();
        }

        if ($observacoes) {
            $this->observacoes = $observacoes;
        }

        return $this->save();
    }

    public function colocarEmManutencao($observacoes = null)
    {
        $this->status = 'manutencao';

        if ($observacoes) {
            $this->observacoes = $observacoes;
        }

        return $this->save();
    }

    public function getEstatisticasUso($periodo = 30)
    {
        $dataInicio = now()->subDays($periodo);

        return $this->dadosIot()
            ->where('created_at', '>=', $dataInicio)
            ->selectRaw('
                COUNT(*) as total_registros,
                AVG(valor) as valor_medio,
                MIN(valor) as valor_minimo,
                MAX(valor) as valor_maximo
            ')
            ->first();
    }

    /**
     * Métodos estáticos para relatórios
     */
    public static function getResumoStatus()
    {
        return static::selectRaw('
            status,
            COUNT(*) as quantidade
        ')
        ->groupBy('status')
        ->get()
        ->pluck('quantidade', 'status')
        ->toArray();
    }

    public static function getUtilizacaoMediaPorTipo($dias = 30)
    {
        $dataInicio = now()->subDays($dias);

        return static::join('dados_iot', 'equipamentos.id', '=', 'dados_iot.equipamento_id')
            ->where('dados_iot.created_at', '>=', $dataInicio)
            ->selectRaw('
                equipamentos.tipo,
                COUNT(dados_iot.id) as total_usos,
                AVG(equipamentos.tempo_uso_total) as tempo_medio_uso
            ')
            ->groupBy('equipamentos.tipo')
            ->get();
    }
}
