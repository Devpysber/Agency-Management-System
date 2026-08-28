<?php

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayment;
use App\Models\PaymentGateway;
use App\Models\staff;

new class extends Component
{
    public $project;
    public $projectId;
    public $progressInput = 0;

    // Milestone form fields
    public $milestone_title;
    public $milestone_description;
    public $milestone_due_date;

    // Payment form fields
    public $payment_amount;
    public $payment_currency = 'USD';
    public $payment_gateway_id;
    public $payment_status = 'pending';
    public $payment_reference;

    protected $listeners = [];

    public function mount($id)
    {
        $this->projectId = $id;
        $this->project = Project::with(['company', 'createdBy', 'staff'])->findOrFail($id);

        // Regular staff / interns with no Projects permission at all may only
        // open projects they're assigned to. Anyone holding Projects.View or
        // Projects.Edit (or admin) may open any project — write actions on this
        // page are separately gated on Projects.Edit.
        $user = auth()->user();
        if ($user && $user->role !== 'admin'
            && ! $user->hasPermission('Projects', 'View')
            && ! $user->hasPermission('Projects', 'Edit')) {
            $myStaffId = staff::where('user_id', $user->id)->value('id');
            abort_unless($myStaffId && $this->project->staff->contains('id', $myStaffId), 403);
        }

        // Keep a completed project visually at 100% even if progress was never tracked.
        if ($this->project->status === 'completed' && (int) $this->project->progress < 100) {
            $this->project->update(['progress' => 100]);
        }
        $this->progressInput = (int) $this->project->progress;
    }

    /**
     * Admin with Projects:Edit permission, OR a staff member assigned to this
     * project, may move the progress bar and complete milestones.
     */
    public function canUpdateProgress(): bool
    {
        $user = auth()->user();
        if ($user->hasPermission('Projects', 'Edit')) {
            return true;
        }
        $staffId = staff::where('user_id', $user->id)->value('id');
        return $staffId && $this->project->staff()->where('staff.id', $staffId)->exists();
    }

    public function updateProgress()
    {
        if (! $this->canUpdateProgress()) {
            session()->flash('error', "You can't update this project's progress.");
            return;
        }

        $data = $this->validate(['progressInput' => 'required|integer|min:0|max:100']);

        $this->project->update(['progress' => $data['progressInput']]);
        $this->project->syncStatusToProgress();
        $this->project->refresh();
        $this->progressInput = (int) $this->project->progress;

        session()->flash('success', 'Progress updated to ' . $data['progressInput'] . '% (status: ' . $this->project->status . ').');
    }

    protected function rulesForMilestone()
    {
        return [
            'milestone_title' => 'required|string|max:255',
            'milestone_description' => 'nullable|string',
            'milestone_due_date' => 'nullable|date',
        ];
    }

    protected function rulesForPayment()
    {
        return [
            'payment_amount' => 'required|numeric|min:0',
            'payment_currency' => 'required|string|max:10',
            'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_reference' => 'nullable|string|max:255',
        ];
    }

    public function addMilestone()
    {
        if (!auth()->user()->hasPermission('Projects', 'Edit')) {
            session()->flash('error', "You don't have permission to add milestones.");
            return;
        }

        $this->validate($this->rulesForMilestone());

        ProjectMilestone::create([
            'project_id' => $this->project->id,
            'title' => $this->milestone_title,
            'description' => $this->milestone_description,
            'due_date' => $this->milestone_due_date ?: null,
            'status' => 'pending',
        ]);

        session()->flash('success', 'Milestone added successfully');
        $this->reset(['milestone_title', 'milestone_description', 'milestone_due_date']);
        $this->project->refresh();
    }

    public function completeMilestone($id)
    {
        if (!$this->canUpdateProgress()) {
            session()->flash('error', "You don't have permission to update milestones.");
            return;
        }

        $milestone = ProjectMilestone::where('project_id', $this->project->id)->findOrFail($id);
        $milestone->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Keep progress / status coherent with milestone completion.
        $all = $this->project->milestones()->count();
        $done = $this->project->milestones()->where('status', 'completed')->count();
        if ($all > 0 && $done === $all) {
            $this->project->update(['progress' => 100]);
        } elseif ($this->project->status === 'planning' && $done > 0) {
            $this->project->update(['status' => 'in_progress']);
        }
        $this->project->syncStatusToProgress();

        session()->flash('success', 'Milestone marked as completed');
        $this->project->refresh();
        $this->progressInput = (int) $this->project->progress;
    }

    public function addPayment()
    {
        if (!auth()->user()->hasPermission('Projects', 'Edit')) {
            session()->flash('error', "You don't have permission to add payments.");
            return;
        }

        $this->validate($this->rulesForPayment());

        ProjectPayment::create([
            'project_id' => $this->project->id,
            'amount' => $this->payment_amount,
            'currency' => $this->payment_currency ?: 'USD',
            'payment_gateway_id' => $this->payment_gateway_id ?: null,
            'status' => $this->payment_status ?: 'pending',
            'reference' => $this->payment_reference,
            'paid_at' => $this->payment_status === 'paid' ? now() : null,
        ]);

        session()->flash('success', 'Payment added successfully');
        $this->reset(['payment_amount', 'payment_gateway_id', 'payment_reference']);
        $this->payment_currency = 'USD';
        $this->payment_status = 'pending';
        $this->project->refresh();
    }

    public function render()
    {
        return $this->view([
            'milestones' => $this->project->milestones()->orderBy('due_date')->get(),
            'payments' => $this->project->payments()->with('gateway')->orderByDesc('created_at')->get(),
            'gateways' => PaymentGateway::where('is_active', true)->get(),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="mb-0">{{ $project->name }}</h1>
                <p class="mb-0">
                    <span class="badge {{ $project->status_badge['class'] }}">
                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                    </span>
                    <span class="text-muted ms-2">{{ $project->company->company_name ?? 'No Company' }}</span>
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('projects.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
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

        <!-- Quick Stats -->
        <div class="row g-3 mb-4 a-stagger">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Progress</h3>
                        <p class="stat-number">{{ $project->progress }}%</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Budget</h3>
                        <p class="stat-number">{{ $project->budget !== null ? '$' . number_format($project->budget, 2) : 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Milestones</h3>
                        <p class="stat-number">{{ $milestones->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Payments</h3>
                        <p class="stat-number">{{ $payments->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="projectTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button" role="tab">
                    <i class="fas fa-info-circle me-1"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="milestones-tab" data-bs-toggle="tab" data-bs-target="#milestones-pane" type="button" role="tab">
                    <i class="fas fa-flag-checkered me-1"></i> Milestones
                    <span class="badge bg-secondary ms-1">{{ $milestones->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-pane" type="button" role="tab">
                    <i class="fas fa-credit-card me-1"></i> Payments
                    <span class="badge bg-secondary ms-1">{{ $payments->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="team-tab" data-bs-toggle="tab" data-bs-target="#team-pane" type="button" role="tab">
                    <i class="fas fa-users me-1"></i> Team &amp; Chat
                    <span class="badge bg-secondary ms-1">{{ $project->staff->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="projectTabsContent">
            <!-- Overview Pane -->
            <div class="tab-pane fade show active" id="overview-pane" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Project Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Company</label>
                                <p class="fw-semibold">{{ $project->company->company_name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Status</label>
                                <p>
                                    <span class="badge {{ $project->status_badge['class'] }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Start Date</label>
                                <p>{{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">End Date</label>
                                <p>{{ $project->end_date ? $project->end_date->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Budget</label>
                                <p>{{ $project->budget !== null ? '$' . number_format($project->budget, 2) : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Created By</label>
                                <p>{{ $project->createdBy->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Progress</label>
                                <div class="progress" style="height:10px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $project->progress }}%;">
                                    </div>
                                </div>
                                <small class="text-muted">{{ $project->progress }}% complete</small>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Description</label>
                                <p class="mb-0">{{ $project->description ?? 'No description available.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Milestones Pane -->
            <div class="tab-pane fade" id="milestones-pane" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-flag-checkered text-primary me-2"></i>
                            Milestones
                        </h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMilestoneModal">
                            <i class="fas fa-plus"></i> Add Milestone
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th style="width:140px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($milestones as $milestone)
                                    <tr>
                                        <td>
                                            <h6 class="mb-0 fw-semibold">{{ $milestone->title }}</h6>
                                            @if($milestone->description)
                                                <small class="text-muted">{{ $milestone->description }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $milestone->due_date ? $milestone->due_date->format('M d, Y') : 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $milestone->status_badge['class'] }}">
                                                {{ ucfirst(str_replace('_', ' ', $milestone->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($milestone->status !== 'completed')
                                                <button class="btn btn-sm btn-outline-success" wire:click="completeMilestone({{ $milestone->id }})">
                                                    <i class="fas fa-check"></i> Mark Complete
                                                </button>
                                            @else
                                                <small class="text-muted">
                                                    Completed {{ $milestone->completed_at ? $milestone->completed_at->diffForHumans() : '' }}
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="fas fa-flag-checkered fa-3x text-muted mb-3 d-block"></i>
                                            <h6 class="text-muted">No milestones yet</h6>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Pane -->
            <div class="tab-pane fade" id="payments-pane" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-credit-card text-primary me-2"></i>
                            Payments
                        </h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fas fa-plus"></i> Add Payment
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Amount</th>
                                        <th>Gateway</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                        <th>Paid At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payments as $payment)
                                    <tr>
                                        <td class="fw-semibold">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $payment->gateway->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $payment->status_badge['class'] }}">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $payment->reference ?? 'N/A' }}</td>
                                        <td>{{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : 'N/A' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-credit-card fa-3x text-muted mb-3 d-block"></i>
                                            <h6 class="text-muted">No payments recorded yet</h6>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team & Chat Pane -->
            <div class="tab-pane fade" id="team-pane" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="fw-semibold mb-0"><i class="fas fa-hourglass-half text-primary me-2"></i>Submission Deadline</h5>
                            </div>
                            <div class="card-body">
                                @if ($project->submission_due_at)
                                    <p class="mb-1 fw-semibold {{ $project->is_overdue ? 'text-danger' : '' }}">
                                        {{ $project->submission_due_at->format('M d, Y · H:i') }}
                                    </p>
                                    <small class="text-muted">{{ $project->submission_countdown }}</small>
                                @else
                                    <p class="text-muted mb-0">No deadline set. Add one on the <a href="{{ route('projects.edit', $project->id) }}">edit screen</a>.</p>
                                @endif
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="fw-semibold mb-0"><i class="fas fa-users text-primary me-2"></i>Assigned Employees</h5>
                            </div>
                            <div class="card-body">
                                @forelse ($project->staff as $member)
                                    <div class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <span class="badge bg-primary rounded-circle" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <div class="fw-semibold">{{ $member->name }}</div>
                                            <small class="text-muted">{{ $member->designation ?: 'Employee' }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No employees assigned. Assign them on the <a href="{{ route('projects.edit', $project->id) }}">edit screen</a>.</p>
                                @endforelse
                            </div>
                        </div>

                        @if ($this->canUpdateProgress())
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="fw-semibold mb-0"><i class="fas fa-percent text-primary me-2"></i>Update Progress</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <input type="range" class="form-range flex-grow-1" min="0" max="100" step="1" wire:model.live="progressInput">
                                        <span class="fw-bold" style="min-width:48px;">{{ $progressInput }}%</span>
                                    </div>
                                    <div class="progress mb-3" style="height:10px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $progressInput }}%; transition: width .3s;"></div>
                                    </div>
                                    <button class="btn btn-primary btn-sm" wire:click="updateProgress" wire:loading.attr="disabled" wire:target="updateProgress">
                                        <i class="fas fa-save"></i> Save progress
                                    </button>
                                    @error('progressInput') <span class="text-danger d-block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-7">
                        <livewire:project-chat :project="$project" :key="'admin-chat-'.$project->id" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Milestone Modal -->
    <div class="modal fade" id="addMilestoneModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-flag-checkered me-2"></i>Add Milestone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model="milestone_title" placeholder="Milestone title">
                        @error('milestone_title')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <textarea class="form-control" wire:model="milestone_description" rows="3" placeholder="Description"></textarea>
                        @error('milestone_description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Due Date</label>
                        <input type="date" class="form-control" wire:model="milestone_due_date">
                        @error('milestone_due_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="addMilestone" data-bs-dismiss="modal">
                        <i class="fas fa-save"></i> Save Milestone
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="payment_amount" placeholder="0.00">
                            @error('payment_amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Currency</label>
                            <input type="text" class="form-control" wire:model="payment_currency" placeholder="USD">
                            @error('payment_currency')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Payment Gateway</label>
                            <select class="form-select" wire:model="payment_gateway_id">
                                <option value="">Select Gateway</option>
                                @foreach ($gateways as $gateway)
                                    <option value="{{ $gateway->id }}">{{ $gateway->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_gateway_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Status</label>
                            <select class="form-select" wire:model="payment_status">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                            @error('payment_status')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Reference</label>
                            <input type="text" class="form-control" wire:model="payment_reference" placeholder="Transaction reference">
                            @error('payment_reference')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="addPayment" data-bs-dismiss="modal">
                        <i class="fas fa-save"></i> Save Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
