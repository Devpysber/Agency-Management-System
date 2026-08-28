<!-- AI Tasks -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
        <div class="stat-info"><h3>Open Tasks</h3><p class="stat-number">{{ $openTasksCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><h3>Completed</h3><p class="stat-number">{{ $completedTasksCount }}</p></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100" wire:ignore>
            <div class="card-header"><h3 class="card-title">Task Status</h3></div>
            <div class="card-body">
                @if(count($taskStatusLabels) > 0)
                    <div style="position:relative; height:240px;"><canvas id="taskStatusChart"></canvas></div>
                @else
                    <p class="text-muted text-center py-5 mb-0">No tasks assigned yet.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">My AI/ML Tasks</h3></div>
            <div class="card-body p-0">
                @forelse ($tasks->take(8) as $task)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span class="small">{{ $task->title }}</span>
                        <span class="badge {{ $task->status_badge['class'] }}">{{ $task->status_badge['icon'] }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No tasks assigned.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card" style="border:1px solid #e5e7eb;">
    <div class="card-body">
        <h6 class="fw-semibold mb-2"><i class="fas fa-circle-info text-muted me-1"></i> Not built yet — flagged, not faked</h6>
        <p class="text-muted small mb-2">
            This schema has no AI-specific data model: no Experiments, Model registry/versions, Evaluations,
            AI usage/cost tracking, Deployment status, or Model Issues table. This dashboard shows the only
            real data available — your assigned Tasks and Project visibility.
        </p>
        <p class="text-muted small mb-0">
            Building the real thing (Projects → Tasks → Experiments → Models → Evaluations → Deployment,
            plus a cost-tracking table Finance can read without seeing technical details) is a genuine new
            subsystem — say if you want it scoped as its own build.
        </p>
    </div>
</div>
