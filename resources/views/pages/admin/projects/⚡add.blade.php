<?php

use Livewire\Component;
use App\Models\Project;
use App\Models\company;

new class extends Component
{
    public $name;
    public $company_id;
    public $description;
    public $start_date;
    public $end_date;
    public $status = 'planning';
    public $progress = 0;
    public $budget;
    public $submission_due_at;

    protected $rules = [
        'name' => 'required|string|max:255',
        'company_id' => 'nullable|exists:companies,id',
        'description' => 'nullable|string',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'required|in:planning,in_progress,on_hold,completed,cancelled',
        'progress' => 'nullable|integer|min:0|max:100',
        'budget' => 'nullable|numeric|min:0',
        'submission_due_at' => 'nullable|date',
    ];

    protected $messages = [
        'name.required' => 'Please enter a project name.',
        'end_date.after_or_equal' => 'End date cannot be before the start date.',
        'status.in' => 'Please select a valid project status.',
    ];

    public function form_submit()
    {
        $this->validate();

        Project::create([
            'name' => $this->name,
            'company_id' => $this->company_id ?: null,
            'description' => $this->description,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'status' => $this->status ?: 'planning',
            'progress' => $this->progress ?: 0,
            'budget' => $this->budget !== '' ? $this->budget : null,
            'submission_due_at' => $this->submission_due_at ?: null,
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Project created successfully');

        return redirect()->route('projects.all');
    }

    public function render()
    {
        return $this->view([
            'companies' => company::orderBy('company_name')->get(),
        ]);
    }
};
?>

<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Add New Project</h1>
                <p>Create a new project record.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('projects.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
                <button type="button" form="projectForm" wire:click="form_submit" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Project
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

        <!-- Project Form -->
        <div class="card">
            <div class="card-body">
                <form id="projectForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-diagram-project me-1 text-muted"></i>
                                Project Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Enter project name">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-building me-1 text-muted"></i>
                                Company
                            </label>
                            <select class="form-select" wire:model="company_id">
                                <option value="">Select Company</option>
                                @foreach ($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">
                                <i class="fas fa-align-left me-1 text-muted"></i>
                                Description
                            </label>
                            <textarea class="form-control" wire:model="description" rows="3" placeholder="Enter project description..."></textarea>
                            @error('description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                Start Date
                            </label>
                            <input type="date" class="form-control" wire:model="start_date">
                            @error('start_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-calendar-check me-1 text-muted"></i>
                                End Date
                            </label>
                            <input type="date" class="form-control" wire:model="end_date">
                            @error('end_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-circle me-1 text-muted"></i>
                                Status
                            </label>
                            <select class="form-select" wire:model="status">
                                <option value="planning">Planning</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            @error('status')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-percent me-1 text-muted"></i>
                                Progress (%)
                            </label>
                            <input type="number" min="0" max="100" class="form-control" wire:model="progress" placeholder="0">
                            @error('progress')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-dollar-sign me-1 text-muted"></i>
                                Budget
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="budget" placeholder="0.00">
                            @error('budget')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-hourglass-half me-1 text-muted"></i>
                                Submission Deadline
                            </label>
                            <input type="datetime-local" class="form-control" wire:model="submission_due_at">
                            <small class="text-muted">Shown to the client as a live countdown.</small>
                            @error('submission_due_at')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
