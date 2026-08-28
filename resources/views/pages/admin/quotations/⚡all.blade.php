<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Quotation;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $sortBy = 'newest';
    public $selectedQuotations = [];
    public $selectAll = false;

    protected function fetchQuotations()
    {
        $query = Quotation::query();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('service_interest', 'like', '%' . $this->search . '%');
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
            case 'amount_high':
                $query->orderByDesc('quoted_amount');
                break;
            case 'amount_low':
                $query->orderBy('quoted_amount', 'asc');
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
            $this->selectedQuotations = $this->fetchQuotations()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedQuotations = [];
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Quotations', 'Delete')) {
            session()->flash('error', "You don't have permission to delete quotations.");
            return;
        }

        try {
            Quotation::findOrFail($id)->delete();
            session()->flash('success', 'Quotation deleted successfully!');
            $this->selectedQuotations = array_diff($this->selectedQuotations, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting quotation: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Quotations', 'Delete')) {
            session()->flash('error', "You don't have permission to delete quotations.");
            return;
        }

        if (empty($this->selectedQuotations)) {
            session()->flash('warning', 'Please select at least one quotation to delete.');
            return;
        }

        try {
            Quotation::whereIn('id', $this->selectedQuotations)->delete();
            session()->flash('success', count($this->selectedQuotations) . ' quotation(s) deleted successfully!');
            $this->selectedQuotations = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting quotations: ' . $e->getMessage());
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
            'total' => Quotation::count(),
            'pending' => Quotation::where('status', 'pending')->count(),
            'quoted' => Quotation::where('status', 'quoted')->count(),
            'accepted' => Quotation::where('status', 'accepted')->count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'quotations' => $this->fetchQuotations()->paginate(15),
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
                <h1>All Quotations</h1>
                <p>Manage and triage inbound quotation inquiries.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('quotations.add') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Quotation
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
                                   placeholder="Search by name, email, or service...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="quoted">Quoted</option>
                            <option value="accepted">Accepted</option>
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
                            <option value="amount_high">Highest Quoted Amount</option>
                            <option value="amount_low">Lowest Quoted Amount</option>
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
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Inquiries</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <p class="stat-number">{{ $stats['pending'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Quoted</h3>
                        <p class="stat-number">{{ $stats['quoted'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Accepted</h3>
                        <p class="stat-number">{{ $stats['accepted'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedQuotations) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedQuotations) }} quotation(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected quotations?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedQuotations', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Quotations Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-signature me-2"></i>
                    Quotations List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $quotations->total() }} Quotations</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchQuotations">
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
                                <th>Name</th>
                                <th>Email</th>
                                <th>Service Interest</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($quotations as $quotation)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $quotation->id }}"
                                           wire:model.live="selectedQuotations">
                                </td>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $quotation->name }}</h6>
                                </td>
                                <td>
                                    {{ $quotation->email }}
                                </td>
                                <td>
                                    {{ $quotation->service_interest ?: 'N/A' }}
                                </td>
                                <td>
                                    <span class="badge {{ $quotation->status_badge['class'] }}">
                                        {{ $quotation->status_badge['icon'] }} {{ ucfirst($quotation->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $quotation->created_at ? $quotation->created_at->format('M d, Y') : 'N/A' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('quotations.show', $quotation->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Quotations', 'Delete'))
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $quotation->id }})"
                                                    wire:confirm="Are you sure you want to delete this quotation?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-file-signature fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No quotations found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('quotations.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> New Quotation
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
                            Showing {{ $quotations->firstItem() ?? 0 }}-{{ $quotations->lastItem() ?? 0 }} of {{ $quotations->total() }}
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
                    @if($quotations->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $quotations->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($quotations->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $quotations->currentPage() - 2); $page <= min($quotations->lastPage(), $quotations->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $quotations->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$quotations->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$quotations->hasMorePages()) disabled @endif>
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
