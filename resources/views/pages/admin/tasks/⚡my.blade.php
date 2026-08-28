<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Task;
use App\Models\staff;

new class extends Component
{
    use WithPagination;

    public $currentStaff = null;
    public $staffLinked = false;

    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';

    public function mount()
    {
        $this->currentStaff = staff::where('user_id', auth()->id())->first();
        $this->staffLinked = (bool) $this->currentStaff;
    }

    protected function fetchTasks()
    {
        $query = Task::query()->with(['assignedTo', 'createdBy']);

        if ($this->staffLinked) {
            $query->assignedTo($this->currentStaff->id);
        }

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        if (!empty($this->statusFilter)) {
            $query->byStatus($this->statusFilter);
        }

        if (!empty($this->priorityFilter)) {
            $query->byPriority($this->priorityFilter);
        }

        return $query->orderBy('due_date')->orderBy('created_at', 'desc');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->priorityFilter = '';
        $this->resetPage();
    }

    public function markComplete($id)
    {
        try {
            $task = Task::findOrFail($id);
            // Own assigned tasks only — Tasks.Edit is granted module-wide to
            // several designations now (Designer/Developer/Tech Lead/...),
            // that's not "edit anyone's task", so this checks per-record.
            abort_unless($this->currentStaff && $task->assigned_to === $this->currentStaff->id, 403);
            if (method_exists($task, 'markAsCompleted')) {
                $task->markAsCompleted();
            } else {
                $task->update(['status' => 'completed', 'completed_at' => now()]);
            }
            session()->flash('success', 'Task marked as completed!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating task: ' . $e->getMessage());
        }
    }

    public function getStatsProperty()
    {
        $base = $this->staffLinked ? Task::query()->assignedTo($this->currentStaff->id) : Task::query();

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->pending()->count(),
            'inProgress' => (clone $base)->inProgress()->count(),
            'overdue' => (clone $base)->overdue()->count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'tasks' => $this->fetchTasks()->paginate(15),
            'stats' => $this->stats,
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>My Tasks</h1>
                <p>Tasks currently assigned to you.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Task
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

        @if(!$staffLinked)
            <div class="alert-flash alert-flash-warning">
                <i class="fas fa-info-circle"></i>
                No staff profile is linked to your account, so we can't determine "your" tasks. Showing all tasks instead.
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <p class="stat-number">{{ $stats['pending'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3>In Progress</h3>
                        <p class="stat-number">{{ $stats['inProgress'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Overdue</h3>
                        <p class="stat-number">{{ $stats['overdue'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
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
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="in_progress">🔄 In Progress</option>
                            <option value="completed">✅ Completed</option>
                            <option value="cancelled">❌ Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-flag me-1 text-muted"></i>
                            Priority
                        </label>
                        <select class="form-select" wire:model.live="priorityFilter">
                            <option value="">All Priorities</option>
                            <option value="low">🟢 Low</option>
                            <option value="medium">🔵 Medium</option>
                            <option value="high">🟡 High</option>
                            <option value="urgent">🔴 Urgent</option>
                        </select>
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
                    <i class="fas fa-user-check me-2"></i>
                    {{ $staffLinked ? 'My Task List' : 'Task List' }}
                </h3>
                <span class="badge bg-primary">{{ $tasks->total() }} Tasks</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th style="width: 130px;">Actions</th>
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
                                    <span class="badge {{ $task->priority_badge['class'] }}">
                                        {{ $task->priority_badge['icon'] }} {{ ucfirst($task->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $task->status_badge['class'] }}">
                                        {{ $task->status_badge['icon'] }} {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $task->assignedTo->name ?? 'Unassigned' }}
                                </td>
                                <td>
                                    @if($task->due_date)
                                        <span class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                            @if($task->isOverdue())
                                                <i class="fas fa-triangle-exclamation me-1"></i>
                                            @endif
                                            {{ $task->due_date->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!in_array($task->status, ['completed', 'cancelled']))
                                        <button class="btn btn-sm btn-outline-success"
                                                wire:click="markComplete({{ $task->id }})"
                                                wire:confirm="Mark this task as completed?">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    @else
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Done
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-user-check fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No tasks found</h5>
                                    <p class="text-muted">
                                        @if($staffLinked)
                                            You have no tasks matching these filters. Enjoy the calm!
                                        @else
                                            There are no tasks in the system yet.
                                        @endif
                                    </p>
                                    <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create Task
                                    </a>
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
                            @if($search || $statusFilter || $priorityFilter)
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $statusFilter || $priorityFilter)
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
