<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_id',
        'causer_name',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    // ==================== SCOPES ====================

    public function scopeLogName($query, $logName)
    {
        return $query->where('log_name', $logName);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('description', 'like', '%' . $term . '%');
    }

    // ==================== ACCESSORS ====================

    public function getLogIconAttribute()
    {
        $icons = [
            'deal' => 'fa-chart-line',
            'contact' => 'fa-user',
            'company' => 'fa-building',
            'task' => 'fa-list-check',
            'project' => 'fa-diagram-project',
            'staff' => 'fa-id-badge',
            'communication' => 'fa-comment',
            'calendar' => 'fa-calendar',
        ];
        return $icons[$this->log_name] ?? 'fa-circle-info';
    }

    // ==================== HELPERS ====================

    public static function record(string $description, ?string $logName = null, ?string $subjectType = null, $subjectId = null, array $properties = [])
    {
        $causer = auth()->user();

        return static::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'causer_id' => $causer?->id,
            'causer_name' => $causer?->name,
            'properties' => $properties,
        ]);
    }
}
