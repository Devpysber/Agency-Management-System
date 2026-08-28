<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component
{
    public $product;

    public function mount($id)
    {
        $this->product = Product::with('category')->findOrFail($id);
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('Products', 'Delete')) {
            session()->flash('error', "You don't have permission to delete products.");
            return;
        }

        try {
            $this->product->delete();
            session()->flash('success', 'Product deleted successfully!');
            return redirect()->route('products.all');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting product: ' . $e->getMessage());
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
                <h1 class="mb-0">{{ $product->name }}</h1>
                <p class="mb-0">
                    <span class="badge {{ $product->status_badge['class'] }}">
                        {{ ucfirst($product->status) }}
                    </span>
                    <span class="text-muted ms-2">{{ $product->category->name ?? 'Uncategorized' }}</span>
                    @if($product->stock_quantity < 10)
                        <span class="badge bg-warning text-dark ms-2">
                            <i class="fas fa-exclamation-triangle me-1"></i> Low Stock
                        </span>
                    @endif
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('products.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Products', 'Edit')))
                    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-danger" wire:click="delete" wire:confirm="Are you sure you want to delete this product?">
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
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="img-fluid rounded mb-3" alt="{{ $product->name }}">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light rounded mb-3 mx-auto" style="width:100%;height:200px;">
                                <i class="fas fa-box fa-3x text-muted"></i>
                            </div>
                        @endif
                        <h5 class="fw-semibold mb-1">{{ $product->name }}</h5>
                        <p class="text-muted mb-0">{{ $product->category->name ?? 'Uncategorized' }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Product Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">SKU</label>
                                <p class="fw-semibold">{{ $product->sku ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Category</label>
                                <p class="fw-semibold">{{ $product->category->name ?? 'Uncategorized' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Status</label>
                                <p>
                                    <span class="badge {{ $product->status_badge['class'] }}">
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Price</label>
                                <p class="fw-semibold">{{ $product->price !== null ? '$' . number_format($product->price, 2) : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Stock Quantity</label>
                                <p class="fw-semibold">
                                    {{ $product->stock_quantity }}
                                    @if($product->stock_quantity < 10)
                                        <span class="badge bg-warning text-dark ms-1">Low Stock</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium">Created</label>
                                <p>{{ $product->created_at ? $product->created_at->format('M d, Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium">Description</label>
                                <p class="mb-0">{{ $product->description ?? 'No description available.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
