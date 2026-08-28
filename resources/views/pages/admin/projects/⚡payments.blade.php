<?php

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\PaymentGateway;

new class extends Component
{
    public $payments;
    public $projectFilter = '';
    public $statusFilter = '';
    public $gatewayFilter = '';

    // Add payment modal fields
    public $new_project_id;
    public $payment_amount;
    public $payment_currency = 'USD';
    public $payment_gateway_id;
    public $payment_status = 'pending';
    public $payment_reference;

    protected $rules = [
        'new_project_id' => 'required|exists:projects,id',
        'payment_amount' => 'required|numeric|min:0',
        'payment_currency' => 'required|string|max:10',
        'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
        'payment_status' => 'required|in:pending,paid,failed,refunded',
        'payment_reference' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->fetchPayments();
    }

    public function fetchPayments()
    {
        $query = ProjectPayment::query()->with(['project', 'gateway']);

        if ($this->projectFilter !== '') {
            $query->where('project_id', $this->projectFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->gatewayFilter !== '') {
            $query->where('payment_gateway_id', $this->gatewayFilter);
        }

        $this->payments = $query->orderByDesc('created_at')->get();
    }

    public function updatedProjectFilter()
    {
        $this->fetchPayments();
    }

    public function updatedStatusFilter()
    {
        $this->fetchPayments();
    }

    public function updatedGatewayFilter()
    {
        $this->fetchPayments();
    }

    public function resetFilters()
    {
        $this->projectFilter = '';
        $this->statusFilter = '';
        $this->gatewayFilter = '';
        $this->fetchPayments();
    }

    public function addPayment()
    {
        if (!auth()->user()->hasPermission('Projects', 'Edit')) {
            session()->flash('error', "You don't have permission to add payments.");
            return;
        }

        $this->validate();

        ProjectPayment::create([
            'project_id' => $this->new_project_id,
            'amount' => $this->payment_amount,
            'currency' => $this->payment_currency ?: 'USD',
            'payment_gateway_id' => $this->payment_gateway_id ?: null,
            'status' => $this->payment_status ?: 'pending',
            'reference' => $this->payment_reference,
            'paid_at' => $this->payment_status === 'paid' ? now() : null,
        ]);

        session()->flash('success', 'Payment added successfully');
        $this->reset(['new_project_id', 'payment_amount', 'payment_gateway_id', 'payment_reference']);
        $this->payment_currency = 'USD';
        $this->payment_status = 'pending';
        $this->fetchPayments();
    }

    public function render()
    {
        return $this->view([
            'projects' => Project::orderBy('name')->get(),
            'gateways' => PaymentGateway::orderBy('name')->get(),
            'totalAmount' => $this->payments->sum('amount'),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Project Payments</h1>
                <p>Cross-project payment ledger and tracking.</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="fas fa-plus"></i> Add Payment
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

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">
                            <i class="fas fa-diagram-project me-1 text-muted"></i>
                            Project
                        </label>
                        <select class="form-select" wire:model.live="projectFilter">
                            <option value="">All Projects</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model.live="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-credit-card me-1 text-muted"></i>
                            Gateway
                        </label>
                        <select class="form-select" wire:model.live="gatewayFilter">
                            <option value="">All Gateways</option>
                            @foreach ($gateways as $gateway)
                                <option value="{{ $gateway->id }}">{{ $gateway->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters" title="Reset Filters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Payments</h3>
                        <p class="stat-number">{{ $payments->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Amount</h3>
                        <p class="stat-number">${{ number_format($totalAmount, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Paid</h3>
                        <p class="stat-number">{{ $payments->where('status', 'paid')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-credit-card me-2"></i>
                    Payment Ledger
                </h3>
                <span class="badge bg-primary">{{ $payments->count() }} Payments</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project</th>
                                <th>Amount</th>
                                <th>Gateway</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>Paid At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                            <tr>
                                <td>
                                    @if($payment->project)
                                        <a href="{{ route('projects.show', $payment->project->id) }}" class="text-decoration-none fw-semibold">
                                            {{ $payment->project->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->gateway->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $payment->status_badge['class'] }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td>{{ $payment->reference ?? 'N/A' }}</td>
                                <td>{{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-credit-card fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No payments found</h5>
                                    <p class="text-muted">Try adjusting your filters or add a new payment.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium">Project <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="new_project_id">
                                <option value="">Select Project</option>
                                @foreach ($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @error('new_project_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="payment_amount" placeholder="0.00">
                            @error('payment_amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Currency</label>
                            <input type="text" class="form-control" wire:model="payment_currency" placeholder="USD">
                            @error('payment_currency')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Payment Gateway</label>
                            <select class="form-select" wire:model="payment_gateway_id">
                                <option value="">Select Gateway</option>
                                @foreach ($gateways as $gateway)
                                    <option value="{{ $gateway->id }}">{{ $gateway->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_gateway_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Status</label>
                            <select class="form-select" wire:model="payment_status">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                            @error('payment_status')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Reference</label>
                            <input type="text" class="form-control" wire:model="payment_reference" placeholder="Transaction reference">
                            @error('payment_reference')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="addPayment" data-bs-dismiss="modal">
                        <i class="fas fa-save"></i> Save Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
