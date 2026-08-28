<?php

use Livewire\Component;
use App\Models\staff;
use App\Models\Designation;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $photo;
    public $name;
    public $email;
    public $whatsapp;
    public $aadhar;
    public $pan;
    public $designation;
    public $employment_type = 'full_time';
    public $shift_start = '09:00';
    public $daily_hours = 8;
    public $joining_date;
    public $tenure_start;
    public $tenure_end;
    public $salary;
    public $status = 'active';
    public $designations = [];

    protected $rules = [
        'photo' => 'nullable|image|max:2048',
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:staff,email',
        'whatsapp' => 'nullable|string|max:20',
        'aadhar' => 'nullable|string|regex:/^\d{4}\s?\d{4}\s?\d{4}$/',
        'pan' => 'nullable|string|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
        'designation' => 'required|string|max:255',
        'employment_type' => 'required|in:full_time,intern,contract',
        'shift_start' => 'required|date_format:H:i',
        'daily_hours' => 'required|integer|min:1|max:16',
        'joining_date' => 'required|date',
        'tenure_start' => 'nullable|date',
        'tenure_end' => 'nullable|date|after_or_equal:tenure_start',
        'salary' => 'nullable|numeric|min:0',
        'status' => 'nullable|string',
    ];

    protected $messages = [
        'aadhar.regex' => 'Aadhaar must be 12 digits.',
        'pan.regex' => 'PAN must be 10 characters (e.g. ABCDE1234F).',
    ];

    public function updatedPan($v) { $this->pan = strtoupper(trim((string) $v)); }

    public function mount()
    {
        abort_unless(auth()->user()->role === 'admin' || auth()->user()->hasPermission('Staff', 'Create'), 403);
        $this->designations = Designation::where('status', 'active')->orderBy('name')->get();
    }

    public function save()
    {
        $this->validate();

        $imagePath = null;
        if ($this->photo) {
            $imagePath = $this->photo->store('staffs', 'public');
        }

        $member = new staff;
        $member->name = $this->name;
        $member->email = $this->email;
        $member->whatsapp = $this->whatsapp;
        $member->aadhar = $this->aadhar ?: null;
        $member->pan = $this->pan ?: null;
        $member->designation = $this->designation;
        $member->employment_type = $this->employment_type ?: 'full_time';
        $member->shift_start = $this->shift_start ?: '09:00';
        $member->daily_hours = (int) ($this->daily_hours ?: 8);
        $member->joining_date = $this->joining_date;
        $member->tenure_start = $this->employment_type === 'intern' ? ($this->tenure_start ?: null) : null;
        $member->tenure_end = $this->employment_type === 'intern' ? ($this->tenure_end ?: null) : null;
        $member->salary = $this->salary;
        $member->status = $this->status ?: 'active';
        if ($imagePath) {
            $member->image = $imagePath;
        }
        $member->save();

        session()->flash('success', 'Staff member added successfully!');

        return redirect()->route('staff.all');
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
                <h1>Add Staff</h1>
                <p>Create a new staff member record.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('staff.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Staff
                </a>
                <button type="button" wire:click="save" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Staff
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

        <!-- Staff Form -->
        <div class="card">
            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-user text-primary me-2"></i>
                                    Basic Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-user me-1 text-muted"></i>
                                            Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Enter full name">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" placeholder="Enter email address">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-whatsapp me-1 text-success"></i>
                                            WhatsApp
                                        </label>
                                        <input type="text" class="form-control @error('whatsapp') is-invalid @enderror" wire:model="whatsapp" placeholder="Enter WhatsApp number">
                                        @error('whatsapp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium"><i class="fas fa-id-card me-1 text-muted"></i> Aadhaar</label>
                                        <input type="text" maxlength="14" class="form-control @error('aadhar') is-invalid @enderror" wire:model="aadhar" placeholder="XXXX XXXX XXXX">
                                        @error('aadhar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium"><i class="fas fa-id-badge me-1 text-muted"></i> PAN</label>
                                        <input type="text" maxlength="10" class="form-control @error('pan') is-invalid @enderror" wire:model.blur="pan" placeholder="ABCDE1234F" style="text-transform:uppercase;">
                                        @error('pan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-briefcase me-1 text-muted"></i>
                                            Designation <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('designation') is-invalid @enderror" wire:model="designation">
                                            <option value="">Select Designation</option>
                                            @foreach ($designations as $d)
                                                <option value="{{ $d->name }}">{{ $d->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('designation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium"><i class="fas fa-user-tag me-1 text-muted"></i> Employment Type <span class="text-danger">*</span></label>
                                        <select class="form-select @error('employment_type') is-invalid @enderror" wire:model.live="employment_type">
                                            <option value="full_time">Full-time</option>
                                            <option value="intern">Intern</option>
                                            <option value="contract">Contract</option>
                                        </select>
                                        @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium"><i class="fas fa-clock me-1 text-muted"></i> Shift Start <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control @error('shift_start') is-invalid @enderror" wire:model="shift_start">
                                        <small class="text-muted">Absent if no check-in by +1h30m.</small>
                                        @error('shift_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-medium"><i class="fas fa-hourglass me-1 text-muted"></i> Daily Hours <span class="text-danger">*</span></label>
                                        <input type="number" min="1" max="16" class="form-control @error('daily_hours') is-invalid @enderror" wire:model="daily_hours">
                                        @error('daily_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                            Joining Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control @error('joining_date') is-invalid @enderror" wire:model="joining_date">
                                        @error('joining_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    @if ($employment_type === 'intern')
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium"><i class="fas fa-hourglass-start me-1 text-muted"></i> Internship Start</label>
                                            <input type="date" class="form-control @error('tenure_start') is-invalid @enderror" wire:model="tenure_start">
                                            @error('tenure_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium"><i class="fas fa-hourglass-end me-1 text-muted"></i> Internship End</label>
                                            <input type="date" class="form-control @error('tenure_end') is-invalid @enderror" wire:model="tenure_end">
                                            @error('tenure_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-money-bill me-1 text-muted"></i>
                                            Salary
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control @error('salary') is-invalid @enderror" wire:model="salary" placeholder="0.00" step="0.01">
                                        </div>
                                        @error('salary')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-camera me-2"></i>
                                        Photo
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <input type="file" class="form-control @error('photo') is-invalid @enderror" wire:model="photo">
                                    @error('photo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" class="img-fluid rounded mt-3" alt="Preview">
                                    @endif
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-circle me-2" style="color: #10B981;"></i>
                                        Status
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <select class="form-select" wire:model="status">
                                        <option value="active">🟢 Active</option>
                                        <option value="inactive">🔴 Inactive</option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
