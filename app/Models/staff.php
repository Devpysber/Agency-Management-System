<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class staff extends Model
{
    protected static function booted(): void
    {
        // Deleting a staff member removes their photo too, so storage
        // doesn't accumulate orphans.
        static::deleting(function (staff $member) {
            if ($member->image) {
                Storage::disk('public')->delete($member->image);
            }
        });
    }

    protected $fillable = [
        'image',
        'name',
        'email',
        'whatsapp',
        'aadhar',
        'pan',
        'designation',
        'employment_type',
        'shift_start',
        'daily_hours',
        'joining_date',
        'tenure_start',
        'tenure_end',
        'user_id',
        'salary',
        'status',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'tenure_start' => 'date',
        'tenure_end' => 'date',
    ];

    public function getMaskedAadharAttribute(): ?string
    {
        if (! $this->aadhar) {
            return null;
        }
        $d = preg_replace('/\D/', '', $this->aadhar);
        return strlen($d) >= 4 ? 'XXXX XXXX ' . substr($d, -4) : $this->aadhar;
    }

    public function getMaskedPanAttribute(): ?string
    {
        if (! $this->pan) {
            return null;
        }
        return strlen($this->pan) >= 4 ? substr($this->pan, 0, 2) . 'XXX' . substr($this->pan, -2) : $this->pan;
    }

    public function isIntern(): bool
    {
        return $this->employment_type === 'intern';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function deals()
    {
        return $this->hasMany(\App\Models\deal::class, 'assigned_to');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'assigned_to');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_staff', 'staff_id', 'project_id')->withTimestamps();
    }

    public function communications()
    {
        return $this->hasMany(Communication::class, 'staff_id');
    }

    public function calendarEvents()
    {
        return $this->hasMany(CalendarEvent::class, 'assigned_to');
    }
}
