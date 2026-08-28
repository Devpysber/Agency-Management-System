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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr wire:key="pay-{{ $payment->id }}"
                                @class(['clickable' => $payment->project])
                                @if ($payment->project) onclick="Livewire.navigate('{{ route('client.project-show', $payment->project->id) }}')" @endif>
                                <td class="t-strong">{{ $payment->project->name ?? '—' }}</td>
                                <td class="t-right t-strong">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="t-muted">{{ $payment->gateway->name ?? '—' }}</td>
                                <td class="t-muted">{{ $payment->reference ?: '—' }}</td>
                                <td>
                                    <span class="cp-badge {{ $badgeMap[$payment->status_badge['class']] ?? 's-secondary' }}">
                                        {{ $payment->status_badge['icon'] }} {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="t-muted">{{ optional($payment->paid_at ?? $payment->created_at)->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
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
    </div>
    @endif
</div>
