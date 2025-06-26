<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'name',
        'type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'extracted_data',
        'is_processed',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'is_processed' => 'boolean',
        'file_size' => 'integer',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'cnis' => 'CNIS',
            'medical_report' => 'Laudo Médico',
            'identity' => 'Documento de Identidade',
            'work_card' => 'Carteira de Trabalho',
            'medical_certificate' => 'Atestado Médico',
            'other' => 'Outro',
            default => ucfirst($this->type),
        };
    }
} 