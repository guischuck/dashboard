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
        'title',
        'content',
        'type',
        'status',
        'file_path',
        'ai_generation_data',
        'created_by',
    ];

    protected $casts = [
        'ai_generation_data' => 'array',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'generated' => 'blue',
            'submitted' => 'yellow',
            'approved' => 'green',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Rascunho',
            'generated' => 'Gerado',
            'submitted' => 'Submetido',
            'approved' => 'Aprovado',
            default => 'Desconhecido',
        };
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'recurso' => 'Recurso',
            'requerimento' => 'Requerimento',
            'defesa' => 'Defesa',
            'impugnacao' => 'Impugnação',
            'other' => 'Outro',
            default => ucfirst($this->type),
        };
    }
} 