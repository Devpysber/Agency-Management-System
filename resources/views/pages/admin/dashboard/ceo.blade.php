<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3>Total Revenue</h3>
            <p class="stat-number">${{ number_format($totalRevenue, 2) }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-sack-dollar"></i></div>
        <div class="stat-info">
            <h3>Revenue This Month</h3>
            <p class="stat-number">${{ number_format($revenueThisMonth, 2) }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-diagram-project"></i></div>
        <div class="stat-info">
            <h3>Active Projects</h3>
            <p class="stat-number">{{ number_format($activeProjects) }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-user-tie"></i></div>
        <div class="stat-info">
            <h3>Active Staff</h3>
            <p class="stat-number">{{ number_format($totalStaff) }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3>Active Pipeline Value</h3>
            <p class="stat-number">${{ number_format($activePipelineValue) }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-trophy"></i></div>
        <div class="stat-info">
            <h3>Win Rate</h3>
            <p class="stat-number">{{ $winRate }}%</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="stat-info">
            <h3>Outstanding</h3>
            <p class="stat-number">${{ number_format($outstanding, 2) }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-building"></i></div>
        <div class="stat-info">
            <h3>Active Clients</h3>
            <p class="stat-number">{{ number_format($activeClients) }}</p>
            <span class="stat-change">{{ $newClientsThisMonth }} new this month</span>
        </div>
    </div>
    @if ($budgetUtilization !== null)
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-scale-balanced"></i></div>
            <div class="stat-info">
                <h3>Budget Utilization</h3>
                <p class="stat-number">{{ $budgetUtilization }}%</p>
                <span class="stat-change">revenue collected vs active project budgets</span>
            </div>
        </div>
    @endif
</div>

<div class="d-flex align-items-center gap-4 flex-wrap mb-4" style="font-size:13px; font-weight:600; color:#6b7280;">
    <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;margin-right:6px;"></span>{{ $onShift }} online now</span>
    @if ($inactiveNow)
        <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:6px;"></span>{{ $inactiveNow }} inactive</span>
    @endif
    <span><i class="fas fa-list-check me-1"></i>{{ $pendingTasks }} open tasks</span>
    @if ($overdueTasks)
        <span class="text-danger"><i class="fas fa-triangle-exclamation me-1"></i>{{ $overdueTasks }} overdue</span>
    @endif
</div>

@if ($pendingAppeals->isNotEmpty() || $overdueMilestones->isNotEmpty())
<div class="row g-4 mb-1">
    @if ($pendingAppeals->isNotEmpty())
        <div class="col-lg-6">
            <div class="card h-100" style="border:1px solid #fde68a;">
                <div class="card-header" style="background:#fffbeb;">
                    <h3 class="card-title" style="color:#92400e;"><i class="fas fa-gavel me-2"></i> Pending Approvals
                        <span class="badge bg-warning text-dark ms-1">{{ $pendingAppeals->count() }}</span></h3>
                </div>
                <div class="card-body p-0">
                    @foreach ($pendingAppeals as $ap)
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="fw-semibold">{{ $ap->staff->name ?? 'Staff' }}</div>
                                <small class="text-muted">{{ \Illuminate\Support\Carbon::parse($ap->date)->format('M d') }} — {{ \Illuminate\Support\Str::limit($ap->message, 60) }}</small>
                            </div>
                            @if ($canApproveAttendance)
                                <div class="flex-shrink-0">
                                    <button class="btn btn-sm btn-success" wire:click="approveAppealFromDashboard({{ $ap->id }})"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="rejectAppealFromDashboard({{ $ap->id }})"><i class="fas fa-xmark"></i></button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    @if ($overdueMilestones->isNotEmpty())
        <div class="col-lg-6">
            <div class="card h-100" style="border:1px solid #fecaca;">
                <div class="card-header" style="background:#fef2f2;">
                    <h3 class="card-title" style="color:#991b1b;"><i class="fas fa-triangle-exclamation me-2"></i> Overdue Milestones
                        <span class="badge bg-danger ms-1">{{ $overdueMilestones->count() }}</span></h3>
                </div>
                <div class="card-body p-0">
                    @foreach ($overdueMilestones as $m)
                        <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="fw-semibold">{{ $m->title }}</div>
                            <small class="text-muted">{{ $m->project->name ?? '—' }} · due {{ \Illuminate\Support\Carbon::parse($m->due_date)->format('M d, Y') }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endif

@if ($atRiskClients->isNotEmpty() || $highValueQuotations->isNotEmpty())
<div class="row g-4 mb-1">
    @if ($atRiskClients->isNotEmpty())
        <div class="col-lg-6">
            <div class="card h-100" style="border:1px solid #fecaca;">
                <div class="card-header" style="background:#fef2f2;">
                    <h3 class="card-title" style="color:#991b1b;"><i class="fas fa-heart-crack me-2"></i> At-Risk Clients
                        <span class="badge bg-danger ms-1">{{ $atRiskClients->count() }}</span></h3>
                </div>
                <div class="card-body p-0">
                    @foreach ($atRiskClients as $c)
                        <a href="{{ route('companies.show', $c->id) }}" class="d-block px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                            {{ $c->company_name }}
                        </a>
                    @endforeach
                </div>
                <div class="card-body pt-0"><small class="text-muted">Stale unpaid invoice (14+ days) or no client contact logged in 30 days.</small></div>
            </div>
        </div>
    @endif
    @if ($highValueQuotations->isNotEmpty())
        <div class="col-lg-6">
            <div class="card h-100" style="border:1px solid #fde68a;">
                <div class="card-header" style="background:#fffbeb;">
                    <h3 class="card-title" style="color:#92400e;"><i class="fas fa-file-invoice-dollar me-2"></i> High-Value Quotations
                        <span class="badge bg-warning text-dark ms-1">{{ $highValueQuotations->count() }}</span></h3>
                </div>
                <div class="card-body p-0">
                    @foreach ($highValueQuotations as $q)
                        <a href="{{ route('quotations.show', $q->id) }}" class="d-flex justify-content-between align-items-center px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                            <span>{{ $q->name }}</span>
                            <span class="fw-semibold">${{ number_format($q->quoted_amount, 2) }}</span>
                        </a>
                    @endforeach
                </div>
                <div class="card-body pt-0"><small class="text-muted">≥ ${{ number_format($quotationThreshold) }}, awaiting a decision — visibility only.</small></div>
            </div>
        </div>
    @endif
</div>
@endif

<!-- Charts -->
<div class="dashboard-grid">
    <div class="card" wire:ignore>
        <div class="card-header"><h3>Revenue Trend (Last 6 Months)</h3></div>
        <div class="card-body">
            <div style="position:relative; height:260px;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>
    <div class="card" wire:ignore>
        <div class="card-header"><h3>Deals by Stage</h3></div>
        <div class="card-body">
            <div style="position:relative; height:260px;">
                <canvas id="dealStageChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Top Open Deals -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Top Open Deals</h3>
                <a href="{{ route('deals.all') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Deal</th>
                                <th>Company</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topDeals as $deal)
                                <tr>
                                    <td>{{ $deal->deal_name }}</td>
                                    <td>{{ $deal->company->company_name ?? '—' }}</td>
                                    <td class="fw-semibold">${{ number_format($deal->deal_value) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No active deals.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff by Designation -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Staff by Designation</h3></div>
            <div class="card-body">
                @forelse ($staffByDesignation as $designationName => $count)
                    <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $designationName ?: 'Unassigned' }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No active staff yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <!-- Recent Activity -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
                <a href="{{ route('communications.calls') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($recentActivity as $item)
                    <div class="d-flex align-items-start gap-2 px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <i class="fas fa-circle text-muted" style="font-size:6px; margin-top:7px;"></i>
                        <div>
                            <div>
                                <span class="fw-semibold">{{ $item->staff->name ?? 'System' }}</span>
                                {{ ucfirst($item->type) }} with {{ $item->contact->first_name ?? '' }} {{ $item->contact->last_name ?? '' }}
                            </div>
                            <small class="text-muted">{{ $item->occurred_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No recent activity.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if ($outstanding > 0)
                        <div class="alert alert-warning py-2 px-3 mb-1 small">
                            <i class="fas fa-circle-dollar-to-slot me-1"></i>
                            ${{ number_format($outstanding, 2) }} outstanding across active projects.
                        </div>
                    @endif
                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-primary text-start">
                        <i class="fas fa-user-clock me-2"></i> Attendance ERP — approvals & roster
                    </a>
                    <a href="{{ route('deals.pipeline') }}" class="btn btn-outline-primary text-start">
                        <i class="fas fa-diagram-project me-2"></i> Deals Pipeline
                    </a>
                    <a href="{{ route('reports.sales') }}" class="btn btn-outline-primary text-start">
                        <i class="fas fa-chart-column me-2"></i> Sales Report
                    </a>
                    <a href="{{ route('staff.all') }}" class="btn btn-outline-primary text-start">
                        <i class="fas fa-users me-2"></i> Staff Directory
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
