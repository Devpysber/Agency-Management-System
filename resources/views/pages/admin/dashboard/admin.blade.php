@php
    $fmtMoney = fn ($v) => '$' . number_format((float) $v);
    $deltaUp = $revenueDelta !== null && $revenueDelta >= 0;
@endphp

<style>
    .dashboard .stat-card { display:flex; gap:14px; align-items:flex-start; }
    .dashboard .stat-info h3 { margin:0 0 4px; font-size:12px; font-weight:600; letter-spacing:.03em; text-transform:uppercase; color:#6b7280; }
    .dashboard .stat-number { margin:0; font-size:26px; font-weight:700; line-height:1.1; color:#111827; }
    .dashboard .stat-sub { display:inline-flex; align-items:center; gap:5px; margin-top:6px; font-size:12px; font-weight:600; color:#6b7280; }
    .dashboard .stat-sub.up { color:#059669; }
    .dashboard .stat-sub.down { color:#dc2626; }
    .dashboard .stat-sub.warn { color:#d97706; }
    .dashboard .team-strip { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:22px; }
    .dashboard .team-pill { flex:1 1 180px; display:flex; align-items:center; gap:12px; padding:14px 16px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; text-decoration:none; transition:border-color .15s, transform .15s; }
    .dashboard .team-pill:hover { border-color:#c7d2fe; transform:translateY(-2px); }
    .dashboard .team-pill .tp-dot { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:15px; color:#fff; flex-shrink:0; }
    .dashboard .team-pill .tp-val { font-size:20px; font-weight:700; color:#111827; line-height:1; }
    .dashboard .team-pill .tp-lbl { font-size:12px; color:#6b7280; margin-top:3px; }
    .dashboard .planned-list { display:flex; flex-direction:column; gap:2px; }
    .dashboard .planned-item { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px dashed #eef0f3; font-size:13px; }
    .dashboard .planned-item:last-child { border-bottom:0; }
    .dashboard .planned-item .pi-when { margin-left:auto; font-size:12px; color:#6b7280; white-space:nowrap; }
    .dashboard .pipeline-card { background:#f9fafb; border:1px solid #eef0f3; border-radius:8px; padding:8px 10px; font-size:13px; margin-bottom:6px; }
</style>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Contacts</h3>
            <p class="stat-number">{{ number_format($totalContacts) }}</p>
            @if ($newContactsThisWeek > 0)
                <span class="stat-sub up"><i class="fas fa-arrow-up"></i> {{ $newContactsThisWeek }} this week</span>
            @else
                <span class="stat-sub">No new contacts this week</span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-building"></i></div>
        <div class="stat-info">
            <h3>Total Companies</h3>
            <p class="stat-number">{{ number_format($totalCompanies) }}</p>
            <span class="stat-sub">Client accounts on file</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3>Active Deals</h3>
            <p class="stat-number">{{ number_format($activeDeals) }}</p>
            <span class="stat-sub {{ $dealsWonThisMonth > 0 ? 'up' : '' }}">
                <i class="fas fa-trophy"></i> {{ $dealsWonThisMonth }} won this month
            </span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-tasks"></i></div>
        <div class="stat-info">
            <h3>Pending Tasks</h3>
            <p class="stat-number">{{ number_format($pendingTasks) }}</p>
            @if ($overdueTasks > 0)
                <span class="stat-sub down"><i class="fas fa-triangle-exclamation"></i> {{ $overdueTasks }} overdue</span>
            @else
                <span class="stat-sub up"><i class="fas fa-check"></i> Nothing overdue</span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-envelope"></i></div>
        <div class="stat-info">
            <h3>Emails This Week</h3>
            <p class="stat-number">{{ number_format($emailsThisWeek) }}</p>
            <span class="stat-sub">{{ $upcomingMeetings }} meeting{{ $upcomingMeetings === 1 ? '' : 's' }} upcoming</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3>Revenue Collected</h3>
            <p class="stat-number">{{ $fmtMoney($totalRevenue) }}</p>
            @if ($revenueDelta !== null)
                <span class="stat-sub {{ $deltaUp ? 'up' : 'down' }}">
                    <i class="fas fa-arrow-{{ $deltaUp ? 'up' : 'down' }}"></i>
                    {{ abs($revenueDelta) }}% vs last month
                </span>
            @else
                <span class="stat-sub up">{{ $fmtMoney($revenueThisMonth) }} this month</span>
            @endif
        </div>
    </div>
</div>

<!-- Team presence right now -->
<div class="team-strip">
    <a href="{{ route('attendance.index') }}" class="team-pill">
        <span class="tp-dot" style="background:#10b981;"><i class="fas fa-circle-check"></i></span>
        <span><span class="tp-val">{{ $onShift }}</span><span class="tp-lbl">On shift now</span></span>
    </a>
    <a href="{{ route('attendance.index') }}" class="team-pill">
        <span class="tp-dot" style="background:#f59e0b;"><i class="fas fa-user-clock"></i></span>
        <span><span class="tp-val">{{ $inactiveNow }}</span><span class="tp-lbl">Inactive (tab closed)</span></span>
    </a>
    <a href="{{ route('attendance.index') }}" class="team-pill">
        <span class="tp-dot" style="background:{{ $pendingAppeals > 0 ? '#dc2626' : '#6b7280' }};"><i class="fas fa-gavel"></i></span>
        <span><span class="tp-val">{{ $pendingAppeals }}</span><span class="tp-lbl">Absence appeals to review</span></span>
    </a>
    <a href="{{ route('reports.sales') }}" class="team-pill">
        <span class="tp-dot" style="background:#4f46e5;"><i class="fas fa-file-invoice-dollar"></i></span>
        <span><span class="tp-val">{{ $fmtMoney($outstanding) }}</span><span class="tp-lbl">Payments outstanding</span></span>
    </a>
</div>

<!-- Charts & Activity Section -->
<div class="dashboard-grid">
    <div class="card" wire:ignore>
        <div class="card-header">
            <h3>Revenue Trend (Last 6 Months)</h3>
        </div>
        <div class="card-body">
            <div style="position:relative; height:260px;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Recent Activity</h3>
            <a href="{{ route('communications.activity-log') }}" class="view-all">View All</a>
        </div>
        <div class="card-body">
            <div class="activity-list">
                @forelse ($recentActivity as $activity)
                    <div class="activity-item">
                        <div class="activity-icon {{ ['email' => 'blue', 'call' => 'red', 'meeting' => 'purple'][$activity->type] ?? 'orange' }}">
                            <i class="fas {{ $activity->type_icon }}"></i>
                        </div>
                        <div class="activity-info">
                            <p><strong>{{ $activity->staff->name ?? 'Someone' }}</strong>
                                {{ $activity->type === 'email' ? 'sent an email' : ($activity->type === 'call' ? 'made a call' : 'logged a meeting') }}
                                @if($activity->contact) with {{ $activity->contact->full_name }} @endif
                            </p>
                            <span class="activity-time">{{ $activity->occurred_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No activity logged yet.</p>
                @endforelse
            </div>

            @if ($plannedActivity->isNotEmpty())
                <hr style="margin:14px 0 10px;border-color:#eef0f3;">
                <h3 style="font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#9ca3af;margin:0 0 6px;">Coming Up</h3>
                <div class="planned-list">
                    @foreach ($plannedActivity as $activity)
                        <div class="planned-item">
                            <i class="fas {{ $activity->type_icon }} text-muted"></i>
                            <span>
                                {{ $activity->type === 'email' ? 'Email' : ($activity->type === 'call' ? 'Call' : 'Meeting') }}
                                @if($activity->contact) with {{ $activity->contact->full_name }} @endif
                                @if($activity->staff) &middot; {{ $activity->staff->name }} @endif
                            </span>
                            <span class="pi-when">{{ $activity->occurred_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Deals by Stage -->
<div class="card full-width" wire:ignore>
    <div class="card-header">
        <h3>Deals by Stage</h3>
    </div>
    <div class="card-body">
        <div style="position:relative; height:240px;">
            <canvas id="dealStageChart"></canvas>
        </div>
    </div>
</div>

<!-- Deal Pipeline -->
<div class="card full-width">
    <div class="card-header">
        <h3>Deal Pipeline</h3>
        <a href="{{ route('deals.pipeline') }}" class="view-all">View Full Pipeline</a>
    </div>
    <div class="card-body">
        <div class="pipeline-grid">
            @foreach ($pipeline as $stageLabel => $deals)
                <div class="pipeline-stage">
                    <h4>{{ $stageLabel }} <span class="stage-count">{{ $deals->count() }}</span></h4>
                    <div class="pipeline-items">
                        @forelse ($deals as $deal)
                            <div class="pipeline-card">
                                <div class="d-flex justify-content-between">
                                    <span>{{ $deal->deal_name }}</span>
                                    <span class="fw-semibold">${{ number_format($deal->deal_value) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">None</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
