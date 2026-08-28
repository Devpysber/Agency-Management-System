<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserAlert;

new class extends Component
{
    use WithPagination;

    public string $filter = 'all'; // all | unread

    public function markRead(int $id): void
    {
        UserAlert::where('user_id', auth()->id())->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        UserAlert::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        $this->dispatch('cp-toast', message: 'All updates marked read', type: 'success');
    }

    public function setFilter(string $f): void
    {
        $this->filter = $f;
        $this->resetPage();
    }

    public function render()
    {
        $q = UserAlert::with('actor:id,name')
            ->where('user_id', auth()->id())
            ->when($this->filter === 'unread', fn ($x) => $x->whereNull('read_at'))
            ->latest();

        return $this->view([
            'alerts' => $q->paginate(20),
            'unreadCount' => UserAlert::where('user_id', auth()->id())->whereNull('read_at')->count(),
        ])->layout('layouts.client');
    }
};
?>
<div>
    <div class="cp-page-head" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <h1 style="margin:0;">Updates</h1>
            <p style="margin:4px 0 0;color:var(--cp-text-faint);">Everything your account manager and delivery team changed — newest first.</p>
        </div>
        @if ($unreadCount > 0)
            <button class="cp-btn" wire:click="markAllRead">
                <i class="fas fa-check-double"></i> Mark all read ({{ $unreadCount }})
            </button>
        @endif
    </div>

    <div class="cp-card">
        <div class="cp-card-head" style="gap:8px;">
            <button class="cp-btn {{ $filter === 'all' ? '' : 'cp-btn-ghost' }} cp-btn-sm" wire:click="setFilter('all')">All</button>
            <button class="cp-btn {{ $filter === 'unread' ? '' : 'cp-btn-ghost' }} cp-btn-sm" wire:click="setFilter('unread')">Unread</button>
        </div>
        <div class="cp-card-body">
            <div class="cp-feed">
                @forelse ($alerts as $a)
                    <a href="{{ $a->url ?: '#' }}" @if ($a->url) wire:navigate @endif
                       wire:click="markRead({{ $a->id }})"
                       class="cp-feed-item" style="text-decoration:none;{{ $a->read_at ? '' : 'background:color-mix(in srgb,var(--cp-primary) 7%,transparent);' }}">
                        @php $tone = ['success' => 'var(--cp-success)', 'warning' => 'var(--cp-warning,#d97706)'][$a->level] ?? 'var(--cp-primary)'; @endphp
                        <span class="cp-feed-icon" style="background:color-mix(in srgb,{{ $tone }} 14%,transparent);color:{{ $tone }};">
                            <i class="fas {{ $a->icon }}"></i>
                        </span>
                        <div class="cp-feed-body" style="flex:1;">
                            <p class="t-strong">{{ $a->title }}</p>
                            @if ($a->body)<span class="d-block" style="color:var(--cp-text-faint);">{{ $a->body }}</span>@endif
                            <span>{{ $a->created_at?->diffForHumans() }}@if ($a->actor) · {{ $a->actor->name }} @endif</span>
                        </div>
                        @unless ($a->read_at)<span style="width:8px;height:8px;border-radius:50%;background:var(--cp-primary);flex-shrink:0;margin-top:6px;"></span>@endunless
                    </a>
                @empty
                    <div class="cp-empty"><i class="fas fa-bell-slash"></i><h6>Nothing here yet</h6>
                        <p>New changes to your projects, payments and documents show up here.</p>
                    </div>
                @endforelse
            </div>

            @if ($alerts->hasPages())
                {{ $alerts->links('partials.cp-pagination') }}
            @endif
        </div>
    </div>
</div>
