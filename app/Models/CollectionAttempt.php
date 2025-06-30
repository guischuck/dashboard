<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'employment_relationship_id',
        'tentativa_num',
        'endereco',
        'rastreamento',
        'data_envio',
        'retorno',
        'email',
        'telefone',
    ];

    public function employmentRelationship()
    {
        return $this->belongsTo(EmploymentRelationship::class);
    }
} 