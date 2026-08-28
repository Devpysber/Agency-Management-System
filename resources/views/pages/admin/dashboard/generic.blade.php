@if(!$staffMember)
    <div class="alert-flash alert-flash-warning">
        <i class="fas fa-info-circle"></i>
        No staff profile is linked to your account yet, so tasks, events, and communications can't be shown. Contact an admin.
    </div>
@endif

<!-- Stats Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
            <div class="stat-info"><h3>Open Tasks</h3><p class="stat-number">{{ $openTasksCount }}</p></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h3>Completed</h3><p class="stat-number">{{ $completedTasksCount }}</p></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="stat-info"><h3>Overdue</h3><p class="stat-number">{{ $overdueTasksCount }}</p></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- My Tasks -->
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
                            <tr><th>Title</th><th>Priority</th><th>Status</th><th>Due Date</th><th style="width: 170px;">Actions</th></tr>
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

    <!-- Sidebar column -->
    <div class="col-lg-5">
        <div class="card mb-4">
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

        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-comment me-2"></i> Recent Communications</h3></div>
            <div class="card-body">
                @forelse ($recentCommunications as $comm)
                    <div class="d-flex align-items-start py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width:32px;height:32px;flex-shrink:0;">
                            <i class="fas {{ $comm->type_icon }} text-primary" style="font-size: 12px;"></i>
                        </div>
                        <div>
                            <p class="mb-0 small fw-semibold">{{ $comm->subject }}</p>
                            <small class="text-muted">{{ $comm->occurred_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No communications logged.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
