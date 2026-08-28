<?php

use Livewire\Component;
use App\Models\staff;
use App\Models\deal;

new class extends Component
{
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function resetFilters()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $staffMembers = staff::withCount([
            'tasks as tasks_assigned_count' => function ($q) {
                $q->when($this->dateFrom, fn ($qq) => $qq->whereDate('created_at', '>=', $this->dateFrom))
                  ->when($this->dateTo, fn ($qq) => $qq->whereDate('created_at', '<=', $this->dateTo));
            },
            'tasks as tasks_completed_count' => function ($q) {
                $q->where('status', 'completed')
                  ->when($this->dateFrom, fn ($qq) => $qq->whereDate('completed_at', '>=', $this->dateFrom))
                  ->when($this->dateTo, fn ($qq) => $qq->whereDate('completed_at', '<=', $this->dateTo));
            },
            'tasks as tasks_overdue_count' => function ($q) {
                $q->where('due_date', '<', now())->whereNotIn('status', ['completed', 'cancelled']);
            },
            'communications as communications_count' => function ($q) {
                $q->when($this->dateFrom, fn ($qq) => $qq->whereDate('occurred_at', '>=', $this->dateFrom))
                  ->when($this->dateTo, fn ($qq) => $qq->whereDate('occurred_at', '<=', $this->dateTo));
            },
        ])->get()->map(function ($member) {
            $member->completion_rate = $member->tasks_assigned_count > 0
                ? round($member->tasks_completed_count / $member->tasks_assigned_count * 100)
                : 0;
            return $member;
        })->sortByDesc('tasks_completed_count')->values();

        // Deals won by the user linked to each staff member.
        $dealsByUser = deal::where('deal_status', 'won')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('actual_close_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('actual_close_date', '<=', $this->dateTo))
            ->get()
            ->groupBy('created_by')
            ->map(fn ($group) => ['count' => $group->count(), 'value' => $group->sum('deal_value')]);

        $staffMembers = $staffMembers->map(function ($member) use ($dealsByUser) {
            $deals = $dealsByUser->get($member->user_id) ?? ['count' => 0, 'value' => 0];
            $member->deals_won_count = $deals['count'];
            $member->deals_won_value = $deals['value'];
            return $member;
        });

        return $this->view([
            'staffMembers' => $staffMembers,
            'totalTasksCompleted' => $staffMembers->sum('tasks_completed_count'),
            'totalDealsWon' => $staffMembers->sum('deals_won_count'),
            'totalOverdue' => $staffMembers->sum('tasks_overdue_count'),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Performance Report</h1>
                <p>Team performance across tasks, deals, and communications.</p>
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
                        <button class="btn btn-secondary w-100" wire:click="resetFilters"><i class="fas fa-undo"></i> Reset to This Month</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info"><h3>Tasks Completed</h3><p class="stat-number">{{ $totalTasksCompleted }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-trophy"></i></div>
                    <div class="stat-info"><h3>Deals Won</h3><p class="stat-number">{{ $totalDealsWon }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
                    <div class="stat-info"><h3>Overdue Tasks</h3><p class="stat-number">{{ $totalOverdue }}</p></div>
                </div>
            </div>
        </div>

        <!-- Staff Performance Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-id-badge me-2"></i> Staff Performance</h3>
                <span class="badge bg-primary">{{ $staffMembers->count() }} Staff</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Tasks Assigned</th>
                                <th>Tasks Completed</th>
                                <th>Completion Rate</th>
                                <th>Overdue</th>
                                <th>Deals Won</th>
                                <th>Revenue</th>
                                <th>Communications</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staffMembers as $member)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($member->image)
                                            <img src="{{ asset('storage/' . $member->image) }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                        @else
                                            <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width:32px;height:32px;">
                                                <i class="fas fa-user text-primary" style="font-size:12px;"></i>
                                            </div>
                                        @endif
                                        <span class="fw-semibold">{{ $member->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $member->tasks_assigned_count }}</td>
                                <td>{{ $member->tasks_completed_count }}</td>
                                <td style="width: 160px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: {{ $member->completion_rate }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $member->completion_rate }}%</small>
                                    </div>
                                </td>
                                <td>
                                    @if($member->tasks_overdue_count > 0)
                                        <span class="badge bg-danger">{{ $member->tasks_overdue_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>{{ $member->deals_won_count }}</td>
                                <td>${{ number_format($member->deals_won_value, 2) }}</td>
                                <td>{{ $member->communications_count }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No staff data available.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
