<?php

use Livewire\Component;
use App\Models\Task;
use App\Models\staff;
use App\Models\Project;
use App\Models\deal;
use App\Models\company;
use App\Models\Contact;

new class extends Component
{
    public $title;
    public $description;
    public $priority = 'medium';
    public $status = 'pending';
    public $due_date;
    public $assigned_to;

    public $related_type = '';
    public $related_to = '';
    public $relatedOptions = [];

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'priority' => 'required|in:low,medium,high,urgent',
        'status' => 'required|in:pending,in_progress,completed,cancelled',
        'due_date' => 'nullable|date',
        'assigned_to' => 'nullable|exists:staff,id',
        'related_type' => 'nullable|in:project,deal,company,contact',
        'related_to' => 'nullable',
    ];

    public function updatedRelatedType()
    {
        $this->related_to = '';
        $this->relatedOptions = [];

        switch ($this->related_type) {
            case 'project':
                $this->relatedOptions = Project::orderBy('name')->pluck('name', 'id')->toArray();
                break;
            case 'deal':
                $this->relatedOptions = deal::orderBy('deal_name')->pluck('deal_name', 'id')->toArray();
                break;
            case 'company':
                $this->relatedOptions = company::orderBy('company_name')->pluck('company_name', 'id')->toArray();
                break;
            case 'contact':
                $this->relatedOptions = Contact::orderBy('first_name')
                    ->get()
                    ->mapWithKeys(fn($c) => [$c->id => trim($c->first_name . ' ' . $c->last_name)])
                    ->toArray();
                break;
        }
    }

    public function save()
    {
        $this->validate();

        Task::create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'assigned_to' => $this->assigned_to ?: null,
            'created_by' => auth()->id(),
            'related_type' => $this->related_type ?: null,
            'related_to' => $this->related_type ? ($this->related_to ?: null) : null,
        ]);

        session()->flash('success', 'Task created successfully!');

        return redirect()->route('tasks.all');
    }

    public function render()
    {
        return $this->view([
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
                <h1>Create Task</h1>
                <p>Add a new task and optionally link it to a project, deal, company, or contact.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('tasks.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to All Tasks
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

        <div class="card">
            <div class="card-header">
                <h5 class="fw-semibold mb-0">
                    <i class="fas fa-plus-circle text-primary me-2"></i>
                    New Task Details
                </h5>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-medium">
                                <i class="fas fa-heading me-1 text-muted"></i>
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   wire:model="title" placeholder="Enter task title">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-medium">
                                <i class="fas fa-align-left me-1 text-muted"></i>
                                Description
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      wire:model="description" rows="4" placeholder="Enter task description"></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-flag me-1 text-muted"></i>
                                Priority <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('priority') is-invalid @enderror" wire:model="priority">
                                <option value="low">🟢 Low</option>
                                <option value="medium">🔵 Medium</option>
                                <option value="high">🟡 High</option>
                                <option value="urgent">🔴 Urgent</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-circle me-1 text-muted"></i>
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('status') is-invalid @enderror" wire:model="status">
                                <option value="pending">⏳ Pending</option>
                                <option value="in_progress">🔄 In Progress</option>
                                <option value="completed">✅ Completed</option>
                                <option value="cancelled">❌ Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                Due Date
                            </label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                   wire:model="due_date">
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-user me-1 text-muted"></i>
                                Assign To
                            </label>
                            <select class="form-select @error('assigned_to') is-invalid @enderror" wire:model="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach($staffList as $staffMember)
                                    <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-link me-1 text-muted"></i>
                                Link To
                            </label>
                            <select class="form-select" wire:model.live="related_type">
                                <option value="">None</option>
                                <option value="project">Project</option>
                                <option value="deal">Deal</option>
                                <option value="company">Company</option>
                                <option value="contact">Contact</option>
                            </select>
                        </div>

                        @if($related_type)
                        <div class="col-md-6 offset-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-hashtag me-1 text-muted"></i>
                                Select {{ ucfirst($related_type) }}
                            </label>
                            @if(count($relatedOptions) > 0)
                                <select class="form-select @error('related_to') is-invalid @enderror" wire:model="related_to">
                                    <option value="">-- Select {{ ucfirst($related_type) }} --</option>
                                    @foreach($relatedOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" disabled placeholder="No {{ $related_type }} records available">
                            @endif
                            @error('related_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Task
                        </button>
                        <a href="{{ route('tasks.all') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
