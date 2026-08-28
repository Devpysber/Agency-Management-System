<?php

use Livewire\Component;
use App\Models\Estimate;

new class extends Component
{
    public Estimate $estimate;

    public function mount($id)
    {
        $this->estimate = Estimate::with(['items', 'company', 'contact', 'createdBy'])->findOrFail($id);
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
        <div class="page-header d-print-none">
            <div>
                <h1>Estimate {{ $estimate->estimate_number }}</h1>
                <p>
                    <span class="badge {{ $estimate->status_badge['class'] }}">
                        {{ $estimate->status_badge['icon'] }} {{ ucfirst($estimate->status) }}
                    </span>
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('estimates.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('estimates.edit', $estimate->id) }}" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button type="button" onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert-flash alert-flash-success d-print-none">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Printable Estimate Document -->
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="row mb-5">
                    <div class="col-6">
                        <h2 class="fw-bold mb-1">ESTIMATE</h2>
                        <p class="text-muted mb-0">{{ $estimate->estimate_number }}</p>
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-1">
                            <strong>Status:</strong>
                            <span class="badge {{ $estimate->status_badge['class'] }}">
                                {{ $estimate->status_badge['icon'] }} {{ ucfirst($estimate->status) }}
                            </span>
                        </p>
                        <p class="mb-1 text-muted">
                            <strong>Issue Date:</strong> {{ $estimate->issue_date ? $estimate->issue_date->format('M d, Y') : 'N/A' }}
                        </p>
                        <p class="mb-0 text-muted">
                            <strong>Valid Until:</strong> {{ $estimate->valid_until ? $estimate->valid_until->format('M d, Y') : 'N/A' }}
                        </p>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-6">
                        <h6 class="text-muted text-uppercase small mb-2">Billed To</h6>
                        @if ($estimate->company)
                            <p class="fw-semibold mb-0">{{ $estimate->company->company_name }}</p>
                            @if ($estimate->company->company_email)
                                <p class="mb-0 text-muted">{{ $estimate->company->company_email }}</p>
                            @endif
                            @if ($estimate->company->company_phone)
                                <p class="mb-0 text-muted">{{ $estimate->company->company_phone }}</p>
                            @endif
                        @elseif ($estimate->contact)
                            <p class="fw-semibold mb-0">{{ $estimate->contact->first_name }} {{ $estimate->contact->last_name }}</p>
                            @if ($estimate->contact->email)
                                <p class="mb-0 text-muted">{{ $estimate->contact->email }}</p>
                            @endif
                        @else
                            <p class="fw-semibold mb-0">{{ $estimate->client_name ?? 'N/A' }}</p>
                            @if ($estimate->client_email)
                                <p class="mb-0 text-muted">{{ $estimate->client_email }}</p>
                            @endif
                        @endif
                    </div>
                    <div class="col-6 text-end">
                        <h6 class="text-muted text-uppercase small mb-2">Prepared By</h6>
                        <p class="fw-semibold mb-0">{{ $estimate->createdBy->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end" style="width: 100px;">Qty</th>
                                <th class="text-end" style="width: 150px;">Unit Price</th>
                                <th class="text-end" style="width: 150px;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($estimate->items as $item)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td class="text-end">{{ $item->qty }}</td>
                                    <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">${{ number_format($item->qty * $item->unit_price, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No line items.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row justify-content-end mb-4">
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span>${{ number_format($estimate->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tax</span>
                            <span>${{ number_format($estimate->tax ?? 0, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="fw-bold fs-5">${{ number_format($estimate->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if ($estimate->notes)
                    <div class="border-top pt-3">
                        <h6 class="text-muted text-uppercase small mb-2">Notes</h6>
                        <p class="mb-0">{{ $estimate->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .sidebar, .header, .d-print-none {
            display: none !important;
        }
    }
</style>
