<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Quotation;

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

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    public function render()
    {
        $sortable = ['service_interest', 'quoted_amount', 'status', 'created_at', 'responded_at'];
        $sort = in_array($this->sort, $sortable, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $base = fn () => Quotation::where('company_id', $this->companyId ?: 0);

        $quotations = $base()
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy($sort, $dir)
            ->paginate($this->perPage);

        $summary = [
            'awaiting' => $base()->where('status', 'quoted')->count(),
            'accepted' => $base()->where('status', 'accepted')->count(),
            'value' => (float) $base()->where('status', 'quoted')->sum('quoted_amount'),
        ];

        return $this->view(compact('quotations', 'summary'))->layout('layouts.client');
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
            <h1>Quotations</h1>
            <p>All quotations for {{ $company->company_name ?? 'your company' }}. <span class="cp-live">Live</span></p>
        </div>
    </div>

    @if (!$company)
        <div class="cp-alert a-warning">
            <i class="fas fa-circle-info"></i> No company is linked to your account yet. Please contact your account manager.
        </div>
    @else

    <div class="cp-grid cols-3" style="margin-bottom:20px;">
        <div class="cp-kpi"><div class="cp-kpi-label">Awaiting Response</div><div class="cp-kpi-value">{{ $summary['awaiting'] }}</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Accepted</div><div class="cp-kpi-value">{{ $summary['accepted'] }}</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Quoted Value</div><div class="cp-kpi-value">@money($summary['value'])</div></div>
    </div>

    <div class="cp-card">
        <div class="cp-card-head" style="flex-wrap:wrap;">
            <h3><i class="fas fa-file-signature"></i> {{ $quotations->total() }} {{ Str::plural('quotation', $quotations->total()) }}</h3>
            <div class="cp-toolbar">
                <div class="cp-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" wire:model.live.debounce.350ms="search" placeholder="Search service or email…">
                </div>
                <select class="cp-select" wire:model.live="status">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="quoted">Quoted</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
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
                            <th class="sortable" wire:click="sortBy('service_interest')">Service Interest <i class="fas {{ $sortIcon('service_interest') }}"></i></th>
                            <th class="sortable t-right" wire:click="sortBy('quoted_amount')">Quoted Amount <i class="fas {{ $sortIcon('quoted_amount') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('status')">Status <i class="fas {{ $sortIcon('status') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('created_at')">Submitted <i class="fas {{ $sortIcon('created_at') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('responded_at')">Responded <i class="fas {{ $sortIcon('responded_at') }}"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr class="clickable" wire:key="quo-{{ $quotation->id }}"
                                onclick="Livewire.navigate('{{ route('client.quotation-show', $quotation->id) }}')">
                                <td class="t-strong">{{ $quotation->service_interest ?: '—' }}</td>
                                <td class="t-right">{{ $quotation->quoted_amount !== null ? \App\Support\Money::client((float) $quotation->quoted_amount) : '—' }}</td>
                                <td>
                                    <span class="cp-badge {{ $badgeMap[$quotation->status_badge['class']] ?? 's-secondary' }}">
                                        {{ $quotation->status_badge['icon'] }} {{ ucfirst($quotation->status) }}
                                    </span>
                                </td>
                                <td class="t-muted">{{ optional($quotation->created_at)->format('M d, Y') ?? '—' }}</td>
                                <td class="t-muted">{{ optional($quotation->responded_at)->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="cp-empty">
                                        <i class="fas fa-file-signature"></i>
                                        <h6>No quotations found</h6>
                                        <p>{{ $search !== '' || $status !== '' ? 'Try adjusting your filters.' : 'Quotations will appear here once submitted.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.cp-pagination', ['paginator' => $quotations])
        </div>
    </div>
    @endif
</div>
