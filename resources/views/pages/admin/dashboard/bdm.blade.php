<!-- Sales Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info"><h3>Active Pipeline</h3><p class="stat-number">${{ number_format($activePipelineValue) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-handshake"></i></div>
        <div class="stat-info"><h3>Won This Month</h3><p class="stat-number">{{ number_format($wonThisMonth) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-percent"></i></div>
        <div class="stat-info"><h3>Conversion Rate</h3><p class="stat-number">{{ $conversionRate }}%</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-user-plus"></i></div>
        <div class="stat-info"><h3>New Leads This Week</h3><p class="stat-number">{{ number_format($newLeadsThisWeek) }}</p></div>
    </div>
</div>

<div class="row g-4 mb-1">
    <!-- Sales Pipeline -->
    <div class="col-lg-6">
        <div class="card h-100" wire:ignore>
            <div class="card-header">
                <h3 class="card-title">Sales Pipeline</h3>
                <a href="{{ route('deals.pipeline') }}" class="view-all">Open Pipeline</a>
            </div>
            <div class="card-body"><div style="position:relative; height:240px;"><canvas id="dealStageChart"></canvas></div></div>
        </div>
    </div>

    <!-- Sales Team workload -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users-gear me-2"></i> Sales Team</h3></div>
            <div class="card-body p-0">
                @forelse ($salesTeam as $s)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div><span class="fw-semibold">{{ $s->name }}</span> <small class="text-muted">{{ $s->designation }}</small></div>
                        <span class="badge bg-primary rounded-pill">{{ $s->open_deals_count }} open</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No sales team members yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Follow-ups Due -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-clock me-2"></i> Follow-ups Due</h3></div>
            <div class="card-body p-0">
                @forelse ($followUpsDue as $d)
                    <a href="{{ route('deals.view', $d->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $d->deal_name }}</span>
                        <small class="text-muted">{{ $d->updated_at->diffForHumans() }}</small>
                    </a>
                @empty
                    <p class="text-muted mb-0 p-3">Everything's been touched recently.</p>
                @endforelse
            </div>
            <div class="card-body pt-0"><small class="text-muted">No activity in 5+ days — proxy signal, no dedicated follow-up model yet.</small></div>
        </div>
    </div>

    <!-- Top Deals -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Top Open Deals</h3>
                <a href="{{ route('deals.all') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($topDeals as $d)
                    <a href="{{ route('deals.view', $d->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $d->deal_name }}</span>
                        <span class="fw-semibold">${{ number_format($d->deal_value) }}</span>
                    </a>
                @empty
                    <p class="text-muted mb-0 p-3">No active deals.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
