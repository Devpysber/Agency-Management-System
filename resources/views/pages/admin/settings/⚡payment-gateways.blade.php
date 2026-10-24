<?php

use Livewire\Component;
use App\Models\PaymentGateway;

new class extends Component
{
    public $gatewayId;
    public $name;
    public $is_active = true;
    public $key_id;
    public $key_secret;
    public $mode = 'sandbox';
    public $webhook_secret;

    public $gateways;
    public $search = '';
    public $showModal = false;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'key_id' => 'nullable|string|max:255',
            'key_secret' => 'nullable|string|max:255',
            'mode' => 'required|in:sandbox,live',
            'webhook_secret' => 'nullable|string|max:255',
        ];
    }

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
        $this->key_id = null;
        $this->key_secret = null;
        $this->mode = 'sandbox';
        $this->webhook_secret = null;
        $this->resetErrorBag();
    }

    public function addPresetGateway(string $name)
    {
        if (!auth()->user()->hasPermission('Settings', 'Create')) {
            session()->flash('error', "You don't have permission to create payment gateways.");
            return;
        }

        $exists = PaymentGateway::where('name', $name)->exists();
        if ($exists) {
            session()->flash('warning', "{$name} is already added in your gateways list.");
            return;
        }

        PaymentGateway::create([
            'name' => $name,
            'is_active' => true,
        ]);

        session()->flash('success', "{$name} payment gateway added successfully!");
        $this->fetchGateways();
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
        $gateway->key_id = $this->key_id ?: null;
        $gateway->key_secret = $this->key_secret ?: null;
        $gateway->mode = $this->mode ?: 'sandbox';
        $gateway->webhook_secret = $this->webhook_secret ?: null;
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
        $this->key_id = $gateway->key_id;
        $this->key_secret = $gateway->key_secret;
        $this->mode = $gateway->mode ?: 'sandbox';
        $this->webhook_secret = $gateway->webhook_secret;
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
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="fw-semibold mb-0">
                        <i class="fas fa-credit-card text-primary me-2"></i>
                        Gateways List
                    </h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted small me-1">Quick Add:</span>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" wire:click="addPresetGateway('Razorpay')">
                            <i class="fas fa-bolt me-1"></i> + Razorpay
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" wire:click="addPresetGateway('Cashfree')">
                            <i class="fas fa-bolt me-1"></i> + Cashfree
                        </button>
                        <span class="badge bg-primary ms-2">{{ $gateways->count() }} Gateways</span>
                    </div>
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
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-semibold">{{ $gateway->name }}</h6>
                                        @if($gateway->isConfigured())
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small" title="API Keys Active">
                                                <i class="fas fa-key me-1"></i> {{ ucfirst($gateway->mode) }}
                                            </span>
                                        @elseif(in_array(strtolower($gateway->name), ['cashfree', 'razorpay', 'stripe', 'paypal']))
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle small" title="Missing API Credentials">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Keys Missing
                                            </span>
                                        @endif
                                    </div>
                                </td>
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
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="gatewayActive">
                            <label class="form-check-label fw-semibold" for="gatewayActive">Gateway Active</label>
                        </div>

                        @php
                            $isApiGateway = in_array(strtolower($name ?? ''), ['cashfree', 'razorpay', 'stripe', 'paypal']);
                        @endphp

                        @if($isApiGateway)
                            <div class="p-3 mb-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-key text-primary me-2"></i> API Credentials</h6>
                                    <span class="badge bg-secondary">{{ strtoupper($name) }}</span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Environment Mode</label>
                                    <select class="form-select form-select-sm" wire:model="mode">
                                        <option value="sandbox">Sandbox / Test Mode</option>
                                        <option value="live">Live / Production Mode</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">
                                        @if(strtolower($name) === 'razorpay') Key ID
                                        @elseif(strtolower($name) === 'cashfree') App ID (Client ID)
                                        @elseif(strtolower($name) === 'stripe') Publishable Key
                                        @else Client ID @endif
                                    </label>
                                    <input type="text" class="form-control form-control-sm font-monospace" wire:model="key_id" placeholder="Enter Key ID / App ID">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">
                                        @if(strtolower($name) === 'razorpay') Key Secret
                                        @elseif(strtolower($name) === 'cashfree') Secret Key
                                        @elseif(strtolower($name) === 'stripe') Secret Key
                                        @else Client Secret @endif
                                    </label>
                                    <input type="password" class="form-control form-control-sm font-monospace" wire:model="key_secret" placeholder="Enter Secret Key">
                                </div>
                                <div class="mb-1">
                                    <label class="form-label small fw-semibold">Webhook Secret / Signing Key <small class="text-muted">(Optional)</small></label>
                                    <input type="text" class="form-control form-control-sm font-monospace" wire:model="webhook_secret" placeholder="Optional webhook secret">
                                </div>
                            </div>
                        @endif
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
