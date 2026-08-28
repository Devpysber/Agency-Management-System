<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceAppeal extends Model
{
    protected $fillable = [
        'staff_id', 'date', 'message', 'status', 'review_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(staff::class, 'staff_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }
}
