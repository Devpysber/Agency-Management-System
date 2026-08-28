<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Service;
use App\Models\ServiceCategory;

new class extends Component
{
    use WithFileUploads;

    public $name;
    public $service_category_id;
    public $description;
    public $price;
    public $status = 'active';
    public $photo;

    protected $rules = [
        'name' => 'required|string|max:255',
        'service_category_id' => 'nullable|exists:service_categories,id',
        'description' => 'nullable|string',
        'price' => 'nullable|numeric|min:0',
        'status' => 'required|in:active,inactive',
        'photo' => 'nullable|image|max:2048',
    ];

    public function form_submit()
    {
        $this->validate();

        $service = new Service;
        $service->name = $this->name;
        $service->service_category_id = $this->service_category_id ?: null;
        $service->description = $this->description;
        $service->price = $this->price !== '' ? $this->price : null;
        $service->status = $this->status ?: 'active';

        if ($this->photo) {
            $service->image = $this->photo->store('services', 'public');
        }

        $service->save();

        session()->flash('success', 'Service created successfully');

        return redirect()->route('services.all');
    }

    public function render()
    {
        return $this->view([
            'categories' => ServiceCategory::where('status', 'active')->orderBy('name')->get(),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Add New Service</h1>
                <p>Create a new service offered by your agency.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('services.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Services
                </a>
                <button type="button" form="serviceForm" wire:click="form_submit" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Service
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

        <!-- Service Form -->
        <div class="card">
            <div class="card-body">
                <form id="serviceForm">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-concierge-bell me-1 text-muted"></i>
                                        Service Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Enter service name">
                                    @error('name')
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
                                        <i class="fas fa-dollar-sign me-1 text-muted"></i>
                                        Price
                                    </label>
                                    <input type="number" step="0.01" min="0" class="form-control" wire:model="price" placeholder="0.00">
                                    @error('price')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-circle me-1 text-muted"></i>
                                        Status
                                    </label>
                                    <select class="form-select" wire:model="status">
                                        <option value="active">🟢 Active</option>
                                        <option value="inactive">🔴 Inactive</option>
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
                                    <textarea class="form-control" wire:model="description" rows="4" placeholder="Enter service description..."></textarea>
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
                                        <i class="fas fa-image me-2"></i>
                                        Service Image
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <input type="file" class="form-control @error('photo') is-invalid @enderror" wire:model="photo">
                                    @error('photo')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" class="img-fluid rounded mt-3" alt="Preview">
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
