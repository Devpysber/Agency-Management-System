<?php

use Livewire\Component;
use App\Models\Deal;

new class extends Component
{
    public $deal;

    public function mount($id)
    {
        $this->deal = Deal::with(['company', 'contact', 'createdBy'])->findOrFail($id);
    }

    public function delete($id)
    {
        $deal = Deal::find($id);
        if ($deal) {
            $deal->delete();
            session()->flash('success', 'Deal deleted successfully!');
            return $this->redirectRoute('deals.all');
        }
    }

};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <div class="d-flex align-items-center gap-3">
                    <div class="deal-icon">
                        <i class="fas fa-chart-line fa-3x text-primary"></i>
                    </div>
                    <div>
                        <h1 class="mb-0">{{ $deal->deal_name }}</h1>
                        <p class="mb-0">
                            <span class="badge bg-success">
                                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                {{ ucfirst($deal->deal_status) }}
                            </span>
                            <span class="text-muted ms-2">|</span>
                            <span class="text-muted ms-2">{{ $deal->currency }} {{ number_format($deal->deal_value, 2) }}</span>
                            <span class="text-muted ms-2">|</span>
                            <span class="text-muted ms-2">Created: {{ $deal->created_at->diffForHumans() }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('deals.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('deals.edit', $deal->id) }}" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button class="btn btn-danger" wire:click="delete({{ $deal->id }})" wire:confirm="Are you sure you want to delete this deal?">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Deal Value</h3>
                        <p class="stat-number">{{ $deal->currency }} {{ number_format($deal->deal_value, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Probability</h3>
                        <p class="stat-number">{{ $deal->probability ?? 0 }}%</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Expected Close</h3>
                        <p class="stat-number">{{ $deal->expected_close_date ? $deal->expected_close_date : 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Actual Close</h3>
                        <p class="stat-number">{{ $deal->actual_close_date ? $deal->actual_close_date : 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Deal Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Deal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Deal Name</label>
                                <p class="fw-semibold">{{ $deal->deal_name }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Deal Value</label>
                                <p class="fw-semibold">{{ $deal->currency }} {{ number_format($deal->deal_value, 2) }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-layer-group me-1"></i> Deal Stage
                                </label>
                                <p>
                                    @php
                                        $stageColors = [
                                            'lead' => 'bg-secondary',
                                            'qualified' => 'bg-primary',
                                            'proposal' => 'bg-info',
                                            'negotiation' => 'bg-warning text-dark',
                                            'closed_won' => 'bg-success',
                                            'closed_lost' => 'bg-danger'
                                        ];
                                        $stageLabels = [
                                            'lead' => 'Lead',
                                            'qualified' => 'Qualified',
                                            'proposal' => 'Proposal Sent',
                                            'negotiation' => 'Negotiation',
                                            'closed_won' => 'Closed Won',
                                            'closed_lost' => 'Closed Lost'
                                        ];
                                    @endphp
                                    <span class="badge {{ $stageColors[$deal->deal_stage] ?? 'bg-secondary' }}">
                                        {{ $stageLabels[$deal->deal_stage] ?? $deal->deal_stage }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-circle me-1"></i> Status
                                </label>
                                <p>
                                    @if($deal->deal_status == 'active')
                                        <span class="badge bg-success">🟢 Active</span>
                                    @elseif($deal->deal_status == 'won')
                                        <span class="badge bg-success">✅ Won</span>
                                    @elseif($deal->deal_status == 'lost')
                                        <span class="badge bg-danger">🔴 Lost</span>
                                    @elseif($deal->deal_status == 'on_hold')
                                        <span class="badge bg-warning text-dark">🟡 On Hold</span>
                                    @else
                                        <span class="badge bg-dark">⛔ Cancelled</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-percentage me-1"></i> Win Probability
                                </label>
                                <p>{{ $deal->probability ?? 0 }}%</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-globe me-1"></i> Currency
                                </label>
                                <p>{{ $deal->currency }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-calendar-check me-1"></i> Expected Close Date
                                </label>
                                <p>{{ $deal->expected_close_date ? $deal->expected_close_date : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-calendar-times me-1"></i> Actual Close Date
                                </label>
                                <p>{{ $deal->actual_close_date ? $deal->actual_close_date : 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-align-left me-1"></i> Notes / Description
                                </label>
                                <p class="mb-0">{{ $deal->deal_notes ?? 'No notes available.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Entities -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-link text-primary me-2"></i>
                            Related Entities
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-building me-1"></i> Company
                                </label>
                                @if($deal->company)
                                    <p>
                                        <a href="{{ route('companies.show', $deal->company->id) }}" class="text-primary">
                                            {{ $deal->company->company_name }}
                                        </a>
                                    </p>
                                @else
                                    <p class="text-muted">N/A</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-user me-1"></i> Primary Contact
                                </label>
                                @if($deal->contact)
                                    <p>
                                        <a href="{{ route('contacts.show', $deal->contact->id) }}" class="text-primary">
                                            {{ $deal->contact->full_name }}
                                        </a>
                                    </p>
                                @else
                                    <p class="text-muted">N/A</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-user-tie me-1"></i> Created By
                                </label>
                                @if($deal->createdBy)
                                    <p>{{ $deal->createdBy->name ?? 'N/A' }}</p>
                                @else
                                    <p class="text-muted">N/A</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">
                                    <i class="fas fa-clock me-1"></i> Created At
                                </label>
                                <p>{{ $deal->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-bolt text-primary me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('contacts.add') }}" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>
                                Add Contact
                            </a>
                            <a href="{{ route('companies.add') }}" class="btn btn-outline-success">
                                <i class="fas fa-building me-2"></i>
                                Add Company
                            </a>
                            <a href="{{ route('calendar.schedule') }}" class="btn btn-outline-info">
                                <i class="fas fa-calendar-plus me-2"></i>
                                Schedule Meeting
                            </a>
                            <a href="{{ route('communications.emails') }}" class="btn btn-outline-warning">
                                <i class="fas fa-envelope me-2"></i>
                                Send Email
                            </a>
                            <a href="{{ route('deals.edit', $deal->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-edit me-2"></i>
                                Edit Deal
                            </a>
                            <button class="btn btn-outline-danger" wire:click="delete({{ $deal->id }})" wire:confirm="Are you sure you want to delete this deal?">
                                <i class="fas fa-trash me-2"></i>
                                Delete Deal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>