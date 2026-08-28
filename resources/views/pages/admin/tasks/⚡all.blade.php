<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Task;
use App\Models\staff;

new class extends Component
{
    use WithPagination;

    /** Company-wide task visibility: Tasks.Assign is the team-wide-manager
     * signal (PM/Tech Lead/COO) — Tasks.Edit is granted broadly to
     * individual contributors for THEIR OWN work and must NOT unlock
     * everyone else's tasks. Everyone else is scoped to their own. */
    private function hasCompanyWideTaskAccess($user): bool
    {
        return $user->role === 'admin' || $user->hasPermission('Tasks', 'Assign');
    }

    // Filters
    public $search = '';
    public $statusFilter = '';
    public $priorityFilter = '';
    public $assignedFilter = '';

    // Bulk select
    public $selectedTasks = [];
    public $selectAll = false;

    // Edit modal
    public $showEditModal = false;
    public $editingTaskId;
    public $title;
    public $description;
    public $priority = 'medium';
    public $status = 'pending';
    public $due_date;
    public $assigned_to;

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'priority' => 'required|in:low,medium,high,urgent',
        'status' => 'required|in:pending,in_progress,completed,cancelled',
        'due_date' => 'nullable|date',
        'assigned_to' => 'nullable|exists:staff,id',
    ];

    protected function fetchTasks()
    {
        $query = Task::query()->with(['assignedTo', 'createdBy']);

        // Non-manager staff/interns only see tasks assigned to them.
        $user = auth()->user();
        if ($user && ! $this->hasCompanyWideTaskAccess($user)) {
            $myStaffId = staff::where('user_id', $user->id)->value('id');
            $query->where('assigned_to', $myStaffId);
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

        if (!empty($this->assignedFilter)) {
            $query->assignedTo($this->assignedFilter);
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

    public function updatedAssignedFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTasks = $this->fetchTasks()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedTasks = [];
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->priorityFilter = '';
        $this->assignedFilter = '';
        $this->resetPage();
    }

    public function getStatsProperty()
    {
        return [
            'total' => Task::count(),
            'pending' => Task::pending()->count(),
            'inProgress' => Task::inProgress()->count(),
            'overdue' => Task::overdue()->count(),
        ];
    }

    public function openCreateLink()
    {
        return redirect()->route('tasks.create');
    }

    public function resetForm()
    {
        $this->editingTaskId = null;
        $this->title = null;
        $this->description = null;
        $this->priority = 'medium';
        $this->status = 'pending';
        $this->due_date = null;
        $this->assigned_to = null;
        $this->resetErrorBag();
    }

    public function editTask($id)
    {
        $task = Task::findOrFail($id);
        $this->editingTaskId = $task->id;
        $this->title = $task->title;
        $this->description = $task->description;
        $this->priority = $task->priority;
        $this->status = $task->status;
        $this->due_date = $task->due_date ? $task->due_date->format('Y-m-d') : null;
        $this->assigned_to = $task->assigned_to;
        $this->showEditModal = true;
    }

    public function updateTask()
    {
        $this->validate();

        try {
            $task = Task::findOrFail($this->editingTaskId);

            // Tasks.Edit is granted module-wide to several designations now
            // (Designer/Developer/Tech Lead/...) for updating THEIR OWN
            // assigned work — not full admin edit (incl. reassignment) of
            // anyone's task. That needs Tasks.Assign (team-wide authority,
            // e.g. PM/Tech Lead/COO) or it must be the assignee's own task.
            // NOTE: do not OR this with Tasks.Edit — that's the individual-
            // contributor grant this check exists to NOT let bypass isMine().
            $user = auth()->user();
            $myStaffId = staff::where('user_id', $user->id)->value('id');
            $isMine = $myStaffId && $task->assigned_to === $myStaffId;
            abort_unless(
                $isMine || $user->role === 'admin' || $user->hasPermission('Tasks', 'Assign'),
                403
            );

            $task->title = $this->title;
            $task->description = $this->description;
            $task->priority = $this->priority;
            $task->status = $this->status;
            $task->due_date = $this->due_date;
            $task->assigned_to = $this->assigned_to;

            if ($this->status === 'completed' && !$task->completed_at) {
                $task->completed_at = now();
            } elseif ($this->status !== 'completed') {
                $task->completed_at = null;
            }

            $task->save();

            session()->flash('success', 'Task updated successfully!');
            $this->showEditModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating task: ' . $e->getMessage());
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function deleteTask($id)
    {
        try {
            Task::findOrFail($id)->delete();
            session()->flash('success', 'Task deleted successfully!');
            $this->selectedTasks = array_diff($this->selectedTasks, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting task: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selectedTasks)) {
            session()->flash('warning', 'Please select at least one task to delete.');
            return;
        }

        try {
            Task::whereIn('id', $this->selectedTasks)->delete();
            session()->flash('success', count($this->selectedTasks) . ' task(s) deleted successfully!');
            $this->selectedTasks = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting tasks: ' . $e->getMessage());
        }
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
                <h1>All Tasks</h1>
                <p>Manage and track every task in your CRM system.</p>
            </div>
            <div class="header-actions">
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Tasks', 'Edit')))
                    <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Task
                    </a>
                @endif
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

        @if (session()->has('warning'))
            <div class="alert-flash alert-flash-warning">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('warning') }}
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
                        <h3>Total Tasks</h3>
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
                    <div class="col-md-3">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                        <button class="btn btn-secondary w-100" wire:click="resetFilters" title="Reset Filters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedTasks) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedTasks) }} task(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure you want to delete selected tasks?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedTasks', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Tasks Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-check me-2"></i>
                    Tasks List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $tasks->total() }} Tasks</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchTasks">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input"
                                           wire:model.live="selectAll">
                                </th>
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
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $task->id }}"
                                           wire:model.live="selectedTasks">
                                </td>
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
                                    <div class="btn-group btn-group-sm">
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Tasks', 'Edit')))
                                            <button class="btn btn-outline-secondary" wire:click="editTask({{ $task->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="deleteTask({{ $task->id }})"
                                                    wire:confirm="Are you sure you want to delete this task?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-list-check fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No tasks found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
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
                            @if($search || $statusFilter || $priorityFilter || $assignedFilter)
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $statusFilter || $priorityFilter || $assignedFilter)
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

    <!-- Edit Task Modal -->
    @if($showEditModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit text-primary me-2"></i>
                        Edit Task
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateTask">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   wire:model="title" placeholder="Enter task title">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      wire:model="description" rows="3" placeholder="Enter task description"></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Priority</label>
                                <select class="form-select" wire:model="priority">
                                    <option value="low">🟢 Low</option>
                                    <option value="medium">🔵 Medium</option>
                                    <option value="high">🟡 High</option>
                                    <option value="urgent">🔴 Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Status</label>
                                <select class="form-select" wire:model="status">
                                    <option value="pending">⏳ Pending</option>
                                    <option value="in_progress">🔄 In Progress</option>
                                    <option value="completed">✅ Completed</option>
                                    <option value="cancelled">❌ Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Due Date</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                       wire:model="due_date">
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Assigned To</label>
                                <select class="form-select" wire:model="assigned_to">
                                    <option value="">Unassigned</option>
                                    @foreach($staffList as $staffMember)
                                        <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeEditModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" wire:click="updateTask">
                        <i class="fas fa-save"></i> Update Task
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
