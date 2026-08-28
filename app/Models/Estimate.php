<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estimate extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_number',
        'company_id',
        'contact_id',
        'client_name',
        'client_email',
        'issue_date',
        'valid_until',
        'status',
        'subtotal',
        'tax',
        'total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function items()
    {
        return $this->hasMany(EstimateItem::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'draft' => ['class' => 'bg-secondary', 'icon' => '⚪'],
            'sent' => ['class' => 'bg-info', 'icon' => '🔵'],
            'approved' => ['class' => 'bg-success', 'icon' => '✅'],
            'rejected' => ['class' => 'bg-danger', 'icon' => '❌'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    public function getClientDisplayNameAttribute()
    {
        if ($this->company) {
            return $this->company->company_name;
        }
        if ($this->contact) {
            return trim($this->contact->first_name . ' ' . $this->contact->last_name);
        }
        return $this->client_name ?: 'N/A';
    }

    // ==================== SCOPES ====================

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('estimate_number', 'like', '%' . $searchTerm . '%')
              ->orWhere('client_name', 'like', '%' . $searchTerm . '%');
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ==================== HELPER METHODS ====================

    public static function generateEstimateNumber()
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        $number = 'EST-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Guard against collisions (e.g. after deletions)
        while (static::where('estimate_number', $number)->exists()) {
            $count++;
            $number = 'EST-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
