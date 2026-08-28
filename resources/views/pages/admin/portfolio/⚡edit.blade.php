<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\PortfolioItem;
use App\Models\ServiceCategory;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $item;
    public $title;
    public $service_category_id;
    public $description;
    public $project_id;
    public $client_name;
    public $completed_date;
    public $status = 'published';
    public $existingImages = [];
    public $newImages = [];

    protected $rules = [
        'title' => 'required|string|max:255',
        'service_category_id' => 'nullable|exists:service_categories,id',
        'description' => 'nullable|string|max:5000',
        'project_id' => 'nullable|exists:projects,id',
        'client_name' => 'nullable|string|max:255',
        'completed_date' => 'nullable|date',
        'status' => 'required|in:draft,published',
        'newImages' => 'nullable|array|max:8',
        'newImages.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
    ];

    public function mount($id)
    {
        $this->item = PortfolioItem::findOrFail($id);
        $this->title = $this->item->title;
        $this->service_category_id = $this->item->service_category_id;
        $this->description = $this->item->description;
        $this->project_id = $this->item->project_id;
        $this->client_name = $this->item->client_name;
        $this->completed_date = $this->item->completed_date ? $this->item->completed_date->format('Y-m-d') : null;
        $this->status = $this->item->status;
        $this->existingImages = $this->item->images ?? [];
    }

    public function removeExistingImage($index)
    {
        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    public function update()
    {
        if (!auth()->user()->hasPermission('Portfolio', 'Edit')) {
            session()->flash('error', "You don't have permission to edit portfolio items.");
            return;
        }

        $this->validate();

        $this->item->title = $this->title;
        $this->item->service_category_id = $this->service_category_id ?: null;
        $this->item->description = $this->description;
        $this->item->project_id = $this->project_id ?: null;
        $this->item->client_name = $this->client_name;
        $this->item->completed_date = $this->completed_date ?: null;
        $this->item->status = $this->status ?: 'published';

        $paths = $this->existingImages;
        foreach ($this->newImages as $img) {
            $paths[] = $img->store('portfolio', 'public');
        }

        // Anything that was on the record before but didn't survive into the
        // final list (removed in the UI) is gone for good — clean it off disk.
        $removed = array_diff($this->item->images ?? [], $paths);
        foreach ($removed as $path) {
            Storage::disk('public')->delete($path);
        }

        $this->item->images = $paths;

        $this->item->save();

        session()->flash('success', 'Portfolio item updated successfully');

        return redirect()->route('portfolio.show', $this->item->id);
    }

    public function render()
    {
        return $this->view([
            'categories' => ServiceCategory::where('status', 'active')->orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Edit Portfolio Item</h1>
                <p>Update portfolio item details.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('portfolio.show', $item->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Item
                </a>
                <button type="button" form="portfolioForm" wire:click="update" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Update Item
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

        <!-- Portfolio Form -->
        <div class="card">
            <div class="card-body">
                <form id="portfolioForm">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-heading me-1 text-muted"></i>
                                        Title <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" wire:model="title" placeholder="Enter project title">
                                    @error('title')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-tags me-1 text-muted"></i>
                                        Category
                                    </label>
                                    <select class="form-select" wire:model="service_category_id">
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('service_category_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-project-diagram me-1 text-muted"></i>
                                        Related Project
                                    </label>
                                    <select class="form-select" wire:model="project_id">
                                        <option value="">None</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('project_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-user me-1 text-muted"></i>
                                        Client Name
                                    </label>
                                    <input type="text" class="form-control" wire:model="client_name" placeholder="Enter client name">
                                    @error('client_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-calendar me-1 text-muted"></i>
                                        Completed Date
                                    </label>
                                    <input type="date" class="form-control" wire:model="completed_date">
                                    @error('completed_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-circle me-1 text-muted"></i>
                                        Status
                                    </label>
                                    <select class="form-select" wire:model="status">
                                        <option value="published">🟢 Published</option>
                                        <option value="draft">⚪ Draft</option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-align-left me-1 text-muted"></i>
                                        Description
                                    </label>
                                    <textarea class="form-control" wire:model="description" rows="4" placeholder="Describe this work..."></textarea>
                                    @error('description')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-images me-2"></i>
                                        Gallery Images
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if (count($existingImages) > 0)
                                        <div class="row g-2 mb-3">
                                            @foreach ($existingImages as $index => $path)
                                                <div class="col-6 position-relative">
                                                    <img src="{{ asset('storage/' . $path) }}" class="img-fluid rounded" alt="Image {{ $index + 1 }}">
                                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                            wire:click="removeExistingImage({{ $index }})"
                                                            wire:confirm="Remove this image?">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <label class="form-label fw-medium">Add More Images</label>
                                    <input type="file" multiple class="form-control @error('newImages.*') is-invalid @enderror" wire:model="newImages">
                                    @error('newImages.*')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    @if ($newImages)
                                        <div class="row g-2 mt-2">
                                            @foreach ($newImages as $img)
                                                <div class="col-6">
                                                    <img src="{{ $img->temporaryUrl() }}" class="img-fluid rounded" alt="Preview">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
