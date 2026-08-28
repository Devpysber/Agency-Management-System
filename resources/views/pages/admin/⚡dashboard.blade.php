<?php

use Livewire\Component;
use App\Models\staff;
use App\Models\Task;
use App\Models\CalendarEvent;
use App\Models\Communication;
use App\Models\deal;
use App\Models\Contact;
use App\Models\company;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayment;
use App\Models\PortfolioItem;
use App\Models\Quotation;

new class extends Component
{
    /**
     * Deal stage/status labels & colors, kept identical to
     * pages/admin/deals/*.blade.php so charts & badges read the same
     * everywhere in the app.
     */
    protected array $stageLabels = [
        'lead' => 'Lead',
        'qualified' => 'Qualified',
        'proposal' => 'Proposal',
        'negotiation' => 'Negotiation',
        'closed_won' => 'Won',
        'closed_lost' => 'Lost',
    ];

    protected array $stageColors = [
        'lead' => '#6b7280',
        'qualified' => '#4F46E5',
        'proposal' => '#0EA5E9',
        'negotiation' => '#F59E0B',
        'closed_won' => '#10B981',
        'closed_lost' => '#EF4444',
    ];

    public function markTaskComplete($id)
    {
        $staffMember = staff::where('user_id', auth()->id())->first();

        $task = Task::where('id', $id)
            ->when($staffMember, fn ($q) => $q->where('assigned_to', $staffMember->id))
            ->first();

        if (!$task) {
            session()->flash('error', 'Task not found.');
            return;
        }

        $task->markAsCompleted();
        session()->flash('success', 'Task marked as completed!');
    }

    public function markTaskInProgress($id)
    {
        $staffMember = staff::where('user_id', auth()->id())->first();

        $task = Task::where('id', $id)
            ->when($staffMember, fn ($q) => $q->where('assigned_to', $staffMember->id))
            ->first();

        if (!$task) {
            session()->flash('error', 'Task not found.');
            return;
        }

        $task->markAsInProgress();
        session()->flash('success', 'Task marked as in progress!');
    }

    public function render()
    {
        $user = auth()->user();
        // The admin is the leader, not tracked staff — ignore any linked staff row.
        $staffMember = $user->role === 'admin' ? null : staff::where('user_id', $user->id)->first();
        $designation = $staffMember?->designation;

        $data = match (true) {
            // Admin and CEO are one combined identity/panel — the technical
            // super-admin login IS the CEO's account, not a separate role.
            $designation === 'CEO' || $user->role === 'admin' => $this->ceoData($staffMember),
            $designation === 'COO' => $this->cooData($staffMember),
            $designation === 'HR & Admin Manager' => $this->hrData($staffMember),
            $designation === 'Account Manager' => $this->accountManagerData($staffMember),
            $designation === 'Business Development Manager' => $this->bdmData($staffMember),
            $designation === 'Project Manager' => $this->projectManagerData($staffMember),
            $designation === 'Developer' => $this->developerData($staffMember),
            $designation === 'Designer' => $this->designerData($staffMember),
            $designation === 'Sales Executive' => $this->salesExecutiveData($staffMember),
            $designation === 'Tech Lead' => $this->techLeadData($staffMember),
            $designation === 'QA Engineer' => $this->qaData($staffMember),
            $designation === 'AI/ML Engineer' => $this->aiEngineerData($staffMember),
            $designation === 'Marketing Manager' => $this->marketingManagerData($staffMember),
            $designation === 'Finance Manager' || $designation === 'Accountant' || $designation === 'Finance Executive' => $this->financeData($staffMember),
            $designation === 'Intern' => $this->internData($staffMember),
            default => $this->genericStaffData($staffMember),
        };

        $data['user'] = $user;
        $data['staffMember'] = $staffMember;
        $data['designation'] = $designation;

        // My attendance this month (read-only self view)
        $data['myAttendance'] = null;
        if ($staffMember) {
            $mStart = now()->startOfMonth()->toDateString();
            $recs = \App\Models\AttendanceRecord::staff()->where('person_id', $staffMember->id)
                ->whereBetween('date', [$mStart, now()->toDateString()])->get();
            $data['myAttendance'] = [
                'present' => $recs->whereIn('status', ['present', 'late', 'remote', 'half_day'])->count(),
                'absent' => $recs->where('status', 'absent')->count(),
                'leave' => $recs->where('status', 'leave')->count(),
                'late' => $recs->where('status', 'late')->count(),
                'hours' => round($recs->sum('active_minutes') / 60, 1),
                'presence' => \App\Models\AttendanceRecord::presenceLabel($staffMember->id),
                'presence_state' => \App\Models\AttendanceRecord::presenceState($staffMember->id)['state'],
            ];
        }

        return $this->view($data)->layout('layouts.app');
    }

    // ==================== SHARED HELPERS ====================

    /**
     * Last 6 months of paid ProjectPayment amounts, bucketed by month —
     * used for every "revenue trend" chart in this dashboard.
     */
    private function revenueTrend(?\Illuminate\Database\Eloquent\Builder $projectScope = null): array
    {
        $buckets = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i)->startOfMonth();
            $buckets[$month->format('Y-m')] = ['label' => $month->format('M Y'), 'total' => 0.0];
        }
        $earliest = now()->copy()->subMonths(5)->startOfMonth();

        $projectIds = $projectScope ? (clone $projectScope)->pluck('id') : null;

        $payments = ProjectPayment::where('status', 'paid')
            ->when($projectIds !== null, fn ($q) => $q->whereIn('project_id', $projectIds))
            ->where(function ($q) use ($earliest) {
                $q->where('paid_at', '>=', $earliest)
                  ->orWhere(function ($q2) use ($earliest) {
                      $q2->whereNull('paid_at')->where('created_at', '>=', $earliest);
                  });
            })
            ->get(['amount', 'paid_at', 'created_at']);

        foreach ($payments as $payment) {
            $date = $payment->paid_at ?? $payment->created_at;
            if (!$date) continue;
            $key = $date->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['total'] += (float) $payment->amount;
            }
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'values' => array_column($buckets, 'total'),
        ];
    }

    /**
     * Deal counts grouped by stage, in the order the pipeline UI uses.
     * Pass a query builder to scope it (e.g. to one owner's deals).
     */
    private function dealsByStage($query): array
    {
        $counts = (clone $query)
            ->selectRaw('deal_stage, count(*) as aggregate')
            ->groupBy('deal_stage')
            ->pluck('aggregate', 'deal_stage');

        $labels = [];
        $values = [];
        $colors = [];
        foreach ($this->stageLabels as $stage => $label) {
            $labels[] = $label;
            $values[] = (int) ($counts[$stage] ?? 0);
            $colors[] = $this->stageColors[$stage];
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $colors];
    }

    private function dealsWonThisMonth($query = null): int
    {
        $query = $query ? clone $query : deal::query();

        return $query->where('deal_status', 'won')
            ->where(function ($q) {
                $q->whereBetween('actual_close_date', [now()->startOfMonth(), now()->endOfMonth()])
                  ->orWhere(function ($q2) {
                      $q2->whereNull('actual_close_date')
                         ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()]);
                  });
            })
            ->count();
    }

    private function tasksForStaff($staffId)
    {
        return Task::when($staffId, fn ($q) => $q->where('assigned_to', $staffId), fn ($q) => $q->whereRaw('1=0'))
            ->orderBy('due_date')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function taskStatusBreakdown($tasks): array
    {
        $statuses = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
        $colors = ['pending' => '#6b7280', 'in_progress' => '#4F46E5', 'completed' => '#10B981', 'cancelled' => '#EF4444'];

        $labels = [];
        $values = [];
        $chartColors = [];
        foreach ($statuses as $key => $label) {
            $count = $tasks->where('status', $key)->count();
            if ($count > 0) {
                $labels[] = $label;
                $values[] = $count;
                $chartColors[] = $colors[$key];
            }
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $chartColors];
    }

    // ==================== CEO ====================

    private function ceoData($staffMember): array
    {
        $totalRevenue = ProjectPayment::where('status', 'paid')->sum('amount');
        $revenueThisMonth = ProjectPayment::where('status', 'paid')
            ->where(function ($q) {
                $q->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                  ->orWhere(function ($q2) {
                      $q2->whereNull('paid_at')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                  });
            })->sum('amount');

        $activeProjects = Project::whereIn('status', ['planning', 'in_progress'])->count();
        $totalStaff = staff::where('status', 'active')->count();
        $activePipelineValue = deal::where('deal_status', 'active')->sum('deal_value');

        $won = deal::where('deal_status', 'won')->count();
        $lost = deal::where('deal_status', 'lost')->count();
        $winRate = ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 1) : 0;

        $trend = $this->revenueTrend();
        $stageBreakdown = $this->dealsByStage(deal::query());

        $topDeals = deal::where('deal_status', 'active')
            ->orderByDesc('deal_value')
            ->with(['company', 'contact'])
            ->limit(5)
            ->get();

        $staffByDesignation = staff::where('status', 'active')
            ->selectRaw('designation, count(*) as aggregate')
            ->groupBy('designation')
            ->pluck('aggregate', 'designation');

        // Pending actions — this is CEO's approval authority made actionable,
        // not just a KPI card. Only shown/actionable if Attendance.Approve holds.
        $pendingAppeals = \App\Models\AttendanceAppeal::pending()
            ->with('staff:id,name,designation')
            ->latest()
            ->limit(8)
            ->get();

        // Alerts — overdue project milestones company-wide (CEO oversight,
        // not any one PM's problem once it's late enough to surface here).
        $overdueMilestones = ProjectMilestone::where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $outstanding = (float) ProjectPayment::whereIn('status', ['pending', 'overdue', 'partial'])->sum('amount');

        // Financial Overview: budget utilization is the honest proxy here —
        // there's no cost/expense model anywhere in the schema, so a real
        // profit/margin figure isn't computable. Not faking one.
        $activeBudget = (float) Project::whereIn('status', ['planning', 'in_progress'])->sum('budget');
        $budgetUtilization = $activeBudget > 0 ? round($totalRevenue / $activeBudget * 100, 1) : null;

        // Client Overview: at-risk = a company with a stale unpaid payment,
        // or one active but silent (no communication logged in 30 days).
        $atRiskClients = company::whereHas('projects', fn ($q) => $q->whereIn('status', ['planning', 'in_progress']))
            ->where(function ($q) {
                $q->whereHas('projects.payments', fn ($p) => $p->whereIn('status', ['pending', 'overdue'])
                    ->where('created_at', '<', now()->subDays(14)))
                  ->orWhereDoesntHave('communications', fn ($c) => $c->where('occurred_at', '>=', now()->subDays(30)));
            })
            ->limit(6)
            ->get(['id', 'company_name']);
        $activeClients = company::whereHas('projects', fn ($q) => $q->whereIn('status', ['planning', 'in_progress']))->count();
        $newClientsThisMonth = company::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        // Approvals: high-value quotations — visibility only (see docs/rbac-spec.md).
        $quotationThreshold = 10000;
        $highValueQuotations = Quotation::where('quoted_amount', '>=', $quotationThreshold)
            ->whereIn('status', ['quoted', 'pending'])
            ->orderByDesc('quoted_amount')
            ->limit(5)
            ->get();

        // Recent activity — company-wide, same feed shape as the admin dashboard.
        $recentActivity = Communication::with(['staff', 'contact'])
            ->where('occurred_at', '<=', now())
            ->orderByDesc('occurred_at')
            ->limit(6)
            ->get();

        // Merged in from the old separate admin dashboard (CEO and admin are
        // now one identity/panel per the user's explicit instruction — the
        // technical super-admin account IS the CEO's login, not a second role).
        $pendingTasks = Task::whereNotIn('status', ['completed', 'cancelled'])->count();
        $overdueTasks = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())->count();
        $onShift = 0;
        $inactiveNow = 0;
        foreach (staff::where('status', 'active')->with('user:id,role')->get() as $member) {
            if ($member->user && $member->user->role === 'admin') {
                continue;
            }
            $state = \App\Models\AttendanceRecord::presenceState($member->id)['state'];
            if ($state === 'online') $onShift++;
            elseif ($state === 'inactive') $inactiveNow++;
        }

        return [
            'type' => 'ceo',
            'pageTitle' => 'CEO Dashboard',
            'pageSubtitle' => $staffMember ? "Company-wide overview, {$staffMember->name}." : 'Company-wide overview.',
            'totalRevenue' => $totalRevenue,
            'revenueThisMonth' => $revenueThisMonth,
            'activeProjects' => $activeProjects,
            'totalStaff' => $totalStaff,
            'activePipelineValue' => $activePipelineValue,
            'winRate' => $winRate,
            'dealsWonThisMonth' => $this->dealsWonThisMonth(),
            'revenueTrendLabels' => $trend['labels'],
            'revenueTrendValues' => $trend['values'],
            'stageLabels' => $stageBreakdown['labels'],
            'stageValues' => $stageBreakdown['values'],
            'stageColors' => $stageBreakdown['colors'],
            'topDeals' => $topDeals,
            'staffByDesignation' => $staffByDesignation,
            'pendingAppeals' => $pendingAppeals,
            'overdueMilestones' => $overdueMilestones,
            'outstanding' => $outstanding,
            'budgetUtilization' => $budgetUtilization,
            'atRiskClients' => $atRiskClients,
            'activeClients' => $activeClients,
            'newClientsThisMonth' => $newClientsThisMonth,
            'highValueQuotations' => $highValueQuotations,
            'quotationThreshold' => $quotationThreshold,
            'recentActivity' => $recentActivity,
            'canApproveAttendance' => auth()->user()->hasPermission('Attendance', 'Approve'),
            'pendingTasks' => $pendingTasks,
            'overdueTasks' => $overdueTasks,
            'onShift' => $onShift,
            'inactiveNow' => $inactiveNow,
        ];
    }

    // CEO dashboard inline approval — same authorization + logic as the
    // Attendance ERP's approveAppeal/rejectAppeal (App\Models\AttendanceAppeal
    // is the single source of truth; this just gives CEO a one-click path
    // from the dashboard instead of a detour through the ERP).
    public function approveAppealFromDashboard(int $appealId): void
    {
        abort_unless(auth()->user()->hasPermission('Attendance', 'Approve'), 403);

        $appeal = \App\Models\AttendanceAppeal::with('staff')->find($appealId);
        if (! $appeal || $appeal->status !== 'pending') {
            return;
        }

        $rec = \App\Models\AttendanceRecord::firstOrNew([
            'person_type' => 'staff', 'person_id' => $appeal->staff_id, 'date' => $appeal->date,
        ]);
        $rec->status = 'present';
        $rec->source = 'manual';
        $rec->recorded_by = auth()->id();
        $rec->note = trim(($rec->note ? $rec->note . ' · ' : '') . 'Appeal approved');
        $rec->recomputeWorkedMinutes();
        $fullDay = ($appeal->staff->daily_hours ?? 8) * 60;
        if (! $rec->worked_minutes) $rec->worked_minutes = $fullDay;
        if (! $rec->active_minutes) $rec->active_minutes = $fullDay;
        $rec->save();

        $appeal->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        session()->flash('ok', $appeal->staff->name . ' marked present — appeal approved.');
    }

    public function rejectAppealFromDashboard(int $appealId): void
    {
        abort_unless(auth()->user()->hasPermission('Attendance', 'Approve'), 403);

        $appeal = \App\Models\AttendanceAppeal::find($appealId);
        if ($appeal && $appeal->status === 'pending') {
            $appeal->update([
                'status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
                'review_note' => 'Declined by ' . auth()->user()->name,
            ]);
            session()->flash('ok', 'Appeal declined.');
        }
    }

    // ==================== COO ====================

    private function cooData($staffMember): array
    {
        // 1. Operations Overview
        $activeProjects = Project::whereIn('status', ['planning', 'in_progress'])->count();
        $onHoldProjects = Project::where('status', 'on_hold')->count();
        $openTasks = Task::whereNotIn('status', ['completed', 'cancelled'])->count();
        $overdueTasks = Task::overdue()->count();

        // 2. Project Health
        $overdueMilestones = ProjectMilestone::where('status', '!=', 'completed')
            ->where('due_date', '<', now())->with('project:id,name')->orderBy('due_date')->limit(8)->get();
        $projectStatusCounts = [
            'Planning' => Project::planning()->count(), 'In Progress' => Project::inProgress()->count(),
            'On Hold' => Project::onHold()->count(), 'Completed' => Project::completed()->count(),
        ];

        // 3. Team Workload — open task count per active staff member, so an
        // overloaded or idle person is visible at a glance.
        $workload = staff::where('status', 'active')
            ->withCount(['tasks as open_tasks_count' => fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled'])])
            ->orderByDesc('open_tasks_count')
            ->limit(8)
            ->get(['id', 'name', 'designation']);

        // 4. Resource Allocation — who's on how many active projects right now.
        $allocation = staff::where('status', 'active')
            ->withCount(['projects as active_projects_count' => fn ($q) => $q->whereIn('status', ['planning', 'in_progress'])])
            ->get(['id', 'name', 'designation'])
            ->filter(fn ($s) => $s->active_projects_count > 0)
            ->sortByDesc('active_projects_count')
            ->take(8)
            ->values();

        // 5. Task Health — company-wide status breakdown.
        $taskBreakdown = $this->taskStatusBreakdown(Task::query()->get());

        // 6. Delayed Work
        $delayedTasks = Task::overdue()->with('assignedTo:id,name')->orderBy('due_date')->limit(8)->get();

        // 7. Client Issues — same signal as CEO's at-risk clients (stale
        // unpaid invoice or silent 30+ days), COO's lens is "needs an
        // operational fix", not the financial angle.
        $clientIssues = company::whereHas('projects', fn ($q) => $q->whereIn('status', ['planning', 'in_progress']))
            ->where(function ($q) {
                $q->whereHas('projects.payments', fn ($p) => $p->whereIn('status', ['pending', 'overdue'])->where('created_at', '<', now()->subDays(14)))
                  ->orWhereDoesntHave('communications', fn ($c) => $c->where('occurred_at', '>=', now()->subDays(30)));
            })->limit(6)->get(['id', 'company_name']);

        // 10. Escalations — no dedicated escalation-ticket model exists yet;
        // represented as work overdue long enough that it's past the point
        // of a routine follow-up (visibility only, see docs/rbac-spec.md).
        $escalations = Task::overdue()->where('due_date', '<', now()->subDays(3))
            ->with('assignedTo:id,name')->orderBy('due_date')->limit(6)->get();

        return [
            'type' => 'coo',
            'pageTitle' => 'Operations Dashboard',
            'pageSubtitle' => "Delivery, workload, and operational health, {$staffMember?->name}.",
            'activeProjects' => $activeProjects,
            'onHoldProjects' => $onHoldProjects,
            'openTasks' => $openTasks,
            'overdueTasks' => $overdueTasks,
            'overdueMilestones' => $overdueMilestones,
            'projectStatusLabels' => array_keys(array_filter($projectStatusCounts)),
            'projectStatusValues' => array_values(array_filter($projectStatusCounts)),
            'workload' => $workload,
            'allocation' => $allocation,
            'taskStatusLabels' => $taskBreakdown['labels'],
            'taskStatusValues' => $taskBreakdown['values'],
            'taskStatusColors' => $taskBreakdown['colors'],
            'delayedTasks' => $delayedTasks,
            'clientIssues' => $clientIssues,
            'escalations' => $escalations,
        ];
    }

    // ==================== HR & ADMIN MANAGER ====================

    private function hrData($staffMember): array
    {
        // 1. Staff Directory / headcount
        $totalActive = staff::where('status', 'active')->count();
        $totalInactive = staff::where('status', 'inactive')->count();
        $byDesignation = staff::where('status', 'active')->selectRaw('designation, count(*) as aggregate')
            ->groupBy('designation')->pluck('aggregate', 'designation');

        // Joining/Exit — recent joiners (last 30d) as a proxy for both;
        // there's no `exit_date`/offboarding field in the schema, so exits
        // aren't tracked yet — flagged, not fabricated.
        $recentJoiners = staff::where('joining_date', '>=', now()->subDays(30))
            ->orderByDesc('joining_date')->limit(6)->get(['id', 'name', 'designation', 'joining_date']);

        // Attendance today, company-wide (HR owns this per the spec).
        $today = now()->toDateString();
        $todayRecords = \App\Models\AttendanceRecord::staff()->forDate($today)->get();
        $presentToday = $todayRecords->whereIn('status', ['present', 'late', 'remote', 'half_day'])->count();
        $absentToday = $todayRecords->where('status', 'absent')->count();
        $notMarkedToday = max(0, $totalActive - $todayRecords->count());

        // Pending attendance appeals — HR is the day-to-day reviewer (CEO
        // is the escalation path, see rbac-spec.md).
        $pendingAppeals = \App\Models\AttendanceAppeal::pending()->with('staff:id,name,designation')->latest()->limit(8)->get();

        return [
            'type' => 'hr',
            'pageTitle' => 'HR & Admin Dashboard',
            'pageSubtitle' => "Staff, attendance, and workforce health, {$staffMember?->name}.",
            'totalActive' => $totalActive,
            'totalInactive' => $totalInactive,
            'byDesignation' => $byDesignation,
            'recentJoiners' => $recentJoiners,
            'presentToday' => $presentToday,
            'absentToday' => $absentToday,
            'notMarkedToday' => $notMarkedToday,
            'pendingAppeals' => $pendingAppeals,
            'canApproveAttendance' => auth()->user()->hasPermission('Attendance', 'Approve'),
        ];
    }

    // ==================== ACCOUNT MANAGER ====================

    private function accountManagerData($staffMember): array
    {
        // "My Clients" — companies whose contacts are assigned to this AM,
        // or whose projects this AM is attached to as team (best available
        // ownership signal; there's no direct company.account_manager_id yet).
        $staffId = $staffMember?->id;
        $myContacts = Contact::where('assigned_to', $staffId)->with('company')->get();
        $myCompanyIds = $myContacts->pluck('company_id')->filter()->unique();

        $myCompanies = company::whereIn('id', $myCompanyIds)->get();
        $myProjects = Project::whereIn('company_id', $myCompanyIds)
            ->whereIn('status', ['planning', 'in_progress'])->with('company')->get();

        $upcomingMeetings = CalendarEvent::where('event_type', 'meeting')->upcoming()
            ->where(function ($q) use ($myCompanyIds) { $q->whereIn('project_id', Project::whereIn('company_id', $myCompanyIds)->pluck('id')); })
            ->limit(5)->get();

        $recentComms = Communication::whereIn('company_id', $myCompanyIds)
            ->where('occurred_at', '<=', now())->orderByDesc('occurred_at')->limit(6)->with('contact')->get();

        // Client health — same at-risk signal, scoped to this AM's book.
        $atRisk = company::whereIn('id', $myCompanyIds)
            ->where(function ($q) {
                $q->whereHas('projects.payments', fn ($p) => $p->whereIn('status', ['pending', 'overdue'])->where('created_at', '<', now()->subDays(14)))
                  ->orWhereDoesntHave('communications', fn ($c) => $c->where('occurred_at', '>=', now()->subDays(30)));
            })->get();

        $openEstimates = \App\Models\Estimate::whereIn('company_id', $myCompanyIds)->whereIn('status', ['draft', 'sent'])->count();
        $openQuotations = Quotation::whereIn('status', ['pending', 'quoted'])
            ->whereIn('contact_id', $myContacts->pluck('id'))->count();

        return [
            'type' => 'account-manager',
            'pageTitle' => 'Account Manager Dashboard',
            'pageSubtitle' => "Your client book, {$staffMember?->name}.",
            'myCompanies' => $myCompanies,
            'myContacts' => $myContacts,
            'myProjects' => $myProjects,
            'atRisk' => $atRisk,
            'upcomingMeetings' => $upcomingMeetings,
            'recentComms' => $recentComms,
            'openEstimates' => $openEstimates,
            'openQuotations' => $openQuotations,
            'clientCount' => $myCompanies->count(),
        ];
    }

    // ==================== BUSINESS DEVELOPMENT MANAGER ====================

    private function bdmData($staffMember): array
    {
        // Sales-team-wide view (not "my" — BDM manages the team, unlike
        // Sales Executive who only sees assigned records).
        $pipelineByStage = $this->dealsByStage(deal::query());
        $activePipelineValue = deal::where('deal_status', 'active')->sum('deal_value');
        $wonThisMonth = $this->dealsWonThisMonth();
        $won = deal::where('deal_status', 'won')->count();
        $lost = deal::where('deal_status', 'lost')->count();
        $conversionRate = ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 1) : 0;

        // Sales team roster + their open deal load (workload, not just a list).
        $salesTeam = staff::where('status', 'active')
            ->whereIn('designation', ['Sales Executive', 'Business Development Manager'])
            ->withCount(['deals as open_deals_count' => fn ($q) => $q->where('deal_status', 'active')])
            ->get(['id', 'name', 'designation']);

        // Follow-ups due — deals with no recent activity, a reasonable proxy
        // for "needs a follow-up" without a dedicated follow-up/task-per-deal
        // model (flagged, not fabricated).
        $followUpsDue = deal::where('deal_status', 'active')
            ->where('updated_at', '<', now()->subDays(5))
            ->orderBy('updated_at')->limit(8)->with('company')->get();

        $topDeals = deal::where('deal_status', 'active')->orderByDesc('deal_value')->with('company')->limit(6)->get();

        $newLeadsThisWeek = Contact::where('lead_status', 'new')
            ->whereBetween('created_at', [now()->startOfWeek(), now()])->count();

        return [
            'type' => 'bdm',
            'pageTitle' => 'Business Development Dashboard',
            'pageSubtitle' => "Sales team performance, {$staffMember?->name}.",
            'stageLabels' => $pipelineByStage['labels'],
            'stageValues' => $pipelineByStage['values'],
            'stageColors' => $pipelineByStage['colors'],
            'activePipelineValue' => $activePipelineValue,
            'wonThisMonth' => $wonThisMonth,
            'conversionRate' => $conversionRate,
            'salesTeam' => $salesTeam,
            'followUpsDue' => $followUpsDue,
            'topDeals' => $topDeals,
            'newLeadsThisWeek' => $newLeadsThisWeek,
        ];
    }

    // ==================== PROJECT MANAGER ====================

    private function projectManagerData($staffMember): array
    {
        $userId = auth()->id();
        $projectsQuery = Project::where('created_by', $userId);

        $activeProjects = (clone $projectsQuery)->whereIn('status', ['planning', 'in_progress'])->count();
        $completedProjects = (clone $projectsQuery)->where('status', 'completed')->count();
        $onHoldProjects = (clone $projectsQuery)->where('status', 'on_hold')->count();

        $myProjectIds = (clone $projectsQuery)->pluck('id');

        $overdueMilestones = ProjectMilestone::whereIn('project_id', $myProjectIds)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        $upcomingMilestones = ProjectMilestone::whereIn('project_id', $myProjectIds)
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->with('project')
            ->limit(6)
            ->get();

        $statusCounts = [
            'Planning' => (clone $projectsQuery)->planning()->count(),
            'In Progress' => (clone $projectsQuery)->inProgress()->count(),
            'On Hold' => (clone $projectsQuery)->onHold()->count(),
            'Completed' => (clone $projectsQuery)->completed()->count(),
            'Cancelled' => (clone $projectsQuery)->cancelled()->count(),
        ];
        $statusCounts = array_filter($statusCounts, fn ($c) => $c > 0);
        $statusColors = ['Planning' => '#6b7280', 'In Progress' => '#4F46E5', 'On Hold' => '#F59E0B', 'Completed' => '#10B981', 'Cancelled' => '#EF4444'];

        $activeProjectsList = (clone $projectsQuery)
            ->whereIn('status', ['planning', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $myTasks = $this->tasksForStaff($staffMember?->id);

        // Team Assignment + Workload — everyone on my active projects, and
        // how many open tasks each of them is carrying right now.
        $teamIds = staff::whereHas('projects', fn ($q) => $q->whereIn('projects.id', $myProjectIds))
            ->where('status', 'active')
            ->withCount(['tasks as open_tasks_count' => fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled'])])
            ->get(['id', 'name', 'designation']);

        // Project Budget — budgeted vs collected, across my active projects.
        $totalBudget = (float) (clone $projectsQuery)->whereIn('status', ['planning', 'in_progress'])->sum('budget');
        $collected = (float) ProjectPayment::whereIn('project_id', $myProjectIds)->where('status', 'paid')->sum('amount');
        $outstanding = (float) ProjectPayment::whereIn('project_id', $myProjectIds)
            ->whereIn('status', ['pending', 'overdue', 'partial'])->sum('amount');

        // Risks — a computed flag, not a stored entity (no risk-register
        // model exists): overdue milestone, or past its own submission
        // deadline, or on_hold. Visibility only, see docs/rbac-spec.md.
        $atRiskProjects = (clone $projectsQuery)->whereIn('status', ['planning', 'in_progress', 'on_hold'])
            ->where(function ($q) {
                $q->where('status', 'on_hold')
                  ->orWhere('submission_due_at', '<', now())
                  ->orWhereHas('milestones', fn ($m) => $m->where('status', '!=', 'completed')->where('due_date', '<', now()));
            })->limit(6)->get();

        // Client Updates — recent communications across my projects' companies.
        $myCompanyIds = (clone $projectsQuery)->pluck('company_id')->filter()->unique();
        $clientUpdates = Communication::whereIn('company_id', $myCompanyIds)
            ->where('occurred_at', '<=', now())->orderByDesc('occurred_at')->limit(6)->with('contact')->get();

        return [
            'type' => 'project-manager',
            'pageTitle' => 'Project Manager Dashboard',
            'pageSubtitle' => "Projects you manage, {$staffMember?->name}.",
            'activeProjects' => $activeProjects,
            'completedProjects' => $completedProjects,
            'onHoldProjects' => $onHoldProjects,
            'overdueMilestones' => $overdueMilestones,
            'upcomingMilestones' => $upcomingMilestones,
            'projectStatusLabels' => array_keys($statusCounts),
            'projectStatusValues' => array_values($statusCounts),
            'projectStatusColors' => array_map(fn ($l) => $statusColors[$l], array_keys($statusCounts)),
            'activeProjectsList' => $activeProjectsList,
            'myTasks' => $myTasks,
            'myOpenTasksCount' => $myTasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'team' => $teamIds,
            'totalBudget' => $totalBudget,
            'collected' => $collected,
            'outstanding' => $outstanding,
            'atRiskProjects' => $atRiskProjects,
            'clientUpdates' => $clientUpdates,
        ];
    }

    // ==================== DEVELOPER ====================

    private function developerData($staffMember): array
    {
        $staffId = $staffMember?->id;
        $tasks = $this->tasksForStaff($staffId);

        $breakdown = $this->taskStatusBreakdown($tasks);

        $completionTrendLabels = [];
        $completionTrendValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->copy()->subDays($i);
            $completionTrendLabels[] = $day->format('D');
            $completionTrendValues[] = Task::where('assigned_to', $staffId)
                ->whereDate('completed_at', $day->toDateString())
                ->count();
        }

        $upcomingEvents = CalendarEvent::when($staffId, fn ($q) => $q->where('assigned_to', $staffId), fn ($q) => $q->whereRaw('1=0'))
            ->upcoming()
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        // My Development Tasks / Bugs — owns assigned bugs (fixing) same as tasks.
        $myBugs = \App\Models\Bug::where('assigned_to', $staffId)->with('project')->orderByDesc('created_at')->get();
        $qaHandoff = (clone $myBugs)->where('status', 'qa_retest'); // submitted, waiting on QA
        $failedRetests = (clone $myBugs)->where('status', 'failed');

        return [
            'type' => 'developer',
            'pageTitle' => 'Developer Dashboard',
            'pageSubtitle' => "Your tasks and deadlines, {$staffMember?->name}.",
            'tasks' => $tasks,
            'openTasksCount' => $tasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'inProgressTasksCount' => $tasks->where('status', 'in_progress')->count(),
            'completedTasksCount' => $tasks->where('status', 'completed')->count(),
            'overdueTasksCount' => $tasks->filter(fn ($t) => $t->isOverdue())->count(),
            'taskStatusLabels' => $breakdown['labels'],
            'taskStatusValues' => $breakdown['values'],
            'taskStatusColors' => $breakdown['colors'],
            'completionTrendLabels' => $completionTrendLabels,
            'completionTrendValues' => $completionTrendValues,
            'upcomingEvents' => $upcomingEvents,
            'myBugs' => $myBugs->whereNotIn('status', ['verified', 'closed'])->take(6),
            'myBugsCount' => $myBugs->whereNotIn('status', ['verified', 'closed'])->count(),
            'qaHandoffCount' => $qaHandoff->count(),
            'failedRetests' => $failedRetests,
        ];
    }

    // ==================== DESIGNER ====================

    private function designerData($staffMember): array
    {
        $staffId = $staffMember?->id;
        $tasks = $this->tasksForStaff($staffId);
        $breakdown = $this->taskStatusBreakdown($tasks);

        $totalPortfolioItems = PortfolioItem::count();
        $publishedPortfolioItems = PortfolioItem::published()->count();
        $draftPortfolioItems = PortfolioItem::where('status', 'draft')->count();
        $recentPortfolioItems = PortfolioItem::orderByDesc('created_at')->limit(5)->get();

        $upcomingEvents = CalendarEvent::when($staffId, fn ($q) => $q->where('assigned_to', $staffId), fn ($q) => $q->whereRaw('1=0'))
            ->upcoming()
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        // Client Feedback — comms logged against this designer's work is the
        // closest real signal (no dedicated feedback/comment-on-design model
        // exists). Revision Requests: no "revision_required" task status
        // exists either — proxy is an in_progress task that's gone overdue
        // (implies it bounced back for rework). Both flagged, not fabricated.
        $clientFeedback = Communication::where('staff_id', $staffId)
            ->where('occurred_at', '<=', now())->orderByDesc('occurred_at')->limit(6)->get();
        $revisionLikely = $tasks->where('status', 'in_progress')->filter(fn ($t) => $t->isOverdue());

        return [
            'type' => 'designer',
            'pageTitle' => 'Designer Dashboard',
            'pageSubtitle' => "Your tasks and the studio's portfolio, {$staffMember?->name}.",
            'tasks' => $tasks,
            'openTasksCount' => $tasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completedTasksCount' => $tasks->where('status', 'completed')->count(),
            'overdueTasksCount' => $tasks->filter(fn ($t) => $t->isOverdue())->count(),
            'taskStatusLabels' => $breakdown['labels'],
            'taskStatusValues' => $breakdown['values'],
            'taskStatusColors' => $breakdown['colors'],
            'totalPortfolioItems' => $totalPortfolioItems,
            'publishedPortfolioItems' => $publishedPortfolioItems,
            'draftPortfolioItems' => $draftPortfolioItems,
            'recentPortfolioItems' => $recentPortfolioItems,
            'upcomingEvents' => $upcomingEvents,
            'clientFeedback' => $clientFeedback,
            'revisionLikely' => $revisionLikely,
        ];
    }

    // ==================== SALES EXECUTIVE ====================

    private function salesExecutiveData($staffMember): array
    {
        $staffId = $staffMember?->id;

        // Owns ASSIGNED opportunities — assigned_to (real ownership), not
        // created_by (just whoever typed the row in; those can differ, e.g.
        // a BDM hands a lead to this rep).
        $myDealsQuery = deal::where('assigned_to', $staffId);
        $myDealsCount = (clone $myDealsQuery)->count();
        $myPipelineValue = (clone $myDealsQuery)->where('deal_status', 'active')->sum('deal_value');
        $myWon = (clone $myDealsQuery)->where('deal_status', 'won')->count();
        $myLost = (clone $myDealsQuery)->where('deal_status', 'lost')->count();
        $myWinRate = ($myWon + $myLost) > 0 ? round($myWon / ($myWon + $myLost) * 100, 1) : 0;

        // My Leads — contacts assigned to me that haven't converted to a deal yet.
        $myLeads = Contact::where('assigned_to', $staffId)
            ->whereIn('lead_status', ['new', 'contacted', 'qualified'])
            ->orderByDesc('created_at')->limit(6)->get();
        $myContactsCount = Contact::where('assigned_to', $staffId)->count();
        $myCompanies = company::whereIn('id', Contact::where('assigned_to', $staffId)->pluck('company_id')->filter()->unique())->get();

        $stageBreakdown = $this->dealsByStage($myDealsQuery);

        $myOpenDeals = (clone $myDealsQuery)
            ->where('deal_status', 'active')
            ->orderByDesc('deal_value')
            ->with(['company', 'contact'])
            ->limit(5)
            ->get();

        // Follow-ups — my active deals untouched 5+ days (same proxy signal
        // as BDM's team-wide view, scoped to just this rep).
        $followUpsDue = (clone $myDealsQuery)->where('deal_status', 'active')
            ->where('updated_at', '<', now()->subDays(5))
            ->orderBy('updated_at')->limit(6)->with('company')->get();

        $recentCommunications = Communication::when($staffId, fn ($q) => $q->where('staff_id', $staffId), fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('occurred_at')
            ->limit(6)
            ->with('contact')
            ->get();

        $upcomingMeetings = CalendarEvent::where('event_type', 'meeting')->upcoming()
            ->where('assigned_to', $staffId)->limit(5)->get();

        // Sales Activity — calls/emails/meetings logged by me this week (raw
        // activity volume, distinct from Sales Performance's outcome metrics).
        $activityThisWeek = Communication::where('staff_id', $staffId)
            ->whereBetween('occurred_at', [now()->startOfWeek(), now()])->count();

        return [
            'type' => 'sales-executive',
            'pageTitle' => 'Sales Executive Dashboard',
            'pageSubtitle' => "Your assigned leads and deals, {$staffMember?->name}.",
            'myDealsCount' => $myDealsCount,
            'myPipelineValue' => $myPipelineValue,
            'myWinRate' => $myWinRate,
            'dealsWonThisMonth' => $this->dealsWonThisMonth($myDealsQuery),
            'myContactsCount' => $myContactsCount,
            'myLeads' => $myLeads,
            'myCompanies' => $myCompanies,
            'stageLabels' => $stageBreakdown['labels'],
            'stageValues' => $stageBreakdown['values'],
            'stageColors' => $stageBreakdown['colors'],
            'myOpenDeals' => $myOpenDeals,
            'followUpsDue' => $followUpsDue,
            'recentCommunications' => $recentCommunications,
            'upcomingMeetings' => $upcomingMeetings,
            'activityThisWeek' => $activityThisWeek,
        ];
    }

    // ==================== TECH LEAD ====================

    private function techLeadData($staffMember): array
    {
        // Developer Team + Development Tasks — the dev team's workload,
        // company-wide (Tech Lead owns technical execution, not just their own).
        $devTeam = staff::where('status', 'active')
            ->whereIn('designation', ['Developer', 'Developer Intern'])
            ->withCount(['tasks as open_tasks_count' => fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled'])])
            ->get(['id', 'name', 'designation']);

        $openTasks = Task::whereIn('assigned_to', $devTeam->pluck('id'))->whereNotIn('status', ['completed', 'cancelled'])->count();

        // Bugs — code review / QA queue / technical risk all live here.
        // Scoped SQL queries instead of loading the whole table (was
        // Bug::with([...])->get() + Collection filtering — fine at a
        // handful of rows, not at scale).
        $openBugsCount = \App\Models\Bug::whereNotIn('status', ['verified', 'closed'])->count();
        $qaQueue = \App\Models\Bug::where('status', 'qa_retest')->with(['assignedTo', 'project'])->limit(8)->get();
        $qaQueueCount = \App\Models\Bug::where('status', 'qa_retest')->count();
        $criticalBugsCount = \App\Models\Bug::whereNotIn('status', ['verified', 'closed'])->where('severity', 'critical')->count();
        // Technical Risks: computed — critical/high bugs open 5+ days.
        $technicalRisks = \App\Models\Bug::whereNotIn('status', ['verified', 'closed'])
            ->whereIn('severity', ['critical', 'high'])
            ->where('created_at', '<', now()->subDays(5))
            ->with(['assignedTo', 'project'])
            ->limit(6)->get();

        // Release Readiness — computed signal, not a stored entity: no
        // critical bugs open + nothing stuck in qa_retest.
        $releaseReady = $criticalBugsCount === 0 && $qaQueueCount === 0;

        $recentBugs = \App\Models\Bug::with(['assignedTo', 'project'])->orderByDesc('created_at')->limit(8)->get();

        return [
            'type' => 'tech-lead',
            'pageTitle' => 'Tech Lead Dashboard',
            'pageSubtitle' => "Technical execution across the dev team, {$staffMember?->name}.",
            'devTeam' => $devTeam,
            'openTasks' => $openTasks,
            'openBugsCount' => $openBugsCount,
            'qaQueue' => $qaQueue,
            'qaQueueCount' => $qaQueueCount,
            'criticalBugsCount' => $criticalBugsCount,
            'technicalRisks' => $technicalRisks,
            'releaseReady' => $releaseReady,
            'recentBugs' => $recentBugs,
        ];
    }

    // ==================== QA ENGINEER ====================

    private function qaData($staffMember): array
    {
        // Scoped SQL queries instead of loading the whole bugs table (was
        // Bug::with([...])->get() + Collection filtering).
        $testingQueue = \App\Models\Bug::where('status', 'qa_retest')
            ->with(['assignedTo', 'project', 'reportedBy'])->get(); // fixed by dev, waiting on QA
        $openBugsCount = \App\Models\Bug::whereNotIn('status', ['verified', 'closed'])->count();
        $verifiedCount = \App\Models\Bug::where('verified_by', $staffMember?->id)->count();
        $failedCount = \App\Models\Bug::where('status', 'failed')->count();

        $passRate = ($verifiedCount + $failedCount) > 0
            ? round($verifiedCount / ($verifiedCount + $failedCount) * 100, 1)
            : 0;

        $recentBugs = \App\Models\Bug::with(['assignedTo', 'project', 'reportedBy'])
            ->orderByDesc('updated_at')->limit(8)->get();

        return [
            'type' => 'qa',
            'pageTitle' => 'QA Dashboard',
            'pageSubtitle' => "Testing queue and QA approvals, {$staffMember?->name}.",
            'testingQueue' => $testingQueue,
            'testingQueueCount' => $testingQueue->count(),
            'openBugsCount' => $openBugsCount,
            'verifiedCount' => $verifiedCount,
            'passRate' => $passRate,
            'recentBugs' => $recentBugs,
        ];
    }

    // ==================== AI/ML ENGINEER ====================

    private function aiEngineerData($staffMember): array
    {
        // No AI-specific data model exists (Experiments/Models/Evaluations/
        // Costs/Deployments) — scoped to what's real: assigned tasks + project
        // visibility. See docs/rbac-spec.md.
        $staffId = $staffMember?->id;
        $tasks = $this->tasksForStaff($staffId);
        $breakdown = $this->taskStatusBreakdown($tasks);

        return [
            'type' => 'ai-engineer',
            'pageTitle' => 'AI/ML Engineer Dashboard',
            'pageSubtitle' => "Your AI/ML implementation tasks, {$staffMember?->name}.",
            'tasks' => $tasks,
            'openTasksCount' => $tasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completedTasksCount' => $tasks->where('status', 'completed')->count(),
            'taskStatusLabels' => $breakdown['labels'],
            'taskStatusValues' => $breakdown['values'],
            'taskStatusColors' => $breakdown['colors'],
        ];
    }

    // ==================== MARKETING MANAGER ====================

    private function marketingManagerData($staffMember): array
    {
        // No Campaign/Lead-gen/Social/SEO/Ad-spend/ROI model exists — scoped
        // to what's real: Portfolio/Testimonials/Blog (content) + Communications
        // (client-facing activity, the closest "campaign performance" proxy).
        $publishedPortfolio = PortfolioItem::published()->count();
        $draftPortfolio = PortfolioItem::where('status', 'draft')->count();
        $totalTestimonials = \App\Models\Testimonial::count();
        $pendingTestimonials = \App\Models\Testimonial::where('status', '!=', 'approved')->count();
        $totalBlogPosts = \App\Models\BlogPost::count();
        $publishedBlogPosts = \App\Models\BlogPost::whereNotNull('published_at')->count();

        $recentContent = PortfolioItem::orderByDesc('created_at')->limit(3)->get()
            ->concat(\App\Models\BlogPost::orderByDesc('created_at')->limit(3)->get());

        return [
            'type' => 'marketing-manager',
            'pageTitle' => 'Marketing Manager Dashboard',
            'pageSubtitle' => "Content and marketing activity, {$staffMember?->name}.",
            'publishedPortfolio' => $publishedPortfolio,
            'draftPortfolio' => $draftPortfolio,
            'totalTestimonials' => $totalTestimonials,
            'pendingTestimonials' => $pendingTestimonials,
            'totalBlogPosts' => $totalBlogPosts,
            'publishedBlogPosts' => $publishedBlogPosts,
            'recentContent' => $recentContent,
        ];
    }

    // ==================== FINANCE ====================

    /** Finance owns invoice creation/payment recording — marks a pending/
     * partial/overdue ProjectPayment as paid. */
    public function recordPayment($id)
    {
        abort_unless(auth()->user()->hasPermission('Finance', 'Create'), 403);
        $payment = ProjectPayment::whereIn('status', ['pending', 'partial', 'overdue'])->findOrFail($id);
        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        session()->flash('success', 'Payment recorded.');
    }

    /** Finance owns refund approval — only a Finance role with Approve
     * (Finance Manager) can reverse an already-paid record. */
    public function approveRefund($id)
    {
        abort_unless(auth()->user()->hasPermission('Finance', 'Approve'), 403);
        $payment = ProjectPayment::where('status', 'paid')->findOrFail($id);
        $payment->update(['status' => 'refunded']);
        session()->flash('success', 'Refund approved.');
    }

    private function financeData($staffMember): array
    {
        // ProjectPayment IS the invoice/payment record in this schema — no
        // separate Invoice/Expense entity exists. See docs/rbac-spec.md.
        $totalPaid = (float) ProjectPayment::where('status', 'paid')->sum('amount');
        $outstanding = (float) ProjectPayment::whereIn('status', ['pending', 'partial'])->sum('amount');
        $overdue = ProjectPayment::where('status', 'overdue')->with('project.company')->get();
        $overdueTotal = (float) $overdue->sum('amount');

        $recentPayments = ProjectPayment::where('status', 'paid')->orderByDesc('paid_at')->limit(8)->with('project.company')->get();
        $pendingPayments = ProjectPayment::whereIn('status', ['pending', 'partial'])->orderBy('created_at')->limit(8)->with('project.company')->get();

        // Client Financial Accounts — outstanding grouped by company.
        $byCompany = ProjectPayment::whereIn('status', ['pending', 'overdue', 'partial'])
            ->with('project.company')->get()
            ->groupBy(fn ($p) => $p->project->company->company_name ?? 'Unknown')
            ->map(fn ($rows) => $rows->sum('amount'))
            ->sortDesc()->take(6);

        return [
            'type' => 'finance',
            'pageTitle' => 'Finance Dashboard',
            'pageSubtitle' => "Invoices, payments, and reconciliation, {$staffMember?->name}.",
            'totalPaid' => $totalPaid,
            'outstanding' => $outstanding,
            'overdueTotal' => $overdueTotal,
            'overdueCount' => $overdue->count(),
            'overduePayments' => $overdue->take(8),
            'recentPayments' => $recentPayments,
            'pendingPayments' => $pendingPayments,
            'byCompany' => $byCompany,
            'canRecord' => auth()->user()->hasPermission('Finance', 'Create'),
            'canApprove' => auth()->user()->hasPermission('Finance', 'Approve'),
        ];
    }

    // ==================== INTERN ====================

    private function internData($staffMember): array
    {
        // Highly restricted: assigned work + own attendance/calendar only.
        $staffId = $staffMember?->id;
        $tasks = $this->tasksForStaff($staffId);
        $myProjects = Project::whereHas('staff', fn ($q) => $q->where('staff.id', $staffId))
            ->whereIn('status', ['planning', 'in_progress'])->get();

        $upcomingEvents = CalendarEvent::when($staffId, fn ($q) => $q->where('assigned_to', $staffId), fn ($q) => $q->whereRaw('1=0'))
            ->upcoming()->orderBy('start_at')->limit(5)->get();

        return [
            'type' => 'intern',
            'pageTitle' => 'My Dashboard',
            'pageSubtitle' => "Your assigned work, {$staffMember?->name}.",
            'tasks' => $tasks,
            'openTasksCount' => $tasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completedTasksCount' => $tasks->where('status', 'completed')->count(),
            'overdueTasksCount' => $tasks->filter(fn ($t) => $t->isOverdue())->count(),
            'myProjects' => $myProjects,
            'upcomingEvents' => $upcomingEvents,
        ];
    }

    // ==================== ADMIN (org-wide, no staff record) ====================

    private function adminData(): array
    {
        $now = now();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();

        $totalContacts = Contact::count();
        $totalCompanies = company::count();
        $activeDeals = deal::where('deal_status', 'active')->count();
        $pendingTasks = Task::whereNotIn('status', ['completed', 'cancelled'])->count();
        $overdueTasks = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $now->toDateString())
            ->count();

        $totalRevenue = (float) ProjectPayment::where('status', 'paid')->sum('amount');
        $revenueThisMonth = (float) ProjectPayment::where('status', 'paid')
            ->where('paid_at', '>=', $monthStart)->sum('amount');
        $revenueLastMonth = (float) ProjectPayment::where('status', 'paid')
            ->whereBetween('paid_at', [$lastMonthStart, $monthStart])->sum('amount');
        $revenueDelta = $revenueLastMonth > 0
            ? (int) round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100)
            : null;
        $outstanding = (float) ProjectPayment::whereIn('status', ['pending', 'overdue', 'partial'])->sum('amount');

        // Cap "this week" windows at now so future-dated rows never inflate the count.
        $emailsThisWeek = Communication::where('type', 'email')
            ->whereBetween('occurred_at', [$weekStart, $now])->count();
        $newContactsThisWeek = Contact::whereBetween('created_at', [$weekStart, $now])->count();
        $upcomingMeetings = CalendarEvent::where('event_type', 'meeting')->upcoming()->count();

        // Live team presence — the admin's own linked staff row (if any) is not tracked.
        $onShift = 0;
        $inactiveNow = 0;
        foreach (staff::where('status', 'active')->with('user:id,role')->get() as $member) {
            if ($member->user && $member->user->role === 'admin') {
                continue;
            }
            $state = \App\Models\AttendanceRecord::presenceState($member->id)['state'];
            if ($state === 'online') {
                $onShift++;
            } elseif ($state === 'inactive') {
                $inactiveNow++;
            }
        }
        $pendingAppeals = \App\Models\AttendanceAppeal::pending()->count();

        $trend = $this->revenueTrend();
        $stageBreakdown = $this->dealsByStage(deal::query());

        $pipeline = [];
        foreach ($this->stageLabels as $stage => $label) {
            $pipeline[$label] = deal::where('deal_stage', $stage)->orderByDesc('created_at')->limit(3)->get();
        }

        // Only communications that have actually happened count as "recent activity".
        $recentActivity = Communication::with(['staff', 'contact'])
            ->where('occurred_at', '<=', $now)
            ->orderByDesc('occurred_at')
            ->limit(6)
            ->get();

        // Future-dated communications are shown separately as what's coming up.
        $plannedActivity = Communication::with(['staff', 'contact'])
            ->where('occurred_at', '>', $now)
            ->orderBy('occurred_at')
            ->limit(4)
            ->get();

        return [
            'type' => 'admin',
            'pageTitle' => 'Dashboard',
            'pageSubtitle' => "Welcome back, {$this->firstName()}! Here's what's happening across the agency.",
            'totalContacts' => $totalContacts,
            'totalCompanies' => $totalCompanies,
            'activeDeals' => $activeDeals,
            'totalRevenue' => $totalRevenue,
            'revenueThisMonth' => $revenueThisMonth,
            'revenueDelta' => $revenueDelta,
            'outstanding' => $outstanding,
            'pendingTasks' => $pendingTasks,
            'overdueTasks' => $overdueTasks,
            'emailsThisWeek' => $emailsThisWeek,
            'newContactsThisWeek' => $newContactsThisWeek,
            'dealsWonThisMonth' => $this->dealsWonThisMonth(),
            'upcomingMeetings' => $upcomingMeetings,
            'onShift' => $onShift,
            'inactiveNow' => $inactiveNow,
            'pendingAppeals' => $pendingAppeals,
            'revenueTrendLabels' => $trend['labels'],
            'revenueTrendValues' => $trend['values'],
            'stageLabels' => $stageBreakdown['labels'],
            'stageValues' => $stageBreakdown['values'],
            'stageColors' => $stageBreakdown['colors'],
            'pipeline' => $pipeline,
            'recentActivity' => $recentActivity,
            'plannedActivity' => $plannedActivity,
        ];
    }

    // ==================== GENERIC STAFF (manager/staff, no matching designation) ====================

    private function genericStaffData($staffMember): array
    {
        $staffId = $staffMember?->id;
        $tasks = $this->tasksForStaff($staffId);

        $upcomingEvents = CalendarEvent::when($staffId, fn ($q) => $q->where('assigned_to', $staffId), fn ($q) => $q->whereRaw('1=0'))
            ->upcoming()
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        $recentCommunications = Communication::when($staffId, fn ($q) => $q->where('staff_id', $staffId), fn ($q) => $q->whereRaw('1=0'))
            ->orderByDesc('occurred_at')
            ->limit(5)
            ->get();

        return [
            'type' => 'generic',
            'pageTitle' => 'Dashboard',
            'pageSubtitle' => $staffMember ? "Welcome back, {$staffMember->name}." : 'No staff profile is linked to your account yet.',
            'tasks' => $tasks,
            'openTasksCount' => $tasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completedTasksCount' => $tasks->where('status', 'completed')->count(),
            'overdueTasksCount' => $tasks->filter(fn ($t) => $t->isOverdue())->count(),
            'upcomingEvents' => $upcomingEvents,
            'recentCommunications' => $recentCommunications,
        ];
    }

    private function firstName(): string
    {
        return explode(' ', auth()->user()->name)[0] ?? auth()->user()->name;
    }
};
?>
<div wire:poll.20s="$refresh">
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header a-reveal">
            <div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $pageSubtitle }}</p>
            </div>
            <div class="header-actions">
                <span class="badge bg-success-subtle text-success" style="font-weight:600;padding:6px 10px;">
                    <i class="fas fa-circle" style="font-size:6px;vertical-align:middle;animation:a-pulse 1.6s ease infinite;"></i>
                    Live
                </span>
                <button class="btn btn-secondary">
                    <i class="fas fa-calendar-alt"></i> Today: {{ date('M d, Y') }}
                </button>
            </div>
        </div>

        @if ($staffMember)
            <div class="card mb-4 a-reveal" style="border:1px solid #e5e7eb;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#4f46e5,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;">
                            {{ strtoupper(substr($staffMember->name, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-bold">
                                {{ $staffMember->name }}
                                @if ($myAttendance)
                                    @php $b = ['online' => 'bg-success', 'inactive' => 'bg-warning text-dark', 'offline' => 'bg-light text-muted border'][$myAttendance['presence_state']] ?? 'bg-light'; @endphp
                                    <span class="badge {{ $b }} ms-1"><i class="fas fa-circle" style="font-size:7px;vertical-align:middle;"></i> {{ $myAttendance['presence'] }}</span>
                                @endif
                            </h5>
                            <div class="text-muted small">
                                {{ $staffMember->designation }} ·
                                {{ ucwords(str_replace('_', ' ', $staffMember->employment_type ?? 'full_time')) }} ·
                                Joined {{ optional($staffMember->joining_date)->format('M Y') ?? '—' }}
                            </div>
                        </div>
                        <a href="{{ route('attendance.person', ['type' => 'staff', 'id' => $staffMember->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-calendar-days"></i> My attendance calendar
                        </a>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-6 col-md-3"><div class="text-muted small">Aadhaar</div><div class="fw-semibold">{{ $staffMember->masked_aadhar ?? '—' }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted small">PAN</div><div class="fw-semibold">{{ $staffMember->masked_pan ?? '—' }}</div></div>
                        @if (($staffMember->employment_type ?? '') === 'intern')
                            <div class="col-6 col-md-3"><div class="text-muted small">Internship</div><div class="fw-semibold">{{ optional($staffMember->tenure_start)->format('M d') ?? '—' }} – {{ optional($staffMember->tenure_end)->format('M d, Y') ?? '—' }}</div></div>
                        @endif
                        @if ($myAttendance)
                            <div class="col-6 col-md-3"><div class="text-muted small">This month</div><div class="fw-semibold">{{ $myAttendance['present'] }} present · {{ $myAttendance['absent'] }} absent</div></div>
                            <div class="col-6 col-md-3"><div class="text-muted small">Hours logged</div><div class="fw-semibold">{{ $myAttendance['hours'] }}h</div></div>
                        @endif
                    </div>
                    <div class="text-muted" style="font-size:11px;margin-top:8px;"><i class="fas fa-lock"></i> These details are managed by HR / admin and are read-only here.</div>
                </div>
            </div>
        @endif

        @switch($type)
            @case('ceo')
                @include('pages.admin.dashboard.ceo')
                @break
            @case('coo')
                @include('pages.admin.dashboard.coo')
                @break
            @case('hr')
                @include('pages.admin.dashboard.hr')
                @break
            @case('account-manager')
                @include('pages.admin.dashboard.account-manager')
                @break
            @case('bdm')
                @include('pages.admin.dashboard.bdm')
                @break
            @case('project-manager')
                @include('pages.admin.dashboard.project-manager')
                @break
            @case('developer')
                @include('pages.admin.dashboard.developer')
                @break
            @case('designer')
                @include('pages.admin.dashboard.designer')
                @break
            @case('sales-executive')
                @include('pages.admin.dashboard.sales-executive')
                @break
            @case('tech-lead')
                @include('pages.admin.dashboard.tech-lead')
                @break
            @case('qa')
                @include('pages.admin.dashboard.qa')
                @break
            @case('ai-engineer')
                @include('pages.admin.dashboard.ai-engineer')
                @break
            @case('marketing-manager')
                @include('pages.admin.dashboard.marketing-manager')
                @break
            @case('finance')
                @include('pages.admin.dashboard.finance')
                @break
            @case('intern')
                @include('pages.admin.dashboard.intern')
                @break
            @case('admin')
                @include('pages.admin.dashboard.admin')
                @break
            @default
                @include('pages.admin.dashboard.generic')
        @endswitch
    </div>
</div>

<script>
    (function() {
        let charts = {};

        function destroyChart(key) {
            if (charts[key]) {
                charts[key].destroy();
                delete charts[key];
            }
            // A prior page visit (wire:ignore keeps the canvas) may still own it.
            const el = document.getElementById(key);
            const existing = el && window.Chart && Chart.getChart(el);
            if (existing) existing.destroy();
        }

        function makeDoughnut(id, labels, values, colors) {
            const canvas = document.getElementById(id);
            if (!canvas || !labels.length) return;
            destroyChart(id);
            charts[id] = new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#ffffff' }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16, font: { size: 12 } } } }
                }
            });
        }

        function makeBar(id, labels, values, colors, currency = false) {
            const canvas = document.getElementById(id);
            if (!canvas || !labels.length) return;
            destroyChart(id);
            charts[id] = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 4, maxBarThickness: 40 }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: currency ? { callbacks: { label: (c) => '$' + Number(c.parsed.y).toLocaleString() } } : {}
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: currency ? { callback: (v) => '$' + Number(v).toLocaleString() } : {}, grid: { color: '#e5e7eb' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        function makeLine(id, labels, values, color, currency = false) {
            const canvas = document.getElementById(id);
            if (!canvas || !labels.length) return;
            destroyChart(id);
            charts[id] = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: { labels, datasets: [{ data: values, borderColor: color, backgroundColor: color + '22', fill: true, tension: 0.35, pointRadius: 3 }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: currency ? { callbacks: { label: (c) => '$' + Number(c.parsed.y).toLocaleString() } } : {}
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: currency ? { callback: (v) => '$' + Number(v).toLocaleString() } : {}, grid: { color: '#e5e7eb' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        window.initDashboardCharts = function() {
            if (typeof Chart === 'undefined') return;

            @if($type === 'ceo')
                makeLine('revenueTrendChart', @json($revenueTrendLabels), @json($revenueTrendValues), '#10B981', true);
                makeBar('dealStageChart', @json($stageLabels), @json($stageValues), @json($stageColors));
            @elseif($type === 'admin')
                makeLine('revenueTrendChart', @json($revenueTrendLabels), @json($revenueTrendValues), '#10B981', true);
                makeBar('dealStageChart', @json($stageLabels), @json($stageValues), @json($stageColors));
            @elseif($type === 'project-manager')
                makeDoughnut('projectStatusChart', @json($projectStatusLabels), @json($projectStatusValues), @json($projectStatusColors));
            @elseif($type === 'developer')
                makeDoughnut('taskStatusChart', @json($taskStatusLabels), @json($taskStatusValues), @json($taskStatusColors));
                makeBar('completionTrendChart', @json($completionTrendLabels), @json($completionTrendValues), '#4F46E5');
            @elseif($type === 'designer')
                makeDoughnut('taskStatusChart', @json($taskStatusLabels), @json($taskStatusValues), @json($taskStatusColors));
            @elseif($type === 'sales-executive')
                makeBar('dealStageChart', @json($stageLabels), @json($stageValues), @json($stageColors));
            @elseif($type === 'coo')
                makeDoughnut('taskStatusChart', @json($taskStatusLabels), @json($taskStatusValues), @json($taskStatusColors));
            @elseif($type === 'bdm')
                makeBar('dealStageChart', @json($stageLabels), @json($stageValues), @json($stageColors));
            @endif
        };

        // Chart.js loads from the layout <head>; wait for it, then paint on the
        // next frame so the canvas has its final size.
        let tries = 0;
        function bootCharts() {
            if (typeof Chart !== 'undefined') {
                requestAnimationFrame(function () { requestAnimationFrame(window.initDashboardCharts); });
                return;
            }
            if (tries++ < 40) setTimeout(bootCharts, 60);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootCharts);
        } else {
            bootCharts();
        }
        document.addEventListener('livewire:navigated', function () { tries = 0; bootCharts(); });
    })();
</script>
