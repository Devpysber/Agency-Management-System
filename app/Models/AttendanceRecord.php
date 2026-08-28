<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    public const STATUSES = ['present', 'late', 'remote', 'half_day', 'leave', 'absent', 'holiday'];

    protected $fillable = [
        'person_type', 'person_id', 'date', 'status',
        'check_in', 'check_out', 'worked_minutes', 'active_minutes', 'source', 'note', 'recorded_by',
    ];

    protected $casts = [
        // `date` intentionally NOT cast — kept as a plain 'Y-m-d' string so
        // whereBetween on 'Y-m-d' bounds works (the date cast serialises back
        // with a time component).
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'worked_minutes' => 'integer',
        'active_minutes' => 'integer',
    ];

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** The staff member or client user this row belongs to. */
    public function getPersonAttribute()
    {
        return $this->person_type === 'client'
            ? User::find($this->person_id)
            : staff::find($this->person_id);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return [
            'present' => 'success', 'late' => 'warning', 'remote' => 'info',
            'half_day' => 'primary', 'leave' => 'secondary', 'absent' => 'danger',
            'holiday' => 'dark',
        ][$this->status] ?? 'secondary';
    }

    public function scopeStaff($q)
    {
        return $q->where('person_type', 'staff');
    }

    public function scopeClients($q)
    {
        return $q->where('person_type', 'client');
    }

    public function scopeForDate($q, string $date)
    {
        return $q->where('date', $date);
    }

    /**
     * Derive worked_minutes from check_in/out if both are set.
     */
    public function recomputeWorkedMinutes(): void
    {
        if ($this->check_in && $this->check_out && $this->check_out->gt($this->check_in)) {
            $this->worked_minutes = $this->check_in->diffInMinutes($this->check_out);
        }
    }

    public function workedHhMm(): string
    {
        $m = (int) ($this->worked_minutes ?? 0);
        return $m > 0 ? intdiv($m, 60) . 'h ' . ($m % 60) . 'm' : '—';
    }

    /** Hours the tab was actually active (heartbeat-tracked). */
    public function activeHhMm(): string
    {
        $m = (int) ($this->active_minutes ?? 0);
        return $m > 0 ? intdiv($m, 60) . 'h ' . ($m % 60) . 'm' : '—';
    }

    /**
     * Add the elapsed active time since the last heartbeat for this staff member
     * (only counts gaps of <= 90s, i.e. a genuinely continuous session).
     */
    public static function accrueActive(int $staffId): void
    {
        $now = now();
        $lastKey = 'staff_last_beat_' . $staffId;
        $last = \Illuminate\Support\Facades\Cache::get($lastKey);
        \Illuminate\Support\Facades\Cache::put($lastKey, $now->timestamp, 300);
        \Illuminate\Support\Facades\Cache::put('staff_online_' . $staffId, $now->timestamp, 120);

        if (! $last) {
            return;
        }
        $gap = $now->timestamp - (int) $last;
        if ($gap <= 0 || $gap > 90) {
            return;
        }

        static::where([
            'person_type' => 'staff', 'person_id' => $staffId, 'date' => $now->toDateString(),
        ])->increment('active_minutes', max(1, (int) round($gap / 60)), ['check_out' => $now]);
    }

    /**
     * Called on every admin-panel page load by a logged-in staff member.
     * Auto-maintains today's check-in / check-out / worked minutes. Only sets
     * the status on a brand-new (auto) row; never overrides a manual entry.
     */
    public static function recordStaffActivity(int $staffId): void
    {
        $now = now();
        $rec = static::firstOrNew([
            'person_type' => 'staff',
            'person_id' => $staffId,
            'date' => $now->toDateString(),
        ]);

        if (! $rec->exists) {
            $rec->check_in = $now;
            $rec->status = $now->hour >= 10 ? 'late' : 'present';
            $rec->source = 'auto';
        } elseif ($rec->source !== 'manual' && $rec->status === 'absent') {
            // Auto-marked absent by the sweep, but they are clearly here now —
            // activity is attendance. Flip them and drop the stale auto note.
            $rec->status = ($rec->check_in ?? $now)->hour >= 10 ? 'late' : 'present';
            if (str_starts_with((string) $rec->note, 'Auto-marked')) {
                $rec->note = null;
            }
        }

        if (! $rec->check_in) {
            $rec->check_in = $now;
        }
        $rec->check_out = $now;
        $rec->recomputeWorkedMinutes();
        $rec->save();

        self::accrueActive($staffId);
    }

    /**
     * If a staff member has no attendance today and it is now more than
     * (shift start + 1h grace + 30m late allowance) with no panel activity,
     * auto-mark them absent. Returns the record if one was just created.
     * A manual entry or an approved appeal is never overwritten.
     */
    public static function evaluateAutoAbsence(staff $member): ?self
    {
        $today = now()->toDateString();

        $rec = static::where(['person_type' => 'staff', 'person_id' => $member->id, 'date' => $today])->first();

        // Repair a stale auto-absence: the sweep ran before the person's first
        // page load, then they showed up. A check-in (or live activity) means
        // they were present — flip the row and clear the "no check-in" note.
        if ($rec && $rec->source !== 'manual' && $rec->status === 'absent'
            && ($rec->check_in || self::lastActiveAgo($member->id) !== null)) {
            $ref = $rec->check_in ?? now();
            $rec->forceFill([
                'status' => $ref->hour >= 10 ? 'late' : 'present',
                'check_in' => $rec->check_in ?? now(),
                'note' => str_starts_with((string) $rec->note, 'Auto-marked') ? null : $rec->note,
            ])->save();
            return $rec;
        }

        if ($rec && ($rec->source === 'manual' || ($rec->status !== 'absent' && $rec->check_in) || $rec->check_in)) {
            return null; // manual entry, already present, or already checked in
        }

        $shift = $member->shift_start ?: '09:00';
        [$h, $m] = array_pad(explode(':', $shift), 2, 0);
        $cutoff = now()->copy()->setTime((int) $h, (int) $m, 0)->addMinutes(90); // 60m grace + 30m late

        if (now()->lt($cutoff)) {
            return null; // still within grace window
        }

        $rec = static::updateOrCreate(
            ['person_type' => 'staff', 'person_id' => $member->id, 'date' => $today],
            ['status' => 'absent', 'source' => 'auto', 'note' => 'Auto-marked: no check-in by ' . $cutoff->format('H:i')]
        );

        return $rec;
    }

    /** Seconds since this staff member was last active, or null if never. */
    public static function lastActiveAgo(int $staffId): ?int
    {
        $ts = \Illuminate\Support\Facades\Cache::get('staff_online_' . $staffId);
        return $ts ? max(0, now()->timestamp - (int) $ts) : null;
    }

    public static function isOnline(int $staffId): bool
    {
        return self::presenceState($staffId)['state'] === 'online';
    }

    /**
     * Three-state presence for a staff member:
     *  - online   : heartbeat within 90s (tab open + visible)
     *  - inactive : checked in today, tab closed / hidden, but still inside their
     *               shift window — NOT absent, just away from the screen
     *  - offline  : no check-in today, or outside shift hours
     *
     * @return array{state:string, ago:?int}
     */
    public static function presenceState(int $staffId): array
    {
        $ago = self::lastActiveAgo($staffId);
        if ($ago !== null && $ago <= 90) {
            return ['state' => 'online', 'ago' => $ago];
        }

        $rec = static::where([
            'person_type' => 'staff', 'person_id' => $staffId, 'date' => now()->toDateString(),
        ])->first();

        if ($rec && $rec->check_in) {
            $member = staff::find($staffId);
            $shift = $member->shift_start ?? '09:00';
            [$h, $m] = array_pad(explode(':', $shift), 2, 0);
            $start = now()->copy()->setTime((int) $h, (int) $m, 0);
            $end = (clone $start)->addHours((int) ($member->daily_hours ?? 8) + 1); // +1h wind-down
            if (now()->betweenIncluded($start, $end)) {
                return ['state' => 'inactive', 'ago' => $ago];
            }
        }

        return ['state' => 'offline', 'ago' => $ago];
    }

    public static function presenceLabel(int $staffId): string
    {
        $p = self::presenceState($staffId);
        if ($p['state'] === 'online') {
            return 'Online';
        }
        $seen = $p['ago'] !== null
            ? ' · seen ' . \Illuminate\Support\Carbon::now()->subSeconds($p['ago'])->diffForHumans(['short' => true])
            : '';
        return ($p['state'] === 'inactive' ? 'Inactive' : 'Offline') . $seen;
    }
}
