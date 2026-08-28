<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\EventNotifier;

class CalendarEvent extends Model
{
    use HasFactory;

    /** Guards against the notifier's own save() re-triggering the hook. */
    public static bool $notifying = false;

    protected static function booted(): void
    {
        static::saved(function (CalendarEvent $event) {
            if (self::$notifying || ! $event->shouldNotify()) {
                return;
            }
            if ($event->notified_digest === $event->currentDigest()) {
                return; // nothing meaningful changed since the last notification
            }
            self::$notifying = true;
            try {
                app(EventNotifier::class)->sync($event);
            } finally {
                self::$notifying = false;
            }
        });
    }

    /** Only real "someone needs to know" events fan out notifications. */
    public function shouldNotify(): bool
    {
        return in_array($this->event_type, ['meeting', 'call', 'deadline', 'reminder'], true)
            && $this->start_at !== null;
    }

    /** Fingerprint of the fields a notification actually depends on. */
    public function currentDigest(): string
    {
        return sha1(implode('|', [
            $this->title,
            optional($this->start_at)->toDateTimeString(),
            optional($this->end_at)->toDateTimeString(),
            $this->status,
            $this->location,
            $this->meeting_url,
            $this->assigned_to,
            $this->notify_all,
            $this->project_id,
            $this->contact_id,
        ]));
    }

    /** Best available join link — explicit field first, else a URL pasted into location. */
    public function getJoinLinkAttribute(): ?string
    {
        if (filled($this->meeting_url)) {
            return $this->meeting_url;
        }
        return filled($this->location) && filter_var($this->location, FILTER_VALIDATE_URL)
            ? $this->location
            : null;
    }

    protected $fillable = [
        'title',
        'description',
        'event_type',
        'start_at',
        'end_at',
        'all_day',
        'location',
        'meeting_url',
        'status',
        'color',
        'assigned_to',
        'notify_all',
        'project_id',
        'contact_id',
        'created_by',
        'notified_digest',
        'communication_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function communication()
    {
        return $this->belongsTo(Communication::class);
    }

    // ==================== ACCESSORS ====================

    public function getTypeBadgeAttribute()
    {
        $types = [
            'meeting' => ['class' => 'bg-primary', 'icon' => 'fa-users'],
            'call' => ['class' => 'bg-info', 'icon' => 'fa-phone'],
            'task' => ['class' => 'bg-secondary', 'icon' => 'fa-list-check'],
            'deadline' => ['class' => 'bg-danger', 'icon' => 'fa-flag-checkered'],
            'reminder' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-bell'],
            'other' => ['class' => 'bg-dark', 'icon' => 'fa-circle'],
        ];
        return $types[$this->event_type] ?? ['class' => 'bg-secondary', 'icon' => 'fa-circle'];
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'scheduled' => ['class' => 'bg-primary', 'icon' => '🔵'],
            'completed' => ['class' => 'bg-success', 'icon' => '✅'],
            'cancelled' => ['class' => 'bg-danger', 'icon' => '❌'],
        ];
        return $statuses[$this->status] ?? ['class' => 'bg-secondary', 'icon' => '⚪'];
    }

    // ==================== SCOPES ====================

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now())->where('status', 'scheduled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_at', now()->toDateString());
    }

    public function scopeOverdue($query)
    {
        return $query->where('start_at', '<', now())->where('status', 'scheduled');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ==================== HELPERS ====================

    public function isOverdue()
    {
        return $this->start_at && $this->start_at->isPast() && $this->status === 'scheduled';
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
    }
}
