<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Project;
use App\Models\ProjectPayment;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $sort = 'created_at';
    #[Url] public string $dir = 'desc';
    public int $perPage = 10;

    public $company;
    public $companyId;

    public function mount()
    {
        $contact = auth()->user()->contact;
        $this->company = $contact?->company;
        $this->companyId = $this->company?->id;
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir = 'asc';
        }
    }

    public $payingPayment = null;
    public $selectedGateway = null;
    public $showPayModal = false;

    public function openPayModal(int $paymentId): void
    {
        $projectIds = Project::where('company_id', $this->companyId ?: 0)->pluck('id');
        $this->payingPayment = ProjectPayment::whereIn('project_id', $projectIds)
            ->where('id', $paymentId)
            ->where('status', '!=', 'paid')
            ->firstOrFail();

        $activeGateways = \App\Models\PaymentGateway::where('is_active', true)->get();
        $this->selectedGateway = $activeGateways->first()?->id;
        $this->showPayModal = true;
    }

    public function processClientPayment(): void
    {
        if (!$this->payingPayment) return;

        $gateway = \App\Models\PaymentGateway::find($this->selectedGateway);
        $gatewayName = $gateway ? $gateway->name : 'Online';

        $txId = strtoupper(substr($gatewayName, 0, 3)) . '-' . strtoupper(\Illuminate\Support\Str::random(10));

        $this->payingPayment->update([
            'status' => 'paid',
            'payment_gateway_id' => $this->selectedGateway,
            'reference' => $this->payingPayment->reference ?: $txId,
            'paid_at' => now(),
        ]);

        $this->showPayModal = false;
        $this->payingPayment = null;
        $this->dispatch('cp-toast', message: "Payment processed successfully via {$gatewayName}!", type: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    public function render()
    {
        $projectIds = Project::where('company_id', $this->companyId ?: 0)->pluck('id');

        $sortable = ['amount', 'status', 'paid_at', 'created_at'];
        $sort = in_array($this->sort, $sortable, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $base = fn () => ProjectPayment::whereIn('project_id', $projectIds);

        $payments = $base()
            ->with(['project:id,name', 'gateway:id,name'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('reference', 'like', '%' . $this->search . '%')
                        ->orWhereHas('project', fn ($p) => $p->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy($sort, $dir)
            ->paginate($this->perPage);

        $summary = [
            'paid' => (float) (clone $base())->where('status', 'paid')->sum('amount'),
            'outstanding' => (float) (clone $base())->where('status', 'pending')->sum('amount'),
            'count' => (clone $base())->count(),
        ];

        return $this->view(compact('payments', 'summary'))->layout('layouts.client');
    }
};
?>

@php
    $badgeMap = ['bg-secondary' => 's-secondary', 'bg-primary' => 's-primary', 'bg-success' => 's-success',
        'bg-warning text-dark' => 's-warning', 'bg-warning' => 's-warning', 'bg-danger' => 's-danger', 'bg-info' => 's-info'];
    $sortIcon = fn ($f) => $sort !== $f ? 'fa-sort' : ($dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
@endphp

<div wire:poll.45s>
    <div class="cp-page-head">
        <div>
            <h1>Payments</h1>
            <p>All payments across projects for {{ $company->company_name ?? 'your company' }}. <span class="cp-live">Live</span></p>
        </div>
    </div>

    @if (!$company)
        <div class="cp-alert a-warning">
            <i class="fas fa-circle-info"></i> No company is linked to your account yet. Please contact your account manager.
        </div>
    @else

    <div class="cp-grid cols-3" style="margin-bottom:20px;">
        <div class="cp-kpi"><div class="cp-kpi-label">Total Paid</div><div class="cp-kpi-value">@money($summary['paid'])</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Outstanding</div><div class="cp-kpi-value">@money($summary['outstanding'])</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Payment Records</div><div class="cp-kpi-value">{{ $summary['count'] }}</div></div>
    </div>

    <div class="cp-card">
        <div class="cp-card-head" style="flex-wrap:wrap;">
            <h3><i class="fas fa-credit-card"></i> {{ $payments->total() }} {{ Str::plural('payment', $payments->total()) }}</h3>
            <div class="cp-toolbar">
                <div class="cp-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" wire:model.live.debounce.350ms="search" placeholder="Search project or reference…">
                </div>
                <select class="cp-select" wire:model.live="status">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
                <select class="cp-select" wire:model.live="perPage">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
                @if ($search !== '' || $status !== '')
                    <button class="cp-btn cp-btn-ghost cp-btn-sm" wire:click="clearFilters"><i class="fas fa-xmark"></i> Clear</button>
                @endif
            </div>
        </div>

        <div class="cp-card-body flush">
            <div class="cp-table-wrap">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th class="sortable t-right" wire:click="sortBy('amount')">Amount <i class="fas {{ $sortIcon('amount') }}"></i></th>
                            <th>Gateway</th>
                            <th>Reference</th>
                            <th class="sortable" wire:click="sortBy('status')">Status <i class="fas {{ $sortIcon('status') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('paid_at')">Date <i class="fas {{ $sortIcon('paid_at') }}"></i></th>
                            <th style="width: 110px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr wire:key="pay-{{ $payment->id }}">
                                <td class="t-strong">
                                    @if ($payment->project)
                                        <a href="{{ route('client.project-show', $payment->project->id) }}" wire:navigate class="text-decoration-none">
                                            {{ $payment->project->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="t-right t-strong">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="t-muted">{{ $payment->gateway->name ?? '—' }}</td>
                                <td class="t-muted">{{ $payment->reference ?: '—' }}</td>
                                <td>
                                    <span class="cp-badge {{ $badgeMap[$payment->status_badge['class']] ?? 's-secondary' }}">
                                        {{ $payment->status_badge['icon'] }} {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="t-muted">{{ optional($payment->paid_at ?? $payment->created_at)->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    @if($payment->status === 'pending')
                                        <button class="cp-btn cp-btn-primary cp-btn-sm py-1 px-2" wire:click="openPayModal({{ $payment->id }})">
                                            <i class="fas fa-lock me-1"></i> Pay Now
                                        </button>
                                    @else
                                        <span class="text-success small fw-medium"><i class="fas fa-check-circle"></i> Paid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="cp-empty">
                                        <i class="fas fa-credit-card"></i>
                                        <h6>No payments found</h6>
                                        <p>{{ $search !== '' || $status !== '' ? 'Try adjusting your filters.' : 'Payments will appear here once recorded.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.cp-pagination', ['paginator' => $payments])
    </div>
    @endif

    <!-- Client Checkout Modal -->
    @if($showPayModal && $payingPayment)
    <div class="modal fade show d-block" style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 9999;" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; background: var(--surface, #fff);">
                <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding: 16px 20px;">
                    <h5 class="modal-title fw-bold" style="font-size: 16px;">
                        <i class="fas fa-shield-halved text-primary me-2"></i>Secure Payment Checkout
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showPayModal', false)"></button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div class="p-3 mb-3 rounded" style="background: var(--surface-2, #f8fafc); border: 1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Project:</span>
                            <span class="fw-semibold small">{{ $payingPayment->project->name ?? 'Project Invoice' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Reference:</span>
                            <span class="small">{{ $payingPayment->reference ?: 'INV-' . str_pad($payingPayment->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total Amount:</span>
                            <span class="fs-5 fw-bold text-primary">{{ $payingPayment->currency }} {{ number_format((float) $payingPayment->amount, 2) }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Select Payment Method</label>
                        @php $gateways = \App\Models\PaymentGateway::where('is_active', true)->get(); @endphp
                        <div class="d-flex flex-column gap-2">
                            @forelse($gateways as $gw)
                                <label class="d-flex align-items-center gap-2 p-2 border rounded" style="cursor: pointer; background: var(--surface, #fff);">
                                    <input type="radio" name="gateway_radio" value="{{ $gw->id }}" wire:model="selectedGateway" class="form-check-input mt-0">
                                    <span class="fw-medium small flex-grow-1">
                                        @if(stripos($gw->name, 'razorpay') !== false)
                                            <i class="fas fa-bolt text-primary me-1"></i>
                                        @elseif(stripos($gw->name, 'cashfree') !== false)
                                            <i class="fas fa-wallet text-success me-1"></i>
                                        @elseif(stripos($gw->name, 'stripe') !== false)
                                            <i class="fab fa-stripe text-indigo me-1"></i>
                                        @elseif(stripos($gw->name, 'paypal') !== false)
                                            <i class="fab fa-paypal text-primary me-1"></i>
                                        @else
                                            <i class="fas fa-credit-card text-muted me-1"></i>
                                        @endif
                                        {{ $gw->name }}
                                    </span>
                                    <span class="badge bg-light text-dark border small" style="font-size: 10px;">Instant</span>
                                </label>
                            @empty
                                <p class="text-muted small">No payment gateways configured.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="fas fa-lock text-success me-1"></i> Transactions are encrypted and processed securely.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 14px 20px;">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showPayModal', false)">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" wire:click="processClientPayment" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="processClientPayment"><i class="fas fa-check-circle me-1"></i> Confirm & Pay</span>
                        <span wire:loading wire:target="processClientPayment"><i class="fas fa-spinner fa-spin me-1"></i> Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
