<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Service;
use App\Models\ServiceCategory;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $category = '';
    public $status = '';
    public $sortBy = 'name_asc';
    public $selectedServices = [];
    public $selectAll = false;

    protected function fetchServices()
    {
        $query = Service::with('category');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->category)) {
            $query->where('service_category_id', $this->category);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        switch ($this->sortBy) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        return $query;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategory()
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
            $this->selectedServices = $this->fetchServices()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedServices = [];
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Services', 'Delete')) {
            session()->flash('error', "You don't have permission to delete services.");
            return;
        }

        try {
            Service::findOrFail($id)->delete();
            session()->flash('success', 'Service deleted successfully!');
            $this->selectedServices = array_diff($this->selectedServices, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting service: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Services', 'Delete')) {
            session()->flash('error', "You don't have permission to delete services.");
            return;
        }

        if (empty($this->selectedServices)) {
            session()->flash('warning', 'Please select at least one service to delete.');
            return;
        }

        try {
            // Iterate (rather than a single mass-delete query) so each
            // model's deleting event fires and cleans up its image.
            Service::whereIn('id', $this->selectedServices)->get()->each->delete();
            session()->flash('success', count($this->selectedServices) . ' service(s) deleted successfully!');
            $this->selectedServices = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting services: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->category = '';
        $this->status = '';
        $this->sortBy = 'name_asc';
        $this->resetPage();
    }

    public function getStatsProperty()
    {
        return [
            'total' => Service::count(),
            'active' => Service::where('status', 'active')->count(),
            'inactive' => Service::where('status', 'inactive')->count(),
            'categories' => ServiceCategory::count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'services' => $this->fetchServices()->paginate(15),
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
                <h1>All Services</h1>
                <p>Manage the services your agency offers.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('services.categories') }}" class="btn btn-secondary">
                    <i class="fas fa-tags"></i> Categories
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Services', 'Create')))
                    <a href="{{ route('services.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Service
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
                                   placeholder="Search services...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-tags me-1 text-muted"></i>
                            Category
                        </label>
                        <select class="form-select" wire:model.live="category">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
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
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-sort me-1 text-muted"></i>
                            Sort By
                        </label>
                        <select class="form-select" wire:model.live="sortBy">
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="price_high">Price High-Low</option>
                            <option value="price_low">Price Low-High</option>
                            <option value="newest">Newest First</option>
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
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Services</h3>
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
                    <div class="stat-icon red">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Inactive</h3>
                        <p class="stat-number">{{ $stats['inactive'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Categories</h3>
                        <p class="stat-number">{{ $stats['categories'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedServices) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedServices) }} service(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected services?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedServices', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Services Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-concierge-bell me-2"></i>
                    Services List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $services->total() }} Services</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchServices">
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
                                <th>Service</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $service->id }}"
                                           wire:model.live="selectedServices">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($service->image)
                                            <img src="{{ asset('storage/' . $service->image) }}" class="rounded me-2" style="width:40px;height:40px;object-fit:cover;" alt="{{ $service->name }}">
                                        @else
                                            <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded" style="width:40px;height:40px;">
                                                <i class="fas fa-concierge-bell text-primary"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $service->name }}</h6>
                                            @if($service->description)
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($service->description, 40) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $service->category->name ?? 'Uncategorized' }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $service->price !== null ? '$' . number_format($service->price, 2) : 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $service->status_badge['class'] }}">
                                        {{ ucfirst($service->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('services.show', $service->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Services', 'Edit')))
                                            <a href="{{ route('services.edit', $service->id) }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $service->id }})"
                                                    wire:confirm="Are you sure you want to delete this service?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-concierge-bell fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No services found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('services.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add New Service
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
                            Showing {{ $services->firstItem() ?? 0 }}-{{ $services->lastItem() ?? 0 }} of {{ $services->total() }}
                            @if($search || $category || $status !== '')
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $category || $status !== '')
                            <button class="btn btn-sm btn-outline-secondary ms-2" wire:click="resetFilters">
                                <i class="fas fa-undo"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                    @if($services->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $services->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($services->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $services->currentPage() - 2); $page <= min($services->lastPage(), $services->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $services->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$services->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$services->hasMorePages()) disabled @endif>
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
