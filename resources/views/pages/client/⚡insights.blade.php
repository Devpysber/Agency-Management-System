<?php

use Livewire\Component;
use App\Models\AccountInsight;
use App\Services\AccountInsights;

new class extends Component
{
    public $company;
    public $companyId;
    public ?string $error = null;

    public function mount()
    {
        $contact = auth()->user()->contact;
        $this->company = $contact?->company;
        $this->companyId = $this->company?->id;
    }

    public function generate(AccountInsights $service, bool $force = false): void
    {
        $this->error = null;

        if (! $this->company) {
            $this->error = 'No company is linked to your account.';
            return;
        }
        if (! $service->configured()) {
            $this->error = 'AI analysis is not configured yet.';
            return;
        }

        try {
            $service->forCompany($this->company, $force);
            $this->dispatch('cp-toast', message: 'Analysis ready', type: 'success');
        } catch (\Throwable $e) {
            report($e);
            $this->error = $e->getMessage();
        }
    }

    public function refresh(AccountInsights $service): void
    {
        $this->generate($service, true);
    }

    public function render(AccountInsights $service)
    {
        $latest = $this->companyId
            ? AccountInsight::where('company_id', $this->companyId)->latest()->first()
            : null;

        $history = $this->companyId
            ? AccountInsight::where('company_id', $this->companyId)->latest()->limit(10)->get()
            : collect();

        return $this->view([
            'configured' => $service->configured(),
            'latest' => $latest,
            'history' => $history,
            'stale' => $this->company ? $service->isStale($this->company, $latest) : false,
        ])->layout('layouts.client');
    }
};
?>

@php
    $sentIcon = ['positive' => 'fa-arrow-trend-up', 'neutral' => 'fa-circle-info', 'watch' => 'fa-triangle-exclamation'];
@endphp

<div>
    <div class="cp-page-head">
        <div>
            <h1>AI Insights</h1>
            <p>An AI-written analysis of {{ $company->company_name ?? 'your account' }} — delivery, finances and risks.</p>
        </div>
        @if ($company && $configured)
            <button class="cp-btn cp-btn-primary" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh,generate">
                <span wire:loading.remove wire:target="refresh,generate"><i class="fas fa-rotate"></i> Regenerate</span>
                <span wire:loading wire:target="refresh,generate"><span class="cp-spin"></span> Analyzing…</span>
            </button>
        @endif
    </div>

    @if (!$company)
        <div class="cp-alert a-warning">
            <i class="fas fa-circle-info"></i> No company is linked to your account yet.
        </div>
    @elseif ($error)
        <div class="cp-alert a-error">
            <i class="fas fa-circle-exclamation"></i> {{ $error }}
            <button onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
        </div>
    @endif

    @if ($company && !$configured)
        <div class="cp-ai-card">
            <div class="cp-ai-setup">
                <i class="fas fa-wand-magic-sparkles"></i>
                <h4>AI analysis isn't configured yet</h4>
                <p>
                    Add an <code>OPENROUTER_API_KEY</code> (or <code>ANTHROPIC_API_KEY</code>) to the
                    application's <code>.env</code> file and run <code>php artisan config:clear</code>.
                    This page will then generate a plain-English briefing of your projects,
                    payments and open documents.
                </p>
            </div>
        </div>
    @elseif ($company)
        <div class="cp-grid split-7-5">
            <div>
                @if (!$latest)
                    <div class="cp-ai-card">
                        <div class="cp-ai-setup">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            <h4>No analysis yet</h4>
                            <p>Generate your first AI briefing from the current state of your account.</p>
                            <div style="margin-top:16px;">
                                <button class="cp-btn cp-btn-primary" wire:click="generate" wire:loading.attr="disabled" wire:target="generate,refresh">
                                    <span wire:loading.remove wire:target="generate,refresh"><i class="fas fa-wand-magic-sparkles"></i> Generate analysis</span>
                                    <span wire:loading wire:target="generate,refresh"><span class="cp-spin"></span> Analyzing…</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    @if ($stale)
                        <div class="cp-alert a-warning">
                            <i class="fas fa-triangle-exclamation"></i>
                            Your account data has changed since this analysis was generated. Click <strong>Regenerate</strong> for an up-to-date briefing.
                        </div>
                    @endif
                    <div class="cp-ai-card">
                        <div class="cp-ai-head">
                            <span class="cp-ai-badge"><i class="fas fa-wand-magic-sparkles"></i> AI analysis</span>
                            @if ($stale)<span class="cp-badge s-warning" style="margin-left:8px;">Outdated</span>@endif
                        </div>
                        <div class="cp-ai-body">
                            <div class="cp-ai-headline">{{ $latest->headline }}</div>
                            <div class="cp-ai-summary">{{ $latest->summary }}</div>

                            <div class="cp-ai-sections">
                                @foreach ($latest->sections as $section)
                                    <div class="cp-ai-section s-{{ $section['sentiment'] ?? 'neutral' }}">
                                        <h4>
                                            <i class="fas {{ $sentIcon[$section['sentiment'] ?? 'neutral'] ?? 'fa-circle-info' }}"></i>
                                            {{ $section['title'] }}
                                        </h4>
                                        <p>{{ $section['body'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="cp-ai-meta">
                                <span><i class="fas fa-clock"></i> {{ $latest->created_at->diffForHumans() }}</span>
                                @if ($latest->generated_by)<span><i class="fas fa-user"></i> {{ $latest->generated_by }}</span>@endif
                            </div>
                        </div>
                    </div>

                    <p class="cp-help" style="margin-top:12px;">
                        AI-generated from your account data. It can be wrong — verify important figures against the
                        Projects and Payments pages. Not legal or tax advice.
                    </p>
                @endif
            </div>

            <div>
                <div class="cp-card">
                    <div class="cp-card-head"><h3><i class="fas fa-clock-rotate-left"></i> History</h3></div>
                    <div class="cp-card-body">
                        @forelse ($history as $item)
                            <div class="cp-ai-history-item">
                                <strong>{{ $item->headline }}</strong>
                                <span>{{ $item->created_at->format('M d, Y H:i') }}
                                    @if ($item->generated_by) · {{ $item->generated_by }} @endif
                                </span>
                            </div>
                        @empty
                            <div class="cp-empty"><i class="fas fa-clock-rotate-left"></i><h6>No history yet</h6></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
