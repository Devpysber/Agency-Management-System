<?php

use Livewire\Component;
use App\Models\Estimate;

new class extends Component
{
    public $estimate;

    public function mount($id)
    {
        $contact = auth()->user()->contact;
        $companyId = $contact?->company_id;

        $estimate = $companyId
            ? Estimate::with(['items'])->where('company_id', $companyId)->find($id)
            : null;

        if (!$estimate) {
            abort(404);
        }

        $this->estimate = $estimate;
    }

    protected function guardedEstimate()
    {
        $contact = auth()->user()->contact;
        $companyId = $contact?->company_id;

        if (!$companyId) {
            session()->flash('error', 'No company linked to your account.');
            return null;
        }

        $estimate = Estimate::where('company_id', $companyId)->find($this->estimate->id);

        if (!$estimate || $estimate->status !== 'sent') {
            session()->flash('error', 'This estimate can no longer be updated.');
            return null;
        }

        return $estimate;
    }

    public function approve()
    {
        $estimate = $this->guardedEstimate();
        if (!$estimate) {
            return;
        }

        $estimate->update(['status' => 'approved']);
        $this->estimate = $estimate->fresh(['items']);
        session()->flash('success', 'Estimate approved. Thank you!');
        $this->dispatch('cp-toast', message: 'Estimate approved', type: 'success');
    }

    public function reject()
    {
        $estimate = $this->guardedEstimate();
        if (!$estimate) {
            return;
        }

        $estimate->update(['status' => 'rejected']);
        $this->estimate = $estimate->fresh(['items']);
        session()->flash('success', 'Estimate rejected.');
        $this->dispatch('cp-toast', message: 'Estimate rejected', type: 'info');
    }

    public function render()
    {
        return $this->view()->layout('layouts.client');
    }
};
?>

@php
    $badgeMap = ['bg-secondary' => 's-secondary', 'bg-primary' => 's-primary', 'bg-success' => 's-success',
        'bg-warning text-dark' => 's-warning', 'bg-warning' => 's-warning', 'bg-danger' => 's-danger', 'bg-info' => 's-info'];
    $toBadge = fn ($c) => $badgeMap[$c] ?? 's-secondary';

    $status = $estimate->status;
    $rejected = $status === 'rejected';
    $steps = [
        ['key' => 'draft', 'label' => 'Drafted', 'done' => true],
        ['key' => 'sent', 'label' => 'Sent to you', 'done' => in_array($status, ['sent', 'approved', 'rejected'], true)],
        ['key' => 'final', 'label' => $rejected ? 'Rejected' : 'Approved', 'done' => in_array($status, ['approved', 'rejected'], true)],
    ];
@endphp

<div wire:poll.60s>
    <div class="cp-detail-head">
        <div>
            <a href="{{ route('client.estimates') }}" wire:navigate class="cp-back"><i class="fas fa-arrow-left"></i> Estimates</a>
            <h1>{{ $estimate->estimate_number }}</h1>
            <div style="margin-top:8px;">
                <span class="cp-badge {{ $toBadge($estimate->status_badge['class']) }}">
                    {{ $estimate->status_badge['icon'] }} {{ ucfirst($estimate->status) }}
                </span>
            </div>
        </div>
        <div class="cp-detail-actions">
            <a href="{{ route('client.estimate-pdf', $estimate->id) }}" target="_blank" class="cp-btn cp-btn-ghost">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="cp-alert a-success">
            <i class="fas fa-circle-check"></i> {{ session('success') }}
            <button onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
        </div>
    @endif
    @if (session('error'))
        <div class="cp-alert a-error">
            <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
            <button onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
        </div>
    @endif

    @if ($estimate->status === 'sent')
        <div class="cp-callout">
            <div>
                <h4><i class="fas fa-hourglass-half"></i> This estimate is awaiting your response</h4>
                <p>Review the line items below, then approve or reject.</p>
            </div>
            <div style="display:flex;gap:10px;">
                <button wire:click="approve" wire:confirm="Approve this estimate?" class="cp-btn cp-btn-primary">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button wire:click="reject" wire:confirm="Reject this estimate?" class="cp-btn cp-btn-ghost">
                    <i class="fas fa-xmark"></i> Reject
                </button>
            </div>
        </div>
    @endif

    <div class="cp-grid split-7-5">
        {{-- Document --}}
        <div class="cp-card">
            <div class="cp-doc">
                <div class="cp-doc-top">
                    <div>
                        <h2>ESTIMATE</h2>
                        <div class="muted">{{ $estimate->estimate_number }}</div>
                    </div>
                    <div class="cp-doc-meta">
                        <div><strong>Issue date:</strong> {{ optional($estimate->issue_date)->format('M d, Y') ?? '—' }}</div>
                        <div><strong>Valid until:</strong> {{ optional($estimate->valid_until)->format('M d, Y') ?? '—' }}</div>
                        <div><strong>Billed to:</strong> {{ $estimate->client_display_name }}</div>
                    </div>
                </div>

                <table class="cp-doc-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="num" style="width:70px;">Qty</th>
                            <th class="num" style="width:110px;">Unit Price</th>
                            <th class="num" style="width:110px;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($estimate->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="num">{{ $item->qty }}</td>
                                <td class="num">@money((float) $item->unit_price)</td>
                                <td class="num">@money((float) $item->qty * (float) $item->unit_price)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="t-muted">No line items.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="cp-doc-totals">
                    <div class="row"><span class="t-muted">Subtotal</span><span>@money((float) $estimate->subtotal)</span></div>
                    <div class="row"><span class="t-muted">Tax</span><span>@money((float) ($estimate->tax ?? 0))</span></div>
                    <div class="row grand"><span>Total</span><span>@money((float) $estimate->total)</span></div>
                </div>

                @if ($estimate->notes)
                    <div class="cp-doc-notes">
                        <h6>Notes</h6>
                        <p>{{ $estimate->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <div>
            <div class="cp-card">
                <div class="cp-card-head"><h3><i class="fas fa-timeline"></i> Status</h3></div>
                <div class="cp-card-body">
                    <div class="cp-timeline">
                        @foreach ($steps as $step)
                            <div class="cp-tl-item">
                                <span class="cp-tl-dot {{ $step['done'] ? 'done' : '' }}">
                                    <i class="fas {{ $step['done'] ? 'fa-check' : 'fa-circle' }}"></i>
                                </span>
                                <div class="cp-tl-title">{{ $step['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
