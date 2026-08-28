<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\staff;
use App\Models\Designation;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $designation = '';
    public $status = '';
    public $designations = [];
    public $selectedStaff = [];
    public $selectAll = false;

    public function mount()
    {
        $this->designations = Designation::where('status', 'active')->orderBy('name')->get();
    }

    protected function baseQuery()
    {
        $query = staff::query();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->designation)) {
            $query->where('designation', $this->designation);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedDesignation() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedStaff = $this->baseQuery()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedStaff = [];
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->designation = '';
        $this->status = '';
        $this->resetPage();
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Staff', 'Delete')) {
            session()->flash('error', "You don't have permission to delete staff.");
            return;
        }

        try {
            staff::findOrFail($id)->delete();
            session()->flash('success', 'Staff member deleted successfully!');
            $this->selectedStaff = array_diff($this->selectedStaff, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting staff member: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Staff', 'Delete')) {
            session()->flash('error', "You don't have permission to delete staff.");
            return;
        }

        if (empty($this->selectedStaff)) {
            session()->flash('warning', 'Please select at least one staff member to delete.');
            return;
        }

        try {
            // Iterate (rather than a single mass-delete query) so each
            // model's deleting event fires and cleans up its photo.
            staff::whereIn('id', $this->selectedStaff)->get()->each->delete();
            session()->flash('success', count($this->selectedStaff) . ' staff member(s) deleted successfully!');
            $this->selectedStaff = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting staff: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return $this->view([
            'staffs' => $this->baseQuery()->paginate(15),
            'stats' => [
                'total' => staff::count(),
                'active' => staff::where('status', 'active')->count(),
                'inactive' => staff::where('status', 'inactive')->count(),
            ],
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>All Staff</h1>
                <p>Manage your staff members.</p>
            </div>
            <div class="header-actions">
                @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Staff', 'Create'))
                    <a href="{{ route('staff.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Staff
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
                                   placeholder="Search by name or email...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-briefcase me-1 text-muted"></i>
                            Designation
                        </label>
                        <select class="form-select" wire:model.live="designation">
                            <option value="">All Designations</option>
                            @foreach ($designations as $d)
                                <option value="{{ $d->name }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Status</option>
                            <option value="active">🟢 Active</option>
                            <option value="inactive">🔴 Inactive</option>
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

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Staff</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active</h3>
                        <p class="stat-number">{{ $stats['active'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Inactive</h3>
                        <p class="stat-number">{{ $stats['inactive'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedStaff) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedStaff) }} staff member(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected staff?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedStaff', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Staff Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users me-2"></i>
                    Staff List
                </h3>
                <span class="badge bg-primary">{{ $staffs->total() }} Staff</span>
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
                                <th>Name</th>
                                <th>Email</th>
                                <th>Designation</th>
                                <th>Joining Date</th>
                                <th>Status</th>
                                <th>Login</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staffs as $member)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $member->id }}"
                                           wire:model.live="selectedStaff">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($member->image)
                                            <img src="{{ asset('storage/'.$member->image) }}" class="rounded-circle me-2" width="32" height="32" alt="">
                                        @else
                                            <i class="fas fa-user-circle fa-2x text-muted me-2"></i>
                                        @endif
                                        <div>
                                            <a href="{{ route('attendance.person', ['type' => 'staff', 'id' => $member->id]) }}"
                                               class="fw-semibold text-decoration-none d-block" style="color:#1f2937;">{{ $member->name }}</a>
                                            <small class="text-muted">{{ ucwords(str_replace('_', ' ', $member->employment_type ?? 'full_time')) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $member->designation ?? 'N/A' }}</span>
                                    @if (($member->employment_type ?? '') === 'intern')
                                        <span class="badge bg-info">Intern</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $member->joining_date ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    @if($member->status === 'inactive')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Inactive
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($member->user_id)
                                        <span class="badge bg-success"><i class="fas fa-key me-1"></i>Active</span>
                                    @else
                                        <a href="{{ route('staff.show', $member->id) }}" class="badge bg-secondary text-decoration-none">
                                            <i class="fas fa-key me-1"></i>None
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('staff.show', $member->id) }}" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('attendance.person', ['type' => 'staff', 'id' => $member->id]) }}" class="btn btn-outline-success" title="Attendance">
                                            <i class="fas fa-calendar-check"></i>
                                        </a>
                                        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Staff', 'Edit'))
                                            <a href="{{ route('staff.edit', $member->id) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Staff', 'Delete'))
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $member->id }})"
                                                    wire:confirm="Are you sure you want to delete this staff member?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No staff found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('staff.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add Staff
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
                            Showing {{ $staffs->firstItem() ?? 0 }}-{{ $staffs->lastItem() ?? 0 }} of {{ $staffs->total() }}
                            @if($search || $designation || $status !== '')
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $designation || $status !== '')
                            <button class="btn btn-sm btn-outline-secondary ms-2" wire:click="resetFilters">
                                <i class="fas fa-undo"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                    @if($staffs->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $staffs->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($staffs->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $staffs->currentPage() - 2); $page <= min($staffs->lastPage(), $staffs->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $staffs->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$staffs->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$staffs->hasMorePages()) disabled @endif>
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
