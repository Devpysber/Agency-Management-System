<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bug extends Model
{
    public const STATUSES = ['open', 'in_progress', 'fixed', 'qa_retest', 'failed', 'verified', 'closed'];
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    protected $fillable = [
        'title', 'description', 'steps_to_reproduce', 'project_id',
        'reported_by', 'assigned_to', 'severity', 'status',
        'verified_by', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(staff::class, 'reported_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(staff::class, 'assigned_to');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(staff::class, 'verified_by');
    }

    public function scopeOpen($q)
    {
        return $q->whereNotIn('status', ['verified', 'closed']);
    }

    public function getSeverityBadgeAttribute(): array
    {
        return [
            'low' => ['class' => 'bg-secondary', 'icon' => '🟢'],
            'medium' => ['class' => 'bg-primary', 'icon' => '🔵'],
            'high' => ['class' => 'bg-warning text-dark', 'icon' => '🟡'],
            'critical' => ['class' => 'bg-danger', 'icon' => '🔴'],
        ][$this->severity] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    public function getStatusBadgeAttribute(): array
    {
        return [
            'open' => ['class' => 'bg-secondary', 'icon' => '⏳'],
            'in_progress' => ['class' => 'bg-primary', 'icon' => '🔄'],
            'fixed' => ['class' => 'bg-info', 'icon' => '🔧'],
            'qa_retest' => ['class' => 'bg-warning text-dark', 'icon' => '🔍'],
            'failed' => ['class' => 'bg-danger', 'icon' => '❌'],
            'verified' => ['class' => 'bg-success', 'icon' => '✅'],
            'closed' => ['class' => 'bg-dark', 'icon' => '🔒'],
        ][$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }
}
