<?php

use Livewire\Component;
use App\Models\Bug;
use App\Models\staff;

new class extends Component
{
    public Bug $bug;

    public function mount($id)
    {
        $this->bug = Bug::with(['project', 'assignedTo', 'reportedBy', 'verifiedBy'])->findOrFail($id);
    }

    private function myStaffId(): ?int
    {
        return staff::where('user_id', auth()->id())->value('id');
    }

    private function isMine(): bool
    {
        return $this->bug->assigned_to && $this->bug->assigned_to === $this->myStaffId();
    }

    /** Developer owns fixing bugs: open/in_progress -> in_progress/fixed, on THEIR OWN assigned bug. */
    public function markInProgress()
    {
        abort_unless($this->isMine() || auth()->user()->hasPermission('Bugs', 'Assign'), 403);
        $this->bug->update(['status' => 'in_progress']);
        session()->flash('success', 'Marked in progress.');
    }

    public function markFixed()
    {
        abort_unless($this->isMine() || auth()->user()->hasPermission('Bugs', 'Assign'), 403);
        $this->bug->update(['status' => 'fixed']);
        session()->flash('success', 'Marked fixed — sent to QA retest.');
        $this->bug->refresh();
        $this->bug->update(['status' => 'qa_retest']);
    }

    /** QA owns testing + QA approval: qa_retest -> verified (pass) or failed (back to developer). */
    public function verify()
    {
        abort_unless(auth()->user()->hasPermission('Bugs', 'Approve'), 403);
        $this->bug->update([
            'status' => 'verified', 'verified_by' => $this->myStaffId(), 'verified_at' => now(),
        ]);
        session()->flash('success', 'Verified — QA approved.');
    }

    public function failRetest()
    {
        abort_unless(auth()->user()->hasPermission('Bugs', 'Approve'), 403);
        $this->bug->update(['status' => 'failed']);
        session()->flash('success', 'Sent back to developer — retest failed.');
    }

    public function reopenAfterFail()
    {
        abort_unless($this->isMine() || auth()->user()->hasPermission('Bugs', 'Assign'), 403);
        $this->bug->update(['status' => 'in_progress']);
    }

    /** Tech Lead / QA close a verified bug. */
    public function close()
    {
        abort_unless(auth()->user()->hasPermission('Bugs', 'Approve') || auth()->user()->hasPermission('Bugs', 'Assign'), 403);
        $this->bug->update(['status' => 'closed']);
        session()->flash('success', 'Closed.');
    }

    /** Tech Lead assigns/reassigns a developer. */
    public function assign($staffId)
    {
        abort_unless(auth()->user()->hasPermission('Bugs', 'Assign'), 403);
        $this->bug->update(['assigned_to' => $staffId ?: null]);
        session()->flash('success', 'Assigned.');
    }

    public function render()
    {
        return $this->view([
            'canApprove' => auth()->user()->hasPermission('Bugs', 'Approve'),
            'canEdit' => auth()->user()->hasPermission('Bugs', 'Assign'),
            'canAssign' => auth()->user()->hasPermission('Bugs', 'Assign'),
            'isMine' => $this->isMine(),
            'developers' => staff::where('status', 'active')->whereIn('designation', ['Developer', 'Developer Intern'])->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <div class="page-header">
            <div>
                <a href="{{ route('bugs.all') }}" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left"></i> Bugs</a>
                <h1 class="mb-0">{{ $bug->title }}</h1>
                <p>{{ $bug->project->name ?? 'No project' }}</p>
            </div>
            <div class="header-actions">
                <span class="badge {{ $bug->severity_badge['class'] }} fs-6">{{ ucfirst($bug->severity) }}</span>
                <span class="badge {{ $bug->status_badge['class'] }} fs-6">{{ ucwords(str_replace('_', ' ', $bug->status)) }}</span>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-flash alert-flash-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Description</h3></div>
                    <div class="card-body">
                        <p>{{ $bug->description ?: 'No description provided.' }}</p>
                        @if ($bug->steps_to_reproduce)
                            <h6 class="mt-3">Steps to Reproduce</h6>
                            <p class="text-muted">{{ $bug->steps_to_reproduce }}</p>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Workflow</h3></div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        @if (in_array($bug->status, ['open']) && ($isMine || $canEdit))
                            <button class="btn btn-primary btn-sm" wire:click="markInProgress"><i class="fas fa-play"></i> Start Work</button>
                        @endif
                        @if (in_array($bug->status, ['open', 'in_progress']) && ($isMine || $canEdit))
                            <button class="btn btn-info btn-sm" wire:click="markFixed"><i class="fas fa-check"></i> Mark Fixed → Send to QA</button>
                        @endif
                        @if ($bug->status === 'qa_retest' && $canApprove)
                            <button class="btn btn-success btn-sm" wire:click="verify"><i class="fas fa-shield-check"></i> Verify (QA Approve)</button>
                            <button class="btn btn-outline-danger btn-sm" wire:click="failRetest"><i class="fas fa-xmark"></i> Fail Retest</button>
                        @endif
                        @if ($bug->status === 'failed' && ($isMine || $canEdit))
                            <button class="btn btn-primary btn-sm" wire:click="reopenAfterFail"><i class="fas fa-rotate-left"></i> Reopen &amp; Fix</button>
                        @endif
                        @if ($bug->status === 'verified' && ($canApprove || $canEdit))
                            <button class="btn btn-dark btn-sm" wire:click="close"><i class="fas fa-lock"></i> Close</button>
                        @endif
                        @if (in_array($bug->status, ['verified', 'closed']))
                            <span class="text-muted small align-self-center">
                                @if ($bug->verifiedBy) Verified by {{ $bug->verifiedBy->name }} {{ $bug->verified_at?->diffForHumans() }} @endif
                            </span>
                        @endif
                        @if (! $isMine && ! $canEdit && ! $canApprove)
                            <span class="text-muted small">Read-only — not assigned to you.</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Details</h3></div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5">Reported by</dt><dd class="col-7">{{ $bug->reportedBy->name ?? '—' }}</dd>
                            <dt class="col-5">Assigned to</dt><dd class="col-7">{{ $bug->assignedTo->name ?? 'Unassigned' }}</dd>
                            <dt class="col-5">Reported</dt><dd class="col-7">{{ $bug->created_at->format('M d, Y') }}</dd>
                        </dl>
                    </div>
                </div>
                @if ($canAssign)
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Assign</h3></div>
                        <div class="card-body">
                            <select class="form-select" onchange="@this.call('assign', $event.target.value)">
                                <option value="">Unassigned</option>
                                @foreach ($developers as $d)
                                    <option value="{{ $d->id }}" @selected($bug->assigned_to === $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
