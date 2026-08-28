<?php

use Livewire\Component;
use App\Models\ProductCategory;

new class extends Component
{
    public $categoryId;
    public $name;
    public $status = 'active';

    public $categories;
    public $search = '';
    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'status' => 'nullable|string',
    ];

    public function mount()
    {
        $this->fetchCategories();
    }

    public function fetchCategories()
    {
        $query = ProductCategory::withCount('products');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->categories = $query->orderBy('name')->get();
    }

    public function updatedSearch()
    {
        $this->fetchCategories();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->categoryId = null;
        $this->name = null;
        $this->status = 'active';
        $this->resetErrorBag();
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('Products', $this->categoryId ? 'Edit' : 'Create')) {
            session()->flash('error', "You don't have permission to " . ($this->categoryId ? 'edit' : 'create') . ' categories.');
            return;
        }

        if ($this->categoryId) {
            $this->validate();
            $category = ProductCategory::find($this->categoryId);
        } else {
            $this->validate();
            $category = new ProductCategory;
        }

        $category->name = $this->name;
        $category->status = $this->status ?: 'active';
        $category->save();

        session()->flash('success', 'Product category saved successfully!');

        $this->showModal = false;
        $this->resetForm();
        $this->fetchCategories();
    }

    public function edit($id)
    {
        $category = ProductCategory::findOrFail($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->status = $category->status;
        $this->showModal = true;
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Products', 'Delete')) {
            session()->flash('error', "You don't have permission to delete product categories.");
            return;
        }

        try {
            ProductCategory::findOrFail($id)->delete();
            session()->flash('success', 'Product category deleted successfully!');
            $this->fetchCategories();
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting category: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return $this->view()->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Product Categories</h1>
                <p>Manage categories used to group your products.</p>
            </div>
            <div class="header-actions">
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Products', 'Create')))
                    <button class="btn btn-primary" wire:click="openAddModal">
                        <i class="fas fa-plus"></i> Add Category
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

        <!-- Categories Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-semibold mb-0">
                        <i class="fas fa-tags text-primary me-2"></i>
                        Categories List
                    </h5>
                    <span class="badge bg-primary">{{ $categories->count() }} Categories</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Search -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control"
                               wire:model.live="search"
                               placeholder="Search categories...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $category->name }}</h6>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $category->products_count }}</span>
                                </td>
                                <td>
                                    @if($category->status === 'inactive')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Inactive
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Products', 'Edit')))
                                            <button class="btn btn-outline-secondary" wire:click="edit({{ $category->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $category->id }})"
                                                    wire:confirm="Are you sure you want to delete this category?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="fas fa-tags fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No categories found</h5>
                                    <p class="text-muted">Add a new category to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Category Modal -->
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tags text-primary me-2"></i>
                        {{ $categoryId ? 'Edit Category' : 'Add Category' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-tag me-1 text-muted"></i>
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   wire:model="name" placeholder="Enter category name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-circle me-1 text-muted"></i>
                                Status
                            </label>
                            <select class="form-select" wire:model="status">
                                <option value="active">🟢 Active</option>
                                <option value="inactive">🔴 Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas {{ $categoryId ? 'fa-save' : 'fa-plus' }}"></i>
                        {{ $categoryId ? 'Update' : 'Add' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
