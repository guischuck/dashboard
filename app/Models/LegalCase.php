<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'case_number',
        'client_name',
        'client_cpf',
        'benefit_type',
        'status',
        'description',
        'estimated_value',
        'success_fee',
        'filing_date',
        'decision_date',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'filing_date' => 'date',
        'decision_date' => 'date',
        'estimated_value' => 'decimal:2',
        'success_fee' => 'decimal:2',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inssProcesses(): HasMany
    {
        return $this->hasMany(InssProcess::class, 'case_id');
    }

    public function employmentRelationships(): HasMany
    {
        return $this->hasMany(EmploymentRelationship::class, 'case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class, 'case_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'case_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'analysis' => 'blue',
            'completed' => 'green',
            'requirement' => 'orange',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'analysis' => 'Em Análise',
            'completed' => 'Concluído',
            'requirement' => 'Exigência',
            'rejected' => 'Rejeitado',
            default => 'Desconhecido',
        };
    }
} 