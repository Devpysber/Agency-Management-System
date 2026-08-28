<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EstimateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_id',
        'description',
        'qty',
        'unit_price',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    // ==================== ACCESSORS ====================

    public function getLineTotalAttribute()
    {
        return $this->qty * $this->unit_price;
    }
}
