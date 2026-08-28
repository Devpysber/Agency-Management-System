<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-diagram-project"></i></div>
        <div class="stat-info">
            <h3>Active Projects</h3>
            <p class="stat-number">{{ $activeProjects }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3>Completed Projects</h3>
            <p class="stat-number">{{ $completedProjects }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-pause-circle"></i></div>
        <div class="stat-info">
            <h3>On Hold</h3>
            <p class="stat-number">{{ $onHoldProjects }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info">
            <h3>Overdue Milestones</h3>
            <p class="stat-number">{{ $overdueMilestones }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-sack-dollar"></i></div>
        <div class="stat-info">
            <h3>Budget Collected</h3>
            <p class="stat-number">${{ number_format($collected, 2) }}</p>
            <span class="stat-change">of ${{ number_format($totalBudget, 2) }} budgeted</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="stat-info">
            <h3>Outstanding</h3>
            <p class="stat-number">${{ number_format($outstanding, 2) }}</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Active Projects -->
    <div class="card">
        <div class="card-header">
            <h3>My Active Projects</h3>
            <a href="{{ route('projects.all') }}" class="view-all">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Status</th><th>Progress</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($activeProjectsList as $project)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('projects.show', $project->id) }}'">
                                <td>{{ $project->name }}</td>
                                <td>
                                    <span class="badge {{ $project->status_badge['class'] }}">
                                        {{ $project->status_badge['icon'] }} {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                    </span>
                                </td>
                                <td>{{ $project->progress }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No active projects.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Project Status Chart -->
    <div class="card" wire:ignore>
        <div class="card-header"><h3>Projects by Status</h3></div>
        <div class="card-body">
            @if(count($projectStatusLabels) > 0)
                <div style="position:relative; height:240px;">
                    <canvas id="projectStatusChart"></canvas>
                </div>
            @else
                <p class="text-muted text-center py-5 mb-0">No project data yet.</p>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Upcoming Milestones -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Upcoming Milestones</h3></div>
            <div class="card-body">
                @forelse ($upcomingMilestones as $milestone)
                    <div class="d-flex align-items-start justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <p class="mb-0 fw-semibold">{{ $milestone->title }}</p>
                            <small class="text-muted">{{ $milestone->project->name ?? '—' }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $milestone->status_badge['class'] }}">{{ ucfirst(str_replace('_', ' ', $milestone->status)) }}</span>
                            <div><small class="text-muted">{{ $milestone->due_date?->format('M d, Y') }}</small></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No upcoming milestones.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- My Tasks -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">My Tasks</h3>
                <span class="badge bg-primary">{{ $myOpenTasksCount }}</span>
            </div>
            <div class="card-body">
                @forelse ($myTasks->take(6) as $task)
                    <div class="d-flex align-items-start justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span class="small">{{ $task->title }}</span>
                        <span class="badge {{ $task->status_badge['class'] }}">{{ $task->status_badge['icon'] }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No tasks assigned.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if ($atRiskProjects->isNotEmpty())
<div class="card my-4" style="border:1px solid #fecaca;">
    <div class="card-header" style="background:#fef2f2;">
        <h3 class="card-title" style="color:#991b1b;"><i class="fas fa-triangle-exclamation me-2"></i> At-Risk Projects
            <span class="badge bg-danger ms-1">{{ $atRiskProjects->count() }}</span></h3>
    </div>
    <div class="card-body p-0">
        @foreach ($atRiskProjects as $p)
            <a href="{{ route('projects.show', $p->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                <span>{{ $p->name }}</span>
                <small class="text-muted">{{ $p->status === 'on_hold' ? 'On hold' : ($p->is_overdue ? 'Deadline passed' : 'Overdue milestone') }}</small>
            </a>
        @endforeach
    </div>
</div>
@endif

<div class="row g-4">
    <!-- Team Assignment / Workload -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users-gear me-2"></i> Team Workload</h3></div>
            <div class="card-body p-0">
                @forelse ($team as $s)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div><span class="fw-semibold">{{ $s->name }}</span> <small class="text-muted">{{ $s->designation }}</small></div>
                        <span class="badge {{ $s->open_tasks_count > 5 ? 'bg-danger' : 'bg-secondary' }} rounded-pill">{{ $s->open_tasks_count }} open</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No team members on your active projects yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Client Updates -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comments me-2"></i> Client Updates</h3>
                <a href="{{ route('communications.calls') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($clientUpdates as $c)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>{{ ucfirst($c->type) }} with {{ $c->contact->first_name ?? '' }} {{ $c->contact->last_name ?? '' }}</div>
                        <small class="text-muted">{{ $c->occurred_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No recent client activity.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
