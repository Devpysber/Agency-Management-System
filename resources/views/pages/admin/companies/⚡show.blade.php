<?php

use Livewire\Component;
use App\Models\company;

new class extends Component
{
    //
    public $company;
    public $contactsCount = 0;
    public $dealsCount = 0;
    public $openTasksCount = 0;
    public $communicationsCount = 0;

    public function mount($id){
        $this->company = company::findOrFail($id);
        $this->contactsCount = \App\Models\Contact::where('company_id', $id)->count();
        $this->dealsCount = \App\Models\deal::where('company_id', $id)->count();
        $this->openTasksCount = \App\Models\Task::where('related_type', 'company')
            ->where('related_to', $id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        $this->communicationsCount = \App\Models\Communication::where('company_id', $id)->count();
    }

    public function render()
    {
        $id = $this->company->id;

        $contactEvents = \App\Models\Contact::where('company_id', $id)
            ->latest('created_at')->limit(3)->get()
            ->map(fn ($c) => [
                'icon' => 'fa-user-plus', 'color' => 'blue',
                'text' => '<strong>' . $c->first_name . ' ' . $c->last_name . '</strong> was added as a contact',
                'time' => $c->created_at,
            ]);

        $dealEvents = \App\Models\deal::where('company_id', $id)
            ->latest('created_at')->limit(3)->get()
            ->map(fn ($d) => [
                'icon' => 'fa-chart-line', 'color' => 'green',
                'text' => 'Deal <strong>' . $d->deal_name . '</strong> was ' . ($d->deal_status === 'won' ? 'won' : ($d->deal_status === 'lost' ? 'lost' : 'created')),
                'time' => $d->created_at,
            ]);

        $commEvents = \App\Models\Communication::where('company_id', $id)
            ->latest('occurred_at')->limit(3)->get()
            ->map(fn ($c) => [
                'icon' => $c->type_icon, 'color' => 'orange',
                'text' => ucfirst($c->type) . ' logged: <strong>' . $c->subject . '</strong>',
                'time' => $c->occurred_at,
            ]);

        $recentActivity = $contactEvents->concat($dealEvents)->concat($commEvents)
            ->sortByDesc('time')->take(5)->values();

        return $this->view([
            'recentActivity' => $recentActivity,
            'companyContacts' => \App\Models\Contact::where('company_id', $id)->latest('created_at')->limit(4)->get(),
            'tags' => $this->company->company_tags
                ? array_filter(array_map('trim', explode(',', $this->company->company_tags)))
                : [],
        ]);
    }
};
?>

<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <div class="d-flex align-items-center gap-3">
                    <div class="company-logo-large">
                        <i class="fas fa-building fa-3x text-primary"></i>
                    </div>
                    <div>
                        <h1 class="mb-0">{{ $company->company_name ?? 'N/A' }}</h1>
                        <p class="mb-0">
                            @if($company->status === 'active')
                                <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Active</span>
                            @elseif($company->status === 'pending')
                                <span class="badge bg-warning text-dark"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Pending</span>
                            @else
                                <span class="badge bg-danger"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Inactive</span>
                            @endif
                            <span class="text-muted ms-2">{{ $company->company_registration_no ?? 'N/A' }}</span>
                            <span class="text-muted ms-2">|</span>
                            <span class="text-muted ms-2">Founded: {{ $company->company_founded_date ? \Carbon\Carbon::parse($company->company_founded_date)->format('M d, Y') : 'N/A' }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('companies.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Companies', 'Edit')))
                    <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endif
                <a href="mailto:{{ $company->company_email }}" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Send Email
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Contacts</h3>
                        <p class="stat-number">{{ $contactsCount }}</p>
                        <a href="{{ route('contacts.all') }}" class="text-primary" style="font-size: 12px;">View all contacts</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Deals</h3>
                        <p class="stat-number">{{ $dealsCount }}</p>
                        <a href="{{ route('deals.pipeline') }}" class="text-primary" style="font-size: 12px;">View pipeline</a>
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
                        <p class="stat-number">{{ $openTasksCount }}</p>
                        <a href="{{ route('tasks.all') }}" class="text-primary" style="font-size: 12px;">View tasks</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Communications</h3>
                        <p class="stat-number">{{ $communicationsCount }}</p>
                        <a href="{{ route('communications.activity-log') }}" class="text-primary" style="font-size: 12px;">View activity</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Company Details -->
            <div class="col-lg-8">
                <!-- Company Information -->
                <!-- Company Information -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="fw-semibold mb-0">
            <i class="fas fa-info-circle text-primary me-2"></i>
            Company Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="text-muted small fw-medium">Company Name</label>
                <p class="fw-semibold">{{ $company->company_name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">Registration No.</label>
                <p class="fw-semibold">{{ $company->company_registration_no ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-globe me-1"></i> Website
                </label>
                <p>
                    @if($company->company_website)
                        <a href="{{ $company->company_website }}" target="_blank" class="text-primary">
                            {{ $company->company_website }}
                        </a>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-envelope me-1"></i> Email
                </label>
                <p>
                    @if($company->company_email)
                        <a href="mailto:{{ $company->company_email }}" class="text-primary">
                            {{ $company->company_email }}
                        </a>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-phone me-1"></i> Phone
                </label>
                <p>{{ $company->company_phone ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-industry me-1"></i> Industry
                </label>
                <p>{{ $company->company_industry ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-tag me-1"></i> Company Type
                </label>
                <p>{{ $company->company_type ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-users me-1"></i> Employee Count
                </label>
                <p>{{ $company->company_size ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-calendar-alt me-1"></i> Founded Date
                </label>
                <p>{{ $company->company_founded_date ? \Carbon\Carbon::parse($company->company_founded_date)->format('M d, Y') : 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-star me-1"></i> Rating
                </label>
                <p>
                    <span class="text-warning">
                        {{ $company->company_rating ?? 'No description available.' }}
                    </span>
                </p>
            </div>
            <div class="col-12">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-align-left me-1"></i> Description
                </label>
                <p class="mb-0">{{ $company->company_notes ?? 'No description available.' }}</p>
            </div>
        </div>
    </div>
</div>

                <!-- Address Information -->
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
                <label class="text-muted small fw-medium">
                    <i class="fas fa-road me-1"></i> Street Address
                </label>
                <p>{{ $company->company_address ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-city me-1"></i> City
                </label>
                <p>{{ $company->company_city ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-map-pin me-1"></i> State
                </label>
                <p>{{ $company->company_state ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-mailbox me-1"></i> Postal Code
                </label>
                <p>{{ $company->company_zip ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="text-muted small fw-medium">
                    <i class="fas fa-flag me-1"></i> Country
                </label>
                <p>{{ $company->company_country ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>

                <!-- Social Media -->
                <!-- Social Media -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="fw-semibold mb-0">
            <i class="fas fa-share-alt text-primary me-2"></i>
            Social Media
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            @php
                $socialMedia = json_decode($company->company_social ?? '{}', true);
            @endphp
            
            @if(isset($socialMedia['facebook']))
                <a href="{{ $socialMedia['facebook'] }}" target="_blank" class="btn btn-outline-primary">
                    <i class="fab fa-facebook"></i> Facebook
                </a>
            @endif
            
            @if(isset($socialMedia['twitter']))
                <a href="{{ $socialMedia['twitter'] }}" target="_blank" class="btn btn-outline-info">
                    <i class="fab fa-twitter"></i> Twitter
                </a>
            @endif
            
            @if(isset($socialMedia['linkedin']))
                <a href="{{ $socialMedia['linkedin'] }}" target="_blank" class="btn btn-outline-primary">
                    <i class="fab fa-linkedin"></i> LinkedIn
                </a>
            @endif
            
            @if(isset($socialMedia['instagram']))
                <a href="{{ $socialMedia['instagram'] }}" target="_blank" class="btn btn-outline-danger">
                    <i class="fab fa-instagram"></i> Instagram
                </a>
            @endif
            
            @if(isset($socialMedia['youtube']))
                <a href="{{ $socialMedia['youtube'] }}" target="_blank" class="btn btn-outline-danger">
                    <i class="fab fa-youtube"></i> YouTube
                </a>
            @endif
            
            @if(isset($socialMedia['github']))
                <a href="{{ $socialMedia['github'] }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="fab fa-github"></i> GitHub
                </a>
            @endif
            
            @if(empty($socialMedia))
                <p class="text-muted">No social media links available.</p>
            @endif
        </div>
    </div>
</div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-semibold mb-0">
                            <i class="fas fa-history text-primary me-2"></i>
                            Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            @forelse ($recentActivity as $event)
                                <div class="activity-item">
                                    <div class="activity-icon {{ $event['color'] }}">
                                        <i class="fas {{ $event['icon'] }}"></i>
                                    </div>
                                    <div class="activity-info">
                                        <p>{!! $event['text'] !!}</p>
                                        <span class="activity-time">{{ $event['time']->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No activity recorded for this company yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Additional Info -->
            <div class="col-lg-4">
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
                            @forelse ($tags as $tag)
                                <span class="badge bg-primary p-2">
                                    <i class="fas fa-tag me-1"></i>
                                    {{ $tag }}
                                </span>
                            @empty
                                <p class="text-muted mb-0">No tags assigned.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Assigned Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-user-tie text-primary me-2"></i>
                            Assignment
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small fw-medium">Company Owner</label>
                            <div class="d-flex align-items-center mt-1">
                                @if($company->company_owner)
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($company->company_owner) }}&background=4F46E5&color=fff"
                                         class="rounded-circle me-2" width="40" height="40">
                                    <p class="fw-semibold mb-0">{{ $company->company_owner }}</p>
                                @else
                                    <p class="text-muted mb-0">Unassigned</p>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="text-muted small fw-medium">Contacts at this Company</label>
                            <div class="d-flex align-items-center mt-1">
                                @forelse ($companyContacts as $person)
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($person->first_name . ' ' . $person->last_name) }}&background=10B981&color=fff"
                                         class="rounded-circle me-1" width="32" height="32" title="{{ $person->first_name }} {{ $person->last_name }}">
                                @empty
                                    <span class="text-muted small">No contacts yet.</span>
                                @endforelse
                                <a href="{{ route('contacts.add') }}" class="btn btn-sm btn-outline-secondary rounded-circle ms-1" style="width: 32px; height: 32px;" title="Add contact">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
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
                            <a href="{{ route('contacts.add') }}" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>
                                Add Contact
                            </a>
                            <a href="{{ route('deals.add') }}" class="btn btn-outline-success">
                                <i class="fas fa-file-invoice me-2"></i>
                                Create Deal
                            </a>
                            <a href="{{ route('communications.meetings') }}" class="btn btn-outline-info">
                                <i class="fas fa-calendar-plus me-2"></i>
                                Schedule Meeting
                            </a>
                            @if($company->company_email)
                                <a href="mailto:{{ $company->company_email }}" class="btn btn-outline-warning">
                                    <i class="fas fa-envelope me-2"></i>
                                    Send Email
                                </a>
                            @else
                                <a href="{{ route('communications.emails') }}" class="btn btn-outline-warning">
                                    <i class="fas fa-envelope me-2"></i>
                                    Log Email
                                </a>
                            @endif
                            <a href="{{ route('communications.calls') }}" class="btn btn-outline-danger">
                                <i class="fas fa-phone me-2"></i>
                                Log Call
                            </a>
                            <a href="{{ route('tasks.create') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-tasks me-2"></i>
                                Create Task
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>