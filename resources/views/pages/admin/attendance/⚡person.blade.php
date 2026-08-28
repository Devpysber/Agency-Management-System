<?php

use Livewire\Component;
use App\Models\AttendanceRecord;
use App\Models\ClientPortalVisit;
use App\Models\staff;
use App\Models\User;
use Illuminate\Support\Carbon;

new class extends Component
{
    public string $type;   // staff | client
    public int $personId;
    public string $month;  // Y-m

    public function mount($type, $id): void
    {
        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $isSelf = $type === 'staff' && $user && staff::where('user_id', $user->id)->where('id', $id)->exists();
        $hasCompanyWideView = $user && $user->hasPermission('Attendance', 'View');
        abort_unless($isAdmin || $isSelf || $hasCompanyWideView, 403);
        abort_unless(in_array($type, ['staff', 'client'], true), 404);
        $this->type = $type;
        $this->personId = (int) $id;
        $this->month = now()->format('Y-m');
    }

    public function shift(int $delta): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonths($delta)->format('Y-m');
    }

    public function render()
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $person = $this->type === 'client' ? User::find($this->personId) : staff::find($this->personId);
        abort_unless($person, 404);

        // Build a day => record/visit map for the month
        $byDay = [];
        if ($this->type === 'staff') {
            AttendanceRecord::staff()->where('person_id', $this->personId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()->each(function ($r) use (&$byDay) {
                    $byDay[$r->date] = ['status' => $r->status, 'color' => $r->status_color, 'label' => $r->status_label,
                        'in' => optional($r->check_in)->format('H:i'), 'out' => optional($r->check_out)->format('H:i'),
                        'hours' => $r->workedHhMm(), 'note' => $r->note];
                });
        } else {
            ClientPortalVisit::where('user_id', $this->personId)
                ->whereBetween('visited_on', [$start->toDateString(), $end->toDateString()])
                ->get()->each(function ($v) use (&$byDay) {
                    $key = $v->visited_on instanceof Carbon ? $v->visited_on->toDateString() : (string) $v->visited_on;
                    $byDay[$key] = ['status' => 'present', 'color' => 'success', 'label' => 'Visited',
                        'in' => optional($v->first_seen_at)->format('H:i'), 'out' => optional($v->last_seen_at)->format('H:i'),
                        'hours' => $v->hits . ' visits', 'note' => null];
                });
        }

        // Calendar cells (leading blanks + days)
        $cells = [];
        for ($i = 0; $i < $start->dayOfWeekIso - 1; $i++) {
            $cells[] = null;
        }
        for ($d = 1; $d <= $end->day; $d++) {
            $date = $start->copy()->day($d);
            $cells[] = ['day' => $d, 'date' => $date->toDateString(), 'data' => $byDay[$date->toDateString()] ?? null,
                'future' => $date->isFuture(), 'weekend' => $date->isWeekend()];
        }

        $present = collect($byDay)->whereIn('status', ['present', 'late', 'remote', 'half_day'])->count();
        $absent = collect($byDay)->where('status', 'absent')->count();
        $leave = collect($byDay)->where('status', 'leave')->count();
        $late = collect($byDay)->where('status', 'late')->count();
        $workdays = collect(range(0, $end->day - 1))
            ->map(fn ($i) => $start->copy()->addDays($i))
            ->reject(fn ($d) => $d->isWeekend() || $d->isFuture())->count();

        return $this->view([
            'personName' => $person->name,
            'personMeta' => $this->type === 'client' ? ($person->email ?? '') : ($person->designation ?? 'Employee'),
            'cells' => $cells,
            'summary' => [
                'present' => $present, 'absent' => $absent, 'leave' => $leave, 'late' => $late,
                'workdays' => $workdays,
                'rate' => $workdays > 0 ? round($present / $workdays * 100) : 0,
                'recorded' => count($byDay),
            ],
        ])->layout('layouts.app');
    }
};
?>

