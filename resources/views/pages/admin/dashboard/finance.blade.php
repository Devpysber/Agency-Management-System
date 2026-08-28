<!-- Finance Dashboard -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-sack-dollar"></i></div>
        <div class="stat-info"><h3>Total Paid</h3><p class="stat-number">${{ number_format($totalPaid, 2) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="stat-info"><h3>Outstanding</h3><p class="stat-number">${{ number_format($outstanding, 2) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info"><h3>Overdue</h3><p class="stat-number">${{ number_format($overdueTotal, 2) }}</p>
            <span class="stat-change">{{ $overdueCount }} invoice(s)</span></div>
    </div>
</div>

@if ($overduePayments->isNotEmpty())
<div class="card mb-4" style="border:1px solid #fecaca;">
    <div class="card-header" style="background:#fef2f2;">
        <h3 class="card-title" style="color:#991b1b;"><i class="fas fa-triangle-exclamation me-2"></i> Overdue Payments</h3>
    </div>
    <div class="card-body p-0">
        @foreach ($overduePayments as $p)
            <div class="d-flex justify-content-between align-items-center px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <span>{{ $p->project->company->company_name ?? $p->project->name ?? '—' }}</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold text-danger">${{ number_format($p->amount, 2) }}</span>
                    @if ($canRecord)
                        <button class="btn btn-sm btn-success" wire:click="recordPayment({{ $p->id }})"
                                wire:confirm="Mark this ${{ number_format($p->amount, 2) }} payment as paid?">
                            <i class="fas fa-check"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="row g-4 mb-4">
    <!-- Client Financial Accounts -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-building me-2"></i> Outstanding by Client</h3></div>
            <div class="card-body p-0">
                @forelse ($byCompany as $company => $amount)
                    <div class="d-flex justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $company }}</span>
                        <span class="fw-semibold">${{ number_format($amount, 2) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No outstanding balances.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-clock-rotate-left me-2"></i> Recent Payments</h3></div>
            <div class="card-body p-0">
                @forelse ($recentPayments as $p)
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $p->project->company->company_name ?? $p->project->name ?? '—' }}</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-success fw-semibold">${{ number_format($p->amount, 2) }}</span>
                            @if ($canApprove)
                                <button class="btn btn-sm btn-outline-warning" wire:click="approveRefund({{ $p->id }})"
                                        wire:confirm="Approve a refund of ${{ number_format($p->amount, 2) }}?">
                                    <i class="fas fa-rotate-left"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No payments recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Pending / Reconciliation -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar me-2"></i> Pending Reconciliation</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Client</th><th>Project</th><th>Amount</th><th>Status</th><th style="width:110px;"></th></tr></thead>
                <tbody>
                    @forelse ($pendingPayments as $p)
                        <tr>
                            <td>{{ $p->project->company->company_name ?? '—' }}</td>
                            <td>{{ $p->project->name ?? '—' }}</td>
                            <td>${{ number_format($p->amount, 2) }}</td>
                            <td><span class="badge bg-warning text-dark">{{ ucfirst($p->status) }}</span></td>
                            <td>
                                @if ($canRecord)
                                    <button class="btn btn-sm btn-success" wire:click="recordPayment({{ $p->id }})"
                                            wire:confirm="Mark this ${{ number_format($p->amount, 2) }} payment as paid?">
                                        <i class="fas fa-check"></i> Record
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nothing pending.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4" style="border:1px solid #e5e7eb;">
    <div class="card-body">
        <p class="text-muted small mb-0">
            <i class="fas fa-circle-info me-1"></i> `ProjectPayment` is the invoice/payment record in this
            schema — there's no separate Invoice or Expense entity. "Reconciliation" here means matching a
            payment's status (pending/partial/paid/overdue) — there's no bank-statement-import/matching
            workflow. Flagged, not fabricated.
        </p>
    </div>
</div>
