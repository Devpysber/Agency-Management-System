<?php

use Livewire\Component;
use App\Models\Service;

new class extends Component
{
    public $service;

    public function mount($id)
    {
        $this->service = Service::with('category')->findOrFail($id);
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('Services', 'Delete')) {
            session()->flash('error', "You don't have permission to delete services.");
            return;
        }

        try {
            $this->service->delete();
            session()->flash('success', 'Service deleted successfully!');
            return redirect()->route('services.all');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting service: ' . $e->getMessage());
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
                <h1 class="mb-0">{{ $service->name }}</h1>
                <p class="mb-0">
                    <span class="badge {{ $service->status_badge['class'] }}">
                        {{ ucfirst($service->status) }}
                    </span>
                    <span class="text-muted ms-2">{{ $service->category->name ?? 'Uncategorized' }}</span>
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('services.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Services', 'Edit')))
                    <a href="{{ route('services.edit', $service->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-danger" wire:click="delete" wire:confirm="Are you sure you want to delete this service?">
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
                        @if($service->image)
                            <img src="{{ asset('storage/' . $service->image) }}" class="img-fluid rounded mb-3" alt="{{ $service->name }}">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light rounded mb-3 mx-auto" style="width:100%;height:200px;">
                                <i class="fas fa-concierge-bell fa-3x text-muted"></i>
                            </div>
                        @endif
                        <h5 class="fw-semibold mb-1">{{ $service->name }}</h5>
                        <p class="text-muted mb-0">{{ $service->category->name ?? 'Uncategorized' }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Service Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Category</label>
                                <p class="fw-semibold">{{ $service->category->name ?? 'Uncategorized' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Status</label>
                                <p>
                                    <span class="badge {{ $service->status_badge['class'] }}">
                                        {{ ucfirst($service->status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Price</label>
                                <p class="fw-semibold">{{ $service->price !== null ? '$' . number_format($service->price, 2) : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Created</label>
                                <p>{{ $service->created_at ? $service->created_at->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Description</label>
                                <p class="mb-0">{{ $service->description ?? 'No description available.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
