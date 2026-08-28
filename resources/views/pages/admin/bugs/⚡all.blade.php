<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Bug;
use App\Models\staff;
use App\Models\Project;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $severityFilter = '';

    public $showModal = false;
    public $title, $description, $steps_to_reproduce, $project_id, $assigned_to, $severity = 'medium';

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'steps_to_reproduce' => 'nullable|string',
        'project_id' => 'nullable|exists:projects,id',
        'assigned_to' => 'nullable|exists:staff,id',
        'severity' => 'required|in:low,medium,high,critical',
    ];

    protected function baseQuery()
    {
        $query = Bug::query()->with(['project', 'assignedTo', 'reportedBy']);

        // Developer owns ASSIGNED bugs only. QA/Tech Lead (Bugs.Approve/Assign) see everything.
        $user = auth()->user();
        $seesAll = $user->role === 'admin' || $user->hasPermission('Bugs', 'Approve') || $user->hasPermission('Bugs', 'Assign');
        if (! $seesAll) {
            $myStaffId = staff::where('user_id', $user->id)->value('id');
            if ($myStaffId) {
                $query->where(function ($q) use ($myStaffId) {
                    $q->where('assigned_to', $myStaffId)->orWhere('reported_by', $myStaffId);
                });
            }
        }

        if ($this->search !== '') {
            $query->where('title', 'like', '%' . $this->search . '%');
        }
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }
        if ($this->severityFilter !== '') {
            $query->where('severity', $this->severityFilter);
        }

        return $query;
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedSeverityFilter() { $this->resetPage(); }

    public function openAddModal()
    {
        if (! auth()->user()->hasPermission('Bugs', 'Create')) {
            session()->flash('error', "You don't have permission to report bugs.");
            return;
        }
        $this->reset(['title', 'description', 'steps_to_reproduce', 'project_id', 'assigned_to']);
        $this->severity = 'medium';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function save()
    {
        if (! auth()->user()->hasPermission('Bugs', 'Create')) {
            session()->flash('error', "You don't have permission to report bugs.");
            return;
        }
        $this->validate();

        $myStaffId = staff::where('user_id', auth()->id())->value('id');
        Bug::create([
            'title' => $this->title,
            'description' => $this->description,
            'steps_to_reproduce' => $this->steps_to_reproduce,
            'project_id' => $this->project_id ?: null,
            'assigned_to' => $this->assigned_to ?: null,
            'reported_by' => $myStaffId,
            'severity' => $this->severity,
            'status' => 'open',
        ]);

        session()->flash('success', 'Bug reported.');
        $this->showModal = false;
    }

    public function render()
    {
        return $this->view([
            'bugs' => $this->baseQuery()->orderByDesc('created_at')->paginate(15),
            'staffOptions' => staff::where('status', 'active')->whereIn('designation', ['Developer', 'Developer Intern', 'Tech Lead'])->orderBy('name')->get(),
            'projectOptions' => Project::orderBy('name')->limit(100)->get(),
            'canCreate' => auth()->user()->hasPermission('Bugs', 'Create'),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <div class="page-header">
            <div>
                <h1>Bugs</h1>
                <p>Report, track, and resolve issues across projects.</p>
            </div>
            <div class="header-actions">
                @if ($canCreate)
                    <button class="btn btn-primary" wire:click="openAddModal">
                        <i class="fas fa-plus"></i> Report Bug
                    </button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search bugs...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="">All Statuses</option>
                            @foreach (\App\Models\Bug::STATUSES as $st)
                                <option value="{{ $st }}">{{ ucwords(str_replace('_', ' ', $st)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" wire:model.live="severityFilter">
                            <option value="">All Severities</option>
                            @foreach (\App\Models\Bug::SEVERITIES as $sv)
                                <option value="{{ $sv }}">{{ ucfirst($sv) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Bug</th><th>Project</th><th>Assigned To</th><th>Severity</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($bugs as $bug)
                                <tr style="cursor:pointer" onclick="window.location='{{ route('bugs.show', $bug->id) }}'">
                                    <td>{{ $bug->title }}</td>
                                    <td>{{ $bug->project->name ?? '—' }}</td>
                                    <td>{{ $bug->assignedTo->name ?? 'Unassigned' }}</td>
                                    <td><span class="badge {{ $bug->severity_badge['class'] }}">{{ $bug->severity_badge['icon'] }} {{ ucfirst($bug->severity) }}</span></td>
                                    <td><span class="badge {{ $bug->status_badge['class'] }}">{{ $bug->status_badge['icon'] }} {{ ucwords(str_replace('_', ' ', $bug->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No bugs found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $bugs->links() }}</div>
    </div>

    @if ($showModal)
    <div class="modal-backdrop-custom" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;display:flex;align-items:center;justify-content:center;">
        <div class="card" style="width:560px;max-width:92vw;max-height:88vh;overflow-y:auto;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-bug text-danger me-2"></i>Report Bug</h5>
                <button type="button" class="btn-close" wire:click="closeModal"></button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" wire:model="title">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Description</label>
                    <textarea class="form-control" rows="3" wire:model="description"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Steps to Reproduce</label>
                    <textarea class="form-control" rows="2" wire:model="steps_to_reproduce"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Project</label>
                        <select class="form-select" wire:model="project_id">
                            <option value="">—</option>
                            @foreach ($projectOptions as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Assign To</label>
                        <select class="form-select" wire:model="assigned_to">
                            <option value="">Unassigned</option>
                            @foreach ($staffOptions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-0 mt-3">
                    <label class="form-label fw-medium">Severity</label>
                    <select class="form-select" wire:model="severity">
                        @foreach (\App\Models\Bug::SEVERITIES as $sv)
                            <option value="{{ $sv }}">{{ ucfirst($sv) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <button class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                <button class="btn btn-primary" wire:click="save"><i class="fas fa-save"></i> Report Bug</button>
            </div>
        </div>
    </div>
    @endif
</div>
