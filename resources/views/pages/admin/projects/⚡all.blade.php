<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Project;
use App\Models\company;
use App\Models\staff;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $companyFilter = '';
    public $sortBy = 'newest';
    public $selectedProjects = [];
    public $selectAll = false;

    /** Company-wide project visibility: any real authority on the Projects
     * module (not just Edit — COO's is Assign, CEO's is Approve). Everyone
     * else is scoped to projects they're actually staffed on. */
    private function hasCompanyWideProjectAccess($user): bool
    {
        return $user->role === 'admin'
            || $user->hasPermission('Projects', 'Edit')
            || $user->hasPermission('Projects', 'Assign')
            || $user->hasPermission('Projects', 'Approve');
    }

    protected function fetchProjects()
    {
        $query = Project::query()->with('company');

        // Non-manager staff (regular staff / interns) only see projects they're assigned to.
        $user = auth()->user();
        if ($user && ! $this->hasCompanyWideProjectAccess($user)) {
            $myStaffId = staff::where('user_id', $user->id)->value('id');
            $query->whereHas('staff', fn ($q) => $q->where('staff.id', $myStaffId));
        }

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->companyFilter !== '') {
            $query->where('company_id', $this->companyFilter);
        }

        switch ($this->sortBy) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'progress_high':
                $query->orderBy('progress', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedCompanyFilter()
    {
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedProjects = $this->fetchProjects()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedProjects = [];
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Projects', 'Delete')) {
            session()->flash('error', "You don't have permission to delete projects.");
            return;
        }

        try {
            Project::findOrFail($id)->delete();
            session()->flash('success', 'Project deleted successfully!');
            $this->selectedProjects = array_diff($this->selectedProjects, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting project: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Projects', 'Delete')) {
            session()->flash('error', "You don't have permission to delete projects.");
            return;
        }

        if (empty($this->selectedProjects)) {
            session()->flash('warning', 'Please select at least one project to delete.');
            return;
        }

        try {
            Project::whereIn('id', $this->selectedProjects)->delete();
            session()->flash('success', count($this->selectedProjects) . ' project(s) deleted successfully!');
            $this->selectedProjects = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting projects: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->companyFilter = '';
        $this->sortBy = 'newest';
        $this->resetPage();
    }

    public function getStats()
    {
        return [
            'total' => Project::count(),
            'in_progress' => Project::where('status', 'in_progress')->count(),
            'completed' => Project::where('status', 'completed')->count(),
            'budget' => Project::sum('budget'),
        ];
    }

    public function render()
    {
        return $this->view([
            'projects' => $this->fetchProjects()->paginate(15),
            'stats' => $this->getStats(),
            'companies' => company::orderBy('company_name')->get(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>All Projects</h1>
                <p>Manage and track all agency projects.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('projects.add') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Project
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

        @if (session()->has('warning'))
            <div class="alert-flash alert-flash-warning">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('warning') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

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
                                   placeholder="Search projects...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-building me-1 text-muted"></i>
                            Company
                        </label>
                        <select class="form-select" wire:model.live="companyFilter">
                            <option value="">All Companies</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Status</option>
                            <option value="planning">Planning</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-sort me-1 text-muted"></i>
                            Sort By
                        </label>
                        <select class="form-select" wire:model.live="sortBy">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="progress_high">Highest Progress</option>
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

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-diagram-project"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Projects</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3>In Progress</h3>
                        <p class="stat-number">{{ $stats['in_progress'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Completed</h3>
                        <p class="stat-number">{{ $stats['completed'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Budget</h3>
                        <p class="stat-number">${{ number_format($stats['budget'] ?? 0, 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedProjects) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedProjects) }} project(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected projects?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedProjects', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Projects Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-diagram-project me-2"></i>
                    Projects List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $projects->total() }} Projects</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchProjects">
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
                                <th>Project</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Budget</th>
                                <th>Dates</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($projects as $project)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $project->id }}"
                                           wire:model.live="selectedProjects">
                                </td>
                                <td>
                                    <h6 class="mb-0 fw-semibold">
                                        <a href="{{ route('projects.show', $project->id) }}" class="text-decoration-none">
                                            {{ $project->name }}
                                        </a>
                                    </h6>
                                </td>
                                <td>{{ $project->company->company_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $project->status_badge['class'] }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                    </span>
                                </td>
                                <td style="min-width:140px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $project->progress }}%;" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted">{{ $project->progress }}%</small>
                                    </div>
                                </td>
                                <td>{{ $project->budget !== null ? '$' . number_format($project->budget, 2) : 'N/A' }}</td>
                                <td>
                                    <small class="text-muted">
                                        {{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}
                                        &ndash;
                                        {{ $project->end_date ? $project->end_date->format('M d, Y') : 'N/A' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger"
                                                wire:click="delete({{ $project->id }})"
                                                wire:confirm="Are you sure you want to delete this project?">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-diagram-project fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No projects found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('projects.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add New Project
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
                            Showing {{ $projects->firstItem() ?? 0 }}-{{ $projects->lastItem() ?? 0 }} of {{ $projects->total() }}
                            @if($search || $status !== '' || $companyFilter !== '')
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $status !== '' || $companyFilter !== '')
                            <button class="btn btn-sm btn-outline-secondary ms-2" wire:click="resetFilters">
                                <i class="fas fa-undo"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                    @if($projects->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $projects->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($projects->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $projects->currentPage() - 2); $page <= min($projects->lastPage(), $projects->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $projects->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$projects->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$projects->hasMorePages()) disabled @endif>
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
