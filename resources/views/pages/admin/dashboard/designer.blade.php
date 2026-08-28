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
            <div class="stat-icon purple"><i class="fas fa-images"></i></div>
            <div class="stat-info"><h3>Portfolio Items</h3><p class="stat-number">{{ $totalPortfolioItems }}</p></div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- My Tasks -->
    <div class="card">
        <div class="card-header">
            <h3>My Tasks</h3>
            <a href="{{ route('tasks.my') }}" class="view-all">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Title</th><th>Status</th><th>Due Date</th><th style="width:120px;">Actions</th></tr></thead>
                    <tbody>
                        @forelse ($tasks->take(6) as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td><span class="badge {{ $task->status_badge['class'] }}">{{ $task->status_badge['icon'] }} {{ ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
                            <td>{{ $task->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                            <td>
                                @if(!in_array($task->status, ['completed', 'cancelled']))
                                    <button class="btn btn-sm btn-outline-success" wire:click="markTaskComplete({{ $task->id }})" wire:confirm="Mark this task as completed?"><i class="fas fa-check"></i></button>
                                @else
                                    <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No tasks assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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
    <!-- Recent Portfolio Items -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Recent Portfolio Items</h3>
                <a href="{{ route('portfolio.all') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Title</th><th>Client</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($recentPortfolioItems as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ $item->client_name ?? '—' }}</td>
                                    <td><span class="badge {{ $item->status_badge['class'] }}">{{ $item->status_badge['icon'] }} {{ ucfirst($item->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No portfolio items yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Studio + Upcoming Events -->
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Studio</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Published</span>
                    <span class="badge bg-success rounded-pill">{{ $publishedPortfolioItems }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span>Draft</span>
                    <span class="badge bg-secondary rounded-pill">{{ $draftPortfolioItems }}</span>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Upcoming Events</h3></div>
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

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-comments me-2"></i> Client Feedback</h3></div>
            <div class="card-body p-0">
                @forelse ($clientFeedback as $c)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>{{ ucfirst($c->type) }} with {{ $c->contact->first_name ?? '' }} {{ $c->contact->last_name ?? '' }}</div>
                        <small class="text-muted">{{ $c->occurred_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No client feedback logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100" style="border:1px solid #fde68a;">
            <div class="card-header" style="background:#fffbeb;">
                <h3 class="card-title" style="color:#92400e;"><i class="fas fa-rotate me-2"></i> Likely Revisions</h3>
            </div>
            <div class="card-body p-0">
                @forelse ($revisionLikely as $t)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">{{ $t->title }}</div>
                @empty
                    <p class="text-muted mb-0 p-3">Nothing flagged.</p>
                @endforelse
            </div>
            <div class="card-body pt-0"><small class="text-muted">In-progress task past due — proxy signal, no dedicated revision-request status exists yet.</small></div>
        </div>
    </div>
</div>
