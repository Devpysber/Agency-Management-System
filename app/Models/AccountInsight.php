<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountInsight extends Model
{
    protected $fillable = [
        'company_id',
        'headline',
        'summary',
        'sections',
        'metrics',
        'model',
        'input_digest',
        'generated_by',
    ];

    protected $casts = [
        'sections' => 'array',
        'metrics' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id');
    }
}
