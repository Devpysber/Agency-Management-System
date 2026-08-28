<?php

use Livewire\Component;
use App\Models\AttendanceRecord;
use App\Models\AttendanceAppeal;
use App\Models\ClientPortalVisit;
use App\Models\staff;
use Illuminate\Support\Carbon;

new class extends Component
{
    public string $date;
    public string $tab = 'employees';
    public string $q = '';
    public array $rows = [];   // staff_id => [status, check_in, check_out, note, saved]
    public bool $canEdit = false;
    public bool $canApprove = false;

    public function mount(): void
    {
        // Full-roster Attendance ERP: admin, or anyone with company-wide
        // Attendance visibility (e.g. CEO). Not the same as attendance.person
        // self-view, which any staff member has for their own row.
        $user = auth()->user();
        abort_unless($user?->role === 'admin' || $user?->hasPermission('Attendance', 'View'), 403);
        $this->canEdit = $user->role === 'admin' || $user->hasPermission('Attendance', 'Edit');
        $this->canApprove = $user->role === 'admin' || $user->hasPermission('Attendance', 'Approve');

        $this->date = now()->toDateString();
        $this->loadStaffRows();
    }

    public function updatedDate(): void
    {
        $this->loadStaffRows();
    }

    private function loadStaffRows(): void
    {
        $existing = AttendanceRecord::staff()->forDate($this->date)->get()->keyBy('person_id');

        $this->rows = [];
        foreach (staff::where('status', 'active')->orderBy('name')->get() as $s) {
            $r = $existing->get($s->id);
            $this->rows[$s->id] = [
                'status' => $r->status ?? 'present',
                'check_in' => optional($r?->check_in)->format('H:i') ?? '',
                'check_out' => optional($r?->check_out)->format('H:i') ?? '',
                'note' => $r->note ?? '',
                'saved' => (bool) $r,
            ];
        }
    }

    public function saveRow($staffId): void
    {
        abort_unless($this->canEdit, 403);
        $staffId = (int) $staffId;
        $row = $this->rows[$staffId] ?? null;
        if (! $row || ! in_array($row['status'], AttendanceRecord::STATUSES, true)) {
            return;
        }

        $ci = $row['check_in'] ? Carbon::parse($this->date . ' ' . $row['check_in']) : null;
        $co = $row['check_out'] ? Carbon::parse($this->date . ' ' . $row['check_out']) : null;

        $rec = AttendanceRecord::updateOrCreate(
            ['person_type' => 'staff', 'person_id' => $staffId, 'date' => $this->date],
            [
                'status' => $row['status'],
                'check_in' => $ci,
                'check_out' => $co,
                'note' => $row['note'] ?: null,
                'source' => 'manual',
                'recorded_by' => auth()->id(),
            ]
        );
        $rec->recomputeWorkedMinutes();
        $rec->save();

        $this->rows[$staffId]['saved'] = true;
        $this->dispatch('toast', message: 'Saved');
        session()->flash('ok', 'Attendance saved.');
    }

    public function markAllPresent(): void
    {
        abort_unless($this->canEdit, 403);
        foreach (staff::where('status', 'active')->pluck('id') as $sid) {
            AttendanceRecord::firstOrCreate(
                ['person_type' => 'staff', 'person_id' => $sid, 'date' => $this->date],
                ['status' => 'present', 'source' => 'manual', 'recorded_by' => auth()->id()]
            );
        }
        $this->loadStaffRows();
        session()->flash('ok', 'All employees marked present for ' . $this->date . '.');
    }

    public function approveAppeal(int $appealId): void
    {
        abort_unless($this->canApprove, 403);
        $appeal = AttendanceAppeal::with('staff')->find($appealId);
        if (! $appeal || $appeal->status !== 'pending') {
            return;
        }

        $rec = AttendanceRecord::firstOrNew([
            'person_type' => 'staff', 'person_id' => $appeal->staff_id, 'date' => $appeal->date,
        ]);
        $rec->status = 'present';
        $rec->source = 'manual';
        $rec->recorded_by = auth()->id();
        $rec->note = trim(($rec->note ? $rec->note . ' · ' : '') . 'Appeal approved');
        // Count a full working day if activity wasn't tracked.
        $rec->recomputeWorkedMinutes();
        $fullDay = ($appeal->staff->daily_hours ?? 8) * 60;
        if (! $rec->worked_minutes) {
            $rec->worked_minutes = $fullDay;
        }
        if (! $rec->active_minutes) {
            $rec->active_minutes = $fullDay;
        }
        $rec->save();

        $appeal->update([
            'status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
        ]);
        $this->loadStaffRows();
        session()->flash('ok', $appeal->staff->name . ' marked present — appeal approved.');
    }

    public function rejectAppeal(int $appealId): void
    {
        abort_unless($this->canApprove, 403);
        $appeal = AttendanceAppeal::find($appealId);
        if ($appeal && $appeal->status === 'pending') {
            $appeal->update([
                'status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
                'review_note' => 'Declined by ' . auth()->user()->name,
            ]);
            session()->flash('ok', 'Appeal declined.');
        }
    }

    public function render()
    {
        $staffList = staff::where('status', 'active')->orderBy('name')
            ->when($this->q !== '', fn ($qq) => $qq->where('name', 'like', "%{$this->q}%")->orWhere('designation', 'like', "%{$this->q}%"))
            ->get();
        $activeCount = staff::where('status', 'active')->count();

        $recForDate = AttendanceRecord::staff()->forDate($this->date)->get();
        $marked = $recForDate->count();
        $countBy = fn ($s) => $recForDate->where('status', $s)->count();

        $monthStart = Carbon::parse($this->date)->startOfMonth()->toDateString();

        $clientsToday = ClientPortalVisit::where('visited_on', $this->date)
            ->with(['user:id,name,email', 'company:id,company_name'])
            ->orderByDesc('last_seen_at')->get();

        $clientMonth = ClientPortalVisit::whereBetween('visited_on', [$monthStart, $this->date])
            ->selectRaw('user_id, COUNT(*) as days, SUM(hits) as hits')
            ->groupBy('user_id')->pluck('days', 'user_id');

        $isToday = $this->date === now()->toDateString();
        $online = [];
        $recById = $recForDate->keyBy('person_id');
        foreach ($staffList as $s) {
            $p = $isToday ? AttendanceRecord::presenceState($s->id) : ['state' => 'offline', 'ago' => null];
            $online[$s->id] = [
                'state' => $p['state'],
                'label' => $isToday ? AttendanceRecord::presenceLabel($s->id) : 'Offline',
                'ago' => $p['ago'],
                'hours' => optional($recById->get($s->id))->activeHhMm() ?? '—',
                'auto' => optional($recById->get($s->id))->source === 'auto',
            ];
        }

        // No-show sweep + pending appeals
        foreach (staff::where('status', 'active')->get() as $m) {
            AttendanceRecord::evaluateAutoAbsence($m);
        }
        $appeals = AttendanceAppeal::pending()->with('staff:id,name,designation')->latest()->get();

        return $this->view([
            'staffList' => $staffList,
            'clientsToday' => $clientsToday,
            'clientMonth' => $clientMonth,
            'online' => $online,
            'isToday' => $isToday,
            'appeals' => $appeals,
            'kpi' => [
                'headcount' => $activeCount,
                'present' => $countBy('present') + $countBy('late') + $countBy('remote') + $countBy('half_day'),
                'absent' => $countBy('absent'),
                'leave' => $countBy('leave'),
                'late' => $countBy('late'),
                'not_marked' => max(0, $activeCount - $marked),
                'avg_hours' => round((float) ($recForDate->avg('active_minutes') ?? 0) / 60, 1),
                'online_now' => collect($online)->where('state', 'online')->count(),
                'inactive_now' => collect($online)->where('state', 'inactive')->count(),
                'clients_active' => ClientPortalVisit::where('visited_on', $this->date)->distinct('user_id')->count('user_id'),
                'client_logins' => (int) ClientPortalVisit::where('visited_on', $this->date)->sum('hits'),
            ],
        ])->layout('layouts.app');
    }
};
?>

