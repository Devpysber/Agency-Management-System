<?php

use Livewire\Component;
use App\Models\ClientPortalVisit;

new class extends Component
{
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function resetFilters()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $from = $this->dateFrom ?: now()->startOfMonth()->format('Y-m-d');
        $to = $this->dateTo ?: now()->format('Y-m-d');

        $rows = ClientPortalVisit::query()
            ->whereBetween('visited_on', [$from, $to])
            ->selectRaw('user_id, company_id,
                COUNT(*) as days_visited,
                SUM(hits) as total_hits,
                MAX(last_seen_at) as last_seen,
                MIN(first_seen_at) as first_seen')
            ->groupBy('user_id', 'company_id')
            ->with(['user:id,name,email', 'company:id,company_name'])
            ->orderByDesc('last_seen')
            ->get();

        $today = now()->toDateString();

        return $this->view([
            'rows' => $rows,
            'kpiActiveToday' => (int) ClientPortalVisit::whereDate('visited_on', $today)->distinct('user_id')->count('user_id'),
            'kpiActiveWeek' => (int) ClientPortalVisit::where('visited_on', '>=', now()->subDays(6)->toDateString())->distinct('user_id')->count('user_id'),
            'kpiActiveMonth' => (int) ClientPortalVisit::where('visited_on', '>=', now()->startOfMonth()->toDateString())->distinct('user_id')->count('user_id'),
            'kpiLoginsInRange' => (int) ClientPortalVisit::whereBetween('visited_on', [$from, $to])->sum('hits'),
            'kpiClientsInRange' => (int) ClientPortalVisit::whereBetween('visited_on', [$from, $to])->distinct('user_id')->count('user_id'),
        ])->layout('layouts.app');
    }
};
?>

<div>
    <div class="dashboard">
        <div class="page-header">
            <div>
                <h1 class="mb-0">Client Portal Attendance</h1>
                <p class="mb-0">Which client contacts are logging into the portal, and how often.</p>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-info"><h3>Active Today</h3><p class="stat-number">{{ $kpiActiveToday }}</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-week"></i></div>
                    <div class="stat-info"><h3>Active (7 days)</h3><p class="stat-number">{{ $kpiActiveWeek }}</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-calendar-days"></i></div>
                    <div class="stat-info"><h3>Active This Month</h3><p class="stat-number">{{ $kpiActiveMonth }}</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-right-to-bracket"></i></div>
                    <div class="stat-info"><h3>Visits in Range</h3><p class="stat-number">{{ $kpiLoginsInRange }}</p></div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-medium">From</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-medium">To</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary" wire:click="resetFilters">
                            <i class="fas fa-rotate-left"></i> Reset
                        </button>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <span class="text-muted small">{{ $kpiClientsInRange }} client(s) in this range</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-check me-2"></i> Attendance by Client</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Company</th>
                                <th class="text-center">Days Visited</th>
                                <th class="text-center">Visits</th>
                                <th>First Seen</th>
                                <th>Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        <h6 class="mb-0 fw-semibold">{{ $row->user->name ?? 'Unknown' }}</h6>
                                        <small class="text-muted">{{ $row->user->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $row->company->company_name ?? '—' }}</td>
                                    <td class="text-center"><span class="badge bg-primary">{{ $row->days_visited }}</span></td>
                                    <td class="text-center">{{ $row->total_hits }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row->first_seen)->format('M d, Y H:i') }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row->last_seen)->format('M d, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-user-clock fa-3x text-muted mb-3 d-block"></i>
                                        <h6 class="text-muted">No portal visits recorded in this range</h6>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