<div class="dashboard a-page">
    <div class="page-header a-reveal">
        <div>
            <a href="{{ route('attendance.index') }}" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left"></i> Attendance ERP</a>
            <h1 class="mb-0">{{ $personName }}</h1>
            <p class="mb-0">{{ ucfirst($type) }} · {{ $personMeta }}</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" wire:click="shift(-1)"><i class="fas fa-chevron-left"></i></button>
            <span class="btn btn-light disabled">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</span>
            <button class="btn btn-secondary" wire:click="shift(1)"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <div class="row g-3 mb-4 a-stagger">
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div>
            <div class="stat-info"><h3>Present</h3><p class="stat-number">{{ $summary['present'] }}</p><span class="stat-change">of {{ $summary['workdays'] }} workdays</span></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon red"><i class="fas fa-xmark"></i></div>
            <div class="stat-info"><h3>Absent</h3><p class="stat-number">{{ $summary['absent'] }}</p><span class="stat-change">{{ $summary['leave'] }} on leave</span></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="stat-info"><h3>Late</h3><p class="stat-number">{{ $summary['late'] }}</p><span class="stat-change">days late</span></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-icon blue"><i class="fas fa-percent"></i></div>
            <div class="stat-info"><h3>Attendance</h3><p class="stat-number">{{ $summary['rate'] }}%</p><span class="stat-change">this month</span></div></div></div>
    </div>

    <div class="card a-reveal">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-days me-2"></i> {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h3></div>
        <div class="card-body">
            <div class="att-cal">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                    <div class="att-dow">{{ $dow }}</div>
                @endforeach
                @foreach ($cells as $cell)
                    @if (! $cell)
                        <div class="att-cell att-empty"></div>
                    @else
                        <div class="att-cell {{ $cell['weekend'] ? 'att-weekend' : '' }} {{ $cell['future'] ? 'att-future' : '' }} {{ $cell['date'] === now()->toDateString() ? 'att-today' : '' }} {{ $cell['data'] ? 'att-'.$cell['data']['color'] : '' }}"
                             @if ($cell['data']) title="{{ $cell['data']['label'] }}{{ $cell['data']['in'] ? ' · '.$cell['data']['in'].'–'.$cell['data']['out'] : '' }}" @endif>
                            <span class="att-num">{{ $cell['day'] }}</span>
                            @if ($cell['data'])
                                <span class="att-badge">{{ $cell['data']['label'] }}</span>
                                @if ($cell['data']['in'])<span class="att-time">{{ $cell['data']['in'] }}–{{ $cell['data']['out'] ?: '…' }}</span>@endif
                                @if ($cell['data']['hours'] && $cell['data']['hours'] !== '—')<span class="att-time">{{ $cell['data']['hours'] }}</span>@endif
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .att-cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 7px; }
        .att-dow { text-align: center; font-size: 10.5px; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; padding-bottom: 6px; }
        .att-cell {
            min-height: 86px; border: 1px solid #e9eaf0; border-radius: 11px; padding: 7px 8px;
            display: flex; flex-direction: column; gap: 3px; background: #fff;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }
        .att-cal.att-anim .att-cell { animation: a-fade-up .32s cubic-bezier(.4,0,.2,1) both; }
        .att-cal.att-anim .att-cell:nth-child(7n+1) { animation-delay: .00s }
        .att-cal.att-anim .att-cell:nth-child(7n+2) { animation-delay: .015s }
        .att-cal.att-anim .att-cell:nth-child(7n+3) { animation-delay: .03s }
        .att-cal.att-anim .att-cell:nth-child(7n+4) { animation-delay: .045s }
        .att-cal.att-anim .att-cell:nth-child(7n+5) { animation-delay: .06s }
        .att-cal.att-anim .att-cell:nth-child(7n+6) { animation-delay: .075s }
        .att-cal.att-anim .att-cell:nth-child(7n)   { animation-delay: .09s }
        .att-cell:not(.att-empty):not(.att-future):hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,24,40,.10); }
        .att-empty { border: 0; background: transparent; }
        .att-weekend { background: #fafbfc; border-style: dashed; }
        .att-future { opacity: .4; }
        .att-today { box-shadow: 0 0 0 2px #4f46e5; border-color: #4f46e5; }
        .att-num { font-size: 12px; font-weight: 800; color: #6b7280; }
        .att-badge { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; padding: 2px 6px; border-radius: 999px; align-self: flex-start; }
        .att-time { font-size: 10px; color: #6b7280; font-variant-numeric: tabular-nums; }
        .att-success { border-color: #a7f3d0; background: linear-gradient(180deg,#f0fdf4,#fff); } .att-success .att-badge { background: #dcfce7; color: #047857; }
        .att-warning { border-color: #fde68a; background: linear-gradient(180deg,#fffbeb,#fff); } .att-warning .att-badge { background: #fef3c7; color: #b45309; }
        .att-danger  { border-color: #fecaca; background: linear-gradient(180deg,#fef2f2,#fff); } .att-danger .att-badge  { background: #fee2e2; color: #b91c1c; }
        .att-info    { border-color: #bae6fd; background: linear-gradient(180deg,#f0f9ff,#fff); } .att-info .att-badge    { background: #e0f2fe; color: #0369a1; }
        .att-primary { border-color: #c7d2fe; background: linear-gradient(180deg,#eef2ff,#fff); } .att-primary .att-badge { background: #e0e7ff; color: #4338ca; }
        .att-secondary .att-badge { background: #f3f4f6; color: #6b7280; }
        .att-dark .att-badge { background: #374151; color: #fff; }
        @media (prefers-reduced-motion: reduce) { .att-cal.att-anim .att-cell { animation: none; } }
        body.tab-hidden .att-cal .att-cell { animation: none !important; }
    </style>
    <script>
        (function () {
            // Play the calendar entrance once per load, then disarm so month
            // navigation / background morphs don't re-animate.
            var c = document.querySelector('.att-cal');
            if (c) { c.classList.add('att-anim'); setTimeout(function () { c.classList.remove('att-anim'); }, 700); }
        })();
    </script>
</div>
