<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'company_id',
        'name',
        'email',
        'phone',
        'service_interest',
        'message',
        'status',
        'quoted_amount',
        'responded_at',
        'created_by',
    ];

    protected $casts = [
        'quoted_amount' => 'decimal:2',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function company()
    {
        return $this->belongsTo(company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => ['class' => 'bg-warning text-dark', 'icon' => '⏳'],
            'reviewed' => ['class' => 'bg-info', 'icon' => '🔍'],
            'quoted' => ['class' => 'bg-primary', 'icon' => '💰'],
            'accepted' => ['class' => 'bg-success', 'icon' => '✅'],
            'rejected' => ['class' => 'bg-danger', 'icon' => '❌'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    // ==================== SCOPES ====================

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', '%' . $searchTerm . '%')
              ->orWhere('email', 'like', '%' . $searchTerm . '%')
              ->orWhere('service_interest', 'like', '%' . $searchTerm . '%');
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
