<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Deleting a testimonial removes its avatar too, so storage
        // doesn't accumulate orphans.
        static::deleting(function (Testimonial $testimonial) {
            if ($testimonial->avatar) {
                Storage::disk('public')->delete($testimonial->avatar);
            }
        });
    }

    protected $fillable = [
        'client_name',
        'company',
        'message',
        'rating',
        'avatar',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'approved' => ['class' => 'bg-success', 'icon' => '🟢'],
            'pending' => ['class' => 'bg-warning text-dark', 'icon' => '🟡'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    // ==================== SCOPES ====================

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('client_name', 'like', '%' . $searchTerm . '%')
              ->orWhere('company', 'like', '%' . $searchTerm . '%');
        });
    }
}
