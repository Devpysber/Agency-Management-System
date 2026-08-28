<?php

use Livewire\Component;
use App\Models\PortfolioItem;

new class extends Component
{
    public $item;

    public function mount($id)
    {
        $this->item = PortfolioItem::with(['category', 'project'])->findOrFail($id);
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('Portfolio', 'Delete')) {
            session()->flash('error', "You don't have permission to delete portfolio items.");
            return;
        }

        try {
            $this->item->delete();
            session()->flash('success', 'Portfolio item deleted successfully!');
            return redirect()->route('portfolio.all');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting portfolio item: ' . $e->getMessage());
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
                <h1 class="mb-0">{{ $item->title }}</h1>
                <p class="mb-0">
                    <span class="badge {{ $item->status_badge['class'] }}">
                        {{ ucfirst($item->status) }}
                    </span>
                    <span class="text-muted ms-2">{{ $item->category->name ?? 'Uncategorized' }}</span>
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('portfolio.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Portfolio', 'Edit')))
                    <a href="{{ route('portfolio.edit', $item->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-danger" wire:click="delete" wire:confirm="Are you sure you want to delete this item?">
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
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-images text-primary me-2"></i>
                            Gallery
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(!empty($item->images) && count($item->images) > 0)
                            <div class="row g-3">
                                @foreach($item->images as $path)
                                    <div class="col-md-6">
                                        <img src="{{ asset('storage/' . $path) }}" class="img-fluid rounded" alt="{{ $item->title }}">
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height:200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Category</label>
                                <p class="fw-semibold">{{ $item->category->name ?? 'Uncategorized' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Status</label>
                                <p>
                                    <span class="badge {{ $item->status_badge['class'] }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Client</label>
                                <p class="fw-semibold">{{ $item->client_name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Completed Date</label>
                                <p>{{ $item->completed_date ? $item->completed_date->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            @if($item->project)
                                <div class="col-12">
                                    <label class="text-muted small fw-medium">Related Project</label>
                                    <p>
                                        <a href="{{ route('projects.show', $item->project_id) }}">
                                            <i class="fas fa-project-diagram me-1"></i>{{ $item->project->name }}
                                        </a>
                                    </p>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Description</label>
                                <p class="mb-0">{{ $item->description ?? 'No description available.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
