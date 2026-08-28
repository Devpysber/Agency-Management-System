<!-- My Clients overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-building"></i></div>
        <div class="stat-info"><h3>My Clients</h3><p class="stat-number">{{ number_format($clientCount) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-diagram-project"></i></div>
        <div class="stat-info"><h3>Active Projects</h3><p class="stat-number">{{ number_format($myProjects->count()) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-file-invoice"></i></div>
        <div class="stat-info"><h3>Open Estimates</h3><p class="stat-number">{{ number_format($openEstimates) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-file-signature"></i></div>
        <div class="stat-info"><h3>Open Quotations</h3><p class="stat-number">{{ number_format($openQuotations) }}</p></div>
    </div>
</div>

@if ($atRisk->isNotEmpty())
<div class="card mb-4" style="border:1px solid #fecaca;">
    <div class="card-header" style="background:#fef2f2;">
        <h3 class="card-title" style="color:#991b1b;"><i class="fas fa-heart-crack me-2"></i> Needs Attention
            <span class="badge bg-danger ms-1">{{ $atRisk->count() }}</span></h3>
    </div>
    <div class="card-body p-0">
        @foreach ($atRisk as $c)
            <a href="{{ route('companies.show', $c->id) }}" class="d-block px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">{{ $c->company_name }}</a>
        @endforeach
    </div>
    <div class="card-body pt-0"><small class="text-muted">Stale unpaid invoice (14+ days) or no client contact logged in 30 days.</small></div>
</div>
@endif

<div class="row g-4 mb-1">
    <!-- My Clients -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users me-2"></i> My Clients</h3>
                <a href="{{ route('companies.all') }}" class="view-all">All Companies</a>
            </div>
            <div class="card-body p-0">
                @forelse ($myCompanies as $c)
                    <a href="{{ route('companies.show', $c->id) }}" class="d-block px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">{{ $c->company_name }}</a>
                @empty
                    <p class="text-muted mb-0 p-3">No clients assigned to you yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- My Client Projects -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-diagram-project me-2"></i> Client Projects</h3>
                <a href="{{ route('projects.all') }}" class="view-all">All Projects</a>
            </div>
            <div class="card-body p-0">
                @forelse ($myProjects as $p)
                    <a href="{{ route('projects.show', $p->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $p->name }}</span>
                        <small class="text-muted">{{ $p->company->company_name ?? '—' }}</small>
                    </a>
                @empty
                    <p class="text-muted mb-0 p-3">No active projects for your clients.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Meetings -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-check me-2"></i> Upcoming Meetings</h3></div>
            <div class="card-body p-0">
                @forelse ($upcomingMeetings as $m)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="fw-semibold">{{ $m->title }}</div>
                        <small class="text-muted">{{ $m->start_at?->format('M d, H:i') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">Nothing scheduled.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Client Communications -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comments me-2"></i> Recent Communications</h3>
                <a href="{{ route('communications.calls') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($recentComms as $c)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>{{ ucfirst($c->type) }} with {{ $c->contact->first_name ?? '' }} {{ $c->contact->last_name ?? '' }}</div>
                        <small class="text-muted">{{ $c->occurred_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No recent activity.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
