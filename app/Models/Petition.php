<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Petition extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'user_id',
        'template_id',
        'type', // 'template' ou 'ia'
        'category',
        'status',
        'title',
        'content',
        'file_path',
        'template_variables',
        'ai_generation_data',
        'ai_prompt',
        'ai_tokens_used',
        'generated_at',
        'submitted_at',
        'notes',
        'version',
    ];

    protected $casts = [
        'template_variables' => 'array',
        'ai_generation_data' => 'array',
        'generated_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PetitionTemplate::class, 'template_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'generated' => 'blue',
            'reviewed' => 'yellow',
            'submitted' => 'orange',
            'approved' => 'green',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Rascunho',
            'generated' => 'Gerado',
            'reviewed' => 'Revisado',
            'submitted' => 'Submetido',
            'approved' => 'Aprovado',
            default => 'Desconhecido',
        };
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'template' => 'Template',
            'ia' => 'Inteligência Artificial',
            default => ucfirst($this->type),
        };
    }

    public function getCategoryTextAttribute(): string
    {
        return match($this->category) {
            'recurso' => 'Recurso',
            'requerimento' => 'Requerimento',
            'defesa' => 'Defesa',
            'impugnacao' => 'Impugnação',
            'contestacao' => 'Contestação',
            'mandado_seguranca' => 'Mandado de Segurança',
            'acao_ordinaria' => 'Ação Ordinária',
            default => $this->category ? ucfirst($this->category) : 'Não especificado',
        };
    }

    /**
     * Marca a petição como gerada
     */
    public function markAsGenerated(): void
    {
        $this->update([
            'status' => 'generated',
            'generated_at' => now(),
        ]);
    }

    /**
     * Marca a petição como submetida
     */
    public function markAsSubmitted(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Verifica se a petição foi gerada por IA
     */
    public function isAiGenerated(): bool
    {
        return $this->type === 'ia';
    }

    /**
     * Verifica se a petição usa template
     */
    public function usesTemplate(): bool
    {
        return $this->type === 'template' && $this->template_id !== null;
    }

    /**
     * Scope para petições por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para petições por categoria
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope para petições por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
} 