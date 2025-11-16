<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $fillable = [
        'nome',
        'data_nascimento',
        'telefone',
        'sexo',
        'cidade',
        'bairro',
        'profissao',
        'endereco_residencial',
        'endereco_comercial',
        'naturalidade',
        'estado_civil',
        'cpf',
        'email'
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamentos
     */
    public function prontuarios(): HasMany
    {
        return $this->hasMany(Prontuario::class, 'id_paciente');
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'id_paciente');
    }

    public function tratamentos(): HasMany
    {
        return $this->hasMany(Tratamento::class, 'paciente_id');
    }

    public function sessoesFisioterapia(): HasMany
    {
        return $this->hasMany(SessaoFisioterapia::class, 'paciente_id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'paciente_id');
    }

    public function dadosIot(): HasMany
    {
        return $this->hasMany(DadoIot::class, 'paciente_id');
    }

    public function previsoesML(): HasMany
    {
        return $this->hasMany(PrevisaoMl::class, 'paciente_id');
    }

    /**
     * Scopes
     */
    public function scopeAtivos($query)
    {
        return $query->whereHas('tratamentos', function($q) {
            $q->where('status', 'ativo');
        });
    }

    public function scopeByCpf($query, $cpf)
    {
        return $query->where('cpf', $cpf);
    }

    public function scopeByNome($query, $nome)
    {
        return $query->where('nome', 'like', "%{$nome}%");
    }

    /**
     * Accessors
     */
    public function getIdadeAttribute()
    {
        return $this->data_nascimento ? $this->data_nascimento->diffInYears(now()) : null;
    }

    public function getEnderecoCompletoAttribute()
    {
        return trim($this->endereco_residencial . ', ' . $this->bairro . ', ' . $this->cidade);
    }

    /**
     * Métodos de análise
     */
    public function getTotalSessoes()
    {
        return $this->sessoesFisioterapia()->where('status', 'realizada')->count();
    }

    public function getReceitaTotal()
    {
        return $this->pagamentos()->where('status_pagamento', 'pago')->sum('valor_consulta');
    }

    public function getTaxaFaltas()
    {
        $totalAgendamentos = $this->agendamentos()->count();
        $faltas = $this->agendamentos()->where('status', 'faltou')->count();

        return $totalAgendamentos > 0 ? ($faltas / $totalAgendamentos) * 100 : 0;
    }
}
