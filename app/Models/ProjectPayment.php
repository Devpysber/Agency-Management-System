<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPayment extends Model
{
    protected $fillable = [
        'project_id',
        'amount',
        'currency',
        'payment_gateway_id',
        'status',
        'reference',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => ['class' => 'bg-secondary', 'icon' => '⏳'],
            'paid' => ['class' => 'bg-success', 'icon' => '✅'],
            'failed' => ['class' => 'bg-danger', 'icon' => '❌'],
            'refunded' => ['class' => 'bg-warning text-dark', 'icon' => '↩️'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }
}
