<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PortfolioItem;
use App\Models\ServiceCategory;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $category = '';
    public $status = '';
    public $sortBy = 'newest';
    public $selectedItems = [];
    public $selectAll = false;

    public function updatedSearch() { $this->resetPage(); }
    public function updatedCategory() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }
    public function updatedSortBy() { $this->resetPage(); }

    protected function baseQuery()
    {
        $query = PortfolioItem::with(['category', 'project']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('client_name', 'like', '%' . $this->search . '%')
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
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Current page only — matches what's actually visible/selectable on screen.
            $this->selectedItems = $this->baseQuery()->paginate(12)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Portfolio', 'Delete')) {
            session()->flash('error', "You don't have permission to delete portfolio items.");
            return;
        }

        try {
            PortfolioItem::findOrFail($id)->delete();
            session()->flash('success', 'Portfolio item deleted successfully!');
            $this->selectedItems = array_diff($this->selectedItems, [(string) $id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting portfolio item: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Portfolio', 'Delete')) {
            session()->flash('error', "You don't have permission to delete portfolio items.");
            return;
        }

        if (empty($this->selectedItems)) {
            session()->flash('warning', 'Please select at least one item to delete.');
            return;
        }

        try {
            // Iterate (rather than a single mass-delete query) so each
            // model's deleting event fires and cleans up its gallery files.
            PortfolioItem::whereIn('id', $this->selectedItems)->get()->each->delete();
            session()->flash('success', count($this->selectedItems) . ' item(s) deleted successfully!');
            $this->selectedItems = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting items: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->category = '';
        $this->status = '';
        $this->sortBy = 'newest';
        $this->resetPage();
    }

    public function render()
    {
        return $this->view([
            'items' => $this->baseQuery()->paginate(12),
            'stats' => [
                'total' => PortfolioItem::count(),
                'published' => PortfolioItem::where('status', 'published')->count(),
                'draft' => PortfolioItem::where('status', 'draft')->count(),
            ],
            'categories' => ServiceCategory::orderBy('name')->get(),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Portfolio</h1>
                <p>Showcase completed work and case studies.</p>
            </div>
            <div class="header-actions">
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Portfolio', 'Create')))
                    <a href="{{ route('portfolio.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Item
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
                                   placeholder="Search portfolio items...">
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
                            <option value="published">🟢 Published</option>
                            <option value="draft">⚪ Draft</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-sort me-1 text-muted"></i>
                            Sort By
                        </label>
                        <select class="form-select" wire:model.live="sortBy">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="title_asc">Title A-Z</option>
                            <option value="title_desc">Title Z-A</option>
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
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Items</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Published</h3>
                        <p class="stat-number">{{ $stats['published'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Draft</h3>
                        <p class="stat-number">{{ $stats['draft'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedItems) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedItems) }} item(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected items?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedItems', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Portfolio Grid -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-images me-2"></i>
                    Portfolio Items
                </h3>
                <span class="badge bg-primary">{{ $items->total() }} Items</span>
            </div>
            <div class="card-body">
                @if($items->total() > 0)
                    <div class="row g-3">
                        <div class="col-12 mb-2">
                            <label class="form-check-label small text-muted">
                                <input type="checkbox" class="form-check-input me-1" wire:model.live="selectAll">
                                Select all
                            </label>
                        </div>
                        @foreach ($items as $item)
                        <div class="col-md-4 col-lg-3">
                            <div class="card h-100">
                                <div class="position-relative">
                                    <input type="checkbox" class="form-check-input position-absolute top-0 start-0 m-2"
                                           value="{{ $item->id }}"
                                           wire:model.live="selectedItems"
                                           style="z-index:2;">
                                    @if(!empty($item->images) && count($item->images) > 0)
                                        <img src="{{ asset('storage/' . $item->images[0]) }}" class="card-img-top" style="height:160px;object-fit:cover;" alt="{{ $item->title }}">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center bg-light" style="height:160px;">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    @endif
                                    <span class="badge {{ $item->status_badge['class'] }} position-absolute top-0 end-0 m-2">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-1">{{ $item->title }}</h6>
                                    <p class="text-muted small mb-1">
                                        <i class="fas fa-tags me-1"></i>{{ $item->category->name ?? 'Uncategorized' }}
                                    </p>
                                    @if($item->client_name)
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-user me-1"></i>{{ $item->client_name }}
                                        </p>
                                    @endif
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="{{ route('portfolio.show', $item->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Portfolio', 'Edit')))
                                            <a href="{{ route('portfolio.edit', $item->id) }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $item->id }})"
                                                    wire:confirm="Are you sure you want to delete this item?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-images fa-3x text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">No portfolio items found</h5>
                        <p class="text-muted">Try adjusting your search or filter criteria.</p>
                        <a href="{{ route('portfolio.add') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Item
                        </a>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted">
                            Showing {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
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
                    @if($items->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($items->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $items->currentPage() - 2); $page <= min($items->lastPage(), $items->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $items->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$items->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$items->hasMorePages()) disabled @endif>
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
