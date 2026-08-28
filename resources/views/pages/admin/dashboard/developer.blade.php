<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
            <div class="stat-info"><h3>Open Tasks</h3><p class="stat-number">{{ $openTasksCount }}</p></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-spinner"></i></div>
            <div class="stat-info"><h3>In Progress</h3><p class="stat-number">{{ $inProgressTasksCount }}</p></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3>Completed</h3><p class="stat-number">{{ $completedTasksCount }}</p></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="stat-info"><h3>Overdue</h3><p class="stat-number">{{ $overdueTasksCount }}</p></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-bug"></i></div>
            <div class="stat-info"><h3>My Bugs</h3><p class="stat-number">{{ $myBugsCount }}</p>
                @if ($qaHandoffCount)<span class="stat-change">{{ $qaHandoffCount }} with QA</span>@endif
            </div>
        </div>
    </div>
</div>

@if ($failedRetests->isNotEmpty())
<div class="alert-flash alert-flash-error mb-4">
    <i class="fas fa-exclamation-circle"></i>
    {{ $failedRetests->count() }} bug{{ $failedRetests->count() == 1 ? '' : 's' }} failed QA retest — needs rework.
    <a href="{{ route('bugs.all') }}" class="ms-2 fw-semibold">View</a>
</div>
@endif

<div class="dashboard-grid">
    <div class="card" wire:ignore>
        <div class="card-header"><h3>Tasks Completed (Last 7 Days)</h3></div>
        <div class="card-body">
            <div style="position:relative; height:240px;">
                <canvas id="completionTrendChart"></canvas>
            </div>
        </div>
    </div>
    <div class="card" wire:ignore>
        <div class="card-header"><h3>Task Status</h3></div>
        <div class="card-body">
            @if(count($taskStatusLabels) > 0)
                <div style="position:relative; height:240px;">
                    <canvas id="taskStatusChart"></canvas>
                </div>
            @else
                <p class="text-muted text-center py-5 mb-0">No tasks assigned yet.</p>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-check me-2"></i> My Tasks</h3>
                <span class="badge bg-primary">{{ $tasks->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Title</th><th>Priority</th><th>Status</th><th>Due Date</th><th style="width:170px;">Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                            <tr>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $task->title }}</h6>
                                    @if($task->description)
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($task->description, 50) }}</small>
                                    @endif
                                </td>
                                <td><span class="badge {{ $task->priority_badge['class'] }}">{{ $task->priority_badge['icon'] }} {{ ucfirst($task->priority) }}</span></td>
                                <td><span class="badge {{ $task->status_badge['class'] }}">{{ $task->status_badge['icon'] }} {{ ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
                                <td>
                                    @if($task->due_date)
                                        <span class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                            @if($task->isOverdue())<i class="fas fa-triangle-exclamation me-1"></i>@endif
                                            {{ $task->due_date->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!in_array($task->status, ['completed', 'cancelled']))
                                        <div class="btn-group btn-group-sm">
                                            @if($task->status !== 'in_progress')
                                                <button class="btn btn-outline-primary" wire:click="markTaskInProgress({{ $task->id }})" title="Start"><i class="fas fa-play"></i></button>
                                            @endif
                                            <button class="btn btn-outline-success" wire:click="markTaskComplete({{ $task->id }})" wire:confirm="Mark this task as completed?" title="Complete"><i class="fas fa-check"></i></button>
                                        </div>
                                    @else
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Done</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No tasks assigned. You're all caught up.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bug me-2"></i> My Bugs</h3>
                <a href="{{ route('bugs.all') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($myBugs as $bug)
                    <a href="{{ route('bugs.show', $bug->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $bug->title }}</span>
                        <span class="badge {{ $bug->status_badge['class'] }}">{{ ucwords(str_replace('_', ' ', $bug->status)) }}</span>
                    </a>
                @empty
                    <p class="text-muted mb-0 p-3">No open bugs assigned to you.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-alt me-2"></i> Upcoming Events</h3></div>
            <div class="card-body">
                @forelse ($upcomingEvents as $event)
                    <div class="d-flex align-items-start py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width:32px;height:32px;flex-shrink:0;">
                            <i class="fas {{ $event->type_badge['icon'] }} text-primary" style="font-size: 12px;"></i>
                        </div>
                        <div>
                            <p class="mb-0 small fw-semibold">{{ $event->title }}</p>
                            <small class="text-muted">{{ $event->start_at->format('M d, Y H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No upcoming events.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
