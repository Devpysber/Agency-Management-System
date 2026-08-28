<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Testimonial;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $rating = '';
    public $sortBy = 'newest';
    public $selectedTestimonials = [];
    public $selectAll = false;

    protected function fetchTestimonials()
    {
        $query = Testimonial::query();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('client_name', 'like', '%' . $this->search . '%')
                  ->orWhere('company', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->rating !== '') {
            $query->where('rating', $this->rating);
        }

        switch ($this->sortBy) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'rating_high':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('client_name', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function updatedSearch()
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
            $this->selectedTestimonials = $this->fetchTestimonials()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedTestimonials = [];
        }
    }

    public function approve($id)
    {
        if (!auth()->user()->hasPermission('Testimonials', 'Edit')) {
            session()->flash('error', "You don't have permission to approve testimonials.");
            return;
        }

        try {
            $testimonial = Testimonial::findOrFail($id);
            $testimonial->status = 'approved';
            $testimonial->save();
            session()->flash('success', 'Testimonial approved successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error approving testimonial: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Testimonials', 'Delete')) {
            session()->flash('error', "You don't have permission to delete testimonials.");
            return;
        }

        try {
            Testimonial::findOrFail($id)->delete();
            session()->flash('success', 'Testimonial deleted successfully!');
            $this->selectedTestimonials = array_diff($this->selectedTestimonials, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting testimonial: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Testimonials', 'Delete')) {
            session()->flash('error', "You don't have permission to delete testimonials.");
            return;
        }

        if (empty($this->selectedTestimonials)) {
            session()->flash('warning', 'Please select at least one testimonial to delete.');
            return;
        }

        try {
            // Iterate (rather than a single mass-delete query) so each
            // model's deleting event fires and cleans up its avatar.
            Testimonial::whereIn('id', $this->selectedTestimonials)->get()->each->delete();
            session()->flash('success', count($this->selectedTestimonials) . ' testimonial(s) deleted successfully!');
            $this->selectedTestimonials = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting testimonials: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->rating = '';
        $this->sortBy = 'newest';
        $this->resetPage();
    }

    public function getStatsProperty()
    {
        return [
            'total' => Testimonial::count(),
            'approved' => Testimonial::where('status', 'approved')->count(),
            'pending' => Testimonial::where('status', 'pending')->count(),
            'average_rating' => round(Testimonial::avg('rating') ?? 0, 1),
        ];
    }

    public function render()
    {
        return $this->view([
            'testimonials' => $this->fetchTestimonials()->paginate(15),
            'stats' => $this->stats,
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>All Testimonials</h1>
                <p>Manage client testimonials and reviews for your agency.</p>
            </div>
            <div class="header-actions">
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Testimonials', 'Create')))
                    <a href="{{ route('testimonials.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Testimonial
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
                                   placeholder="Search by client or company...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Status</option>
                            <option value="approved">🟢 Approved</option>
                            <option value="pending">🟡 Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">
                            <i class="fas fa-star me-1 text-muted"></i>
                            Rating
                        </label>
                        <select class="form-select" wire:model.live="rating">
                            <option value="">All Ratings</option>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
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
                            <option value="rating_high">Rating High-Low</option>
                            <option value="rating_low">Rating Low-High</option>
                            <option value="name_asc">Name A-Z</option>
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
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Testimonials</h3>
                        <p class="stat-number">{{ $stats['total'] }}</p>
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
                    <div class="stat-icon purple">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Average Rating</h3>
                        <p class="stat-number">{{ $stats['average_rating'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selectedTestimonials) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedTestimonials) }} testimonial(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected testimonials?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedTestimonials', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Testimonials Grid -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-quote-left me-2"></i>
                    Testimonials List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $testimonials->total() }} Testimonials</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchTestimonials">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse ($testimonials as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $testimonial->id }}"
                                           wire:model.live="selectedTestimonials">
                                    <span class="badge {{ $testimonial->status_badge['class'] }}">
                                        {{ ucfirst($testimonial->status) }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    @if($testimonial->avatar)
                                        <img src="{{ asset('storage/' . $testimonial->avatar) }}" class="rounded-circle me-3" style="width:50px;height:50px;object-fit:cover;" alt="{{ $testimonial->client_name }}">
                                    @else
                                        <div class="me-3 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width:50px;height:50px;">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="mb-0 fw-semibold">{{ $testimonial->client_name }}</h6>
                                        @if($testimonial->company)
                                            <small class="text-muted">{{ $testimonial->company }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-2">
                                    @for($i = 0; $i < $testimonial->rating; $i++)
                                        <i class="fas fa-star text-warning"></i>
                                    @endfor
                                    @for($i = $testimonial->rating; $i < 5; $i++)
                                        <i class="far fa-star text-warning"></i>
                                    @endfor
                                </div>
                                <p class="text-muted mb-3">
                                    <i class="fas fa-quote-left text-muted me-1" style="font-size:0.75rem;"></i>
                                    {{ \Illuminate\Support\Str::limit($testimonial->message, 120) }}
                                </p>
                                <div class="btn-group btn-group-sm w-100">
                                    @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Testimonials', 'Edit')))
                                        @if($testimonial->status !== 'approved')
                                            <button class="btn btn-outline-success"
                                                    wire:click="approve({{ $testimonial->id }})"
                                                    title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
                                        <a href="{{ route('testimonials.edit', $testimonial->id) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger"
                                                wire:click="delete({{ $testimonial->id }})"
                                                wire:confirm="Are you sure you want to delete this testimonial?"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="text-center py-4">
                            <i class="fas fa-quote-left fa-3x text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No testimonials found</h5>
                            <p class="text-muted">Try adjusting your search or filter criteria.</p>
                            <a href="{{ route('testimonials.add') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Testimonial
                            </a>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted">
                            Showing {{ $testimonials->firstItem() ?? 0 }}-{{ $testimonials->lastItem() ?? 0 }} of {{ $testimonials->total() }}
                            @if($search || $status !== '' || $rating !== '')
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                        @if($search || $status !== '' || $rating !== '')
                            <button class="btn btn-sm btn-outline-secondary ms-2" wire:click="resetFilters">
                                <i class="fas fa-undo"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                    @if($testimonials->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $testimonials->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($testimonials->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $testimonials->currentPage() - 2); $page <= min($testimonials->lastPage(), $testimonials->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $testimonials->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$testimonials->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$testimonials->hasMorePages()) disabled @endif>
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
