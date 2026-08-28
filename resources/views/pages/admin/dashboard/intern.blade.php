<!-- My Dashboard -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
        <div class="stat-info"><h3>Open Tasks</h3><p class="stat-number">{{ $openTasksCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><h3>Completed</h3><p class="stat-number">{{ $completedTasksCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info"><h3>Overdue</h3><p class="stat-number">{{ $overdueTasksCount }}</p></div>
    </div>
</div>

<div class="row g-4">
    <!-- My Tasks -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-check me-2"></i> My Tasks</h3>
                <a href="{{ route('tasks.my') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($tasks->take(8) as $task)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-semibold">{{ $task->title }}</div>
                            @if ($task->due_date)
                                <small class="{{ $task->isOverdue() ? 'text-danger' : 'text-muted' }}">Due {{ $task->due_date->format('M d, Y') }}</small>
                            @endif
                        </div>
                        <span class="badge {{ $task->status_badge['class'] }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No tasks assigned to you yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- My Projects + Calendar -->
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-diagram-project me-2"></i> My Projects</h3></div>
            <div class="card-body p-0">
                @forelse ($myProjects as $p)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">{{ $p->name }}</div>
                @empty
                    <p class="text-muted mb-0 p-3">Not on any active project team yet.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-alt me-2"></i> My Calendar</h3></div>
            <div class="card-body p-0">
                @forelse ($upcomingEvents as $event)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="small fw-semibold">{{ $event->title }}</div>
                        <small class="text-muted">{{ $event->start_at->format('M d, Y H:i') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">Nothing scheduled.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card mt-4" style="border:1px solid #e5e7eb;">
    <div class="card-body">
        <p class="text-muted small mb-0">
            <i class="fas fa-circle-info me-1"></i> Access is restricted to what's explicitly assigned to
            you — no company financials, client list, sales pipeline, or staff/settings access. My Messages
            and My Files use the existing project chat and calendar — open a project above to reach them.
        </p>
    </div>
</div>
