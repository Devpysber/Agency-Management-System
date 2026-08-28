<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Deleting a service removes its image too, so storage doesn't
        // accumulate orphans.
        static::deleting(function (Service $service) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
        });
    }

    protected $fillable = [
        'service_category_id',
        'name',
        'description',
        'price',
        'status',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function category()
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

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%');
        });
    }
}
