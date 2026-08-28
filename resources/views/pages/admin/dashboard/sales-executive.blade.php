<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-handshake"></i></div>
        <div class="stat-info">
            <h3>My Deals</h3>
            <p class="stat-number">{{ $myDealsCount }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3>My Pipeline Value</h3>
            <p class="stat-number">${{ number_format($myPipelineValue) }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-trophy"></i></div>
        <div class="stat-info">
            <h3>Won This Month</h3>
            <p class="stat-number">{{ $dealsWonThisMonth }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-percent"></i></div>
        <div class="stat-info">
            <h3>Win Rate</h3>
            <p class="stat-number">{{ $myWinRate }}%</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-address-book"></i></div>
        <div class="stat-info">
            <h3>My Contacts</h3>
            <p class="stat-number">{{ $myContactsCount }}</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-phone-volume"></i></div>
        <div class="stat-info">
            <h3>Activity This Week</h3>
            <p class="stat-number">{{ $activityThisWeek }}</p>
            <span class="stat-change">calls, emails & meetings logged</span>
        </div>
    </div>
</div>

@if ($followUpsDue->isNotEmpty())
<div class="card mb-4" style="border:1px solid #fde68a;">
    <div class="card-header" style="background:#fffbeb;">
        <h3 class="card-title" style="color:#92400e;"><i class="fas fa-clock me-2"></i> Follow-ups Due
            <span class="badge bg-warning text-dark ms-1">{{ $followUpsDue->count() }}</span></h3>
    </div>
    <div class="card-body p-0">
        @foreach ($followUpsDue as $d)
            <a href="{{ route('deals.view', $d->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                <span>{{ $d->deal_name }}</span>
                <small class="text-muted">{{ $d->updated_at->diffForHumans() }}</small>
            </a>
        @endforeach
    </div>
    <div class="card-body pt-0"><small class="text-muted">No activity in 5+ days.</small></div>
</div>
@endif

<div class="dashboard-grid">
    <!-- My Open Deals -->
    <div class="card">
        <div class="card-header">
            <h3>My Open Deals</h3>
            <a href="{{ route('deals.all') }}" class="view-all">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Deal</th><th>Company</th><th>Value</th></tr></thead>
                    <tbody>
                        @forelse ($myOpenDeals as $deal)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('deals.view', $deal->id) }}'">
                                <td>{{ $deal->deal_name }}</td>
                                <td>{{ $deal->company->company_name ?? '—' }}</td>
                                <td class="fw-semibold">${{ number_format($deal->deal_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No open deals.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" wire:ignore>
        <div class="card-header"><h3>My Deals by Stage</h3></div>
        <div class="card-body">
            <div style="position:relative; height:240px;">
                <canvas id="dealStageChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- My Leads -->
    <div class="card">
        <div class="card-header">
            <h3>My Leads</h3>
            <a href="{{ route('contacts.all') }}" class="view-all">View All</a>
        </div>
        <div class="card-body p-0">
            @forelse ($myLeads as $lead)
                <a href="{{ route('contacts.show', $lead->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                    <span>{{ $lead->first_name }} {{ $lead->last_name }}</span>
                    <small class="text-muted">{{ ucfirst($lead->lead_status) }}</small>
                </a>
            @empty
                <p class="text-muted mb-0 p-3">No open leads assigned to you.</p>
            @endforelse
        </div>
    </div>

    <!-- My Companies -->
    <div class="card">
        <div class="card-header">
            <h3>My Companies</h3>
            <a href="{{ route('companies.all') }}" class="view-all">View All</a>
        </div>
        <div class="card-body p-0">
            @forelse ($myCompanies as $c)
                <a href="{{ route('companies.show', $c->id) }}" class="d-block px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">{{ $c->company_name }}</a>
            @empty
                <p class="text-muted mb-0 p-3">No companies yet — comes from your assigned contacts.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card full-width">
    <div class="card-header">
        <h3>Recent Communications</h3>
        <a href="{{ route('communications.activity-log') }}" class="view-all">View All</a>
    </div>
    <div class="card-body">
        <div class="activity-list">
            @forelse ($recentCommunications as $comm)
                <div class="activity-item">
                    <div class="activity-icon blue"><i class="fas {{ $comm->type_icon }}"></i></div>
                    <div class="activity-info">
                        <p><strong>{{ $comm->subject }}</strong> @if($comm->contact) — {{ $comm->contact->full_name }} @endif</p>
                        <span class="activity-time">{{ $comm->occurred_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No communications logged yet.</p>
            @endforelse
        </div>
    </div>
</div>
