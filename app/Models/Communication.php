<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'direction',
        'subject',
        'notes',
        'status',
        'duration_minutes',
        'occurred_at',
        'contact_id',
        'company_id',
        'deal_id',
        'staff_id',
        'created_by',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'duration_minutes' => 'integer',
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

    public function deal()
    {
        return $this->belongsTo(deal::class);
    }

    public function staff()
    {
        return $this->belongsTo(staff::class, 'staff_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'scheduled' => ['class' => 'bg-primary', 'icon' => '🔵'],
            'completed' => ['class' => 'bg-success', 'icon' => '✅'],
            'cancelled' => ['class' => 'bg-danger', 'icon' => '❌'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    public function getTypeIconAttribute()
    {
        $icons = [
            'email' => 'fa-envelope',
            'call' => 'fa-phone',
            'meeting' => 'fa-users',
        ];
        return $icons[$this->type] ?? 'fa-comment';
    }

    // ==================== SCOPES ====================

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('subject', 'like', '%' . $term . '%')
              ->orWhere('notes', 'like', '%' . $term . '%');
        });
    }
}
