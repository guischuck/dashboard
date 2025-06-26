<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InssProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'process_number',
        'protocol_number',
        'status',
        'last_movement',
        'last_movement_date',
        'is_seen',
        'has_changes',
        'movements_history',
    ];

    protected $casts = [
        'last_movement_date' => 'date',
        'is_seen' => 'boolean',
        'has_changes' => 'boolean',
        'movements_history' => 'array',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'analysis' => 'blue',
            'completed' => 'green',
            'requirement' => 'orange',
            'rejected' => 'red',
            'appeal' => 'purple',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'analysis' => 'Em Análise',
            'completed' => 'Concluído',
            'requirement' => 'Exigência',
            'rejected' => 'Rejeitado',
            'appeal' => 'Recurso',
            default => 'Desconhecido',
        };
    }
} 