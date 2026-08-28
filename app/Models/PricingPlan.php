<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_category_id',
        'name',
        'price',
        'billing_period',
        'features',
        'countries',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
        'countries' => 'array',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function serviceType()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'active' => ['class' => 'bg-success', 'icon' => '🟢'],
            'inactive' => ['class' => 'bg-danger', 'icon' => '🔴'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    public function getBillingPeriodLabelAttribute()
    {
        $labels = [
            'monthly' => '/month',
            'yearly' => '/year',
            'one_time' => 'one-time',
        ];
        return $labels[$this->billing_period] ?? $this->billing_period;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'like', '%' . $searchTerm . '%');
    }
}
