<?php

use Livewire\Component;
use App\Models\Deal;

new class extends Component
{
    public $deals;
    public $search = '';

    public function mount()
    {
        $this->fetchDeals();
    }

    public function fetchDeals()
    {
        $query = Deal::with(['company', 'contact'])->where('deal_status', 'pipeline');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('deal_name', 'like', '%' . $this->search . '%')
                  ->orWhere('deal_notes', 'like', '%' . $this->search . '%');
            });
        }

        $this->deals = $query->orderBy('created_at', 'desc')->get();
    }

    public function updatedSearch()
    {
        $this->fetchDeals();
    }

    public function delete($id)
    {
        $deal = Deal::find($id);
        if ($deal) {
            $deal->delete();
            session()->flash('success', 'Deal deleted successfully!');
            $this->fetchDeals();
        }
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>All Deals</h1>
                <p>Manage and view all deals in your CRM system.</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary">
                    <i class="fas fa-file-export"></i> Export
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <a href="{{ route('deals.add') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Deal
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

        <!-- Search Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-medium">
                            <i class="fas fa-search me-1 text-muted"></i>
                            Search Deals
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" 
                                   wire:model.live="search" 
                                   placeholder="Search by deal name or notes...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-secondary" wire:click="$set('search', ''); fetchDeals()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Deals</h3>
                        <p class="stat-number">{{ $deals->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active</h3>
                        <p class="stat-number">{{ $deals->where('deal_status', 'active')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Won</h3>
                        <p class="stat-number">{{ $deals->where('deal_status', 'won')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Lost</h3>
                        <p class="stat-number">{{ $deals->where('deal_status', 'lost')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deals Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line me-2"></i>
                    Deals List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $deals->count() }} Deals</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchDeals">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Deal Name</th>
                                <th>Value</th>
                                <th>Company</th>
                                <th>Contact</th>
                                <th>Stage</th>
                                <th>Status</th>
                                <th>Expected Close</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deals as $deal)
                            <tr>
                                <td>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">{{ $deal->deal_name }}</h6>
                                        @if($deal->deal_notes)
                                            <small class="text-muted">{{ Str::limit($deal->deal_notes, 30) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold">
                                        {{ $deal->currency }} {{ number_format($deal->deal_value, 2) }}
                                    </span>
                                    @if($deal->probability)
                                        <br><small class="text-muted">{{ $deal->probability }}% probability</small>
                                    @endif
                                </td>
                                <td>
                                    @if($deal->company)
                                        <span class="badge bg-secondary">{{ $deal->company->company_name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($deal->contact)
                                        <span class="badge bg-info">{{ $deal->contact->first_name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
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
                                            'proposal' => 'Proposal',
                                            'negotiation' => 'Negotiation',
                                            'closed_won' => 'Won',
                                            'closed_lost' => 'Lost'
                                        ];
                                    @endphp
                                    <span class="badge {{ $stageColors[$deal->deal_stage] ?? 'bg-secondary' }}">
                                        {{ $stageLabels[$deal->deal_stage] ?? $deal->deal_stage }}
                                    </span>
                                </td>
                                <td>
                                    @if($deal->deal_status == 'active')
                                        <span class="badge bg-success">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Active
                                        </span>
                                    @elseif($deal->deal_status == 'won')
                                        <span class="badge bg-success">
                                            <i class="fas fa-trophy me-1"></i>
                                            Won
                                        </span>
                                    @elseif($deal->deal_status == 'lost')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>
                                            Lost
                                        </span>
                                    @elseif($deal->deal_status == 'on_hold')
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-pause me-1"></i>
                                            On Hold
                                        </span>
                                    @else
                                        <span class="badge bg-dark">
                                            <i class="fas fa-ban me-1"></i>
                                            Cancelled
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($deal->expected_close_date)
                                        <small class="text-muted" title="{{ $deal->expected_close_date }}">
                                            {{ $deal->expected_close_date }}
                                        </small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('deals.view', $deal->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('deals.edit', $deal->id) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" 
                                                wire:click="delete({{ $deal->id }})" 
                                                wire:confirm="Are you sure you want to delete this deal?">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-chart-line fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No deals found</h5>
                                    <p class="text-muted">Try adjusting your search or add a new deal.</p>
                                    <a href="{{ route('deals.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add New Deal
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted">
                            Showing {{ $deals->count() }} deal(s)
                            @if($search)
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>