<?php

use Livewire\Component;
use App\Models\Task;
use App\Models\Communication;
use App\Models\CalendarEvent;
use App\Models\ActivityLog;
use App\Models\staff;

new class extends Component
{
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function resetFilters()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $range = fn ($query, $column = 'created_at') => $query
            ->when($this->dateFrom, fn ($q) => $q->whereDate($column, '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate($column, '<=', $this->dateTo));

        $tasksCompleted = $range(Task::where('status', 'completed'), 'completed_at')->count();
        $tasksCreated = $range(Task::query())->count();
        $emailsLogged = $range(Communication::type('email'), 'occurred_at')->count();
        $callsLogged = $range(Communication::type('call'), 'occurred_at')->count();
        $meetingsLogged = $range(Communication::type('meeting'), 'occurred_at')->count();
        $eventsScheduled = $range(CalendarEvent::query(), 'start_at')->count();

        $byStaff = staff::withCount([
            'tasks as tasks_completed_count' => function ($q) {
                $q->where('status', 'completed')
                    ->when($this->dateFrom, fn ($qq) => $qq->whereDate('completed_at', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($qq) => $qq->whereDate('completed_at', '<=', $this->dateTo));
            },
        ])->get()->sortByDesc('tasks_completed_count')->take(5)->values();

        $recentLogs = $range(ActivityLog::query())->orderBy('created_at', 'desc')->limit(8)->get();

        return $this->view([
            'tasksCompleted' => $tasksCompleted,
            'tasksCreated' => $tasksCreated,
            'emailsLogged' => $emailsLogged,
            'callsLogged' => $callsLogged,
            'meetingsLogged' => $meetingsLogged,
            'eventsScheduled' => $eventsScheduled,
            'byStaff' => $byStaff,
            'recentLogs' => $recentLogs,
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Activity Report</h1>
                <p>What the team has been doing — tasks, calls, emails, and meetings.</p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">From</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">To</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters"><i class="fas fa-undo"></i> Reset to Last 30 Days</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info"><h3>Tasks Done</h3><p class="stat-number">{{ $tasksCompleted }}</p></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
                    <div class="stat-info"><h3>Tasks Created</h3><p class="stat-number">{{ $tasksCreated }}</p></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-envelope"></i></div>
                    <div class="stat-info"><h3>Emails</h3><p class="stat-number">{{ $emailsLogged }}</p></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-phone"></i></div>
                    <div class="stat-info"><h3>Calls</h3><p class="stat-number">{{ $callsLogged }}</p></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><h3>Meetings</h3><p class="stat-number">{{ $meetingsLogged }}</p></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info"><h3>Events</h3><p class="stat-number">{{ $eventsScheduled }}</p></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Top Staff -->
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-ranking-star me-2"></i> Most Tasks Completed</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Staff</th><th>Completed</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($byStaff as $member)
                                <tr>
                                    <td class="fw-semibold">{{ $member->name }}</td>
                                    <td><span class="badge bg-success">{{ $member->tasks_completed_count }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center py-4 text-muted">No staff data available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-stream me-2"></i> Recent Activity</h3>
                        <a href="{{ route('communications.activity-log') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        @forelse ($recentLogs as $log)
                            <div class="d-flex align-items-start py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width:32px;height:32px;flex-shrink:0;">
                                    <i class="fas {{ $log->log_icon }} text-primary" style="font-size: 12px;"></i>
                                </div>
                                <div>
                                    <p class="mb-0 small">
                                        <strong>{{ $log->causer_name ?? 'System' }}</strong> {{ $log->description }}
                                    </p>
                                    <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center py-4 mb-0">No activity in this range.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
