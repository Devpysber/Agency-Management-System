<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class PortfolioItem extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Keep the disk in sync — deleting a portfolio item (single or in
        // bulk, since we iterate rather than mass-delete) removes its
        // gallery files too, so storage doesn't accumulate orphans.
        static::deleting(function (PortfolioItem $item) {
            foreach ($item->images ?? [] as $path) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    protected $fillable = [
        'service_category_id',
        'title',
        'description',
        'images',
        'project_id',
        'client_name',
        'completed_date',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'completed_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'published' => ['class' => 'bg-success', 'icon' => '🟢'],
            'draft' => ['class' => 'bg-secondary', 'icon' => '⚪'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    // ==================== SCOPES ====================

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%')
              ->orWhere('client_name', 'like', '%' . $searchTerm . '%');
        });
    }
}
