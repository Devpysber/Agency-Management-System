<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Estimate;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $sortBy = 'newest';
    public $selectedEstimates = [];
    public $selectAll = false;

    protected function fetchEstimates()
    {
        $query = Estimate::query();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('estimate_number', 'like', '%' . $this->search . '%')
                  ->orWhere('client_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        switch ($this->sortBy) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'total_high':
                $query->orderBy('total', 'desc');
                break;
            case 'total_low':
                $query->orderBy('total', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query->with(['company', 'contact']);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedEstimates = $this->fetchEstimates()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedEstimates = [];
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Estimates', 'Delete')) {
            session()->flash('error', "You don't have permission to delete estimates.");
            return;
        }

        try {
            Estimate::findOrFail($id)->delete();
            session()->flash('success', 'Estimate deleted successfully!');
            $this->selectedEstimates = array_diff($this->selectedEstimates, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting estimate: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Estimates', 'Delete')) {
            session()->flash('error', "You don't have permission to delete estimates.");
            return;
        }

        if (empty($this->selectedEstimates)) {
            session()->flash('warning', 'Please select at least one estimate to delete.');
            return;
        }

        try {
            Estimate::whereIn('id', $this->selectedEstimates)->delete();
            session()->flash('success', count($this->selectedEstimates) . ' estimate(s) deleted successfully!');
            $this->selectedEstimates = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting estimates: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->sortBy = 'newest';
        $this->resetPage();
    }

    public function getStatusCounts()
    {
        return [
            'total' => Estimate::count(),
            'draft' => Estimate::where('status', 'draft')->count(),
            'approved' => Estimate::where('status', 'approved')->count(),
            'value' => Estimate::sum('total'),
        ];
    }

    public function render()
    {
        return $this->view([
            'estimates' => $this->fetchEstimates()->paginate(15),
            'stats' => $this->getStatusCounts(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>All Estimates</h1>
                <p>Manage and track client estimates.</p>
            </div>
            <div class="header-actions">
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Estimates', 'Create')))
                    <a href="{{ route('estimates.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Estimate
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

        @if (session()->has('warning'))
            <div class="alert-flash alert-flash-warning">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('warning') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

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
                                   placeholder="Search by estimate # or client...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-sort me-1 text-muted"></i>
                            Sort By
                        </label>
                        <select class="form-select" wire:model.live="sortBy">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="total_high">Highest Total</option>
                            <option value="total_low">Lowest Total</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters" title="Reset Filters">
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
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Estimates</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Draft</h3>
                        <p class="stat-number">{{ $stats['draft'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Approved</h3>
                        <p class="stat-number">{{ $stats['approved'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Value</h3>
                        <p class="stat-number">${{ number_format($stats['value'], 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedEstimates) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedEstimates) }} estimate(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected estimates?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedEstimates', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Estimates Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Estimates List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $estimates->total() }} Estimates</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchEstimates">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input"
                                           wire:model.live="selectAll">
                                </th>
                                <th>Estimate #</th>
                                <th>Client</th>
                                <th>Issue Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($estimates as $estimate)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $estimate->id }}"
                                           wire:model.live="selectedEstimates">
                                </td>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $estimate->estimate_number }}</h6>
                                </td>
                                <td>
                                    {{ $estimate->company->company_name ?? $estimate->client_name ?? 'N/A' }}
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $estimate->issue_date ? $estimate->issue_date->format('M d, Y') : 'N/A' }}
                                    </small>
                                </td>
                                <td>
                                    <span class="fw-semibold">${{ number_format($estimate->total, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $estimate->status_badge['class'] }}">
                                        {{ $estimate->status_badge['icon'] }} {{ ucfirst($estimate->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('estimates.show', $estimate->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Estimates', 'Edit'))
                                            <a href="{{ route('estimates.edit', $estimate->id) }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Estimates', 'Delete'))
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $estimate->id }})"
                                                    wire:confirm="Are you sure you want to delete this estimate?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No estimates found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('estimates.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> New Estimate
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted">
                            Showing {{ $estimates->firstItem() ?? 0 }}-{{ $estimates->lastItem() ?? 0 }} of {{ $estimates->total() }}
                            @if($search || $status !== '')
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $status !== '')
                            <button class="btn btn-sm btn-outline-secondary ms-2" wire:click="resetFilters">
                                <i class="fas fa-undo"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                    @if($estimates->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $estimates->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($estimates->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $estimates->currentPage() - 2); $page <= min($estimates->lastPage(), $estimates->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $estimates->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$estimates->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$estimates->hasMorePages()) disabled @endif>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
