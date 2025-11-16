<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DadoIot extends Model
{
    use HasFactory;

    protected $table = 'dados_iot';
    public $timestamps = false; // Apenas created_at

    protected $fillable = [
        'paciente_id',
        'sessao_id',
        'equipamento_id',
        'tipo_sensor',
        'timestamp',
        'valor',
        'unidade_medida',
        'contexto',
        'observacoes'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'valor' => 'decimal:4',
        'created_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function sessao(): BelongsTo
    {
        return $this->belongsTo(SessaoFisioterapia::class, 'sessao_id');
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class, 'equipamento_id');
    }

    /**
     * Scopes
     */
    public function scopePorTipoSensor($query, $tipo)
    {
        return $query->where('tipo_sensor', $tipo);
    }

    public function scopePorContexto($query, $contexto)
    {
        return $query->where('contexto', $contexto);
    }

    public function scopePorPeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('timestamp', [$inicio, $fim]);
    }

    public function scopeFrequenciaCardiaca($query)
    {
        return $query->where('tipo_sensor', 'frequencia_cardiaca');
    }

    public function scopePressaoArterial($query)
    {
        return $query->where('tipo_sensor', 'pressao');
    }

    public function scopeTemperatura($query)
    {
        return $query->where('tipo_sensor', 'temperatura');
    }

    public function scopeMovimento($query)
    {
        return $query->where('tipo_sensor', 'movimento');
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('timestamp', today());
    }

    public function scopeUltimas24h($query)
    {
        return $query->where('timestamp', '>=', now()->subDay());
    }

    /**
     * Accessors
     */
    public function getTipoSensorFormatadoAttribute()
    {
        $tipos = [
            'frequencia_cardiaca' => 'Frequência Cardíaca',
            'pressao' => 'Pressão Arterial',
            'temperatura' => 'Temperatura',
            'movimento' => 'Movimento',
            'saturacao' => 'Saturação O2'
        ];

        return $tipos[$this->tipo_sensor] ?? ucfirst(str_replace('_', ' ', $this->tipo_sensor));
    }

    public function getContextoFormatadoAttribute()
    {
        $contextos = [
            'repouso' => 'Repouso',
            'durante_exercicio' => 'Durante Exercício',
            'pos_exercicio' => 'Pós Exercício',
            'inicio_sessao' => 'Início da Sessão',
            'fim_sessao' => 'Fim da Sessão'
        ];

        return $contextos[$this->contexto] ?? ucfirst(str_replace('_', ' ', $this->contexto));
    }

    public function getValorFormatadoAttribute()
    {
        return $this->valor . ($this->unidade_medida ? ' ' . $this->unidade_medida : '');
    }

    /**
     * Métodos estáticos para análise
     */
    public static function getEstatisticasPaciente($pacienteId, $tipoSensor, $dias = 30)
    {
        $dataInicio = now()->subDays($dias);

        return static::where('paciente_id', $pacienteId)
            ->where('tipo_sensor', $tipoSensor)
            ->where('timestamp', '>=', $dataInicio)
            ->selectRaw('
                AVG(valor) as media,
                MIN(valor) as minimo,
                MAX(valor) as maximo,
                COUNT(*) as total_leituras
            ')
            ->first();
    }

    public static function getTendenciaPaciente($pacienteId, $tipoSensor, $dias = 7)
    {
        $dataInicio = now()->subDays($dias);

        return static::where('paciente_id', $pacienteId)
            ->where('tipo_sensor', $tipoSensor)
            ->where('timestamp', '>=', $dataInicio)
            ->selectRaw('DATE(timestamp) as data, AVG(valor) as valor_medio')
            ->groupBy('data')
            ->orderBy('data')
            ->get();
    }

    public static function getComparacaoContextos($pacienteId, $tipoSensor, $dias = 30)
    {
        $dataInicio = now()->subDays($dias);

        return static::where('paciente_id', $pacienteId)
            ->where('tipo_sensor', $tipoSensor)
            ->where('timestamp', '>=', $dataInicio)
            ->selectRaw('
                contexto,
                AVG(valor) as valor_medio,
                COUNT(*) as quantidade
            ')
            ->groupBy('contexto')
            ->get();
    }

    public static function getAlertasPaciente($pacienteId, $limites = [])
    {
        $query = static::where('paciente_id', $pacienteId)
            ->where('timestamp', '>=', now()->subDay());

        $alertas = [];

        foreach ($limites as $tipo => $limite) {
            if (isset($limite['min'])) {
                $baixos = $query->where('tipo_sensor', $tipo)
                    ->where('valor', '<', $limite['min'])
                    ->count();

                if ($baixos > 0) {
                    $alertas[] = [
                        'tipo' => $tipo,
                        'nivel' => 'baixo',
                        'quantidade' => $baixos,
                        'limite' => $limite['min']
                    ];
                }
            }

            if (isset($limite['max'])) {
                $altos = $query->where('tipo_sensor', $tipo)
                    ->where('valor', '>', $limite['max'])
                    ->count();

                if ($altos > 0) {
                    $alertas[] = [
                        'tipo' => $tipo,
                        'nivel' => 'alto',
                        'quantidade' => $altos,
                        'limite' => $limite['max']
                    ];
                }
            }
        }

        return $alertas;
    }
}
