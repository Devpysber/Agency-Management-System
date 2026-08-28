<?php

use Livewire\Component;
use App\Models\staff;
use App\Models\User;
use App\Mail\StaffCredentialsMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

new class extends Component
{
    public $member;
    public $generatedPassword = null;
    public $passwordWasReset = false;

    public function mount($id)
    {
        $this->member = staff::with('user')->findOrFail($id);
    }

    public function generateLogin()
    {
        if (!auth()->user()->hasPermission('Staff', 'Edit')) {
            session()->flash('error', "You don't have permission to manage staff logins.");
            return;
        }

        if (!$this->member->email) {
            session()->flash('error', 'This staff member needs an email address before a login can be created.');
            return;
        }

        if ($this->member->user_id) {
            session()->flash('error', 'This staff member already has a login account. Use "Reset Password" instead.');
            return;
        }

        $existing = User::where('email', $this->member->email)->first();
        if ($existing) {
            session()->flash('error', 'A user account with this email already exists. Cannot auto-link — check for a duplicate email.');
            return;
        }

        $password = Str::password(12, true, true, true, false);

        $user = User::create([
            'name' => $this->member->name,
            'email' => $this->member->email,
            'password' => $password,
            'role' => 'staff',
        ]);

        $this->member->update(['user_id' => $user->id]);
        $this->member->refresh();

        $this->generatedPassword = $password;
        $this->passwordWasReset = false;
        session()->flash('success', 'Login account created. Copy the password below or send it by email.');
    }

    public function resetPassword()
    {
        if (!auth()->user()->hasPermission('Staff', 'Edit')) {
            session()->flash('error', "You don't have permission to manage staff logins.");
            return;
        }

        if (!$this->member->user_id || !$this->member->user) {
            session()->flash('error', 'This staff member does not have a login account yet.');
            return;
        }

        $password = Str::password(12, true, true, true, false);

        $this->member->user->update(['password' => $password]);

        $this->generatedPassword = $password;
        $this->passwordWasReset = true;
        session()->flash('success', 'Password reset. Copy it below or send it by email.');
    }

    public function revokeLogin()
    {
        if (!auth()->user()->hasPermission('Staff', 'Edit')) {
            session()->flash('error', "You don't have permission to manage staff logins.");
            return;
        }

        if (!$this->member->user_id) {
            return;
        }

        User::where('id', $this->member->user_id)->where('role', 'staff')->delete();
        $this->member->update(['user_id' => null]);
        $this->member->refresh();
        $this->generatedPassword = null;
        $this->passwordWasReset = false;
        session()->flash('success', 'Login revoked.');
    }

    public function sendCredentials()
    {
        if (!auth()->user()->hasPermission('Staff', 'Edit')) {
            session()->flash('error', "You don't have permission to manage staff logins.");
            return;
        }

        if (!$this->generatedPassword || !$this->member->user_id) {
            session()->flash('error', 'Generate or reset the password first, then send it.');
            return;
        }

        try {
            Mail::to($this->member->email)->send(new StaffCredentialsMail(
                staffName: $this->member->name,
                email: $this->member->email,
                password: $this->generatedPassword,
                isReset: $this->passwordWasReset,
            ));
            session()->flash('success', 'Credentials emailed to ' . $this->member->email . '.');
        } catch (\Exception $e) {
            session()->flash('error', 'Could not send email: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $mStart = now()->startOfMonth()->toDateString();
        $recs = \App\Models\AttendanceRecord::staff()->where('person_id', $this->member->id)
            ->whereBetween('date', [$mStart, now()->toDateString()])->get();

        return $this->view([
            'attendance' => [
                'present' => $recs->whereIn('status', ['present', 'late', 'remote', 'half_day'])->count(),
                'absent' => $recs->where('status', 'absent')->count(),
                'leave' => $recs->where('status', 'leave')->count(),
                'late' => $recs->where('status', 'late')->count(),
                'hours' => round($recs->sum('active_minutes') / 60, 1),
                'presence' => \App\Models\AttendanceRecord::presenceLabel($this->member->id),
                'presence_state' => \App\Models\AttendanceRecord::presenceState($this->member->id)['state'],
            ],
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <div class="d-flex align-items-center gap-3">
                    @if($member->image)
                        <img src="{{ asset('storage/'.$member->image) }}" class="rounded-circle" width="64" height="64" alt="">
                    @else
                        <i class="fas fa-user-circle fa-4x text-muted"></i>
                    @endif
                    <div>
                        <h1 class="mb-0">{{ $member->name ?? 'N/A' }}</h1>
                        <p class="mb-0">
                            @if($member->status === 'inactive')
                                <span class="badge bg-danger"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Inactive</span>
                            @else
                                <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Active</span>
                            @endif
                            <span class="text-muted ms-2">{{ $member->designation ?? 'N/A' }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('staff.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('staff.edit', $member->id) }}" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                @if($member->email)
                    <a href="mailto:{{ $member->email }}" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Send Email
                    </a>
                @endif
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Login Access -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="fw-semibold mb-0">
                    <i class="fas fa-key text-primary me-2"></i>
                    Login Access
                </h5>
            </div>
            <div class="card-body">
                @if($member->user_id)
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <span>
                            <span class="badge bg-success me-2"><i class="fas fa-check-circle me-1"></i>Login Active</span>
                            <span class="text-muted">{{ $member->email }}</span>
                        </span>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" wire:click="resetPassword" wire:confirm="Reset this staff member's password? The old password will stop working.">
                                <i class="fas fa-rotate"></i> Reset Password
                            </button>
                            <button class="btn btn-outline-danger" wire:click="revokeLogin" wire:confirm="Revoke this staff member's login? They will no longer be able to sign in.">
                                <i class="fas fa-ban"></i> Revoke Login
                            </button>
                        </div>
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <span>
                            <span class="badge bg-secondary me-2"><i class="fas fa-circle-xmark me-1"></i>No Login Account</span>
                            <span class="text-muted">This staff member cannot sign in yet.</span>
                        </span>
                        <button class="btn btn-primary btn-sm" wire:click="generateLogin">
                            <i class="fas fa-user-plus"></i> Generate Login Credentials
                        </button>
                    </div>
                @endif

                @if($generatedPassword)
                    <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                        <div>
                            <strong><i class="fas fa-triangle-exclamation me-1"></i> New password (shown once):</strong>
                            <code class="ms-2 fs-6">{{ $generatedPassword }}</code>
                            <div class="small text-muted mt-1">Email: {{ $member->email }} — copy this now, it won't be shown again.</div>
                        </div>
                        <button class="btn btn-success btn-sm" wire:click="sendCredentials">
                            <i class="fas fa-paper-plane"></i> Send by Email
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Attendance snapshot -->
        <div class="card mb-4 a-reveal">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-semibold mb-0"><i class="fas fa-calendar-check text-primary me-2"></i> Attendance — this month</h5>
                <a href="{{ route('attendance.person', ['type' => 'staff', 'id' => $member->id]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-calendar-days"></i> Full calendar
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col"><div class="text-muted small">Status now</div>
                        @php $psColor = ['online' => 'text-success', 'inactive' => 'text-warning', 'offline' => 'text-muted'][$attendance['presence_state']] ?? 'text-muted'; @endphp
                        <div class="fw-bold {{ $psColor }}" title="{{ $attendance['presence'] }}">
                            <i class="fas fa-circle" style="font-size:8px;"></i> {{ $attendance['presence'] }}
                        </div>
                    </div>
                    <div class="col"><div class="text-muted small">Present</div><div class="fw-bold text-success">{{ $attendance['present'] }}</div></div>
                    <div class="col"><div class="text-muted small">Absent</div><div class="fw-bold text-danger">{{ $attendance['absent'] }}</div></div>
                    <div class="col"><div class="text-muted small">Late</div><div class="fw-bold text-warning">{{ $attendance['late'] }}</div></div>
                    <div class="col"><div class="text-muted small">Leave</div><div class="fw-bold">{{ $attendance['leave'] }}</div></div>
                    <div class="col"><div class="text-muted small">Hours</div><div class="fw-bold">{{ $attendance['hours'] }}h</div></div>
                </div>
            </div>
        </div>

        <!-- Staff Details -->
        <div class="card">
            <div class="card-header">
                <h5 class="fw-semibold mb-0">
                    <i class="fas fa-user-circle text-primary me-2"></i>
                    Staff Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">Name</label>
                        <p class="fw-semibold">{{ $member->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">Email</label>
                        <p>{{ $member->email ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">
                            <i class="fab fa-whatsapp text-success"></i> WhatsApp
                        </label>
                        <p>{{ $member->whatsapp ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">Designation</label>
                        <p>
                            <span class="badge bg-secondary">{{ $member->designation ?? 'N/A' }}</span>
                            <span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $member->employment_type ?? 'full_time')) }}</span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">Joining Date</label>
                        <p>{{ optional($member->joining_date)->format('M d, Y') ?? 'N/A' }}</p>
                    </div>
                    @if (($member->employment_type ?? '') === 'intern')
                        <div class="col-md-6">
                            <label class="text-muted small fw-medium">Internship Tenure</label>
                            <p>{{ optional($member->tenure_start)->format('M d, Y') ?? '—' }} &nbsp;→&nbsp; {{ optional($member->tenure_end)->format('M d, Y') ?? '—' }}</p>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="text-muted small fw-medium">Shift Start</label>
                        <p>{{ $member->shift_start ?? '09:00' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small fw-medium">Daily Hours</label>
                        <p>{{ $member->daily_hours ?? 8 }}h</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small fw-medium"><i class="fas fa-id-card"></i> Aadhaar</label>
                        <p>{{ $member->masked_aadhar ?? '—' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small fw-medium"><i class="fas fa-id-badge"></i> PAN</label>
                        <p>{{ $member->masked_pan ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">Salary</label>
                        <p class="fw-semibold text-success">${{ number_format($member->salary ?? 0, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">Status</label>
                        <p>
                            @if($member->status === 'inactive')
                                <span class="badge bg-danger">
                                    <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                    Inactive
                                </span>
                            @else
                                <span class="badge bg-success">
                                    <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                    Active
                                </span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-medium">Created At</label>
                        <p>{{ $member->created_at ? $member->created_at->format('M d, Y h:i A') : 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
