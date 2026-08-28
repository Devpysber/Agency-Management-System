<?php

use Livewire\Component;
use App\Models\PricingPlan;
use App\Models\ServiceCategory;

new class extends Component
{
    public $name;
    public $service_category_id;
    public $price;
    public $billing_period = 'monthly';
    public $featuresText = '';
    public $countriesText = '';
    public $status = 'active';

    protected $rules = [
        'name' => 'required|string|max:255',
        'service_category_id' => 'nullable|exists:service_categories,id',
        'price' => 'required|numeric|min:0',
        'billing_period' => 'required|in:monthly,yearly,one_time',
        'featuresText' => 'nullable|string',
        'countriesText' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ];

    public function form_submit()
    {
        $this->validate();

        $plan = new PricingPlan;
        $plan->name = $this->name;
        $plan->service_category_id = $this->service_category_id ?: null;
        $plan->price = $this->price;
        $plan->billing_period = $this->billing_period ?: 'monthly';
        $plan->features = array_values(array_filter(array_map('trim', explode("\n", $this->featuresText))));
        $plan->countries = array_values(array_filter(array_map('trim', explode("\n", $this->countriesText))));
        $plan->status = $this->status ?: 'active';
        $plan->save();

        session()->flash('success', 'Pricing plan created successfully');

        return redirect()->route('pricing.all');
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
                <h1>Add New Pricing Plan</h1>
                <p>Create a new pricing plan for your agency's services.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('pricing.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Pricing
                </a>
                <button type="button" form="pricingForm" wire:click="form_submit" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Plan
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

        <!-- Pricing Plan Form -->
        <div class="card">
            <div class="card-body">
                <form id="pricingForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-tag me-1 text-muted"></i>
                                Plan Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="e.g. Professional">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-tags me-1 text-muted"></i>
                                Service Type
                            </label>
                            <select class="form-select" wire:model="service_category_id">
                                <option value="">Select Service Type</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('service_category_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-dollar-sign me-1 text-muted"></i>
                                Price <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" wire:model="price" placeholder="0.00">
                            @error('price')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                <i class="fas fa-calendar me-1 text-muted"></i>
                                Billing Period
                            </label>
                            <select class="form-select" wire:model="billing_period">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="one_time">One Time</option>
                            </select>
                            @error('billing_period')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-list-check me-1 text-muted"></i>
                                Features <small class="text-muted">(one per line)</small>
                            </label>
                            <textarea class="form-control" wire:model="featuresText" rows="6" placeholder="Unlimited revisions&#10;24/7 support&#10;Priority queue"></textarea>
                            @error('featuresText')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                <i class="fas fa-globe me-1 text-muted"></i>
                                Available Countries <small class="text-muted">(one per line)</small>
                            </label>
                            <textarea class="form-control" wire:model="countriesText" rows="6" placeholder="United States&#10;United Kingdom&#10;Canada"></textarea>
                            @error('countriesText')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
