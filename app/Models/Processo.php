<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processo extends Model
{
    use HasFactory;

    protected $table = 'processos';

    protected $fillable = [
        'protocolo',
        'servico',
        'situacao',
        'situacao_anterior',
        'ultima_atualizacao',
        'protocolado_em',
        'cpf',
        'nome',
        'id_empresa',
        'criado_em',
        'atualizado_em',
    ];

    protected $casts = [
        'ultima_atualizacao' => 'datetime',
        'protocolado_em' => 'datetime',
        'criado_em' => 'datetime',
        'atualizado_em' => 'datetime',
    ];

    // Relacionamentos
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_empresa');
    }

    public function historicoSituacoes(): HasMany
    {
        return $this->hasMany(HistoricoSituacao::class, 'id_processo');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('id_empresa', $companyId);
    }

    public function scopeByCpf($query, $cpf)
    {
        return $query->where('cpf', $cpf);
    }

    public function scopeBySituacao($query, $situacao)
    {
        return $query->where('situacao', $situacao);
    }

    // Métodos auxiliares
    public function temMudancaSituacao(): bool
    {
        return !empty($this->situacao_anterior) && 
               $this->situacao_anterior !== $this->situacao;
    }

    public function getUltimaAtualizacaoFormatada(): string
    {
        return $this->ultima_atualizacao ? 
               $this->ultima_atualizacao->format('d/m/Y H:i:s') : 
               'N/A';
    }

    public function getProtocoladoEmFormatado(): string
    {
        return $this->protocolado_em ? 
               $this->protocolado_em->format('d/m/Y H:i:s') : 
               'N/A';
    }

    // Accessor para campos de data formatados
    public function getCriadoEmAttribute()
    {
        return $this->created_at ? $this->created_at->toISOString() : null;
    }

    public function getAtualizadoEmAttribute()
    {
        return $this->updated_at ? $this->updated_at->toISOString() : null;
    }

    // Append formatted dates
    protected $appends = [
        'criado_em',
        'atualizado_em'
    ];
} 