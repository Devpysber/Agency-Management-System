<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $post;
    public $title;
    public $blog_category_id;
    public $content;
    public $featured_image;
    public $status = 'draft';
    public $seo_title;
    public $seo_description;

    protected $rules = [
        'title' => 'required|string|max:255',
        'blog_category_id' => 'nullable|exists:blog_categories,id',
        'content' => 'required|string',
        'featured_image' => 'nullable|image|max:2048',
        'status' => 'required|in:draft,published',
        'seo_title' => 'nullable|string|max:255',
        'seo_description' => 'nullable|string',
    ];

    public function mount($id)
    {
        $this->post = BlogPost::findOrFail($id);
        $this->title = $this->post->title;
        $this->blog_category_id = $this->post->blog_category_id;
        $this->content = $this->post->content;
        $this->status = $this->post->status;
        $this->seo_title = $this->post->seo_title;
        $this->seo_description = $this->post->seo_description;
    }

    public function update()
    {
        $this->validate();

        $wasPublished = $this->post->status === 'published';

        $this->post->title = $this->title;
        $this->post->blog_category_id = $this->blog_category_id ?: null;
        $this->post->content = $this->content;
        $this->post->status = $this->status ?: 'draft';
        $this->post->seo_title = $this->seo_title;
        $this->post->seo_description = $this->seo_description;

        if ($this->status === 'published' && !$wasPublished && !$this->post->published_at) {
            $this->post->published_at = now();
        }

        if ($this->featured_image) {
            if ($this->post->featured_image) {
                Storage::disk('public')->delete($this->post->featured_image);
            }
            $this->post->featured_image = $this->featured_image->store('blog', 'public');
        }

        $this->post->save();

        session()->flash('success', 'Blog post updated successfully');

        return redirect()->route('blog.show', $this->post->id);
    }

    public function render()
    {
        return $this->view([
            'categories' => BlogCategory::where('status', 'active')->orderBy('name')->get(),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Edit Post</h1>
                <p>Update blog post details.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('blog.show', $post->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Post
                </a>
                <button type="button" form="blogForm" wire:click="update" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Update Post
                </button>
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

        <!-- Blog Form -->
        <div class="card">
            <div class="card-body">
                <form id="blogForm">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-heading me-1 text-muted"></i>
                                        Title <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" wire:model="title" placeholder="Enter post title">
                                    <small class="text-muted">Slug: <code>{{ $post->slug }}</code> (unchanged on edit)</small>
                                    @error('title')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-tags me-1 text-muted"></i>
                                        Category
                                    </label>
                                    <select class="form-select" wire:model="blog_category_id">
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('blog_category_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-align-left me-1 text-muted"></i>
                                        Content <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('content') is-invalid @enderror" wire:model="content" rows="15" placeholder="Write your post content..."></textarea>
                                    @error('content')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-image me-2"></i>
                                        Featured Image
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if ($post->featured_image && !$featured_image)
                                        <img src="{{ asset('storage/' . $post->featured_image) }}" class="img-fluid rounded mb-3" alt="{{ $post->title }}">
                                    @endif
                                    <input type="file" class="form-control @error('featured_image') is-invalid @enderror" wire:model="featured_image">
                                    @error('featured_image')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    @if ($featured_image)
                                        <img src="{{ $featured_image->temporaryUrl() }}" class="img-fluid rounded mt-3" alt="Preview">
                                    @endif
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-circle me-2"></i>
                                        Publish
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <label class="form-label fw-medium">Status</label>
                                    <select class="form-select" wire:model="status">
                                        <option value="draft">⚪ Draft</option>
                                        <option value="published">🟢 Published</option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-search me-2"></i>
                                        SEO
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">SEO Title</label>
                                        <input type="text" class="form-control" wire:model="seo_title" placeholder="SEO title">
                                        @error('seo_title')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">SEO Description</label>
                                        <textarea class="form-control" wire:model="seo_description" rows="3" placeholder="Meta description"></textarea>
                                        @error('seo_description')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
