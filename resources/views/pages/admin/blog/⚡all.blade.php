<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BlogPost;
use App\Models\BlogCategory;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $category = '';
    public $status = '';
    public $sortBy = 'newest';
    public $selectedPosts = [];
    public $selectAll = false;

    protected function fetchPosts()
    {
        $query = BlogPost::with(['category', 'author']);

        if (!empty($this->search)) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->category)) {
            $query->where('blog_category_id', $this->category);
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
            $this->selectedPosts = $this->fetchPosts()->paginate(15)->pluck('id')->toArray();
        } else {
            $this->selectedPosts = [];
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Blog', 'Delete')) {
            session()->flash('error', "You don't have permission to delete blog posts.");
            return;
        }

        try {
            BlogPost::findOrFail($id)->delete();
            session()->flash('success', 'Blog post deleted successfully!');
            $this->selectedPosts = array_diff($this->selectedPosts, [$id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting post: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Blog', 'Delete')) {
            session()->flash('error', "You don't have permission to delete blog posts.");
            return;
        }

        if (empty($this->selectedPosts)) {
            session()->flash('warning', 'Please select at least one post to delete.');
            return;
        }

        try {
            // Iterate (rather than a single mass-delete query) so each
            // model's deleting event fires and cleans up its featured image.
            BlogPost::whereIn('id', $this->selectedPosts)->get()->each->delete();
            session()->flash('success', count($this->selectedPosts) . ' post(s) deleted successfully!');
            $this->selectedPosts = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting posts: ' . $e->getMessage());
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

    public function getStatsProperty()
    {
        return [
            'total' => BlogPost::count(),
            'published' => BlogPost::where('status', 'published')->count(),
            'draft' => BlogPost::where('status', 'draft')->count(),
            'categories' => BlogCategory::count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'posts' => $this->fetchPosts()->paginate(15),
            'stats' => $this->stats,
            'categories' => BlogCategory::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Blog Posts</h1>
                <p>Manage your agency's blog content.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('blog.categories') }}" class="btn btn-secondary">
                    <i class="fas fa-tags"></i> Categories
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Blog', 'Create')))
                    <a href="{{ route('blog.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Post
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
                                   placeholder="Search posts...">
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
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-blog"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Posts</h3>
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
                        <h3>Published</h3>
                        <p class="stat-number">{{ $stats['published'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Draft</h3>
                        <p class="stat-number">{{ $stats['draft'] }}</p>
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
        @if(count($selectedPosts) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selectedPosts) }} post(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure to delete selected posts?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selectedPosts', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Posts Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-blog me-2"></i>
                    Blog Posts List
                </h3>
                <div>
                    <span class="badge bg-primary me-2">{{ $posts->total() }} Posts</span>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="fetchPosts">
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
                                <th>Post</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Published</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($posts as $post)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $post->id }}"
                                           wire:model.live="selectedPosts">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($post->featured_image)
                                            <img src="{{ asset('storage/' . $post->featured_image) }}" class="rounded me-2" style="width:40px;height:40px;object-fit:cover;" alt="{{ $post->title }}">
                                        @else
                                            <div class="me-2 d-flex align-items-center justify-content-center bg-light rounded" style="width:40px;height:40px;">
                                                <i class="fas fa-blog text-primary"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $post->title }}</h6>
                                            <small class="text-muted">{{ $post->slug }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $post->category->name ?? 'Uncategorized' }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $post->status_badge['class'] }}">
                                        {{ ucfirst($post->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $post->published_at ? $post->published_at->format('M d, Y') : '—' }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('blog.show', $post->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Blog', 'Edit')))
                                            <a href="{{ route('blog.edit', $post->id) }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $post->id }})"
                                                    wire:confirm="Are you sure you want to delete this post?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-blog fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No blog posts found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('blog.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add New Post
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
                            Showing {{ $posts->firstItem() ?? 0 }}-{{ $posts->lastItem() ?? 0 }} of {{ $posts->total() }}
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
                    @if($posts->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $posts->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($posts->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $posts->currentPage() - 2); $page <= min($posts->lastPage(), $posts->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $posts->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$posts->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$posts->hasMorePages()) disabled @endif>
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
