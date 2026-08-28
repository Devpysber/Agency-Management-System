<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Task;
use App\Models\staff;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $assignedFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected function fetchTasks()
    {
        $query = Task::completed()->with(['assignedTo', 'createdBy']);

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        if (!empty($this->assignedFilter)) {
            $query->assignedTo($this->assignedFilter);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        return $query->orderBy('completed_at', 'desc');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedAssignedFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->assignedFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function getStatsProperty()
    {
        $completed = Task::completed()->get();

        $totalCompleted = $completed->count();

        $thisMonth = Task::completed()
            ->whereDate('completed_at', '>=', now()->startOfMonth())
            ->whereDate('completed_at', '<=', now()->endOfMonth())
            ->count();

        $withBothDates = $completed->filter(fn($t) => $t->completed_at && $t->created_at);
        $avgDays = $withBothDates->count() > 0
            ? round($withBothDates->avg(fn($t) => $t->created_at->diffInDays($t->completed_at)), 1)
            : null;

        $onTime = $completed->filter(function ($t) {
            return $t->due_date && $t->completed_at && $t->completed_at->lte($t->due_date->copy()->endOfDay());
        })->count();

        return [
            'total' => $totalCompleted,
            'thisMonth' => $thisMonth,
            'avgDays' => $avgDays,
            'onTime' => $onTime,
        ];
    }

    public function render()
    {
        return $this->view([
            'tasks' => $this->fetchTasks()->paginate(15),
            'stats' => $this->stats,
            'staffList' => staff::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Completed Tasks</h1>
                <p>Review tasks that have been marked completed.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('tasks.all') }}" class="btn btn-secondary">
                    <i class="fas fa-list"></i> All Tasks
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Completed</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Completed This Month</h3>
                        <p class="stat-number">{{ $stats['thisMonth'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon teal">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Avg. Days to Complete</h3>
                        <p class="stat-number">{{ $stats['avgDays'] !== null ? $stats['avgDays'] : 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Completed On Time</h3>
                        <p class="stat-number">{{ $stats['onTime'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">
                            <i class="fas fa-search me-1 text-muted"></i>
                            Search
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control"
                                   wire:model.live="search"
                                   placeholder="Search title or description...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-user me-1 text-muted"></i>
                            Assigned Staff
                        </label>
                        <select class="form-select" wire:model.live="assignedFilter">
                            <option value="">All Staff</option>
                            @foreach($staffList as $staffMember)
                                <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-calendar me-1 text-muted"></i>
                            From
                        </label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-calendar me-1 text-muted"></i>
                            To
                        </label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters" title="Reset Filters">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-check-double me-2"></i>
                    Completed Task List
                </h3>
                <span class="badge bg-success">{{ $tasks->total() }} Completed</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Completed By</th>
                                <th>Due Date</th>
                                <th>Completed At</th>
                                <th>On Time?</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                            <tr>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $task->title }}</h6>
                                    @if($task->description)
                                        <small class="text-muted">{{ Str::limit($task->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $task->assignedTo->name ?? 'Unassigned' }}
                                </td>
                                <td>
                                    {{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    {{ $task->completed_at ? $task->completed_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    @if(!$task->due_date || !$task->completed_at)
                                        <span class="badge bg-secondary">N/A</span>
                                    @elseif($task->completed_at->lte($task->due_date->copy()->endOfDay()))
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> On Time
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-clock"></i> Late
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-check-double fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No completed tasks found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted">
                            Showing {{ $tasks->firstItem() ?? 0 }}-{{ $tasks->lastItem() ?? 0 }} of {{ $tasks->total() }}
                            @if($search || $assignedFilter || $dateFrom || $dateTo)
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $assignedFilter || $dateFrom || $dateTo)
                            <button class="btn btn-sm btn-outline-secondary ms-2" wire:click="resetFilters">
                                <i class="fas fa-undo"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                    @if($tasks->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $tasks->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($tasks->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $tasks->currentPage() - 2); $page <= min($tasks->lastPage(), $tasks->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $tasks->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$tasks->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$tasks->hasMorePages()) disabled @endif>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
