<?php

use Livewire\Component;
use App\Models\Contact;

new class extends Component
{
    public $groups = [];
    public $totalContacts = 0;

    public function mount()
    {
        $this->fetchGroups();
    }

    public function fetchGroups()
    {
        $this->totalContacts = Contact::count();

        $labels = [
            'new' => ['label' => 'New', 'icon' => '🟢', 'color' => 'blue'],
            'contacted' => ['label' => 'Contacted', 'icon' => '🟡', 'color' => 'orange'],
            'qualified' => ['label' => 'Qualified', 'icon' => '🔵', 'color' => 'purple'],
            'lost' => ['label' => 'Lost', 'icon' => '🔴', 'color' => 'red'],
            'customer' => ['label' => 'Customer', 'icon' => '✅', 'color' => 'green'],
        ];

        $groups = [];

        $counts = Contact::selectRaw('lead_status, count(*) as total')
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status');

        foreach ($counts as $leadStatus => $total) {
            $key = $leadStatus ?: 'uncategorized';
            $meta = $labels[$key] ?? ['label' => ucfirst($key), 'icon' => '⚪', 'color' => 'blue'];

            $groups[] = [
                'key' => $leadStatus,
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'count' => $total,
                'contacts' => Contact::when($leadStatus, function ($q) use ($leadStatus) {
                        $q->where('lead_status', $leadStatus);
                    }, function ($q) {
                        $q->whereNull('lead_status');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'first_name', 'last_name']),
            ];
        }

        // Sort groups by count desc for a nicer display
        usort($groups, fn($a, $b) => $b['count'] <=> $a['count']);

        $this->groups = $groups;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Contact Groups</h1>
                <p>Contacts clustered by lead status for quick access.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('contacts.all') }}" class="btn btn-secondary">
                    <i class="fas fa-address-book"></i> All Contacts
                </a>
                <a href="{{ route('contacts.add') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Contact
                </a>
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

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Contacts</h3>
                        <p class="stat-number">{{ $totalContacts }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Groups</h3>
                        <p class="stat-number">{{ count($groups) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Largest Group</h3>
                        <p class="stat-number">{{ $groups[0]['count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Avg Group Size</h3>
                        <p class="stat-number">{{ count($groups) ? round($totalContacts / count($groups), 1) : 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Groups -->
        <div class="row g-3">
            @forelse ($groups as $group)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                <span>{{ $group['icon'] }}</span>
                                {{ $group['label'] }}
                            </h3>
                            <span class="badge bg-primary">{{ $group['count'] }}</span>
                        </div>
                        <div class="card-body">
                            @forelse ($group['contacts'] as $contact)
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user-circle text-muted me-2"></i>
                                    <span>{{ trim($contact->first_name . ' ' . $contact->last_name) ?: 'Unnamed Contact' }}</span>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No contacts in this group.</p>
                            @endforelse

                            @if ($group['count'] > 5)
                                <p class="text-muted small mb-0">+ {{ $group['count'] - 5 }} more</p>
                            @endif
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('contacts.all') }}" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="fas fa-eye"></i> View in All Contacts
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-layer-group fa-3x text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No contact groups yet</h5>
                            <p class="text-muted">Add contacts and set a lead status to see them grouped here.</p>
                            <a href="{{ route('contacts.add') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Contact
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
