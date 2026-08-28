<!-- 1. Operations Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-diagram-project"></i></div>
        <div class="stat-info"><h3>Active Projects</h3><p class="stat-number">{{ number_format($activeProjects) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-pause"></i></div>
        <div class="stat-info"><h3>On Hold</h3><p class="stat-number">{{ number_format($onHoldProjects) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-list-check"></i></div>
        <div class="stat-info"><h3>Open Tasks</h3><p class="stat-number">{{ number_format($openTasks) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info"><h3>Overdue Tasks</h3><p class="stat-number">{{ number_format($overdueTasks) }}</p></div>
    </div>
</div>

<!-- 8. Operational Alerts + 10. Escalations -->
@if ($escalations->isNotEmpty() || $overdueMilestones->isNotEmpty())
<div class="row g-4 mb-1">
    @if ($escalations->isNotEmpty())
        <div class="col-lg-6">
            <div class="card h-100" style="border:1px solid #fecaca;">
                <div class="card-header" style="background:#fef2f2;">
                    <h3 class="card-title" style="color:#991b1b;"><i class="fas fa-bell me-2"></i> Escalations
                        <span class="badge bg-danger ms-1">{{ $escalations->count() }}</span></h3>
                </div>
                <div class="card-body p-0">
                    @foreach ($escalations as $t)
                        <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="fw-semibold">{{ $t->title }}</div>
                            <small class="text-muted">{{ $t->assignedTo->name ?? 'Unassigned' }} · overdue since {{ $t->due_date->format('M d') }}</small>
                        </div>
                    @endforeach
                </div>
                <div class="card-body pt-0"><small class="text-muted">Overdue 3+ days — past routine follow-up, needs a decision.</small></div>
            </div>
        </div>
    @endif
    @if ($overdueMilestones->isNotEmpty())
        <div class="col-lg-6">
            <div class="card h-100" style="border:1px solid #fde68a;">
                <div class="card-header" style="background:#fffbeb;">
                    <h3 class="card-title" style="color:#92400e;"><i class="fas fa-flag me-2"></i> Overdue Milestones
                        <span class="badge bg-warning text-dark ms-1">{{ $overdueMilestones->count() }}</span></h3>
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

<div class="row g-4 mb-1">
    <!-- 3. Team Workload -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users-gear me-2"></i> Team Workload</h3></div>
            <div class="card-body p-0">
                @forelse ($workload as $s)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div><span class="fw-semibold">{{ $s->name }}</span> <small class="text-muted">{{ $s->designation }}</small></div>
                        <span class="badge {{ $s->open_tasks_count > 5 ? 'bg-danger' : 'bg-secondary' }} rounded-pill">{{ $s->open_tasks_count }} open</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No active staff.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 4. Resource Allocation -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-people-arrows me-2"></i> Resource Allocation</h3></div>
            <div class="card-body p-0">
                @forelse ($allocation as $s)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div><span class="fw-semibold">{{ $s->name }}</span> <small class="text-muted">{{ $s->designation }}</small></div>
                        <span class="badge bg-primary rounded-pill">{{ $s->active_projects_count }} project{{ $s->active_projects_count == 1 ? '' : 's' }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No one currently allocated to an active project.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-1">
    <!-- 5. Task Health -->
    <div class="col-lg-5">
        <div class="card h-100" wire:ignore>
            <div class="card-header"><h3 class="card-title">Task Health</h3></div>
            <div class="card-body"><div style="position:relative; height:220px;"><canvas id="taskStatusChart"></canvas></div></div>
        </div>
    </div>

    <!-- 6. Delayed Work -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Delayed Work</h3>
                <a href="{{ route('tasks.all') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($delayedTasks as $t)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-semibold">{{ $t->title }}</div>
                            <small class="text-muted">{{ $t->assignedTo->name ?? 'Unassigned' }}</small>
                        </div>
                        <span class="badge bg-danger">due {{ $t->due_date->format('M d') }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">Nothing overdue.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- 7. Client Issues + 9. Performance Reports -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-heart-crack me-2"></i> Client Issues</h3></div>
            <div class="card-body p-0">
                @forelse ($clientIssues as $c)
                    <a href="{{ route('companies.show', $c->id) }}" class="d-block px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">{{ $c->company_name }}</a>
                @empty
                    <p class="text-muted mb-0 p-3">No flagged clients right now.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Performance Reports</h3></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('reports.performance') }}" class="btn btn-outline-primary text-start"><i class="fas fa-chart-column me-2"></i> Team Performance</a>
                <a href="{{ route('reports.activity') }}" class="btn btn-outline-primary text-start"><i class="fas fa-list-check me-2"></i> Activity Report</a>
                <a href="{{ route('projects.all') }}" class="btn btn-outline-primary text-start"><i class="fas fa-diagram-project me-2"></i> All Projects</a>
            </div>
        </div>
    </div>
</div>
