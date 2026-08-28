<!-- Development Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
        <div class="stat-info"><h3>Open Dev Tasks</h3><p class="stat-number">{{ $openTasks }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-bug"></i></div>
        <div class="stat-info"><h3>Open Bugs</h3><p class="stat-number">{{ $openBugsCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-magnifying-glass"></i></div>
        <div class="stat-info"><h3>QA Queue</h3><p class="stat-number">{{ $qaQueueCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $releaseReady ? 'green' : 'red' }}"><i class="fas {{ $releaseReady ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i></div>
        <div class="stat-info"><h3>Release Readiness</h3><p class="stat-number">{{ $releaseReady ? 'Ready' : 'Blocked' }}</p>
            @if ($criticalBugsCount)<span class="stat-change">{{ $criticalBugsCount }} critical bug(s)</span>@endif
        </div>
    </div>
</div>

@if ($technicalRisks->isNotEmpty())
<div class="card mb-4" style="border:1px solid #fecaca;">
    <div class="card-header" style="background:#fef2f2;">
        <h3 class="card-title" style="color:#991b1b;"><i class="fas fa-triangle-exclamation me-2"></i> Technical Risks
            <span class="badge bg-danger ms-1">{{ $technicalRisks->count() }}</span></h3>
    </div>
    <div class="card-body p-0">
        @foreach ($technicalRisks as $b)
            <a href="{{ route('bugs.show', $b->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                <span>{{ $b->title }}</span>
                <span class="badge {{ $b->severity_badge['class'] }}">{{ ucfirst($b->severity) }} · open {{ $b->created_at->diffForHumans(null, true) }}</span>
            </a>
        @endforeach
    </div>
    <div class="card-body pt-0"><small class="text-muted">Critical/high severity bug open 5+ days — escalation signal.</small></div>
</div>
@endif

<div class="row g-4 mb-1">
    <!-- Developer Team -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users-gear me-2"></i> Developer Team</h3></div>
            <div class="card-body p-0">
                @forelse ($devTeam as $s)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div><span class="fw-semibold">{{ $s->name }}</span> <small class="text-muted">{{ $s->designation }}</small></div>
                        <span class="badge {{ $s->open_tasks_count > 5 ? 'bg-danger' : 'bg-secondary' }} rounded-pill">{{ $s->open_tasks_count }} open</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No developers on the team yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- QA Queue -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-vial me-2"></i> QA Queue</h3>
                <a href="{{ route('bugs.all') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse ($qaQueue as $b)
                    <a href="{{ route('bugs.show', $b->id) }}" class="d-flex justify-content-between px-3 py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $b->title }}</span>
                        <small class="text-muted">{{ $b->assignedTo->name ?? '—' }}</small>
                    </a>
                @empty
                    <p class="text-muted mb-0 p-3">Nothing awaiting QA.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Bugs (Code Review status proxy) + Architecture Notes -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bug me-2"></i> Recent Bugs</h3>
                <a href="{{ route('bugs.all') }}" class="view-all">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Bug</th><th>Assigned</th><th>Severity</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($recentBugs as $b)
                                <tr style="cursor:pointer" onclick="window.location='{{ route('bugs.show', $b->id) }}'">
                                    <td>{{ $b->title }}</td>
                                    <td>{{ $b->assignedTo->name ?? 'Unassigned' }}</td>
                                    <td><span class="badge {{ $b->severity_badge['class'] }}">{{ ucfirst($b->severity) }}</span></td>
                                    <td><span class="badge {{ $b->status_badge['class'] }}">{{ ucwords(str_replace('_', ' ', $b->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No bugs reported yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Architecture Notes</h3></div>
            <div class="card-body">
                <p class="text-muted small mb-0">
                    <i class="fas fa-circle-info me-1"></i> No dedicated architecture-notes/technical-documentation
                    module exists yet — flagged rather than built as a stub. Say if you want one (a simple
                    per-project notes page would be the natural fit).
                </p>
            </div>
        </div>
    </div>
</div>
