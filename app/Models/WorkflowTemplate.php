<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'benefit_type',
        'name',
        'description',
        'tasks',
        'is_active',
        'is_global',
        'company_id',
    ];

    protected $casts = [
        'tasks' => 'array',
        'is_active' => 'boolean',
        'is_global' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'workflow_template_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getBenefitTypes(): array
    {
        return [
            'aposentadoria_por_idade' => 'Aposentadoria por Idade',
            'aposentadoria_por_tempo_contribuicao' => 'Aposentadoria por Tempo de Contribuição',
            'aposentadoria_professor' => 'Aposentadoria Professor',
            'aposentadoria_pcd' => 'Aposentadoria PCD',
            'aposentadoria_especial' => 'Aposentadoria Especial',
            'auxilio_doenca' => 'Auxílio-Doença',
            'beneficio_por_incapacidade' => 'Benefício por Incapacidade',
            'pensao_por_morte' => 'Pensão por Morte',
            'auxilio_acidente' => 'Auxílio-Acidente',
            'salario_maternidade' => 'Salário-Maternidade',
        ];
    }

    public function getBenefitTypeNameAttribute(): string
    {
        return self::getBenefitTypes()[$this->benefit_type] ?? $this->benefit_type;
    }

    /**
     * Scope para templates globais
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope para templates de uma empresa específica
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope para templates disponíveis para uma empresa (globais + da empresa)
     */
    public function scopeAvailableForCompany($query, $companyId)
    {
        return $query->where(function($q) use ($companyId) {
            $q->where('is_global', true)
              ->orWhere('company_id', $companyId);
        });
    }
} 