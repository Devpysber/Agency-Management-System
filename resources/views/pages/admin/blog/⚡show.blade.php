<?php

use Livewire\Component;
use App\Models\BlogPost;

new class extends Component
{
    public $post;

    public function mount($id)
    {
        $this->post = BlogPost::with(['category', 'author'])->findOrFail($id);
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('Blog', 'Delete')) {
            session()->flash('error', "You don't have permission to delete blog posts.");
            return;
        }

        try {
            $this->post->delete();
            session()->flash('success', 'Blog post deleted successfully!');
            return redirect()->route('blog.all');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting post: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return $this->view();
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="mb-0">{{ $post->title }}</h1>
                <p class="mb-0">
                    <span class="badge {{ $post->status_badge['class'] }}">
                        {{ ucfirst($post->status) }}
                    </span>
                    <span class="text-muted ms-2">{{ $post->category->name ?? 'Uncategorized' }}</span>
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('blog.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Blog', 'Edit')))
                    <a href="{{ route('blog.edit', $post->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-danger" wire:click="delete" wire:confirm="Are you sure you want to delete this post?">
                        <i class="fas fa-trash"></i> Delete
                    </button>
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

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        @if($post->featured_image)
                            <img src="{{ asset('storage/' . $post->featured_image) }}" class="img-fluid rounded mb-3" alt="{{ $post->title }}">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light rounded mb-3 mx-auto" style="width:100%;height:200px;">
                                <i class="fas fa-blog fa-3x text-muted"></i>
                            </div>
                        @endif
                        <h5 class="fw-semibold mb-1">{{ $post->title }}</h5>
                        <p class="text-muted mb-0">{{ $post->category->name ?? 'Uncategorized' }}</p>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Post Info
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="text-muted small fw-medium">Author</label>
                            <p class="mb-0 fw-semibold">{{ $post->author->name ?? 'Unknown' }}</p>
                        </div>
                        <div class="mb-2">
                            <label class="text-muted small fw-medium">Published</label>
                            <p class="mb-0">{{ $post->published_at ? $post->published_at->format('M d, Y') : 'Not published' }}</p>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small fw-medium">Slug</label>
                            <p class="mb-0"><code>{{ $post->slug }}</code></p>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-search text-primary me-2"></i>
                            SEO Meta
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="text-muted small fw-medium">SEO Title</label>
                            <p class="mb-0">{{ $post->seo_title ?: 'Not set' }}</p>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small fw-medium">SEO Description</label>
                            <p class="mb-0">{{ $post->seo_description ?: 'Not set' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-align-left text-primary me-2"></i>
                            Content
                        </h5>
                    </div>
                    <div class="card-body">
                        <div style="white-space: pre-wrap;">{{ $post->content }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
