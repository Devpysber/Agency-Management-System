<?php

use Livewire\Component;
use App\Models\Project;

new class extends Component
{
    public $project;

    public function mount($id)
    {
        $contact = auth()->user()->contact;
        $companyId = $contact?->company_id;

        $project = $companyId
            ? Project::with(['milestones', 'payments.gateway'])->where('company_id', $companyId)->find($id)
            : null;

        if (!$project) {
            abort(404);
        }

        $this->project = $project;
    }

    public function render()
    {
        $milestones = $this->project->milestones->sortBy('due_date')->values();
        $activeMilestoneId = optional($milestones->firstWhere('status', '!=', 'completed'))->id;

        $paidTotal = (float) $this->project->payments->where('status', 'paid')->sum('amount');
        $budget = (float) ($this->project->budget ?? 0);

        return $this->view([
            'milestones' => $milestones,
            'activeMilestoneId' => $activeMilestoneId,
            'paidTotal' => $paidTotal,
            'budgetPct' => $budget > 0 ? min(100, (int) round($paidTotal / $budget * 100)) : 0,
            'dueAtIso' => $this->project->submission_due_at?->toIso8601String(),
        ])->layout('layouts.client');
    }
};
?>

@php
    $badgeMap = ['bg-secondary' => 's-secondary', 'bg-primary' => 's-primary', 'bg-success' => 's-success',
        'bg-warning text-dark' => 's-warning', 'bg-warning' => 's-warning', 'bg-danger' => 's-danger', 'bg-info' => 's-info'];
    $toBadge = fn ($c) => $badgeMap[$c] ?? 's-secondary';
@endphp

