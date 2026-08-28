<?php

use Livewire\Component;
use App\Models\Designation;

new class extends Component
{
    public $designationId;
    public $name;
    public $status = 'active';

    public $designations;
    public $search = '';
    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'status' => 'nullable|string',
    ];

    public function mount()
    {
        $this->fetchDesignations();
    }

    public function fetchDesignations()
    {
        $query = Designation::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->designations = $query->orderBy('name')->get();
    }

    public function updatedSearch()
    {
        $this->fetchDesignations();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->designationId = null;
        $this->name = null;
        $this->status = 'active';
        $this->resetErrorBag();
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('Staff', $this->designationId ? 'Edit' : 'Create')) {
            session()->flash('error', "You don't have permission to " . ($this->designationId ? 'edit' : 'create') . ' designations.');
            return;
        }

        if ($this->designationId) {
            $this->validate();
            $designation = Designation::find($this->designationId);
        } else {
            $this->validate();
            $designation = new Designation;
        }

        $designation->name = $this->name;
        $designation->status = $this->status ?: 'active';
        $designation->save();

        session()->flash('success', 'Designation saved successfully!');

        $this->showModal = false;
        $this->resetForm();
        $this->fetchDesignations();
    }

    public function edit($id)
    {
        $designation = Designation::findOrFail($id);
        $this->designationId = $designation->id;
        $this->name = $designation->name;
        $this->status = $designation->status;
        $this->showModal = true;
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Staff', 'Delete')) {
            session()->flash('error', "You don't have permission to delete designations.");
            return;
        }

        try {
            Designation::findOrFail($id)->delete();
            session()->flash('success', 'Designation deleted successfully!');
            $this->fetchDesignations();
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting designation: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return $this->view()->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Designations</h1>
                <p>Manage staff designations / job titles.</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" wire:click="openAddModal">
                    <i class="fas fa-plus"></i> Add Designation
                </button>
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

        <!-- Designations Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-semibold mb-0">
                        <i class="fas fa-id-badge text-primary me-2"></i>
                        Designations List
                    </h5>
                    <span class="badge bg-primary">{{ $designations->count() }} Designations</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Search -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control"
                               wire:model.live="search"
                               placeholder="Search designations...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($designations as $designation)
                            <tr>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $designation->name }}</h6>
                                </td>
                                <td>
                                    @if($designation->status === 'inactive')
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
                                    <div class="btn-group btn-group-sm">
                                        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Staff', 'Edit'))
                                            <button class="btn btn-outline-secondary" wire:click="edit({{ $designation->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Staff', 'Delete'))
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $designation->id }})"
                                                    wire:confirm="Are you sure you want to delete this designation?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-id-badge fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No designations found</h5>
                                    <p class="text-muted">Add a new designation to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Designation Modal -->
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-id-badge text-primary me-2"></i>
                        {{ $designationId ? 'Edit Designation' : 'Add Designation' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-id-badge me-1 text-muted"></i>
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   wire:model="name" placeholder="Enter designation name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-circle me-1 text-muted"></i>
                                Status
                            </label>
                            <select class="form-select" wire:model="status">
                                <option value="active">🟢 Active</option>
                                <option value="inactive">🔴 Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas {{ $designationId ? 'fa-save' : 'fa-plus' }}"></i>
                        {{ $designationId ? 'Update' : 'Add' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
