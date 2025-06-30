<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoSituacao extends Model
{
    use HasFactory;

    protected $table = 'historico_situacoes';

    protected $fillable = [
        'id_processo',
        'situacao_anterior',
        'situacao_atual',
        'data_mudanca',
        'id_empresa',
        'visto',
        'visto_em',
    ];

    protected $casts = [
        'data_mudanca' => 'datetime',
        'visto_em' => 'datetime',
        'visto' => 'boolean',
    ];

    // Relacionamentos
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'id_processo');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_empresa');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('id_empresa', $companyId);
    }

    public function scopeByProcesso($query, $processoId)
    {
        return $query->where('id_processo', $processoId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('data_mudanca', '>=', now()->subDays($days));
    }

    // Métodos auxiliares
    public function getDataMudancaFormatada(): string
    {
        return $this->data_mudanca->format('d/m/Y H:i:s');
    }

    public function getDescricaoMudanca(): string
    {
        $anterior = $this->situacao_anterior ?: 'N/A';
        return "De '{$anterior}' para '{$this->situacao_atual}'";
    }
} 