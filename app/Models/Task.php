<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'assigned_to',
        'created_by',
        'related_to',
        'related_type',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function assignedTo()
    {
        return $this->belongsTo(staff::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function related()
    {
        // Explicit column names — this table uses related_to/related_type,
        // not morphTo()'s default related_id/related_type.
        return $this->morphTo('related', 'related_type', 'related_to');
    }

    // ==================== ACCESSORS ====================

    public function getPriorityBadgeAttribute()
    {
        $priorities = [
            'low' => ['class' => 'bg-secondary', 'icon' => '🟢'],
            'medium' => ['class' => 'bg-primary', 'icon' => '🔵'],
            'high' => ['class' => 'bg-warning text-dark', 'icon' => '🟡'],
            'urgent' => ['class' => 'bg-danger', 'icon' => '🔴'],
        ];
        return $priorities[$this->priority] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => ['class' => 'bg-secondary', 'icon' => '⏳'],
            'in_progress' => ['class' => 'bg-primary', 'icon' => '🔄'],
            'completed' => ['class' => 'bg-success', 'icon' => '✅'],
            'cancelled' => ['class' => 'bg-danger', 'icon' => '❌'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, $staffId)
    {
        return $query->where('assigned_to', $staffId);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('title', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%');
        });
    }

    // ==================== HELPER METHODS ====================

    public function isOverdue()
    {
        return $this->due_date && $this->due_date < now() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsInProgress()
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    public function markAsPending()
    {
        $this->update([
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    public function getRelatedModel()
    {
        if ($this->related_type === 'deal') {
            return deal::find($this->related_to);
        } elseif ($this->related_type === 'contact') {
            return Contact::find($this->related_to);
        } elseif ($this->related_type === 'company') {
            return company::find($this->related_to);
        } elseif ($this->related_type === 'project') {
            return Project::find($this->related_to);
        }
        return null;
    }
}