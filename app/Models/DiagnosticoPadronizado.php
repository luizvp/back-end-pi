<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticoPadronizado extends Model
{
    use HasFactory;

    protected $table = 'diagnosticos_padronizados';

    protected $fillable = [
        'codigo_cid',
        'descricao',
        'categoria',
        'subcategoria'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relacionamento: Um diagnóstico pode estar em vários prontuários
     */
    public function prontuarios(): HasMany
    {
        return $this->hasMany(Prontuario::class, 'diagnostico_cid_id');
    }

    /**
     * Scope para filtrar por categoria
     */
    public function scopeByCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope para buscar por código CID
     */
    public function scopeByCodigo($query, $codigo)
    {
        return $query->where('codigo_cid', 'like', "%{$codigo}%");
    }

    /**
     * Accessor para formatar código + descrição
     */
    public function getCodigoComDescricaoAttribute()
    {
        return $this->codigo_cid . ' - ' . $this->descricao;
    }
}
