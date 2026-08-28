<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Company;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $industry = '';
    public $status = '';
    public $rating = '';
    public $sortBy = 'name_asc';
    public $perPage = 10;
    public $selectedCompanies = [];
    public $selectAll = false;

    protected function fetchCompanies()
    {
        $query = Company::query();

        // Search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('company_email', 'like', '%' . $this->search . '%')
                  ->orWhere('company_registration_no', 'like', '%' . $this->search . '%')
                  ->orWhere('company_phone', 'like', '%' . $this->search . '%');
            });
        }

        // Industry filter
        if (!empty($this->industry)) {
            $query->where('company_industry', $this->industry);
        }

        // Status filter
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        // Rating filter
        if (!empty($this->rating)) {
            $query->where('company_rating', '>=', (int)$this->rating);
        }

        // Sorting
        switch ($this->sortBy) {
            case 'name_asc':
                $query->orderBy('company_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('company_name', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'rating_high':
                $query->orderBy('company_rating', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('company_rating', 'asc');
                break;
            default:
                $query->orderBy('company_name', 'asc');
        }

        return $query;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedIndustry()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedRating()
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
            $this->selectedCompanies = $this->fetchCompanies()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedCompanies = [];
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Companies', 'Delete')) {
            session()->flash('error', "You don't have permission to delete companies.");
            return;
        }

        try {
            Company::findOrFail($id)->delete();
            session()->flash('success', 'Company deleted successfully!');
            $this->selectedCompanies = array_diff($this->selectedCompanies, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting company: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Companies', 'Delete')) {
            session()->flash('error', "You don't have permission to delete companies.");
            return;
        }

        if (empty($this->selectedCompanies)) {
            session()->flash('warning', 'Please select at least one company to delete.');
            return;
        }

        try {
            Company::whereIn('id', $this->selectedCompanies)->delete();
            session()->flash('success', count($this->selectedCompanies) . ' companies deleted successfully!');
            $this->selectedCompanies = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting companies: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->industry = '';
        $this->status = '';
        $this->rating = '';
        $this->sortBy = 'name_asc';
        $this->resetPage();
    }

    public function getStatusCounts()
    {
        return [
            'total' => Company::count(),
            'active' => Company::where('status', 'active')->count(),
            'pending' => Company::where('status', 'pending')->count(),
            'inactive' => Company::whereIn('status', ['inactive', 'suspended'])->count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'companies' => $this->fetchCompanies()->paginate(15),
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
                <h1>All Companies</h1>
                <p>Manage and view all companies in your CRM system.</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" wire:click="export">
                    <i class="fas fa-file-export"></i> Export
                </button>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Companies', 'Edit')))
                    <button class="btn btn-secondary" wire:click="import">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                    <a href="{{ route('companies.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Company
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
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-search me-1 text-muted"></i>
                            Search
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" 
                                   wire:model.live="search" 
                                   placeholder="Search companies...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-industry me-1 text-muted"></i>
                            Industry
                        </label>
                        <select class="form-select" wire:model.live="industry">
                            <option value="">All Industries</option>
                            <option value="Technology">Technology</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Finance">Finance</option>
                            <option value="Education">Education</option>
                            <option value="Retail">Retail</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Real Estate">Real Estate</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Other">Other</option>
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
                            <option value="pending">🟡 Pending</option>
                            <option value="inactive">🔴 Inactive</option>
                            <option value="suspended">⚫ Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-star me-1 text-muted"></i>
                            Min Rating
                        </label>
                        <select class="form-select" wire:model.live="rating">
                            <option value="">All Ratings</option>
                            <option value="1">⭐ 1+ Star</option>
                            <option value="2">⭐⭐ 2+ Stars</option>
                            <option value="3">⭐⭐⭐ 3+ Stars</option>
                            <option value="4">⭐⭐⭐⭐ 4+ Stars</option>
                            <option value="5">⭐⭐⭐⭐⭐ 5 Stars</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-sort me-1 text-muted"></i>
                            Sort By
                        </label>
                        <select class="form-select" wire:model.live="sortBy">
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="rating_high">Highest Rating</option>
                            <option value="rating_low">Lowest Rating</option>
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

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Companies</h3>
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
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pending</h3>
                        <p class="stat-number">{{ $stats['pending'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Inactive</h3>
                        <p class="stat-number">{{ $stats['inactive'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedCompanies) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedCompanies) }} company(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected companies?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedCompanies', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Companies Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building me-2"></i>
                    Companies List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $companies->total() }} Companies</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchCompanies">
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
                                <th>Company</th>
                                <th>Industry</th>
                                <th>Status</th>
                                <th>Rating</th>
                                <th>Created</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($companies as $company)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input" 
                                           value="{{ $company->id }}" 
                                           wire:model.live="selectedCompanies">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="company-logo me-2">
                                            <i class="fas fa-building fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $company->company_name }}</h6>
                                            <small class="text-muted">{{ $company->company_registration_no ?? 'N/A' }}</small>
                                            @if($company->company_email)
                                                <br><small class="text-muted"><i class="fas fa-envelope"></i> {{ $company->company_email }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $company->company_industry ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($company->status === 'active')
                                        <span class="badge bg-success">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Active
                                        </span>
                                    @elseif($company->status === 'pending')
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Pending
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $rating = $company->company_rating ?? 0;
                                        $fullStars = floor($rating);
                                        $halfStar = $rating - $fullStars >= 0.5 ? 1 : 0;
                                        $emptyStars = 5 - $fullStars - $halfStar;
                                    @endphp
                                    <span class="text-warning">
                                        @for($i = 0; $i < $fullStars; $i++)
                                            <i class="fas fa-star"></i>
                                        @endfor
                                        @if($halfStar)
                                            <i class="fas fa-star-half-alt"></i>
                                        @endif
                                        @for($i = 0; $i < $emptyStars; $i++)
                                            <i class="far fa-star"></i>
                                        @endfor
                                    </span>
                                    <span class="text-muted ms-1">{{ number_format($rating, 1) }}</span>
                                </td>
                                <td>
                                    <small class="text-muted" title="{{ $company->created_at->format('M d, Y h:i A') }}">
                                        {{ $company->created_at->diffForHumans() }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('companies.show', $company->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Companies', 'Edit')))
                                            <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $company->id }})"
                                                    wire:confirm="Are you sure you want to delete this company?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-building fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No companies found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('companies.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add New Company
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
                            Showing {{ $companies->firstItem() ?? 0 }}-{{ $companies->lastItem() ?? 0 }} of {{ $companies->total() }}
                            @if($search || $industry || $status !== '' || $rating)
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $industry || $status !== '' || $rating)
                            <button class="btn btn-sm btn-outline-secondary ms-2" wire:click="resetFilters">
                                <i class="fas fa-undo"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                    @if($companies->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $companies->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($companies->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $companies->currentPage() - 2); $page <= min($companies->lastPage(), $companies->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $companies->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$companies->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$companies->hasMorePages()) disabled @endif>
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