<!-- QA Dashboard -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-vial"></i></div>
        <div class="stat-info"><h3>Testing Queue</h3><p class="stat-number">{{ $testingQueueCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-bug"></i></div>
        <div class="stat-info"><h3>Open Bugs</h3><p class="stat-number">{{ $openBugsCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-shield-check"></i></div>
        <div class="stat-info"><h3>Verified by Me</h3><p class="stat-number">{{ $verifiedCount }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-percent"></i></div>
        <div class="stat-info"><h3>Pass Rate</h3><p class="stat-number">{{ $passRate }}%</p></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-magnifying-glass me-2"></i> Testing Queue — Fixed, Awaiting Retest</h3>
        <a href="{{ route('bugs.all') }}" class="view-all">All Bugs</a>
    </div>
    <div class="card-body p-0">
        @forelse ($testingQueue as $b)
            <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div>
                    <div class="fw-semibold">{{ $b->title }}</div>
                    <small class="text-muted">{{ $b->project->name ?? '—' }} · fixed by {{ $b->assignedTo->name ?? '—' }}</small>
                </div>
                <a href="{{ route('bugs.show', $b->id) }}" class="btn btn-sm btn-success"><i class="fas fa-shield-check"></i> Test Now</a>
            </div>
        @empty
            <p class="text-muted mb-0 p-3">Nothing waiting on QA right now.</p>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-bug me-2"></i> All Bugs</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Bug</th><th>Project</th><th>Assigned</th><th>Severity</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($recentBugs as $b)
                        <tr style="cursor:pointer" onclick="window.location='{{ route('bugs.show', $b->id) }}'">
                            <td>{{ $b->title }}</td>
                            <td>{{ $b->project->name ?? '—' }}</td>
                            <td>{{ $b->assignedTo->name ?? 'Unassigned' }}</td>
                            <td><span class="badge {{ $b->severity_badge['class'] }}">{{ ucfirst($b->severity) }}</span></td>
                            <td><span class="badge {{ $b->status_badge['class'] }}">{{ ucwords(str_replace('_', ' ', $b->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No bugs reported yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
