<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetitionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'benefit_type',
        'description',
        'content',
        'variables',
        'sections',
        'is_active',
        'is_default',
        'is_global',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'sections' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_global' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class, 'template_id');
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
            default => ucfirst($this->category),
        };
    }

    public function getBenefitTypeTextAttribute(): string
    {
        return match($this->benefit_type) {
            'aposentadoria_idade' => 'Aposentadoria por Idade',
            'aposentadoria_tempo' => 'Aposentadoria por Tempo de Contribuição',
            'aposentadoria_invalidez' => 'Aposentadoria por Invalidez',
            'aposentadoria_especial' => 'Aposentadoria Especial',
            'aposentadoria_professor' => 'Aposentadoria de Professor',
            'aposentadoria_pcd' => 'Aposentadoria da Pessoa com Deficiência',
            'auxilio_doenca' => 'Auxílio-Doença',
            'auxilio_acidente' => 'Auxílio-Acidente',
            'pensao_morte' => 'Pensão por Morte',
            'salario_maternidade' => 'Salário-Maternidade',
            'bpc' => 'Benefício de Prestação Continuada',
            default => $this->benefit_type ? ucfirst(str_replace('_', ' ', $this->benefit_type)) : 'Geral',
        };
    }

    public function getUsageCountAttribute(): int
    {
        return $this->petitions()->count();
    }

    /**
     * Substitui as variáveis no template com os dados fornecidos
     */
    public function renderTemplate(array $data): string
    {
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }

    /**
     * Extrai as variáveis do template
     */
    public function extractVariables(): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $this->content, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Scope para templates ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para templates por categoria
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope para templates por tipo de benefício
     */
    public function scopeByBenefitType($query, string $benefitType)
    {
        return $query->where('benefit_type', $benefitType);
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
