<?php

namespace App\Livewire;

use App\Models\AttendanceAppeal;
use App\Models\CalendarEvent;
use App\Models\DirectMessage;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\staff;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Bell / message / profile dropdowns in the admin top bar. The badge counts
 * only UNREAD items (newer than the user's last "seen" timestamp); opening a
 * dropdown marks that stream seen and clears its badge.
 */
class AdminHeaderMenu extends Component
{
    private function myStaffId(): ?int
    {
        return auth()->user()->role === 'admin'
            ? null
            : staff::where('user_id', auth()->id())->value('id');
    }

    #[Computed]
    public function alerts()
    {
        $user = auth()->user();

        $events = CalendarEvent::where('status', 'scheduled')
            ->where('start_at', '<=', now()->addDay())
            ->when($user->role !== 'admin', fn ($q) => $q->where('assigned_to', $this->myStaffId()))
            ->with('project:id,name')
            ->orderBy('start_at')->limit(6)->get()
            ->map(function ($e) {
                $where = $e->join_link ? 'Online' : ($e->location ?: null);
                $meta = ($e->start_at && $e->start_at->isPast() ? 'Overdue · ' : '')
                    . optional($e->start_at)->format('M j, g:i A')
                    . ($where ? ' · ' . $where : '')
                    . ($e->project ? ' · ' . $e->project->name : '');

                return [
                    'icon' => $e->type_badge['icon'] ?? 'fa-calendar',
                    'text' => ucfirst($e->event_type) . ': ' . $e->title,
                    'meta' => $meta,
                    'at' => $e->updated_at,
                    'url' => $e->join_link ?: route('calendar.events'),
                    'danger' => $e->start_at && $e->start_at->isPast(),
                ];
            });

        if ($user->role === 'admin') {
            AttendanceAppeal::pending()->with('staff:id,name')->latest()->limit(4)->get()
                ->each(fn ($a) => $events->push([
                    'icon' => 'fa-gavel',
                    'text' => 'Absence appeal — ' . ($a->staff->name ?? 'staff'),
                    'meta' => \Illuminate\Support\Carbon::parse($a->date)->format('M d'),
                    'at' => $a->created_at,
                    'url' => route('attendance.index'),
                    'danger' => true,
                ]));

            // Staff idle for ~1h+ WHILE still on shift — nudge the admin to check on them.
            // Only heartbeat-tracked rows (source=auto) qualify: a manually / agent
            // marked "present" row says nothing about whether the person is at their desk.
            $today = now()->toDateString();
            \App\Models\AttendanceRecord::staff()->forDate($today)
                ->where('source', 'auto')
                ->whereNotNull('check_in')->whereNotNull('check_out')
                ->where('check_out', '<=', now()->subHour())
                ->get()
                ->each(function ($rec) use ($events) {
                    $s = \App\Models\staff::find($rec->person_id);
                    if (! $s || $s->status !== 'active') {
                        return;
                    }
                    if ($s->user && $s->user->role === 'admin') {
                        return; // the leader isn't tracked staff
                    }
                    // 'inactive' == checked in, tab closed, but still inside shift hours.
                    // 'offline' means the shift is over — no need to chase them.
                    if (\App\Models\AttendanceRecord::presenceState($s->id)['state'] !== 'inactive') {
                        return;
                    }
                    $idleH = round($rec->check_out->diffInMinutes(now()) / 60, 1);
                    $events->push([
                        'icon' => 'fa-user-clock',
                        'text' => $s->name . ' inactive for ~' . $idleH . 'h — check why',
                        'meta' => 'Last seen ' . $rec->check_out->format('H:i') . ', still on shift',
                        'at' => $rec->check_out,
                        'url' => route('attendance.person', ['type' => 'staff', 'id' => $s->id]),
                        'danger' => true,
                    ]);
                });
        }

        // In-app alerts raised for this user (project changes, meetings, chat…).
        \App\Models\UserAlert::with('actor:id,name')
            ->where('user_id', $user->id)
            ->latest()->limit(10)->get()
            ->each(fn ($a) => $events->push([
                'icon' => $a->icon ?: 'fa-bell',
                'text' => $a->title,
                'meta' => trim(($a->body ? $a->body . ' · ' : '') . ($a->actor->name ?? '')),
                'at' => $a->created_at,
                'url' => $a->url ?: route('dashboard'),
                'danger' => $a->level === 'warning',
            ]));

        return $events->sortByDesc('at')->values();
    }

    #[Computed]
    public function alertsUnread(): int
    {
        $seen = auth()->user()->notifications_seen_at;
        return $this->alerts->filter(fn ($a) => $a['at'] && (! $seen || $a['at']->gt($seen)))->count();
    }

    #[Computed]
    public function messages()
    {
        $user = auth()->user();
        $q = ProjectMessage::with(['user:id,name', 'project:id,name'])->latest()->limit(8);

        if ($user->role !== 'admin') {
            $sid = $this->myStaffId();
            $projectIds = $sid
                ? Project::whereHas('staff', fn ($x) => $x->where('staff.id', $sid))->pluck('id')
                : collect();
            $q->whereIn('project_id', $projectIds);
        }

        $projectMessages = $q->get()->map(fn ($m) => [
            'who' => $m->user_id === auth()->id() ? 'You' : ($m->user->name ?? 'User'),
            'text' => \Illuminate\Support\Str::limit($m->body, 60),
            'project' => $m->project->name ?? 'Project',
            'when' => $m->created_at->diffForHumans(),
            'at' => $m->created_at,
            'mine' => $m->user_id === auth()->id(),
            'url' => $m->project ? route('projects.show', $m->project_id) : '#',
        ]);

        // Direct staff<->staff/CEO chat — merged into the same panel so a
        // new DirectMessage shows up here too, not just on the Messages page.
        $direct = DirectMessage::with(['from:id,name', 'to:id,name'])
            ->where('from_user_id', $user->id)->orWhere('to_user_id', $user->id)
            ->latest()->limit(8)->get()
            ->map(fn ($m) => [
                'who' => $m->from_user_id === $user->id ? 'You' : ($m->from->name ?? 'User'),
                'text' => \Illuminate\Support\Str::limit($m->body, 60),
                'project' => 'Direct Message',
                'when' => $m->created_at->diffForHumans(),
                'at' => $m->created_at,
                'mine' => $m->from_user_id === $user->id,
                'url' => route('messages.index'),
            ]);

        return $projectMessages->concat($direct)->sortByDesc('at')->take(8)->values();
    }

    #[Computed]
    public function messagesUnread(): int
    {
        $seen = auth()->user()->messages_seen_at;
        return $this->messages
            ->filter(fn ($m) => ! $m['mine'] && $m['at'] && (! $seen || $m['at']->gt($seen)))
            ->count();
    }

    public function markAlertsSeen(): void
    {
        auth()->user()->forceFill(['notifications_seen_at' => now()])->save();
        unset($this->alertsUnread);
    }

    public function markMessagesSeen(): void
    {
        auth()->user()->forceFill(['messages_seen_at' => now()])->save();
        unset($this->messagesUnread);
    }

    public function render()
    {
        return view('livewire.admin-header-menu');
    }
}
