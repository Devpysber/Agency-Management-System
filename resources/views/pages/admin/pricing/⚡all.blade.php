<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PricingPlan;
use App\Models\ServiceCategory;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $category = '';
    public $billingPeriod = '';
    public $status = '';

    protected function fetchPlans()
    {
        $query = PricingPlan::with('serviceType');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->category)) {
            $query->where('service_category_id', $this->category);
        }

        if (!empty($this->billingPeriod)) {
            $query->where('billing_period', $this->billingPeriod);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return $query->orderBy('price', 'asc');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategory()
    {
        $this->resetPage();
    }

    public function updatedBillingPeriod()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Pricing', 'Delete')) {
            session()->flash('error', "You don't have permission to delete pricing plans.");
            return;
        }

        try {
            PricingPlan::findOrFail($id)->delete();
            session()->flash('success', 'Pricing plan deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting pricing plan: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->category = '';
        $this->billingPeriod = '';
        $this->status = '';
        $this->resetPage();
    }

    public function getStatsProperty()
    {
        return [
            'total' => PricingPlan::count(),
            'active' => PricingPlan::where('status', 'active')->count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'plans' => $this->fetchPlans()->paginate(15),
            'stats' => $this->stats,
            'categories' => ServiceCategory::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Pricing Plans</h1>
                <p>Manage the pricing plans your agency offers.</p>
            </div>
            <div class="header-actions">
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Pricing', 'Edit')))
                    <a href="{{ route('pricing.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Plan
                    </a>
                @endif
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

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Plans</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
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
                        <p class="stat-number">{{ $stats['active'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">
                            <i class="fas fa-search me-1 text-muted"></i>
                            Search
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control"
                                   wire:model.live="search"
                                   placeholder="Search plans...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-tags me-1 text-muted"></i>
                            Service Type
                        </label>
                        <select class="form-select" wire:model.live="category">
                            <option value="">All Service Types</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-calendar me-1 text-muted"></i>
                            Billing
                        </label>
                        <select class="form-select" wire:model.live="billingPeriod">
                            <option value="">All</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="one_time">One Time</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Status</option>
                            <option value="active">🟢 Active</option>
                            <option value="inactive">🔴 Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters" title="Reset Filters">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="row g-4">
            @forelse ($plans as $plan)
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary">{{ $plan->serviceType->name ?? 'General' }}</span>
                            <span class="badge {{ $plan->status_badge['class'] }}">
                                {{ ucfirst($plan->status) }}
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-semibold mb-2">{{ $plan->name }}</h5>
                            <div class="mb-3">
                                <span class="fs-3 fw-bold">${{ number_format($plan->price, 2) }}</span>
                                <span class="text-muted">{{ $plan->billing_period_label }}</span>
                            </div>

                            @if (!empty($plan->features))
                                <ul class="list-unstyled mb-3 flex-grow-1">
                                    @foreach ($plan->features as $feature)
                                        <li class="mb-1">
                                            <i class="fas fa-check text-success me-2"></i>{{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="flex-grow-1"></div>
                            @endif

                            @if (!empty($plan->countries))
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-globe me-1"></i>
                                    {{ implode(', ', $plan->countries) }}
                                </p>
                            @endif

                            <div class="btn-group btn-group-sm mt-auto">
                                @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Pricing', 'Edit'))
                                    <a href="{{ route('pricing.edit', $plan->id) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endif
                                @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Pricing', 'Delete'))
                                    <button class="btn btn-outline-danger"
                                            wire:click="delete({{ $plan->id }})"
                                            wire:confirm="Are you sure you want to delete this pricing plan?">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No pricing plans found</h5>
                            <p class="text-muted">Try adjusting your search or filter criteria.</p>
                            <a href="{{ route('pricing.add') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Plan
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        @if($plans->hasPages())
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
            <div>
                <span class="text-muted">
                    Showing {{ $plans->firstItem() ?? 0 }}-{{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }}
                </span>
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $plans->onFirstPage() ? 'disabled' : '' }}">
                        <button class="page-link" wire:click="previousPage" @if($plans->onFirstPage()) disabled @endif>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    </li>
                    @for ($page = max(1, $plans->currentPage() - 2); $page <= min($plans->lastPage(), $plans->currentPage() + 2); $page++)
                        <li class="page-item {{ $page == $plans->currentPage() ? 'active' : '' }}">
                            <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        </li>
                    @endfor
                    <li class="page-item {{ !$plans->hasMorePages() ? 'disabled' : '' }}">
                        <button class="page-link" wire:click="nextPage" @if(!$plans->hasMorePages()) disabled @endif>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>
