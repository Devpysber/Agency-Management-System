<?php

use Livewire\Component;
use App\Models\Staff;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;
    // Form properties
    public $staff_id;
    public $photo;
    public $name;
    public $email;
    public $whatsapp;
    public $designation;
    public $joining_date;
    public $user_id;
    public $salary;
    
    // List properties
    public $staffs;
    public $search = '';
    public $showModal = false;
    public $viewingStaff = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:staff,email',
        'whatsapp' => 'nullable|string|max:20',
        'designation' => 'required|string|max:255',
        'joining_date' => 'required|date',
        'salary' => 'nullable|numeric|min:0',
        'user_id' => 'nullable|exists:users,id',
    ];

    public function mount()
    {
        $this->fetchStaff();
    }

    public function fetchStaff()
    {
        $query = Staff::query();
        
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('designation', 'like', '%' . $this->search . '%');
            });
        }
        
        $this->staffs = $query->orderBy('created_at', 'desc')->get();
    }

    public function updatedSearch()
    {
        $this->fetchStaff();
    }

    public function resetForm()
    {
        $this->staff_id = null;
        $this->image = null;
        $this->name = null;
        $this->email = null;
        $this->whatsapp = null;
        $this->designation = null;
        $this->joining_date = null;
        $this->user_id = null;
        $this->salary = null;
    }

    public function save()
    {
        // For update, remove unique validation for current record
        if ($this->staff_id) {
            $rules = $this->rules;
            $rules['email'] = 'required|email|unique:staff,email,' . $this->staff_id;
            $this->validate($rules);
        } else {
            $this->validate();
        }
      //  $imagePath = null;
        if($this->photo){
            $imageName = time().'.'.$this->photo->extension();
            $this->photo->storeAs('staffs',$imageName,'public');
            $imagePath = 'staffs/'.$imageName;
        }
        if($this->staff_id){
            $staff = staff::find($this->staff_id);
        } else {
            $staff = new staff;
        }
        $staff->name = $this->name;
        $staff->email = $this->email;
        $staff->whatsapp = $this->whatsapp;
        $staff->designation = $this->designation;
        $staff->joining_date = $this->joining_date;
        $staff->user_id = $this->user_id;
        $staff->salary = $this->salary;
        if($this->photo){
            $staff->image = $imagePath;
        }
        $staff->save();
        
        $this->resetForm();
        $this->fetchStaff();
        session()->flash('success','Staff saved successfully!');

    }

    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        $this->staff_id = $staff->id;
        $this->name = $staff->name;
        $this->email = $staff->email;
        $this->whatsapp = $staff->whatsapp;
        $this->designation = $staff->designation;
        $this->joining_date = $staff->joining_date;
        $this->user_id = $staff->user_id;
        $this->salary = $staff->salary;
        
        // Scroll to form
        $this->dispatch('scrollToForm');
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Staff', 'Delete')) {
            session()->flash('error', "You don't have permission to delete staff.");
            return;
        }

        try {
            $staff = Staff::findOrFail($id);
            $staff->delete();
            session()->flash('success', 'Staff deleted successfully!');
            $this->fetchStaff();
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting staff: ' . $e->getMessage());
        }
    }

    public function viewStaff($id)
    {
        $this->viewingStaff = Staff::findOrFail($id);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->viewingStaff = null;
    }

    public function cancel()
    {
        $this->resetForm();
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Staff Management</h1>
                <p>Manage your staff members efficiently.</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" wire:click="fetchStaff">
                    <i class="fas fa-sync-alt"></i> Refresh
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

        <div class="row g-4">
            <!-- Left Column - Form -->
            <div class="col-lg-4">
                <div class="card" id="formCard">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-user-plus text-primary me-2"></i>
                            {{ $staff_id ? 'Edit Staff' : 'Add New Staff' }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="save" >
                             <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-user me-1 text-muted"></i>
                                    Photo <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" 
                                       wire:model="photo" placeholder="Enter full name">
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-user me-1 text-muted"></i>
                                    Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       wire:model="name" placeholder="Enter full name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-envelope me-1 text-muted"></i>
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       wire:model="email" placeholder="Enter email address">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fab fa-whatsapp me-1 text-success"></i>
                                    WhatsApp
                                </label>
                                <input type="text" class="form-control @error('whatsapp') is-invalid @enderror" 
                                       wire:model="whatsapp" placeholder="Enter WhatsApp number">
                                @error('whatsapp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-briefcase me-1 text-muted"></i>
                                    Designation <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('designation') is-invalid @enderror" 
                                       wire:model="designation" placeholder="Enter designation">
                                @error('designation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                    Joining Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control @error('joining_date') is-invalid @enderror" 
                                       wire:model="joining_date">
                                @error('joining_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-money-bill me-1 text-muted"></i>
                                    Salary
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control @error('salary') is-invalid @enderror" 
                                           wire:model="salary" placeholder="0.00" step="0.01">
                                </div>
                                @error('salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas {{ $staff_id ? 'fa-save' : 'fa-plus' }} me-2"></i>
                                    {{ $staff_id ? 'Update Staff' : 'Add Staff' }}
                                </button>
                                @if($staff_id)
                                    <button type="button" class="btn btn-secondary" wire:click="cancel">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Data Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-semibold mb-0">
                                <i class="fas fa-users text-primary me-2"></i>
                                Staff List
                            </h5>
                            <span class="badge bg-primary">{{ $staffs->count() }} Staff</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" 
                                       wire:model.live="search" 
                                       placeholder="Search by name, email, or designation...">
                            </div>
                        </div>

                        <!-- Staff Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Designation</th>
                                        <th>Salary</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($staffs as $index => $staff)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">
                                                    <img src="{{ asset('storage/'.$staff->image) }}" class="rounded-circle me-2" width="30" height="30" alt="">    
                                                    {{ $staff->name }}
                                                    </h6>
                                                    @if($staff->whatsapp)
                                                        <small class="text-muted">
                                                            <i class="fab fa-whatsapp text-success"></i>
                                                            {{ $staff->whatsapp }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $staff->designation }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">
                                                ${{ number_format($staff->salary ?? 0, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-info" wire:click="viewStaff({{ $staff->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" wire:click="edit({{ $staff->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" 
                                                        wire:click="delete({{ $staff->id }})" 
                                                        wire:confirm="Are you sure you want to delete this staff member?">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                            <h5 class="text-muted">No staff found</h5>
                                            <p class="text-muted">Add a new staff member using the form on the left.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Staff Modal -->
        @if($showModal && $viewingStaff)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-circle text-primary me-2"></i>
                            Staff Details
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Name</label>
                                <p class="fw-semibold">{{ $viewingStaff->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Email</label>
                                <p>{{ $viewingStaff->email }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fab fa-whatsapp text-success"></i> WhatsApp
                                </label>
                                <p>{{ $viewingStaff->whatsapp ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Designation</label>
                                <p>
                                    <span class="badge bg-secondary">{{ $viewingStaff->designation }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Joining Date</label>
                                <p>{{ $viewingStaff->joining_date ? $viewingStaff->joining_date : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Salary</label>
                                <p class="fw-semibold text-success">${{ number_format($viewingStaff->salary ?? 0, 2) }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">User ID</label>
                                <p>{{ $viewingStaff->user_id ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Status</label>
                                <p>
                                    <span class="badge bg-success">
                                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                        Active
                                    </span>
                                </p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Created At</label>
                                <p>{{ $viewingStaff->created_at ? $viewingStaff->created_at : 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Updated At</label>
                                <p>{{ $viewingStaff->updated_at ? $viewingStaff->updated_at->diffForHumans() : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="closeModal">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button class="btn btn-primary" wire:click="edit({{ $viewingStaff->id }})" wire:click="closeModal">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for scroll to form event
        window.addEventListener('scrollToForm', function() {
            document.getElementById('formCard').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        });
    });
</script>