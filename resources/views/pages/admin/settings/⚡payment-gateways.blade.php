<?php

use Livewire\Component;
use App\Models\PaymentGateway;

new class extends Component
{
    public $gatewayId;
    public $name;
    public $is_active = true;

    public $gateways;
    public $search = '';
    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->fetchGateways();
    }

    public function fetchGateways()
    {
        $query = PaymentGateway::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->gateways = $query->orderBy('name')->get();
    }

    public function updatedSearch()
    {
        $this->fetchGateways();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->gatewayId = null;
        $this->name = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('Settings', $this->gatewayId ? 'Edit' : 'Create')) {
            session()->flash('error', "You don't have permission to " . ($this->gatewayId ? 'edit' : 'create') . ' payment gateways.');
            return;
        }

        $this->validate();

        $gateway = $this->gatewayId ? PaymentGateway::find($this->gatewayId) : new PaymentGateway;
        $gateway->name = $this->name;
        $gateway->is_active = (bool) $this->is_active;
        $gateway->save();

        session()->flash('success', 'Payment gateway saved successfully!');

        $this->showModal = false;
        $this->resetForm();
        $this->fetchGateways();
    }

    public function edit($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $this->gatewayId = $gateway->id;
        $this->name = $gateway->name;
        $this->is_active = $gateway->is_active;
        $this->showModal = true;
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Settings', 'Delete')) {
            session()->flash('error', "You don't have permission to delete payment gateways.");
            return;
        }

        try {
            PaymentGateway::findOrFail($id)->delete();
            session()->flash('success', 'Payment gateway deleted successfully!');
            $this->fetchGateways();
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting payment gateway: ' . $e->getMessage());
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
                <h1>Payment Gateways</h1>
                <p>Manage the payment gateways available when logging a project payment.</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->hasPermission('Settings', 'Create'))
                <button class="btn btn-primary" wire:click="openAddModal">
                    <i class="fas fa-plus"></i> Add Gateway
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

        <!-- Gateways Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-semibold mb-0">
                        <i class="fas fa-credit-card text-primary me-2"></i>
                        Gateways List
                    </h5>
                    <span class="badge bg-primary">{{ $gateways->count() }} Gateways</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control"
                               wire:model.live="search"
                               placeholder="Search gateways...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($gateways as $gateway)
                            <tr>
                                <td><h6 class="mb-0 fw-semibold">{{ $gateway->name }}</h6></td>
                                <td>
                                    @if($gateway->is_active)
                                        <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Active</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" wire:click="edit({{ $gateway->id }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger"
                                                wire:click="delete({{ $gateway->id }})"
                                                wire:confirm="Delete this payment gateway? Existing payments logged against it will keep their record but lose the gateway link.">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-credit-card fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No payment gateways found</h5>
                                    <p class="text-muted">Add a payment gateway to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Gateway Modal -->
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-credit-card text-primary me-2"></i>
                        {{ $gatewayId ? 'Edit Payment Gateway' : 'Add Payment Gateway' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   wire:model="name" placeholder="e.g. Stripe, PayPal, Bank Transfer">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="gatewayActive">
                            <label class="form-check-label" for="gatewayActive">Active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas {{ $gatewayId ? 'fa-save' : 'fa-plus' }}"></i>
                        {{ $gatewayId ? 'Update' : 'Add' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
