<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAlert extends Model
{
    protected $fillable = [
        'user_id', 'actor_id', 'icon', 'level', 'title', 'body', 'url', 'read_at',
    ];

    protected $casts = ['read_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeFor($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
