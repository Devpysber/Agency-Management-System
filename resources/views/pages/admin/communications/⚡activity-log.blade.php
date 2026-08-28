<?php

use Livewire\Component;
use App\Models\ActivityLog;

new class extends Component
{
    public $logs;
    public $search = '';
    public $filterLogName = '';

    public function mount()
    {
        $this->fetchLogs();
    }

    public function fetchLogs()
    {
        $query = ActivityLog::with('causer');

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        if (!empty($this->filterLogName)) {
            $query->logName($this->filterLogName);
        }

        $this->logs = $query->orderBy('created_at', 'desc')->limit(100)->get();
    }

    public function updatedSearch() { $this->fetchLogs(); }
    public function updatedFilterLogName() { $this->fetchLogs(); }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterLogName = '';
        $this->fetchLogs();
    }

    public function render()
    {
        return $this->view([
            'logNames' => ActivityLog::query()->distinct()->pluck('log_name')->filter()->values(),
            'todayCount' => ActivityLog::whereDate('created_at', now()->toDateString())->count(),
            'weekCount' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Activity Log</h1>
                <p>A running timeline of everything happening across the system.</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-list"></i></div>
                    <div class="stat-info"><h3>Showing</h3><p class="stat-number">{{ $logs->count() }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-info"><h3>Today</h3><p class="stat-number">{{ $todayCount }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-week"></i></div>
                    <div class="stat-info"><h3>This Week</h3><p class="stat-number">{{ $weekCount }}</p></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Search</label>
                        <input type="text" class="form-control" wire:model.live="search" placeholder="Search activity...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Category</label>
                        <select class="form-select" wire:model.live="filterLogName">
                            <option value="">All Categories</option>
                            @foreach ($logNames as $name)
                                <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-stream me-2"></i> Recent Activity</h3>
                <span class="badge bg-primary">Last 100</span>
            </div>
            <div class="card-body">
                @forelse ($logs as $log)
                    <div class="d-flex align-items-start py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width:40px;height:40px;flex-shrink:0;">
                            <i class="fas {{ $log->log_icon }} text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1">
                                <strong>{{ $log->causer_name ?? 'System' }}</strong>
                                {{ $log->description }}
                            </p>
                            <small class="text-muted">
                                @if($log->log_name)
                                    <span class="badge bg-secondary me-1">{{ ucfirst($log->log_name) }}</span>
                                @endif
                                {{ $log->created_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="fas fa-stream fa-3x text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">No activity recorded</h5>
                        <p class="text-muted">Activity will appear here as the system is used.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