<div>
    <div class="cp-detail-head">
        <div>
            <a href="{{ route('client.projects') }}" wire:navigate class="cp-back"><i class="fas fa-arrow-left"></i> Projects</a>
            <h1>{{ $project->name }}</h1>
            <div style="margin-top:8px;">
                <span class="cp-badge {{ $toBadge($project->status_badge['class']) }}">
                    {{ $project->status_badge['icon'] }} {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                </span>
            </div>
        </div>
    </div>

    @if ($dueAtIso)
        <div class="cp-deadline {{ $project->is_overdue ? 'is-overdue' : '' }}" x-data="cpCountdown('{{ $dueAtIso }}')" x-init="start()">
            <div class="cp-deadline-icon"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <span class="cp-deadline-label">Submission deadline set by your account manager</span>
                <span class="cp-deadline-time" x-text="label"></span>
            </div>
            <span class="cp-deadline-date">{{ $project->submission_due_at->format('M d, Y · H:i') }}</span>
        </div>
    @endif

    <div class="cp-stat-strip">
        <div class="cp-stat">
            <span class="cp-stat-label">Progress</span>
            <span class="cp-stat-value" x-data x-init="cpCountUp($el, {{ (int) $project->progress }})">{{ (int) $project->progress }}%</span>
            <span class="cp-stat-sub">
                {{ optional($project->start_date)->format('M Y') ?? '—' }} – {{ optional($project->end_date)->format('M Y') ?? '—' }}
            </span>
        </div>
        <div class="cp-stat">
            <span class="cp-stat-label">Budget</span>
            <span class="cp-stat-value">{{ $project->budget !== null ? \App\Support\Money::client($project->budget) : '—' }}</span>
            <span class="cp-stat-sub">@money($paidTotal) paid ({{ $budgetPct }}%)</span>
        </div>
        <div class="cp-stat">
            <span class="cp-stat-label">Milestones</span>
            <span class="cp-stat-value">{{ $milestones->count() }}</span>
            <span class="cp-stat-sub">{{ $milestones->where('status', 'completed')->count() }} completed</span>
        </div>
        <div class="cp-stat">
            <span class="cp-stat-label">Payments</span>
            <span class="cp-stat-value">{{ $project->payments->count() }}</span>
            <span class="cp-stat-sub">{{ $project->payments->where('status', 'pending')->count() }} pending</span>
        </div>
    </div>

    <div class="cp-card" style="margin-bottom:20px;">
        <div class="cp-card-head"><h3><i class="fas fa-circle-info"></i> Project Details</h3></div>
        <div class="cp-card-body">
            <dl class="cp-dl">
                <div><dt>Start Date</dt><dd>{{ optional($project->start_date)->format('M d, Y') ?? 'Not set' }}</dd></div>
                <div><dt>End Date</dt><dd>{{ optional($project->end_date)->format('M d, Y') ?? 'Not set' }}</dd></div>
                <div class="span-2">
                    <dt>Completion</dt>
                    <dd>
                        <div class="cp-progress-row" style="max-width:360px;">
                            <div class="cp-progress"><span style="width:{{ (int) $project->progress }}%"></span></div>
                            <small>{{ (int) $project->progress }}%</small>
                        </div>
                    </dd>
                </div>
                <div class="span-2"><dt>Description</dt><dd>{{ $project->description ?: 'No description available.' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="cp-grid split-6-6">
        <div class="cp-card">
            <div class="cp-card-head"><h3><i class="fas fa-flag-checkered"></i> Milestones</h3></div>
            <div class="cp-card-body">
                @if ($milestones->count())
                    <div class="cp-timeline">
                        @foreach ($milestones as $ms)
                            @php
                                $state = $ms->status === 'completed' ? 'done'
                                    : ($ms->id === $activeMilestoneId ? 'active' : '');
                                $overdue = $ms->status !== 'completed' && $ms->due_date && $ms->due_date->isPast();
                            @endphp
                            <div class="cp-tl-item">
                                <span class="cp-tl-dot {{ $state }}">
                                    <i class="fas {{ $state === 'done' ? 'fa-check' : 'fa-circle' }}"></i>
                                </span>
                                <div class="cp-tl-title">{{ $ms->title }}</div>
                                <div class="cp-tl-meta">
                                    <span class="cp-badge {{ $toBadge($ms->status_badge['class']) }}" style="margin-right:6px;">
                                        {{ ucfirst(str_replace('_', ' ', $ms->status)) }}
                                    </span>
                                    @if ($ms->status === 'completed')
                                        @if ($ms->completed_at) on {{ $ms->completed_at->format('M d, Y') }} @endif
                                    @elseif ($ms->due_date)
                                        {{ $overdue ? 'Overdue — was due' : 'Due' }} {{ $ms->due_date->format('M d, Y') }}
                                    @else
                                        No due date
                                    @endif
                                </div>
                                @if ($ms->description)
                                    <div class="cp-tl-meta" style="margin-top:4px;">{{ $ms->description }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="cp-empty"><i class="fas fa-flag-checkered"></i><h6>No milestones yet</h6></div>
                @endif
            </div>
        </div>

        <div class="cp-card">
            <div class="cp-card-head"><h3><i class="fas fa-credit-card"></i> Payments</h3></div>
            <div class="cp-card-body flush">
                <div class="cp-table-wrap">
                    <table class="cp-table">
                        <thead><tr><th>Amount</th><th>Gateway</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse ($project->payments->sortByDesc('created_at') as $payment)
                                <tr wire:key="ppay-{{ $payment->id }}">
                                    <td class="t-strong">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="t-muted">{{ $payment->gateway->name ?? '—' }}</td>
                                    <td>
                                        <span class="cp-badge {{ $toBadge($payment->status_badge['class']) }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="t-muted">{{ optional($payment->paid_at ?? $payment->created_at)->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="cp-empty"><i class="fas fa-credit-card"></i><h6>No payments recorded</h6></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="cp-card mt-20" style="padding:0;overflow:visible;background:transparent;border:0;box-shadow:none;">
        <livewire:project-chat :project="$project" :key="'chat-'.$project->id" />
    </div>
</div>
