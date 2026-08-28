<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Deleting a product removes its image too, so storage doesn't
        // accumulate orphans.
        static::deleting(function (Product $product) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
        });
    }

    protected $fillable = [
        'product_category_id',
        'name',
        'sku',
        'description',
        'price',
        'stock_quantity',
        'status',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
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

    public function getIsLowStockAttribute()
    {
        return $this->stock_quantity < 10;
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
              ->orWhere('sku', 'like', '%' . $searchTerm . '%');
        });
    }
}