<div class="dashboard a-page" wire:poll.30s>
    <div class="page-header a-reveal">
        <div>
            <h1 class="mb-0">Attendance ERP</h1>
            <p class="mb-0">
                Daily attendance for every employee and client — {{ \Illuminate\Support\Carbon::parse($date)->format('l, M d, Y') }}
                @if ($isToday)
                    · <span style="color:#10b981;font-weight:700;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;"></span> {{ $kpi['online_now'] }} online</span>
                    @if ($kpi['inactive_now'])
                        · <span style="color:#f59e0b;font-weight:700;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;"></span> {{ $kpi['inactive_now'] }} inactive</span>
                    @endif
                @endif
            </p>
        </div>
        <div class="header-actions">
            <input type="date" class="form-control" wire:model.live="date" style="max-width:180px;">
            @if ($canEdit)
                <button class="btn btn-primary" wire:click="markAllPresent"
                        onclick="return confirm('Mark ALL employees present for this date?')">
                    <i class="fas fa-user-check"></i> Mark all present
                </button>
            @endif
        </div>
    </div>

    @if ($isToday)
        <div class="att-legend mb-3">
            <span><span class="att-dot att-dot-pulse" style="background:#10b981;"></span> Online</span>
            <span><span class="att-dot" style="background:#f59e0b;"></span> Inactive</span>
            <span><span class="att-dot" style="background:#d1d5db;"></span> Offline</span>
        </div>
    @endif

    @if (session('ok'))
        <div class="alert-flash alert-flash-success a-reveal">
            <i class="fas fa-check-circle"></i> {{ session('ok') }}
            <button class="alert-flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        </div>
    @endif

    {{-- KPIs --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 mb-4 a-stagger">
        <div class="col"><div class="stat-card"><div class="stat-icon green"><i class="fas fa-user-check"></i></div>
            <div class="stat-info"><h3>Present</h3><p class="stat-number">{{ $kpi['present'] }}</p><span class="stat-change">of {{ $kpi['headcount'] }} staff</span></div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-icon red"><i class="fas fa-user-xmark"></i></div>
            <div class="stat-info"><h3>Absent</h3><p class="stat-number">{{ $kpi['absent'] }}</p><span class="stat-change">{{ $kpi['leave'] }} on leave</span></div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="stat-info"><h3>Late</h3><p class="stat-number">{{ $kpi['late'] }}</p><span class="stat-change">{{ $kpi['not_marked'] }} not marked</span></div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-icon blue"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-info"><h3>Avg Hours</h3><p class="stat-number">{{ $kpi['avg_hours'] }}</p><span class="stat-change">worked today</span></div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-icon purple"><i class="fas fa-users"></i></div>
            <div class="stat-info"><h3>Clients Active</h3><p class="stat-number">{{ $kpi['clients_active'] }}</p><span class="stat-change">on the portal</span></div></div></div>
        <div class="col"><div class="stat-card"><div class="stat-icon teal"><i class="fas fa-right-to-bracket"></i></div>
            <div class="stat-info"><h3>Client Visits</h3><p class="stat-number">{{ $kpi['client_logins'] }}</p><span class="stat-change">portal visits today</span></div></div></div>
    </div>

    @if ($appeals->isNotEmpty())
        <div class="card mb-4 a-reveal" style="border:1px solid #fde68a;">
            <div class="card-header" style="background:#fffbeb;">
                <h3 class="card-title" style="color:#92400e;"><i class="fas fa-gavel me-2"></i> Absence Appeals <span class="badge bg-warning text-dark ms-1">{{ $appeals->count() }}</span></h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Employee</th><th>Date</th><th>Reason</th><th style="width:170px;">Decision</th></tr></thead>
                        <tbody>
                            @foreach ($appeals as $ap)
                                <tr wire:key="ap-{{ $ap->id }}">
                                    <td class="fw-semibold">{{ $ap->staff->name ?? 'Staff' }}<div><small class="text-muted">{{ $ap->staff->designation ?? '' }}</small></div></td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($ap->date)->format('M d, Y') }}</td>
                                    <td style="max-width:420px;">{{ $ap->message }}</td>
                                    <td>
                                        @if ($canApprove)
                                            <button class="btn btn-sm btn-success" wire:click="approveAppeal({{ $ap->id }})"><i class="fas fa-check"></i> Approve</button>
                                            <button class="btn btn-sm btn-outline-danger" wire:click="rejectAppeal({{ $ap->id }})"><i class="fas fa-xmark"></i></button>
                                        @else
                                            <span class="text-muted small">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link {{ $tab === 'employees' ? 'active' : '' }}" wire:click="$set('tab','employees')">
            <i class="fas fa-id-badge me-1"></i> Employees <span class="badge bg-secondary ms-1">{{ $kpi['headcount'] }}</span></button></li>
        <li class="nav-item"><button class="nav-link {{ $tab === 'clients' ? 'active' : '' }}" wire:click="$set('tab','clients')">
            <i class="fas fa-user-group me-1"></i> Clients <span class="badge bg-secondary ms-1">{{ $kpi['clients_active'] }}</span></button></li>
    </ul>

    @if ($tab === 'employees')
        <div class="card a-reveal">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title"><i class="fas fa-id-badge me-2"></i> Employee Attendance</h3>
                <input type="text" class="form-control" style="max-width:260px;" wire:model.live.debounce.300ms="q" placeholder="Search employee...">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th><th style="width:150px;">Status</th>
                                <th style="width:120px;">Check In</th><th style="width:120px;">Check Out</th>
                                <th style="width:90px;">Hours</th><th>Note</th><th style="width:90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staffList as $s)
                                @php $row = $rows[$s->id] ?? ['status' => 'present', 'check_in' => '', 'check_out' => '', 'note' => '', 'saved' => false]; @endphp
                                @php
                                    $pres = $online[$s->id] ?? ['state' => 'offline', 'label' => 'Offline', 'ago' => null, 'hours' => '—', 'auto' => false];
                                    $dotColor = ['online' => '#10b981', 'inactive' => '#f59e0b', 'offline' => '#d1d5db'][$pres['state']] ?? '#d1d5db';
                                @endphp
                                <tr wire:key="att-{{ $s->id }}" class="att-row att-row-{{ $row['status'] }}">
                                    <td>
                                        <span class="att-dot {{ $pres['state'] === 'online' ? 'att-dot-pulse' : '' }}" style="background:{{ $dotColor }};"
                                            title="{{ $pres['label'] }}"></span>
                                        <a href="{{ route('attendance.person', ['type' => 'staff', 'id' => $s->id]) }}" class="fw-semibold text-decoration-none">
                                            {{ $s->name }}
                                        </a>
                                        <span class="badge {{ $pres['state'] === 'online' ? 'bg-success' : ($pres['state'] === 'inactive' ? 'bg-warning text-dark' : 'bg-light text-muted border') }} ms-1" style="font-size:9px;">{{ $pres['label'] }}</span>
                                        <div>
                                            <small class="text-muted">{{ $s->designation ?: 'Employee' }}</small>
                                            @if ($pres['auto'])<small class="text-success ms-1" title="Hours auto-tracked from panel activity"><i class="fas fa-bolt"></i> {{ $pres['hours'] }}</small>@endif
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model="rows.{{ $s->id }}.status" @disabled(! $canEdit)>
                                            @foreach (\App\Models\AttendanceRecord::STATUSES as $st)
                                                <option value="{{ $st }}">{{ ucwords(str_replace('_', ' ', $st)) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="time" class="form-control form-control-sm" wire:model="rows.{{ $s->id }}.check_in" @disabled(! $canEdit)></td>
                                    <td><input type="time" class="form-control form-control-sm" wire:model="rows.{{ $s->id }}.check_out" @disabled(! $canEdit)></td>
                                    <td>
                                        @php
                                            $mins = 0;
                                            if ($row['check_in'] && $row['check_out']) {
                                                $mins = max(0, (strtotime($row['check_out']) - strtotime($row['check_in'])) / 60);
                                            }
                                        @endphp
                                        <span class="text-muted small">{{ $mins > 0 ? intdiv($mins, 60) . 'h ' . ($mins % 60) . 'm' : '—' }}</span>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" wire:model="rows.{{ $s->id }}.note" placeholder="—" @disabled(! $canEdit)></td>
                                    <td>
                                        @if ($canEdit)
                                            <button class="btn btn-sm {{ $row['saved'] ? 'btn-outline-success' : 'btn-primary' }}" wire:click="saveRow({{ $s->id }})">
                                                <i class="fas {{ $row['saved'] ? 'fa-check' : 'fa-save' }}"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">No employees found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card a-reveal">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-user-group me-2"></i> Client Portal Attendance</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Client</th><th>Company</th><th>First Seen</th><th>Last Seen</th><th class="text-center">Visits Today</th><th class="text-center">Days This Month</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($clientsToday as $v)
                                <tr wire:key="cv-{{ $v->id }}">
                                    <td>
                                        <a href="{{ route('attendance.person', ['type' => 'client', 'id' => $v->user_id]) }}" class="fw-semibold text-decoration-none">
                                            {{ $v->user->name ?? 'Unknown' }}
                                        </a>
                                        <div><small class="text-muted">{{ $v->user->email ?? '' }}</small></div>
                                    </td>
                                    <td>{{ $v->company->company_name ?? '—' }}</td>
                                    <td>{{ optional($v->first_seen_at)->format('H:i') ?? '—' }}</td>
                                    <td>{{ optional($v->last_seen_at)->format('H:i') ?? '—' }}</td>
                                    <td class="text-center"><span class="badge bg-primary">{{ $v->hits }}</span></td>
                                    <td class="text-center">{{ $clientMonth[$v->user_id] ?? 1 }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4">
                                    <i class="fas fa-user-clock fa-2x text-muted mb-2 d-block"></i>
                                    <span class="text-muted">No client visited the portal on this date.</span>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <style>
        .att-legend { display: flex; gap: 16px; font-size: 12px; font-weight: 600; color: #6b7280; }
        .att-legend span { display: inline-flex; align-items: center; gap: 6px; }
        .att-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; vertical-align: middle; }
        .att-dot-pulse { box-shadow: 0 0 0 3px rgba(16, 185, 129, .2); animation: att-pulse 1.8s ease-in-out infinite; }
        @keyframes att-pulse { 0%, 100% { box-shadow: 0 0 0 3px rgba(16,185,129,.2); } 50% { box-shadow: 0 0 0 5px rgba(16,185,129,.12); } }
        .att-row td { border-left: 3px solid transparent; transition: background-color .15s ease; }
        .att-row:hover td { background: #fafbfc; }
        .att-row-present td:first-child, .att-row-late td:first-child, .att-row-remote td:first-child, .att-row-half_day td:first-child { border-left-color: #10b981; }
        .att-row-absent td:first-child { border-left-color: #ef4444; }
        .att-row-leave td:first-child { border-left-color: #9ca3af; }
        .att-row-holiday td:first-child { border-left-color: #6366f1; }
        @media (prefers-reduced-motion: reduce) { .att-dot-pulse { animation: none; } }
    </style>
</div>
