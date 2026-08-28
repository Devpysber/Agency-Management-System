<?php

use Livewire\Component;
use App\Models\Project;
use App\Models\Estimate;
use App\Models\Quotation;
use App\Models\ProjectPayment;
use App\Models\ProjectMilestone;
use App\Models\Communication;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\AccountInsight;
use App\Services\AccountInsights;

new class extends Component
{
    public $companyId;
    public $company;

    public ?string $aiError = null;

    public function mount()
    {
        $contact = auth()->user()->contact;
        $this->company = $contact?->company;
        $this->companyId = $this->company?->id;
    }

    public function generateInsight(AccountInsights $service): void
    {
        $this->aiError = null;

        if (! $this->company) {
            $this->aiError = 'No company is linked to your account.';
            return;
        }
        if (! $service->configured()) {
            $this->aiError = 'AI analysis is not configured yet.';
            return;
        }

        try {
            $service->forCompany($this->company, true);
            $this->dispatch('cp-toast', message: 'Analysis ready', type: 'success');
        } catch (\Throwable $e) {
            report($e);
            $this->aiError = $e->getMessage();
        }
    }

    /**
     * Percentage change between two periods, guarded against divide-by-zero.
     * Returns null when there is no meaningful comparison to show.
     */
    private function delta(float $current, float $previous): ?array
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? ['dir' => 'up', 'pct' => 100] : null;
        }
        $pct = (int) round((($current - $previous) / $previous) * 100);
        return [
            'dir' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'),
            'pct' => abs($pct),
        ];
    }

    public function render(AccountInsights $insights)
    {
        $companyId = $this->companyId;

        $aiConfigured = $insights->configured();
        $aiInsight = $companyId
            ? AccountInsight::where('company_id', $companyId)->latest()->first()
            : null;
        $aiStale = $aiInsight && $this->company && $insights->isStale($this->company, $aiInsight);

        $blank = [
            'kpis' => [],
            'recentProjects' => collect(),
            'upcomingMilestones' => collect(),
            'activity' => collect(),
            'upcomingMeetings' => collect(),
            'awaitingEstimates' => collect(),
            'awaitingQuotations' => collect(),
            'projectStatusLabels' => [], 'projectStatusValues' => [], 'projectStatusColors' => [],
            'paymentHistoryLabels' => [], 'paymentHistoryValues' => [],
            'aiConfigured' => $aiConfigured, 'aiInsight' => $aiInsight, 'aiStale' => false,
            'dueProjects' => collect(),
        ];

        if (!$companyId) {
            return $this->view($blank)->layout('layouts.client');
        }

        $projectIds = Project::where('company_id', $companyId)->pluck('id');

        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();

        // ---- KPI: active projects ----
        $activeProjects = Project::where('company_id', $companyId)
            ->whereIn('status', ['planning', 'in_progress'])->count();
        $newThisMonth = Project::where('company_id', $companyId)
            ->where('created_at', '>=', $thisMonthStart)->count();
        $newLastMonth = Project::where('company_id', $companyId)
            ->whereBetween('created_at', [$lastMonthStart, $thisMonthStart])->count();

        // ---- KPI: pending estimates / quotations ----
        $pendingEstimates = Estimate::where('company_id', $companyId)->where('status', 'sent')->count();
        $pendingQuotations = Quotation::where('company_id', $companyId)->where('status', 'quoted')->count();

        // ---- KPI: total paid + this vs last month ----
        $paidQuery = ProjectPayment::whereIn('project_id', $projectIds)->where('status', 'paid');
        $totalPaid = (float) (clone $paidQuery)->sum('amount');
        $paidThisMonth = (float) (clone $paidQuery)
            ->where(fn ($q) => $q->where('paid_at', '>=', $thisMonthStart)
                ->orWhere(fn ($q2) => $q2->whereNull('paid_at')->where('created_at', '>=', $thisMonthStart)))
            ->sum('amount');
        $paidLastMonth = (float) (clone $paidQuery)
            ->where(fn ($q) => $q->whereBetween('paid_at', [$lastMonthStart, $thisMonthStart])
                ->orWhere(fn ($q2) => $q2->whereNull('paid_at')->whereBetween('created_at', [$lastMonthStart, $thisMonthStart])))
            ->sum('amount');

        $outstanding = (float) ProjectPayment::whereIn('project_id', $projectIds)
            ->where('status', 'pending')->sum('amount');

        $kpis = [
            [
                'label' => 'Active Projects', 'value' => (string) $activeProjects,
                'icon' => 'fa-diagram-project', 'tone' => 'i-primary',
                'delta' => null,
                'deltaLabel' => $newThisMonth > 0
                    ? $newThisMonth . ' started this month'
                    : 'in planning or in progress',
            ],
            [
                'label' => 'Pending Estimates', 'value' => (string) $pendingEstimates,
                'icon' => 'fa-file-invoice', 'tone' => 'i-violet',
                'delta' => null, 'deltaLabel' => 'awaiting your response',
            ],
            [
                'label' => 'Pending Quotations', 'value' => (string) $pendingQuotations,
                'icon' => 'fa-file-signature', 'tone' => 'i-warning',
                'delta' => null, 'deltaLabel' => 'awaiting your response',
            ],
            [
                'label' => 'Total Paid', 'value' => \App\Support\Money::client($totalPaid),
                'icon' => 'fa-sack-dollar', 'tone' => 'i-success',
                'delta' => $this->delta($paidThisMonth, $paidLastMonth),
                'deltaLabel' => $outstanding > 0 ? \App\Support\Money::client($outstanding) . ' outstanding' : 'vs last month',
            ],
        ];

        // ---- Recent projects ----
        $recentProjects = Project::where('company_id', $companyId)
            ->orderByDesc('created_at')->limit(5)->get();

        // ---- Upcoming milestones ----
        $upcomingMilestones = ProjectMilestone::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(5)->get();

        // ---- Upcoming meetings & calls scheduled with this client ----
        $contactIds = Contact::where('company_id', $companyId)->pluck('id');
        $upcomingMeetings = CalendarEvent::where('status', 'scheduled')
            ->whereIn('event_type', ['meeting', 'call'])
            ->where('start_at', '>=', $now->copy()->subHours(2))
            ->where(fn ($q) => $q->whereIn('project_id', $projectIds)->orWhereIn('contact_id', $contactIds))
            ->with('project:id,name')
            ->orderBy('start_at')
            ->limit(6)->get();

        // ---- Activity feed: a merged, real event stream ----
        $events = collect();

        ProjectMilestone::whereIn('project_id', $projectIds)
            ->where('status', 'completed')->whereNotNull('completed_at')
            ->with('project:id,name')->latest('completed_at')->limit(8)->get()
            ->each(fn ($m) => $events->push([
                'icon' => 'fa-flag-checkered', 'tone' => 'ok',
                'text' => 'Milestone "' . $m->title . '" completed',
                'sub' => $m->project?->name, 'at' => $m->completed_at,
            ]));

        ProjectPayment::whereIn('project_id', $projectIds)
            ->with('project:id,name')->latest('created_at')->limit(8)->get()
            ->each(fn ($p) => $events->push([
                'icon' => 'fa-credit-card', 'tone' => $p->status === 'paid' ? 'ok' : 'muted',
                'text' => ucfirst($p->status) . ' payment of ' . $p->currency . ' ' . number_format((float) $p->amount, 2),
                'sub' => $p->project?->name, 'at' => $p->paid_at ?? $p->created_at,
            ]));

        Estimate::where('company_id', $companyId)->whereIn('status', ['sent', 'approved', 'rejected'])
            ->latest('updated_at')->limit(6)->get()
            ->each(fn ($e) => $events->push([
                'icon' => 'fa-file-invoice', 'tone' => $e->status === 'approved' ? 'ok' : ($e->status === 'rejected' ? 'bad' : 'muted'),
                'text' => 'Estimate ' . $e->estimate_number . ' — ' . $e->status,
                'sub' => \App\Support\Money::client((float) $e->total), 'at' => $e->updated_at,
            ]));

        Quotation::where('company_id', $companyId)->whereIn('status', ['quoted', 'accepted', 'rejected'])
            ->latest('updated_at')->limit(6)->get()
            ->each(fn ($q) => $events->push([
                'icon' => 'fa-file-signature', 'tone' => $q->status === 'accepted' ? 'ok' : ($q->status === 'rejected' ? 'bad' : 'muted'),
                'text' => 'Quotation "' . ($q->service_interest ?: 'enquiry') . '" — ' . $q->status,
                'sub' => $q->quoted_amount ? \App\Support\Money::client((float) $q->quoted_amount) : null, 'at' => $q->updated_at,
            ]));

        Communication::where('company_id', $companyId)->latest('occurred_at')->limit(6)->get()
            ->each(fn ($c) => $events->push([
                'icon' => $c->type_icon, 'tone' => 'muted',
                'text' => $c->subject ?: (ucfirst($c->type) . ' ' . ($c->direction ?? '')),
                'sub' => ucfirst($c->type), 'at' => $c->occurred_at ?? $c->created_at,
            ]));

        $activity = $events->filter(fn ($e) => $e['at'])->sortByDesc('at')->take(8)->values();

        // ---- Awaiting response ----
        $awaitingEstimates = Estimate::where('company_id', $companyId)
            ->where('status', 'sent')->orderByDesc('created_at')->limit(5)->get();
        $awaitingQuotations = Quotation::where('company_id', $companyId)
            ->where('status', 'quoted')->orderByDesc('created_at')->limit(5)->get();

        // ---- Project status doughnut ----
        $base = Project::where('company_id', $companyId);
        $statusColorMap = [
            'Planning' => '#6b7280', 'In Progress' => '#4f46e5', 'On Hold' => '#f59e0b',
            'Completed' => '#10b981', 'Cancelled' => '#ef4444',
        ];
        $statusCounts = array_filter([
            'Planning' => (clone $base)->planning()->count(),
            'In Progress' => (clone $base)->inProgress()->count(),
            'On Hold' => (clone $base)->onHold()->count(),
            'Completed' => (clone $base)->completed()->count(),
            'Cancelled' => (clone $base)->cancelled()->count(),
        ], fn ($c) => $c > 0);
        $projectStatusLabels = array_keys($statusCounts);
        $projectStatusValues = array_values($statusCounts);
        $projectStatusColors = array_map(fn ($l) => $statusColorMap[$l], $projectStatusLabels);

        // ---- Payment history, last 6 months ----
        $buckets = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i)->startOfMonth();
            $buckets[$m->format('Y-m')] = ['label' => $m->format('M'), 'total' => 0.0];
        }
        $earliest = $now->copy()->subMonths(5)->startOfMonth();
        $recentPaid = ProjectPayment::whereIn('project_id', $projectIds)
            ->where('status', 'paid')
            ->where(fn ($q) => $q->where('paid_at', '>=', $earliest)
                ->orWhere(fn ($q2) => $q2->whereNull('paid_at')->where('created_at', '>=', $earliest)))
            ->get(['amount', 'paid_at', 'created_at']);
        foreach ($recentPaid as $p) {
            $d = $p->paid_at ?? $p->created_at;
            if (!$d) continue;
            $k = $d->format('Y-m');
            if (isset($buckets[$k])) $buckets[$k]['total'] += (float) $p->amount;
        }

        return $this->view([
            'kpis' => $kpis,
            'recentProjects' => $recentProjects,
            'upcomingMilestones' => $upcomingMilestones,
            'activity' => $activity,
            'upcomingMeetings' => $upcomingMeetings,
            'awaitingEstimates' => $awaitingEstimates,
            'awaitingQuotations' => $awaitingQuotations,
            'projectStatusLabels' => $projectStatusLabels,
            'projectStatusValues' => $projectStatusValues,
            'projectStatusColors' => $projectStatusColors,
            'paymentHistoryLabels' => array_column($buckets, 'label'),
            'paymentHistoryValues' => array_column($buckets, 'total'),
            'aiConfigured' => $aiConfigured,
            'aiInsight' => $aiInsight,
            'aiStale' => $aiStale,
            'dueProjects' => Project::where('company_id', $companyId)
                ->whereNotNull('submission_due_at')
                ->where('status', '!=', 'completed')
                ->orderBy('submission_due_at')
                ->limit(3)->get(),
        ])->layout('layouts.client');
    }
};
?>

