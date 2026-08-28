<!-- Staff Directory / headcount -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-users"></i></div>
        <div class="stat-info"><h3>Active Staff</h3><p class="stat-number">{{ number_format($totalActive) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-user-slash"></i></div>
        <div class="stat-info"><h3>Inactive</h3><p class="stat-number">{{ number_format($totalInactive) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-user-check"></i></div>
        <div class="stat-info"><h3>Present Today</h3><p class="stat-number">{{ number_format($presentToday) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-user-xmark"></i></div>
        <div class="stat-info"><h3>Absent Today</h3><p class="stat-number">{{ number_format($absentToday) }}</p><span class="stat-change">{{ $notMarkedToday }} not marked</span></div>
    </div>
</div>

@if ($pendingAppeals->isNotEmpty())
<div class="card mb-4" style="border:1px solid #fde68a;">
    <div class="card-header" style="background:#fffbeb;">
        <h3 class="card-title" style="color:#92400e;"><i class="fas fa-gavel me-2"></i> Pending Attendance Appeals
            <span class="badge bg-warning text-dark ms-1">{{ $pendingAppeals->count() }}</span></h3>
        <a href="{{ route('attendance.index') }}" class="view-all">Open Attendance ERP</a>
    </div>
    <div class="card-body p-0">
        @foreach ($pendingAppeals as $ap)
            <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div>
                    <span class="fw-semibold">{{ $ap->staff->name ?? 'Staff' }}</span>
                    <small class="text-muted d-block">{{ \Illuminate\Support\Carbon::parse($ap->date)->format('M d') }} — {{ \Illuminate\Support\Str::limit($ap->message, 70) }}</small>
                </div>
                @if ($canApproveAttendance)
                    <div class="flex-shrink-0">
                        <button class="btn btn-sm btn-success" wire:click="approveAppealFromDashboard({{ $ap->id }})"><i class="fas fa-check"></i></button>
                        <button class="btn btn-sm btn-outline-danger" wire:click="rejectAppealFromDashboard({{ $ap->id }})"><i class="fas fa-xmark"></i></button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="row g-4">
    <!-- Staff by Designation -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sitemap me-2"></i> Staff by Designation</h3>
                <a href="{{ route('staff.all') }}" class="view-all">Directory</a>
            </div>
            <div class="card-body">
                @forelse ($byDesignation as $name => $count)
                    <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span>{{ $name ?: 'Unassigned' }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No active staff.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Joiners -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-user-plus me-2"></i> Recent Joiners (30d)</h3></div>
            <div class="card-body p-0">
                @forelse ($recentJoiners as $s)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div><span class="fw-semibold">{{ $s->name }}</span> <small class="text-muted">{{ $s->designation }}</small></div>
                        <small class="text-muted">{{ \Illuminate\Support\Carbon::parse($s->joining_date)->format('M d') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0 p-3">No new joiners this month.</p>
                @endforelse
            </div>
            <div class="card-body pt-0"><small class="text-muted"><i class="fas fa-circle-info me-1"></i> Exit/offboarding tracking isn't in the schema yet — flag if you want it added.</small></div>
        </div>
    </div>
</div>
