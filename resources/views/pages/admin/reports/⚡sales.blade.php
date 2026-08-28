<?php

use Livewire\Component;
use App\Models\deal;
use App\Models\ProjectPayment;

new class extends Component
{
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatedDateFrom() {}
    public function updatedDateTo() {}

    public function resetFilters()
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $range = fn ($query, $column = 'created_at') => $query
            ->when($this->dateFrom, fn ($q) => $q->whereDate($column, '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate($column, '<=', $this->dateTo));

        $wonDeals = $range(deal::where('deal_status', 'won'));
        $lostDeals = $range(deal::where('deal_status', 'lost'));
        $activeDeals = deal::where('deal_status', 'active');

        $totalRevenue = (clone $wonDeals)->sum('deal_value');
        $wonCount = (clone $wonDeals)->count();
        $lostCount = (clone $lostDeals)->count();
        $pipelineValue = (clone $activeDeals)->sum('deal_value');
        $winRate = ($wonCount + $lostCount) > 0 ? round($wonCount / ($wonCount + $lostCount) * 100, 1) : 0;

        $collected = ProjectPayment::where('status', 'paid')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('paid_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('paid_at', '<=', $this->dateTo))
            ->sum('amount');

        // Revenue by month (last 6 months, based on actual_close_date)
        $monthly = collect(range(5, 0))->map(function ($i) {
            $month = now()->subMonths($i);
            $total = deal::where('deal_status', 'won')
                ->whereYear('actual_close_date', $month->year)
                ->whereMonth('actual_close_date', $month->month)
                ->sum('deal_value');
            return ['label' => $month->format('M Y'), 'total' => (float) $total];
        });
        $maxMonthly = max(1, $monthly->max('total'));

        $topCompanies = deal::with('company')
            ->where('deal_status', 'won')
            ->get()
            ->filter(fn ($d) => $d->company)
            ->groupBy('company_id')
            ->map(fn ($group) => [
                'company' => $group->first()->company,
                'total' => $group->sum('deal_value'),
                'count' => $group->count(),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();

        return $this->view([
            'totalRevenue' => $totalRevenue,
            'wonCount' => $wonCount,
            'lostCount' => $lostCount,
            'pipelineValue' => $pipelineValue,
            'winRate' => $winRate,
            'collected' => $collected,
            'monthly' => $monthly,
            'maxMonthly' => $maxMonthly,
            'topCompanies' => $topCompanies,
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Sales Report</h1>
                <p>Revenue, win rate, and pipeline performance.</p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">From</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">To</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters"><i class="fas fa-undo"></i> Reset to Year</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-sack-dollar"></i></div>
                    <div class="stat-info"><h3>Revenue Won</h3><p class="stat-number">${{ number_format($totalRevenue, 2) }}</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-money-check-dollar"></i></div>
                    <div class="stat-info"><h3>Payments Collected</h3><p class="stat-number">${{ number_format($collected, 2) }}</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-info"><h3>Open Pipeline</h3><p class="stat-number">${{ number_format($pipelineValue, 2) }}</p></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-trophy"></i></div>
                    <div class="stat-info"><h3>Win Rate</h3><p class="stat-number">{{ $winRate }}%</p></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Revenue Trend -->
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-column me-2"></i> Revenue Trend (Won Deals)</h3>
                    </div>
                    <div class="card-body">
                        @foreach ($monthly as $row)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="fw-medium">{{ $row['label'] }}</small>
                                    <small class="text-muted">${{ number_format($row['total'], 2) }}</small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" style="width: {{ $row['total'] > 0 ? max(4, round($row['total'] / $maxMonthly * 100)) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Win / Loss -->
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-scale-balanced me-2"></i> Won vs Lost</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-trophy text-success me-1"></i> Won</span>
                            <span class="fw-semibold">{{ $wonCount }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: {{ ($wonCount + $lostCount) > 0 ? round($wonCount / ($wonCount + $lostCount) * 100) : 0 }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-times-circle text-danger me-1"></i> Lost</span>
                            <span class="fw-semibold">{{ $lostCount }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-danger" style="width: {{ ($wonCount + $lostCount) > 0 ? round($lostCount / ($wonCount + $lostCount) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Companies -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building me-2"></i> Top Companies by Revenue</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Company</th>
                                <th>Deals Won</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topCompanies as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['company']->company_name }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>${{ number_format($row['total'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No won deals with a company yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
