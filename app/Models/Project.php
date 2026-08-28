<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'submission_due_at',
        'status',
        'progress',
        'budget',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'submission_due_at' => 'datetime',
        'progress' => 'integer',
        'budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function company()
    {
        return $this->belongsTo(\App\Models\company::class, 'company_id');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function payments()
    {
        return $this->hasMany(ProjectPayment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function staff()
    {
        return $this->belongsToMany(staff::class, 'project_staff', 'project_id', 'staff_id')->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(ProjectMessage::class)->oldest();
    }

    // ==================== ACCESSORS ====================

    /**
     * Human "time left" string for the submission deadline, or null.
     */
    public function getSubmissionCountdownAttribute(): ?string
    {
        if (! $this->submission_due_at) {
            return null;
        }

        return $this->submission_due_at->isPast()
            ? 'Overdue by ' . $this->submission_due_at->diffForHumans(null, true)
            : $this->submission_due_at->diffForHumans(null, true) . ' left';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->submission_due_at
            && $this->submission_due_at->isPast()
            && $this->status !== 'completed';
    }

    /**
     * Keep `status` consistent with `progress` after an inline progress / milestone
     * update, so the portal never shows "100% · Planning". Only ever moves status
     * forward (planning -> in_progress -> completed); never downgrades, never
     * touches a cancelled or already-completed project.
     */
    public function syncStatusToProgress(): void
    {
        if (in_array($this->status, ['cancelled', 'completed'], true)) {
            return;
        }

        $progress = (int) $this->progress;
        $new = $this->status;

        if ($progress >= 100) {
            $new = 'completed';
        } elseif ($progress > 0 && $this->status === 'planning') {
            $new = 'in_progress';
        }

        if ($new !== $this->status) {
            $this->status = $new;
            $this->save();
        }
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'planning' => ['class' => 'bg-secondary', 'icon' => '📝'],
            'in_progress' => ['class' => 'bg-primary', 'icon' => '🔄'],
            'on_hold' => ['class' => 'bg-warning text-dark', 'icon' => '⏸️'],
            'completed' => ['class' => 'bg-success', 'icon' => '✅'],
            'cancelled' => ['class' => 'bg-danger', 'icon' => '❌'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    public function statusBadgeClass()
    {
        return $this->getStatusBadgeAttribute()['class'];
    }

    // ==================== SCOPES ====================

    public function scopePlanning($query)
    {
        return $query->where('status', 'planning');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeOnHold($query)
    {
        return $query->where('status', 'on_hold');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%');
        });
    }
}
