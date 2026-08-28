<?php

use Livewire\Component;
use App\Models\Quotation;

new class extends Component
{
    public $quotation;

    public function mount($id)
    {
        $contact = auth()->user()->contact;
        $companyId = $contact?->company_id;

        $quotation = $companyId
            ? Quotation::where('company_id', $companyId)->find($id)
            : null;

        if (!$quotation) {
            abort(404);
        }

        $this->quotation = $quotation;
    }

    protected function guardedQuotation()
    {
        $contact = auth()->user()->contact;
        $companyId = $contact?->company_id;

        if (!$companyId) {
            session()->flash('error', 'No company linked to your account.');
            return null;
        }

        $quotation = Quotation::where('company_id', $companyId)->find($this->quotation->id);

        if (!$quotation || $quotation->status !== 'quoted') {
            session()->flash('error', 'This quotation can no longer be updated.');
            return null;
        }

        return $quotation;
    }

    public function accept()
    {
        $quotation = $this->guardedQuotation();
        if (!$quotation) {
            return;
        }

        $quotation->update(['status' => 'accepted', 'responded_at' => now()]);
        $this->quotation = $quotation->fresh();
        session()->flash('success', 'Quotation accepted. Thank you!');
        $this->dispatch('cp-toast', message: 'Quotation accepted', type: 'success');
    }

    public function reject()
    {
        $quotation = $this->guardedQuotation();
        if (!$quotation) {
            return;
        }

        $quotation->update(['status' => 'rejected', 'responded_at' => now()]);
        $this->quotation = $quotation->fresh();
        session()->flash('success', 'Quotation rejected.');
        $this->dispatch('cp-toast', message: 'Quotation rejected', type: 'info');
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

    $order = ['pending' => 1, 'reviewed' => 2, 'quoted' => 3, 'accepted' => 4, 'rejected' => 4];
    $rank = $order[$quotation->status] ?? 1;
    $rejected = $quotation->status === 'rejected';
    $steps = [
        ['label' => 'Submitted', 'done' => true],
        ['label' => 'Reviewed', 'done' => $rank >= 2],
        ['label' => 'Quoted', 'done' => $rank >= 3],
        ['label' => $rejected ? 'Rejected' : 'Accepted', 'done' => $rank >= 4],
    ];
@endphp

<div wire:poll.60s>
    <div class="cp-detail-head">
        <div>
            <a href="{{ route('client.quotations') }}" wire:navigate class="cp-back"><i class="fas fa-arrow-left"></i> Quotations</a>
            <h1>{{ $quotation->service_interest ?: 'Quotation' }}</h1>
            <div style="margin-top:8px;">
                <span class="cp-badge {{ $toBadge($quotation->status_badge['class']) }}">
                    {{ $quotation->status_badge['icon'] }} {{ ucfirst($quotation->status) }}
                </span>
            </div>
        </div>
        <div class="cp-detail-actions">
            <a href="{{ route('client.quotation-pdf', $quotation->id) }}" target="_blank" class="cp-btn cp-btn-ghost">
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

    @if ($quotation->status === 'quoted')
        <div class="cp-callout">
            <div>
                <h4><i class="fas fa-hourglass-half"></i> This quotation is awaiting your response</h4>
                <p>Review the quoted amount below, then accept or reject.</p>
            </div>
            <div style="display:flex;gap:10px;">
                <button wire:click="accept" wire:confirm="Accept this quotation?" class="cp-btn cp-btn-primary">
                    <i class="fas fa-check"></i> Accept
                </button>
                <button wire:click="reject" wire:confirm="Reject this quotation?" class="cp-btn cp-btn-ghost">
                    <i class="fas fa-xmark"></i> Reject
                </button>
            </div>
        </div>
    @endif

    <div class="cp-grid split-7-5">
        <div class="cp-card">
            <div class="cp-card-head">
                <h3><i class="fas fa-circle-info"></i> Quotation Details</h3>
                <span class="cp-badge s-primary" style="font-size:13px;">
                    {{ $quotation->quoted_amount !== null ? \App\Support\Money::client((float) $quotation->quoted_amount) : 'Not yet quoted' }}
                </span>
            </div>
            <div class="cp-card-body">
                <dl class="cp-dl">
                    <div><dt>Service Interest</dt><dd>{{ $quotation->service_interest ?: '—' }}</dd></div>
                    <div><dt>Quoted Amount</dt><dd>{{ $quotation->quoted_amount !== null ? \App\Support\Money::client((float) $quotation->quoted_amount) : 'Not yet quoted' }}</dd></div>
                    <div><dt>Contact Name</dt><dd>{{ $quotation->name ?: '—' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $quotation->email ?: '—' }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $quotation->phone ?: '—' }}</dd></div>
                    <div><dt>Submitted</dt><dd>{{ optional($quotation->created_at)->format('M d, Y H:i') ?? '—' }}</dd></div>
                    <div><dt>Responded</dt><dd>{{ optional($quotation->responded_at)->format('M d, Y H:i') ?? 'Not yet responded' }}</dd></div>
                    <div class="span-2"><dt>Message</dt><dd>{{ $quotation->message ?: 'No message provided.' }}</dd></div>
                </dl>
            </div>
        </div>

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
