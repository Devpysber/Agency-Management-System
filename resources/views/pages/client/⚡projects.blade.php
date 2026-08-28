<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Project;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $sort = 'created_at';
    #[Url] public string $dir = 'desc';
    public int $perPage = 10;

    public $company;
    public $companyId;

    public function mount()
    {
        $contact = auth()->user()->contact;
        $this->company = $contact?->company;
        $this->companyId = $this->company?->id;
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    public function render()
    {
        $sortable = ['name', 'status', 'progress', 'start_date', 'end_date', 'budget', 'created_at'];
        $sort = in_array($this->sort, $sortable, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $projects = Project::where('company_id', $this->companyId ?: 0)
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy($sort, $dir)
            ->paginate($this->perPage);

        $summary = [
            'total' => Project::where('company_id', $this->companyId ?: 0)->count(),
            'active' => Project::where('company_id', $this->companyId ?: 0)->whereIn('status', ['planning', 'in_progress'])->count(),
            'completed' => Project::where('company_id', $this->companyId ?: 0)->where('status', 'completed')->count(),
        ];

        return $this->view(compact('projects', 'summary'))->layout('layouts.client');
    }
};
?>

@php
    $badgeMap = ['bg-secondary' => 's-secondary', 'bg-primary' => 's-primary', 'bg-success' => 's-success',
        'bg-warning text-dark' => 's-warning', 'bg-warning' => 's-warning', 'bg-danger' => 's-danger', 'bg-info' => 's-info'];
    $sortIcon = fn ($f) => $sort !== $f ? 'fa-sort' : ($dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
@endphp

<div wire:poll.45s>
    <div class="cp-page-head">
        <div>
            <h1>Projects</h1>
            <p>All projects for {{ $company->company_name ?? 'your company' }}. <span class="cp-live">Live</span></p>
        </div>
    </div>

    @if (!$company)
        <div class="cp-alert a-warning">
            <i class="fas fa-circle-info"></i> No company is linked to your account yet. Please contact your account manager.
        </div>
    @else

    <div class="cp-grid cols-3" style="margin-bottom:20px;">
        <div class="cp-kpi"><div class="cp-kpi-label">Total Projects</div><div class="cp-kpi-value">{{ $summary['total'] }}</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Active</div><div class="cp-kpi-value">{{ $summary['active'] }}</div></div>
        <div class="cp-kpi"><div class="cp-kpi-label">Completed</div><div class="cp-kpi-value">{{ $summary['completed'] }}</div></div>
    </div>

    <div class="cp-card">
        <div class="cp-card-head" style="flex-wrap:wrap;">
            <h3><i class="fas fa-diagram-project"></i> {{ $projects->total() }} {{ Str::plural('project', $projects->total()) }}</h3>
            <div class="cp-toolbar">
                <div class="cp-field">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" wire:model.live.debounce.350ms="search" placeholder="Search projects…">
                </div>
                <select class="cp-select" wire:model.live="status">
                    <option value="">All statuses</option>
                    <option value="planning">Planning</option>
                    <option value="in_progress">In progress</option>
                    <option value="on_hold">On hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select class="cp-select" wire:model.live="perPage">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
                @if ($search !== '' || $status !== '')
                    <button class="cp-btn cp-btn-ghost cp-btn-sm" wire:click="clearFilters">
                        <i class="fas fa-xmark"></i> Clear
                    </button>
                @endif
            </div>
        </div>

        <div class="cp-card-body flush">
            <div class="cp-table-wrap">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th class="sortable" wire:click="sortBy('name')">Project <i class="fas {{ $sortIcon('name') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('status')">Status <i class="fas {{ $sortIcon('status') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('progress')">Progress <i class="fas {{ $sortIcon('progress') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('start_date')">Start <i class="fas {{ $sortIcon('start_date') }}"></i></th>
                            <th class="sortable" wire:click="sortBy('end_date')">End <i class="fas {{ $sortIcon('end_date') }}"></i></th>
                            <th class="sortable t-right" wire:click="sortBy('budget')">Budget <i class="fas {{ $sortIcon('budget') }}"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr class="clickable" wire:key="proj-{{ $project->id }}"
                                onclick="Livewire.navigate('{{ route('client.project-show', $project->id) }}')">
                                <td class="t-strong">{{ $project->name }}</td>
                                <td>
                                    <span class="cp-badge {{ $badgeMap[$project->status_badge['class']] ?? 's-secondary' }}">
                                        {{ $project->status_badge['icon'] }} {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="cp-progress-row">
                                        <div class="cp-progress"><span style="width:{{ (int) $project->progress }}%"></span></div>
                                        <small>{{ (int) $project->progress }}%</small>
                                    </div>
                                </td>
                                <td class="t-muted">{{ optional($project->start_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="t-muted">{{ optional($project->end_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="t-right">{{ $project->budget !== null ? \App\Support\Money::client($project->budget) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="cp-empty">
                                        <i class="fas fa-diagram-project"></i>
                                        <h6>No projects found</h6>
                                        <p>{{ $search !== '' || $status !== '' ? 'Try adjusting your filters.' : 'Projects will appear here once created.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('partials.cp-pagination', ['paginator' => $projects])
        </div>
    </div>
    @endif
</div>
