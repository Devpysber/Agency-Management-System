<?php

use Livewire\Component;
use App\Models\Contact;
use App\Models\deal;
use App\Models\Task;
use App\Models\Communication;
use App\Models\User;
use App\Mail\ClientPortalCredentials;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

new class extends Component
{
    public $contact;
    public $deals;
    public $tasks;
    public $communications;
    public $generatedPassword = null;

    public function mount($id)
    {
        $this->contact = Contact::with(['company', 'user'])->findOrFail($id);
        $this->deals = deal::where('contact_id', $id)->orderBy('created_at', 'desc')->get();
        $this->tasks = Task::where('related_type', 'contact')->where('related_to', $id)
            ->orderBy('due_date')->get();
        $this->communications = Communication::where('contact_id', $id)
            ->orderBy('occurred_at', 'desc')->limit(10)->get();
    }

    /**
     * Creates (or reuses) a User login with role=client, linked to this
     * contact, so they can sign in to the client portal at /client/dashboard.
     */
    public function grantPortalAccess()
    {
        if (!auth()->user()->hasPermission('Contacts', 'Edit')) {
            session()->flash('error', "You don't have permission to manage portal access.");
            return;
        }

        if ($this->contact->user_id) {
            session()->flash('error', 'Portal access is already granted.');
            return;
        }

        if (!$this->contact->email) {
            session()->flash('error', 'This contact has no email address to log in with.');
            return;
        }

        if (User::where('email', $this->contact->email)->exists()) {
            session()->flash('error', 'A user account with this email already exists.');
            return;
        }

        if (!$this->contact->company_id) {
            session()->flash('error', 'This contact is not linked to a company, so portal data has nothing to scope to.');
            return;
        }

        $password = Str::password(12);

        $user = User::create([
            'name' => $this->contact->full_name ?: $this->contact->email,
            'email' => $this->contact->email,
            'password' => bcrypt($password),
            'role' => 'client',
        ]);

        $this->contact->user_id = $user->id;
        $this->contact->save();

        // Sent synchronously, not queued — nothing in the deploy setup runs
        // a queue worker, so a queued mail would sit in the jobs table
        // forever while this page tells the admin it was sent.
        Mail::to($this->contact->email)->send(new ClientPortalCredentials(
            clientName: $this->contact->full_name ?: $this->contact->email,
            email: $this->contact->email,
            password: $password,
            loginUrl: route('login'),
        ));

        $this->generatedPassword = $password;
        $this->contact->refresh();
        session()->flash('success', 'Portal access granted and emailed to them. The login shown below is only shown once here.');
    }

    public function revokePortalAccess()
    {
        if (!auth()->user()->hasPermission('Contacts', 'Edit')) {
            session()->flash('error', "You don't have permission to manage portal access.");
            return;
        }

        if (!$this->contact->user_id) {
            return;
        }

        User::where('id', $this->contact->user_id)->where('role', 'client')->delete();
        $this->contact->user_id = null;
        $this->contact->save();
        $this->contact->refresh();
        $this->generatedPassword = null;
        session()->flash('success', 'Portal access revoked.');
    }

    public function render()
    {
        return $this->view()->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
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

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <div class="d-flex align-items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($contact->first_name . ' ' . $contact->last_name) }}&background=4F46E5&color=fff"
                         class="rounded-circle" width="60" height="60">
                    <div>
                        <h1 class="mb-0">{{ $contact->first_name }} {{ $contact->last_name }}</h1>
                        <p class="mb-0">
                            @if($contact->status === 'active')
                                <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Active</span>
                            @elseif($contact->status === 'inactive')
                                <span class="badge bg-secondary"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Inactive</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> {{ ucfirst($contact->status ?? 'Unknown') }}</span>
                            @endif
                            @if($contact->lead_status)
                                <span class="badge bg-info ms-1">{{ ucfirst($contact->lead_status) }}</span>
                            @endif
                            <span class="text-muted ms-2">{{ $contact->job_title ?? 'N/A' }}</span>
                            @if($contact->company)
                                <span class="text-muted ms-1">at {{ $contact->company->company_name }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('contacts.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Contacts', 'Edit')))
                    <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endif
                @if($contact->email)
                    <a href="mailto:{{ $contact->email }}" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Send Email
                    </a>
                @endif
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Deals</h3>
                        <p class="stat-number">{{ $deals->count() }}</p>
                        <a href="{{ route('deals.all') }}" class="text-primary" style="font-size: 12px;">View deals</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Open Tasks</h3>
                        <p class="stat-number">{{ $tasks->whereNotIn('status', ['completed', 'cancelled'])->count() }}</p>
                        <a href="{{ route('tasks.all') }}" class="text-primary" style="font-size: 12px;">View tasks</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-comment"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Communications</h3>
                        <p class="stat-number">{{ $communications->count() }}</p>
                        <a href="{{ route('communications.activity-log') }}" class="text-primary" style="font-size: 12px;">View activity</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Lead Score</h3>
                        <p class="stat-number">{{ $contact->lead_score ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Contact Details -->
            <div class="col-lg-8">
                <!-- Contact Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Contact Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-envelope me-1"></i> Email</label>
                                <p>
                                    @if($contact->email)
                                        <a href="mailto:{{ $contact->email }}" class="text-primary">{{ $contact->email }}</a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-phone me-1"></i> Phone</label>
                                <p>{{ $contact->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-mobile-alt me-1"></i> Mobile</label>
                                <p>{{ $contact->mobile ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-building me-1"></i> Department</label>
                                <p>{{ $contact->department ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-bullseye me-1"></i> Source</label>
                                <p>{{ $contact->source ?? $contact->lead_source ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-calendar-alt me-1"></i> Last Contacted</label>
                                <p>{{ $contact->last_contacted_at ? $contact->last_contacted_at->format('M d, Y') : 'Never' }}</p>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-medium"><i class="fas fa-align-left me-1"></i> Notes</label>
                                <p class="mb-0">{{ $contact->notes ?? 'No notes available.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            Address Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-road me-1"></i> Street Address</label>
                                <p>{{ $contact->address ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-medium"><i class="fas fa-city me-1"></i> City</label>
                                <p>{{ $contact->city ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small fw-medium"><i class="fas fa-map-pin me-1"></i> State</label>
                                <p>{{ $contact->state ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small fw-medium"><i class="fas fa-mailbox me-1"></i> Zip Code</label>
                                <p>{{ $contact->zip_code ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small fw-medium"><i class="fas fa-flag me-1"></i> Country</label>
                                <p>{{ $contact->country ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Deals -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Deals
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr><th>Deal</th><th>Value</th><th>Stage</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($deals as $d)
                                    <tr>
                                        <td><a href="{{ route('deals.view', $d->id) }}" class="fw-semibold text-decoration-none">{{ $d->deal_name }}</a></td>
                                        <td>{{ $d->currency }} {{ number_format($d->deal_value, 2) }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $d->deal_stage)) }}</span></td>
                                        <td><span class="badge bg-primary">{{ ucfirst($d->deal_status) }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">No deals linked to this contact yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Communications -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-history text-primary me-2"></i>
                            Recent Communications
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            @forelse ($communications as $comm)
                                <div class="activity-item">
                                    <div class="activity-icon {{ $comm->type === 'email' ? 'blue' : ($comm->type === 'call' ? 'green' : 'purple') }}">
                                        <i class="fas {{ $comm->type_icon }}"></i>
                                    </div>
                                    <div class="activity-info">
                                        <p><strong>{{ $comm->subject }}</strong></p>
                                        <span class="activity-time">{{ $comm->occurred_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No communications logged with this contact yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Additional Info -->
            <div class="col-lg-4">
                <!-- Company -->
                @if($contact->company)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-building text-primary me-2"></i>
                            Company
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="fw-semibold mb-1">{{ $contact->company->company_name }}</p>
                        <a href="{{ route('companies.show', $contact->company->id) }}" class="text-primary" style="font-size: 12px;">View company profile</a>
                    </div>
                </div>
                @endif

                <!-- Portal Access -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-key text-primary me-2"></i>
                            Client Portal Access
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($contact->user_id)
                            <p class="mb-2">
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Access granted</span>
                            </p>
                            <p class="text-muted mb-3" style="font-size: 12px;">
                                Logs in as <strong>{{ $contact->email }}</strong>
                            </p>
                            @if($generatedPassword)
                                <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 13px;">
                                    Temporary password (shown once):
                                    <code>{{ $generatedPassword }}</code>
                                </div>
                            @endif
                            @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Contacts', 'Edit')))
                                <button class="btn btn-sm btn-outline-danger w-100" wire:click="revokePortalAccess"
                                        wire:confirm="Revoke this client's portal login? They will no longer be able to sign in.">
                                    <i class="fas fa-ban"></i> Revoke Access
                                </button>
                            @endif
                        @else
                            <p class="text-muted mb-3" style="font-size: 13px;">
                                No portal login yet. Grant access so this contact can sign in at
                                <code>/client/dashboard</code> and view their company's projects, estimates,
                                quotations, and payments.
                            </p>
                            @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Contacts', 'Edit')))
                                <button class="btn btn-sm btn-primary w-100" wire:click="grantPortalAccess">
                                    <i class="fas fa-user-plus"></i> Grant Portal Access
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Tags -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-tags text-primary me-2"></i>
                            Tags
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @forelse (($contact->tags ?? []) as $tag)
                                <span class="badge bg-primary p-2"><i class="fas fa-tag me-1"></i>{{ $tag }}</span>
                            @empty
                                <p class="text-muted mb-0">No tags assigned.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Communication Preferences -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-sliders-h text-primary me-2"></i>
                            Communication Preferences
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-envelope me-1 text-muted"></i> Email opt-in</span>
                            @if($contact->email_opt_in)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-sms me-1 text-muted"></i> SMS opt-in</span>
                            @if($contact->sms_opt_in)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-phone me-1 text-muted"></i> Call opt-in</span>
                            @if($contact->call_opt_in)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-bolt text-primary me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('deals.add') }}" class="btn btn-outline-success">
                                <i class="fas fa-file-invoice me-2"></i> Create Deal
                            </a>
                            <a href="{{ route('communications.meetings') }}" class="btn btn-outline-info">
                                <i class="fas fa-calendar-plus me-2"></i> Log Meeting
                            </a>
                            <a href="{{ route('communications.emails') }}" class="btn btn-outline-warning">
                                <i class="fas fa-envelope me-2"></i> Log Email
                            </a>
                            <a href="{{ route('communications.calls') }}" class="btn btn-outline-danger">
                                <i class="fas fa-phone me-2"></i> Log Call
                            </a>
                            <a href="{{ route('tasks.create') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-tasks me-2"></i> Create Task
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
