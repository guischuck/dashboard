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
        'notes',
        'workflow_tasks',
        'estimated_value',
        'success_fee',
        'filing_date',
        'decision_date',
        'assigned_to',
        'created_by',
        'company_id',
    ];

    protected $casts = [
        'filing_date' => 'date',
        'decision_date' => 'date',
        'estimated_value' => 'decimal:2',
        'success_fee' => 'decimal:2',
        'workflow_tasks' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

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
            'pendente' => 'yellow',
            'em_coleta' => 'blue',
            'aguarda_peticao' => 'orange',
            'protocolado' => 'purple',
            'concluido' => 'green',
            'rejeitado' => 'red',
            default => 'gray',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pendente' => 'Pendente',
            'em_coleta' => 'Em Coleta',
            'aguarda_peticao' => 'Aguarda Petição',
            'protocolado' => 'Protocolado',
            'concluido' => 'Concluído',
            'rejeitado' => 'Rejeitado',
            default => 'Desconhecido',
        };
    }

    /**
     * Calcula o progresso da coleta de documentos
     */
    public function getCollectionProgressAttribute(): array
    {
        $totalVinculos = $this->employmentRelationships()->count();
        
        if ($totalVinculos === 0) {
            return [
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'status' => 'Sem vínculos'
            ];
        }
        
        $vinculosConcluidos = $this->employmentRelationships()
            ->where('is_active', false)
            ->count();
        
        $percentage = $totalVinculos > 0 ? round(($vinculosConcluidos / $totalVinculos) * 100) : 0;
        
        return [
            'percentage' => $percentage,
            'completed' => $vinculosConcluidos,
            'total' => $totalVinculos,
            'status' => $percentage == 100 ? 'Completo' : 'Em andamento'
        ];
    }

    /**
     * Atualiza automaticamente o status do caso baseado no progresso da coleta
     */
    public function updateStatusBasedOnProgress(): void
    {
        $progress = $this->collection_progress;
        
        // Se não há vínculos, mantém status atual
        if ($progress['total'] === 0) {
            return;
        }
        
        // Se todos os vínculos foram concluídos
        if ($progress['percentage'] == 100) {
            // Se estava em coleta, muda para aguarda petição
            if ($this->status === 'em_coleta') {
                $this->update(['status' => 'aguarda_peticao']);
            }
        } else {
            // Se há vínculos pendentes e não está em coleta
            if ($this->status === 'pendente') {
                $this->update(['status' => 'em_coleta']);
            }
        }
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
} 