<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    use HasFactory;

    protected $table = 'pagamentos';

    protected $fillable = [
        'agendamento_id',
        'paciente_id',
        'sessao_id',
        'descricao',
        'tipo',
        'valor_consulta',
        'valor_sessao',
        'forma_pagamento',
        'status_pagamento',
        'data_pagamento',
        'observacao'
    ];

    protected $casts = [
        'valor_consulta' => 'decimal:2',
        'valor_sessao' => 'decimal:2',
        'data_pagamento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function sessao(): BelongsTo
    {
        return $this->belongsTo(SessaoFisioterapia::class, 'sessao_id');
    }

    public function scopePagos($query)
    {
        return $query->where('status_pagamento', 'pago');
    }

    public function scopePendentes($query)
    {
        return $query->where('status_pagamento', 'pendente');
    }

    public function getValorTotalAttribute()
    {
        return ($this->valor_consulta ?? 0) + ($this->valor_sessao ?? 0);
    }
}
