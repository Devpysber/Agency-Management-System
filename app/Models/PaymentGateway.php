<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'key_id',
        'key_secret',
        'mode',
        'webhook_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Check if the gateway has API keys configured */
    public function isConfigured(): bool
    {
        return !empty($this->key_id) && !empty($this->key_secret);
    }

    public function payments()
    {
        return $this->hasMany(ProjectPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
