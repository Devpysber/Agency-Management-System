<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $product;
    public $name;
    public $sku;
    public $product_category_id;
    public $description;
    public $price;
    public $stock_quantity = 0;
    public $status = 'active';
    public $photo;

    protected $rules = [];

    public function mount($id)
    {
        $this->product = Product::findOrFail($id);
        $this->name = $this->product->name;
        $this->sku = $this->product->sku;
        $this->product_category_id = $this->product->product_category_id;
        $this->description = $this->product->description;
        $this->price = $this->product->price;
        $this->stock_quantity = $this->product->stock_quantity;
        $this->status = $this->product->status;

        $this->rules = [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $this->product->id,
            'product_category_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
        ];
    }

    public function update()
    {
        $this->validate();

        $this->product->name = $this->name;
        $this->product->sku = $this->sku ?: null;
        $this->product->product_category_id = $this->product_category_id ?: null;
        $this->product->description = $this->description;
        $this->product->price = $this->price !== '' ? $this->price : null;
        $this->product->stock_quantity = $this->stock_quantity !== '' ? $this->stock_quantity : 0;
        $this->product->status = $this->status ?: 'active';

        if ($this->photo) {
            if ($this->product->image) {
                Storage::disk('public')->delete($this->product->image);
            }
            $this->product->image = $this->photo->store('products', 'public');
        }

        $this->product->save();

        session()->flash('success', 'Product updated successfully');

        return redirect()->route('products.show', $this->product->id);
    }

    public function render()
    {
        return $this->view([
            'categories' => ProductCategory::where('status', 'active')->orderBy('name')->get(),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Edit Product</h1>
                <p>Update product details.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('products.show', $product->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Product
                </a>
                <button type="button" form="productForm" wire:click="update" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Update Product
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

        <!-- Product Form -->
        <div class="card">
            <div class="card-body">
                <form id="productForm">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-box me-1 text-muted"></i>
                                        Product Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Enter product name">
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-barcode me-1 text-muted"></i>
                                        SKU
                                    </label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror" wire:model="sku" placeholder="Enter SKU">
                                    @error('sku')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        <i class="fas fa-tags me-1 text-muted"></i>
                                        Category
                                    </label>
                                    <select class="form-select" wire:model="product_category_id">
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('product_category_id')
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
                                        <i class="fas fa-cubes me-1 text-muted"></i>
                                        Stock Quantity
                                    </label>
                                    <input type="number" step="1" min="0" class="form-control @error('stock_quantity') is-invalid @enderror" wire:model="stock_quantity" placeholder="0">
                                    @error('stock_quantity')
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
                                    <textarea class="form-control" wire:model="description" rows="4" placeholder="Enter product description..."></textarea>
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
                                        Product Image
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if ($product->image && !$photo)
                                        <img src="{{ asset('storage/' . $product->image) }}" class="img-fluid rounded mb-3" alt="{{ $product->name }}">
                                    @endif
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
