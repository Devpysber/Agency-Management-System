<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Estimate;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $sort = 'issue_date';
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
        $sortable = ['estimate_number', 'total', 'status', 'issue_date', 'valid_until', 'created_at'];
        $sort = in_array($this->sort, $sortable, true) ? $this->sort : 'issue_date';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $base = fn () => Estimate::where('company_id', $this->companyId ?: 0);

        $estimates = $base()
            ->withCount('items')
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy($sort, $dir)
            ->paginate($this->perPage);

        $summary = [
            'awaiting' => $base()->where('status', 'sent')->count(),
            'approved' => $base()->where('status', 'approved')->count(),
            'value' => (float) $base()->whereIn('status', ['sent', 'approved'])->sum('total'),
        ];

        return $this->view(compact('estimates', 'summary'))->layout('layouts.client');
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
            <h1>Estimates</h1>
            <p>All estimates for {{ $company->company_name ?? 'your company' }}. <span class="cp-live">Live</span></p>
        </div>
    </div>

    @if (!$company)
        <div class="cp-alert a-warning">
            <i class="fas fa-circle-info"></i> No company is linked to your account yet. Please contact your account manager.
        </div>
    @else

    <div class="cp-grid cols-3" style="margin-bottom:20px;">
        <div class="cp-kpi"><div class="cp-kpi-label">Awaiting Response</div><div class="cp-kpi-value">{{ $summary['awaiting'] }}</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Approved</div><div class="cp-kpi-value">{{ $summary['approved'] }}</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Open Value</div><div class="cp-kpi-value">@money($summary['value'])</div></div>
    </div>

    <div class="cp-card">
        <div class="cp-card-head" style="flex-wrap:wrap;">
            <h3><i class="fas fa-file-invoice"></i> {{ $estimates->total() }} {{ Str::plural('estimate', $estimates->total()) }}</h3>
            <div class="cp-toolbar">
                <div class="cp-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" wire:model.live.debounce.350ms="search" placeholder="Search reference…">
                </div>
                <select class="cp-select" wire:model.live="status">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="approved">Approved</option>
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
                            <th class="sortable" wire:click="sortBy('estimate_number')">Reference <i class="fas {{ $sortIcon('estimate_number') }}"></i></th>
                            <th>Items</th>
                            <th class="sortable t-right" wire:click="sortBy('total')">Total <i class="fas {{ $sortIcon('total') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('status')">Status <i class="fas {{ $sortIcon('status') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('issue_date')">Issued <i class="fas {{ $sortIcon('issue_date') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('valid_until')">Valid Until <i class="fas {{ $sortIcon('valid_until') }}"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($estimates as $estimate)
                            <tr class="clickable" wire:key="est-{{ $estimate->id }}"
                                onclick="Livewire.navigate('{{ route('client.estimate-show', $estimate->id) }}')">
                                <td class="t-strong">{{ $estimate->estimate_number }}</td>
                                <td class="t-muted">{{ $estimate->items_count }}</td>
                                <td class="t-right t-strong">@money((float) $estimate->total)</td>
                                <td>
                                    <span class="cp-badge {{ $badgeMap[$estimate->status_badge['class']] ?? 's-secondary' }}">
                                        {{ $estimate->status_badge['icon'] }} {{ ucfirst($estimate->status) }}
                                    </span>
                                </td>
                                <td class="t-muted">{{ optional($estimate->issue_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="t-muted">{{ optional($estimate->valid_until)->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="cp-empty">
                                        <i class="fas fa-file-invoice"></i>
                                        <h6>No estimates found</h6>
                                        <p>{{ $search !== '' || $status !== '' ? 'Try adjusting your filters.' : 'Estimates will appear here once issued.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.cp-pagination', ['paginator' => $estimates])
        </div>
    </div>
    @endif
</div>
