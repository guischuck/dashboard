<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'employer_name',
        'employer_cnpj',
        'start_date',
        'end_date',
        'salary',
        'position',
        'cbo_code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function getDurationAttribute(): string
    {
        $start = $this->start_date;
        $end = $this->end_date ?? now();
        
        $diff = $start->diff($end);
        
        if ($diff->y > 0) {
            return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '') . 
                   ($diff->m > 0 ? ' e ' . $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '') : '');
        }
        
        if ($diff->m > 0) {
            return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '') . 
                   ($diff->d > 0 ? ' e ' . $diff->d . ' dia' . ($diff->d > 1 ? 's' : '') : '');
        }
        
        return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
    }
} 