@php
    $badgeMap = ['bg-secondary' => 's-secondary', 'bg-primary' => 's-primary', 'bg-success' => 's-success',
        'bg-warning text-dark' => 's-warning', 'bg-warning' => 's-warning', 'bg-danger' => 's-danger', 'bg-info' => 's-info'];
    $toBadge = fn ($cls) => $badgeMap[$cls] ?? 's-secondary';
@endphp

<div wire:poll.30s>
    <div class="cp-page-head">
        <div>
            <h1>{{ $company->company_name ?? 'Welcome' }}</h1>
            <p>Here's an overview of your account activity and finances. <span class="cp-live">Live</span></p>
        </div>
        @if ($company)
            <a href="{{ route('client.projects') }}" wire:navigate class="cp-btn cp-btn-ghost">
                <i class="fas fa-diagram-project"></i> View all projects
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="cp-alert a-success">
            <i class="fas fa-circle-check"></i> {{ session('success') }}
            <button onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
        </div>
    @endif
    @if (session('error'))
        <div class="cp-alert a-error">
            <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
            <button onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
        </div>
    @endif

    @if (!$company)
        <div class="cp-alert a-warning">
            <i class="fas fa-circle-info"></i>
            No company is linked to your account yet. Please contact your account manager.
        </div>
    @else

    @foreach ($dueProjects as $dp)
        <div class="cp-deadline {{ $dp->is_overdue ? 'is-overdue' : '' }}"
             x-data="cpCountdown('{{ $dp->submission_due_at->toIso8601String() }}')" x-init="start()">
            <div class="cp-deadline-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <span class="cp-deadline-label">
                    <a href="{{ route('client.project-show', $dp->id) }}" wire:navigate style="color:inherit;">{{ $dp->name }}</a>
                    — submission deadline
                </span>
                <span class="cp-deadline-time" x-text="label"></span>
            </div>
            <span class="cp-deadline-date">{{ $dp->submission_due_at->format('M d, Y · H:i') }}</span>
        </div>
    @endforeach

    {{-- ==================== KPI row ==================== --}}
    <div class="cp-grid cols-4">
        @foreach ($kpis as $kpi)
            <div class="cp-kpi">
                <div class="cp-kpi-top">
                    <span class="cp-kpi-icon {{ $kpi['tone'] }}"><i class="fas {{ $kpi['icon'] }}"></i></span>
                    @if ($kpi['delta'])
                        <span class="cp-kpi-delta {{ $kpi['delta']['dir'] }}">
                            <i class="fas fa-arrow-{{ $kpi['delta']['dir'] === 'up' ? 'up' : ($kpi['delta']['dir'] === 'down' ? 'down' : 'right') }}"></i>
                            {{ $kpi['delta']['pct'] }}%
                        </span>
                    @endif
                </div>
                <div class="cp-kpi-label">{{ $kpi['label'] }}</div>
                <div class="cp-kpi-value">{{ $kpi['value'] }}</div>
                <div class="cp-kpi-label" style="margin-top:6px;font-size:11px;">{{ $kpi['deltaLabel'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ==================== AI insight ==================== --}}
    @php $sentIcon = ['positive' => 'fa-arrow-trend-up', 'neutral' => 'fa-circle-info', 'watch' => 'fa-triangle-exclamation']; @endphp
    <div class="cp-ai-card mt-20">
        @if ($aiError)
            <div class="cp-alert a-error" style="margin:16px;">
                <i class="fas fa-circle-exclamation"></i> {{ $aiError }}
            </div>
        @endif

        @if ($aiInsight)
            <div class="cp-ai-head">
                <span class="cp-ai-badge"><i class="fas fa-wand-magic-sparkles"></i> AI analysis</span>
                @if ($aiStale)
                    <span class="cp-badge s-warning" style="margin-left:8px;">
                        <i class="fas fa-triangle-exclamation"></i> Account data changed since this run
                    </span>
                @endif
                <div style="margin-left:auto;display:flex;gap:12px;align-items:center;">
                    @if ($aiConfigured)
                        <button class="cp-btn {{ $aiStale ? 'cp-btn-primary' : 'cp-btn-ghost' }} cp-btn-sm" wire:click="generateInsight"
                                wire:loading.attr="disabled" wire:target="generateInsight">
                            <span wire:loading.remove wire:target="generateInsight"><i class="fas fa-rotate"></i> {{ $aiStale ? 'Update analysis' : 'Refresh' }}</span>
                            <span wire:loading wire:target="generateInsight"><span class="cp-spin"></span> Analyzing…</span>
                        </button>
                    @endif
                    <a href="{{ route('client.insights') }}" wire:navigate class="cp-link">Full report <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="cp-ai-body">
                <div class="cp-ai-headline">{{ $aiInsight->headline }}</div>
                <div class="cp-ai-summary">{{ $aiInsight->summary }}</div>

                <div class="cp-ai-sections" style="margin-top:14px;">
                    @foreach (array_slice($aiInsight->sections, 0, 4) as $section)
                        <div class="cp-ai-section s-{{ $section['sentiment'] ?? 'neutral' }}">
                            <h4><i class="fas {{ $sentIcon[$section['sentiment'] ?? 'neutral'] ?? 'fa-circle-info' }}"></i> {{ $section['title'] }}</h4>
                            <p>{{ $section['body'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="cp-ai-meta">
                    <span><i class="fas fa-clock"></i> {{ $aiInsight->created_at->diffForHumans() }}</span>
                </div>
            </div>
        @elseif ($aiConfigured)
            <div class="cp-ai-setup">
                <i class="fas fa-wand-magic-sparkles"></i>
                <h4>Get an AI briefing on your account</h4>
                <p>A plain-English analysis of your delivery progress, cash flow and risks.</p>
                <div style="margin-top:14px;">
                    <button class="cp-btn cp-btn-primary" wire:click="generateInsight"
                            wire:loading.attr="disabled" wire:target="generateInsight">
                        <span wire:loading.remove wire:target="generateInsight"><i class="fas fa-wand-magic-sparkles"></i> Generate analysis</span>
                        <span wire:loading wire:target="generateInsight"><span class="cp-spin"></span> Analyzing…</span>
                    </button>
                </div>
            </div>
        @else
            <div class="cp-ai-setup">
                <i class="fas fa-wand-magic-sparkles"></i>
                <h4>AI analysis available</h4>
                <p>Ask your account manager to enable AI insights for this portal.</p>
            </div>
        @endif
    </div>

    {{-- ==================== Charts ==================== --}}
    <div class="cp-grid split-7-5 mt-20">
        <div class="cp-card" wire:ignore>
            <div class="cp-card-head">
                <h3><i class="fas fa-chart-column"></i> Payment History</h3>
                <span class="cp-badge s-secondary">Last 6 months</span>
            </div>
            <div class="cp-card-body">
                <div class="cp-chart" style="height:280px;">
                    <canvas id="cpPaymentChart"></canvas>
                </div>
            </div>
        </div>

        <div class="cp-card" wire:ignore>
            <div class="cp-card-head"><h3><i class="fas fa-chart-pie"></i> Projects by Status</h3></div>
            <div class="cp-card-body">
                @if (count($projectStatusLabels))
                    <div class="cp-chart" style="height:280px;">
                        <canvas id="cpStatusChart"></canvas>
                    </div>
                @else
                    <div class="cp-empty">
                        <i class="fas fa-chart-pie"></i>
                        <h6>No project data yet</h6>
                        <p>Charts appear once you have projects.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ==================== Recent projects + milestones ==================== --}}
    <div class="cp-grid split-7-5 mt-20">
        <div class="cp-card">
            <div class="cp-card-head">
                <h3><i class="fas fa-diagram-project"></i> Recent Projects</h3>
                <a href="{{ route('client.projects') }}" wire:navigate class="cp-link">View all <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="cp-card-body flush">
                <div class="cp-table-wrap">
                    <table class="cp-table">
                        <thead><tr><th>Project</th><th>Status</th><th>Progress</th></tr></thead>
                        <tbody>
                            @forelse ($recentProjects as $project)
                                <tr class="clickable" onclick="Livewire.navigate('{{ route('client.project-show', $project->id) }}')">
                                    <td class="t-strong">{{ $project->name }}</td>
                                    <td>
                                        <span class="cp-badge {{ $toBadge($project->status_badge['class']) }}">
                                            {{ $project->status_badge['icon'] }} {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="cp-progress-row">
                                            <div class="cp-progress"><span style="width: {{ (int) $project->progress }}%"></span></div>
                                            <small>{{ (int) $project->progress }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="cp-empty"><i class="fas fa-diagram-project"></i><h6>No projects yet</h6></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="cp-card">
            <div class="cp-card-head"><h3><i class="fas fa-flag-checkered"></i> Upcoming Milestones</h3></div>
            <div class="cp-card-body">
                @forelse ($upcomingMilestones as $ms)
                    @php $overdue = $ms->due_date && $ms->due_date->isPast(); @endphp
                    <div class="cp-feed-item">
                        <span class="cp-feed-icon" @style(['background:var(--cp-danger-soft);color:var(--cp-danger)' => $overdue])>
                            <i class="fas {{ $overdue ? 'fa-triangle-exclamation' : 'fa-flag' }}"></i>
                        </span>
                        <div class="cp-feed-body">
                            <p class="t-strong">{{ $ms->title }}</p>
                            <span>
                                {{ $ms->project?->name ?? 'Project' }} ·
                                {{ $overdue ? 'Overdue ' : 'Due ' }}{{ $ms->due_date?->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="cp-empty"><i class="fas fa-flag-checkered"></i><h6>No upcoming milestones</h6></div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ==================== Upcoming meetings & calls ==================== --}}
    @if ($upcomingMeetings->isNotEmpty())
    <div class="cp-card mt-20">
        <div class="cp-card-head"><h3><i class="fas fa-calendar-day"></i> Upcoming Meetings &amp; Calls</h3></div>
        <div class="cp-card-body">
            @foreach ($upcomingMeetings as $mt)
                @php
                    $link = $mt->meeting_url ?: (filter_var($mt->location, FILTER_VALIDATE_URL) ? $mt->location : null);
                    $place = $link ? null : $mt->location;
                @endphp
                <div class="cp-feed-item" style="align-items:flex-start;">
                    <span class="cp-feed-icon" style="background:var(--cp-primary-soft,rgba(99,102,241,.14));color:var(--cp-primary);">
                        <i class="fas {{ $mt->event_type === 'call' ? 'fa-phone' : 'fa-users' }}"></i>
                    </span>
                    <div class="cp-feed-body" style="flex:1;">
                        <p class="t-strong">{{ $mt->title }}</p>
                        <span>
                            {{ $mt->start_at->format('D, M j · g:i A') }}
                            @if ($mt->end_at) – {{ $mt->end_at->format('g:i A') }} @endif
                            @if ($place) · {{ $place }} @endif
                            @if ($mt->project) · {{ $mt->project->name }} @endif
                        </span>
                        @if ($mt->description)
                            <span class="d-block" style="color:var(--cp-text-faint);">{{ \Illuminate\Support\Str::limit($mt->description, 120) }}</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2" style="flex-shrink:0;">
                        @if ($mt->project)
                            <a href="{{ route('client.project-show', $mt->project_id) }}" wire:navigate class="cp-link" style="white-space:nowrap;">
                                Details
                            </a>
                        @endif
                        @if ($link)
                            <a href="{{ $link }}" target="_blank" rel="noopener"
                               style="white-space:nowrap;padding:6px 14px;border-radius:8px;background:var(--cp-primary);color:#fff;font-weight:600;font-size:13px;text-decoration:none;">
                                <i class="fas fa-video"></i> Join
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ==================== Activity + awaiting ==================== --}}
    <div class="cp-grid split-6-6 mt-20">
        <div class="cp-card">
            <div class="cp-card-head"><h3><i class="fas fa-wave-square"></i> Recent Activity</h3></div>
            <div class="cp-card-body">
                <div class="cp-feed">
                    @forelse ($activity as $ev)
                        @php $tone = ['ok' => 'var(--cp-success)', 'bad' => 'var(--cp-danger)', 'muted' => 'var(--cp-text-faint)'][$ev['tone']] ?? 'var(--cp-primary)'; @endphp
                        <div class="cp-feed-item">
                            <span class="cp-feed-icon" style="background:color-mix(in srgb,{{ $tone }} 14%,transparent);color:{{ $tone }};">
                                <i class="fas {{ $ev['icon'] }}"></i>
                            </span>
                            <div class="cp-feed-body">
                                <p class="t-strong">{{ $ev['text'] }}</p>
                                <span>{{ $ev['at']?->diffForHumans() }}{{ $ev['sub'] ? ' · ' . $ev['sub'] : '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="cp-empty"><i class="fas fa-wave-square"></i><h6>No recent activity</h6></div>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <div class="cp-card">
                <div class="cp-card-head">
                    <h3><i class="fas fa-file-invoice"></i> Estimates Awaiting Response</h3>
                    <a href="{{ route('client.estimates') }}" wire:navigate class="cp-link">All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="cp-card-body">
                    @forelse ($awaitingEstimates as $estimate)
                        <a href="{{ route('client.estimate-show', $estimate->id) }}" wire:navigate
                           class="cp-feed-item" style="text-decoration:none;">
                            <span class="cp-feed-icon"><i class="fas fa-file-invoice"></i></span>
                            <div class="cp-feed-body" style="flex:1;display:flex;justify-content:space-between;align-items:center;">
                                <div>
                                    <p class="t-strong">{{ $estimate->estimate_number }}</p>
                                    <span>@money((float) $estimate->total)</span>
                                </div>
                                <span class="cp-badge {{ $toBadge($estimate->status_badge['class']) }}">{{ ucfirst($estimate->status) }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="cp-empty"><i class="fas fa-file-invoice"></i><h6>Nothing awaiting response</h6></div>
                    @endforelse
                </div>
            </div>

            <div class="cp-card mt-20">
                <div class="cp-card-head">
                    <h3><i class="fas fa-file-signature"></i> Quotations Awaiting Response</h3>
                    <a href="{{ route('client.quotations') }}" wire:navigate class="cp-link">All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="cp-card-body">
                    @forelse ($awaitingQuotations as $quotation)
                        <a href="{{ route('client.quotation-show', $quotation->id) }}" wire:navigate
                           class="cp-feed-item" style="text-decoration:none;">
                            <span class="cp-feed-icon"><i class="fas fa-file-signature"></i></span>
                            <div class="cp-feed-body" style="flex:1;display:flex;justify-content:space-between;align-items:center;">
                                <div>
                                    <p class="t-strong">{{ $quotation->service_interest ?: $quotation->name }}</p>
                                    <span>{{ $quotation->quoted_amount ? \App\Support\Money::client((float) $quotation->quoted_amount) : 'Amount pending' }}</span>
                                </div>
                                <span class="cp-badge {{ $toBadge($quotation->status_badge['class']) }}">{{ ucfirst($quotation->status) }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="cp-empty"><i class="fas fa-file-signature"></i><h6>Nothing awaiting response</h6></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@script
<script>
    (function () {
        let charts = [];

        function destroy() {
            charts.forEach(c => { try { c.destroy(); } catch (e) {} });
            charts = [];
        }

        function build() {
            if (typeof Chart === 'undefined') return;
            destroy();
            if (window.clientPortal) window.clientPortal.applyChartDefaults();

            const colors = window.clientPortal ? window.clientPortal.chartColors()
                : { text: '#5b6270', grid: '#e7e8ef' };

            const payEl = document.getElementById('cpPaymentChart');
            if (payEl) {
                charts.push(new Chart(payEl, {
                    type: 'bar',
                    data: {
                        labels: @json($paymentHistoryLabels),
                        datasets: [{
                            label: 'Paid',
                            data: @json($paymentHistoryValues),
                            backgroundColor: 'rgba(79,70,229,0.85)',
                            hoverBackgroundColor: '#4f46e5',
                            borderRadius: 6,
                            maxBarThickness: 44,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: c => '$' + Number(c.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 })
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: colors.grid },
                                ticks: { callback: v => '$' + Number(v).toLocaleString() }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                }));
            }

            const statusEl = document.getElementById('cpStatusChart');
            if (statusEl) {
                charts.push(new Chart(statusEl, {
                    type: 'doughnut',
                    data: {
                        labels: @json($projectStatusLabels),
                        datasets: [{
                            data: @json($projectStatusValues),
                            backgroundColor: @json($projectStatusColors),
                            borderWidth: 2,
                            borderColor: colors.surface || '#fff',
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '68%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14, usePointStyle: true } } }
                    }
                }));
            }
        }

        build();
        document.addEventListener('cp:theme-changed', build);
        document.addEventListener('livewire:navigated', build, { once: true });
    })();
</script>
@endscript
