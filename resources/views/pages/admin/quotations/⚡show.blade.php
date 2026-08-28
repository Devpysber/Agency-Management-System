<?php

use Livewire\Component;
use App\Models\Quotation;

new class extends Component
{
    public Quotation $quotation;
    public $status;
    public $quoted_amount;

    public function mount($id)
    {
        $this->quotation = Quotation::with(['company', 'contact', 'createdBy'])->findOrFail($id);
        $this->status = $this->quotation->status;
        $this->quoted_amount = $this->quotation->quoted_amount;
    }

    protected function rules()
    {
        return [
            'status' => 'required|in:pending,reviewed,quoted,accepted,rejected',
            'quoted_amount' => 'nullable|numeric|min:0',
        ];
    }

    public function respond()
    {
        if (!auth()->user()->hasPermission('Quotations', 'Edit')) {
            session()->flash('error', "You don't have permission to update quotations.");
            return;
        }

        $this->validate();

        $wasPending = $this->quotation->status === 'pending';

        $this->quotation->update([
            'status' => $this->status,
            'quoted_amount' => $this->quoted_amount ?: null,
            'responded_at' => ($wasPending && $this->status !== 'pending')
                ? now()
                : $this->quotation->responded_at,
        ]);

        $this->quotation->refresh();

        session()->flash('success', 'Quotation updated successfully!');
    }

    public function render()
    {
        return $this->view();
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>{{ $quotation->name }}</h1>
                <p>
                    <span class="badge {{ $quotation->status_badge['class'] }}">
                        {{ $quotation->status_badge['icon'] }} {{ ucfirst($quotation->status) }}
                    </span>
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('quotations.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Inquiry Details -->
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle me-2"></i>Inquiry Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Name</h6>
                                <p class="mb-0 fw-semibold">{{ $quotation->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Email</h6>
                                <p class="mb-0">{{ $quotation->email }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Phone</h6>
                                <p class="mb-0">{{ $quotation->phone ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Service Interest</h6>
                                <p class="mb-0">{{ $quotation->service_interest ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Linked Company</h6>
                                <p class="mb-0">{{ $quotation->company->company_name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Linked Contact</h6>
                                <p class="mb-0">
                                    @if ($quotation->contact)
                                        {{ $quotation->contact->first_name }} {{ $quotation->contact->last_name }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Submitted</h6>
                                <p class="mb-0">{{ $quotation->created_at ? $quotation->created_at->format('M d, Y H:i') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Responded At</h6>
                                <p class="mb-0">{{ $quotation->responded_at ? $quotation->responded_at->format('M d, Y H:i') : 'Not yet responded' }}</p>
                            </div>
                            <div class="col-12">
                                <h6 class="text-muted text-uppercase small mb-1">Message</h6>
                                <p class="mb-0">{{ $quotation->message ?: 'No message provided.' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase small mb-1">Created By</h6>
                                <p class="mb-0">{{ $quotation->createdBy->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Triage / Respond Panel -->
            @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Quotations', 'Edit')))
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-reply me-2"></i>Update Quotation</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-circle me-1 text-muted"></i>
                                Status
                            </label>
                            <select class="form-select" wire:model="status">
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="quoted">Quoted</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            @error('status')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-dollar-sign me-1 text-muted"></i>
                                Quoted Amount
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="quoted_amount" placeholder="0.00">
                            @error('quoted_amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="button" wire:click="respond" class="btn btn-primary w-100">
                            <i class="fas fa-save" wire:loading.remove wire:target="respond"></i>
                            <i class="fas fa-spinner fa-spin" wire:loading wire:target="respond"></i>
                            Update
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
