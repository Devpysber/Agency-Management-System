<div class="ab" wire:poll.20s
     x-data="{ open: false }"
     @keydown.escape.window="open = false">

    @php
        $unread = $this->unread;
        $items = $this->items;
        $viewAll = $variant === 'client' && \Illuminate\Support\Facades\Route::has('client.updates');
    @endphp

    <span id="alert-bell-marker"
          data-variant="{{ $variant }}"
          data-newest="{{ $items->first()?->id ?? 0 }}"
          data-latest="{{ $items->first() ? \Illuminate\Support\Str::limit($items->first()->title, 80) : '' }}"
          style="display:none;"></span>

    <button type="button" class="ab-btn" @click="open = !open" aria-label="Notifications">
        <i class="fas fa-bell" :class="{ 'ab-ring': {{ $unread > 0 ? 'true' : 'false' }} }"></i>
        @if ($unread > 0)
            <span class="ab-badge">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>

    <div class="ab-panel" x-show="open" x-transition.opacity @click.outside="open = false" x-cloak>
        <div class="ab-head">
            <strong>Notifications</strong>
            @if ($unread > 0)
                <button type="button" class="ab-link" wire:click="markAllRead">Mark all read</button>
            @endif
        </div>

        <div class="ab-list">
            @forelse ($items as $a)
                <a href="{{ $a->url ?: '#' }}"
                   @if ($a->url) wire:navigate @endif
                   wire:click="markRead({{ $a->id }})"
                   class="ab-item {{ $a->read_at ? '' : 'is-unread' }}">
                    <span class="ab-ic ab-{{ $a->level }}"><i class="fas {{ $a->icon }}"></i></span>
                    <span class="ab-body">
                        <span class="ab-title">{{ $a->title }}</span>
                        @if ($a->body)<span class="ab-sub">{{ $a->body }}</span>@endif
                        <span class="ab-meta">
                            {{ $a->created_at?->diffForHumans() }}
                            @if ($a->actor) · {{ $a->actor->name }} @endif
                        </span>
                    </span>
                </a>
            @empty
                <div class="ab-empty"><i class="fas fa-bell-slash"></i><span>You're all caught up</span></div>
            @endforelse
        </div>

        @if ($viewAll)
            <a href="{{ route('client.updates') }}" wire:navigate class="ab-foot">View all updates</a>
        @endif
    </div>

    <style>
        .ab { position: relative; }
        .ab-btn { position: relative; width: 40px; height: 40px; border: 0; border-radius: 12px;
            background: var(--cp-surface-2, #f1f3f9); color: var(--cp-text, #1f2937);
            cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .ab-btn:hover { filter: brightness(.97); }
        .ab-ring { animation: ab-ring 2.6s ease-in-out infinite; transform-origin: 50% 0; }
        @keyframes ab-ring { 0%,70%,100%{transform:rotate(0)} 74%{transform:rotate(14deg)} 79%{transform:rotate(-11deg)} 84%{transform:rotate(7deg)} 89%{transform:rotate(-4deg)} }
        .ab-badge { position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 5px;
            background: #ef4444; color: #fff; border-radius: 999px; font-size: 11px; font-weight: 800;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px var(--cp-bg, #fff); }
        .ab-panel { position: absolute; right: 0; top: calc(100% + 10px); width: 360px; max-width: calc(100vw - 32px);
            background: var(--cp-bg, #fff); color: var(--cp-text, #1f2937);
            border: 1px solid var(--cp-border, #e5e7eb); border-radius: 16px; z-index: 1400;
            box-shadow: 0 24px 60px rgba(16,24,40,.22); overflow: hidden; }
        .ab-head { display: flex; align-items: center; justify-content: space-between; padding: 13px 16px;
            border-bottom: 1px solid var(--cp-border, #eef0f3); font-size: 14px; }
        .ab-link { border: 0; background: none; color: #4f46e5; font-size: 12px; font-weight: 700; cursor: pointer; }
        .ab-list { max-height: 60vh; overflow-y: auto; }
        .ab-item { display: flex; gap: 11px; padding: 12px 16px; text-decoration: none; color: inherit;
            border-bottom: 1px solid var(--cp-border, #f3f4f6); transition: background .12s; }
        .ab-item:hover { background: var(--cp-surface-2, #f8fafc); }
        .ab-item.is-unread { background: color-mix(in srgb, #4f46e5 7%, transparent); }
        .ab-ic { width: 34px; height: 34px; flex-shrink: 0; border-radius: 10px; display: flex;
            align-items: center; justify-content: center; font-size: 13px; background: #eef2ff; color: #4f46e5; }
        .ab-ic.ab-success { background: #ecfdf5; color: #059669; }
        .ab-ic.ab-warning { background: #fffbeb; color: #d97706; }
        .ab-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .ab-title { font-weight: 650; font-size: 13px; line-height: 1.3; }
        .ab-sub { font-size: 12px; color: var(--cp-text-faint, #6b7280); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ab-meta { font-size: 11px; color: var(--cp-text-faint, #9ca3af); margin-top: 2px; }
        .ab-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 34px 16px;
            color: var(--cp-text-faint, #9ca3af); font-size: 13px; }
        .ab-empty i { font-size: 22px; }
        .ab-foot { display: block; text-align: center; padding: 11px; font-size: 12px; font-weight: 700;
            color: #4f46e5; text-decoration: none; border-top: 1px solid var(--cp-border, #eef0f3); }
        .ab-foot:hover { background: var(--cp-surface-2, #f8fafc); }
        [x-cloak] { display: none !important; }
        @media (prefers-reduced-motion: reduce) { .ab-ring, .ab-badge { animation: none !important; } }
    </style>

    @script
    <script>
        (function () {
            if (window.__abWatch) return;
            window.__abWatch = true;
            let seen = null;
            setInterval(function () {
                var m = document.getElementById('alert-bell-marker');
                if (!m) return;
                var newest = Number(m.dataset.newest || 0);
                if (seen === null) { seen = newest; return; }
                if (newest > seen) {
                    var msg = m.dataset.latest || 'New notification';
                    if (m.dataset.variant === 'client' && window.clientPortal) {
                        window.clientPortal.toast('🔔 ' + msg, 'info');
                    } else if (window.showToast) {
                        window.showToast('🔔 ' + msg, 'info');
                    }
                    seen = newest;
                }
            }, 6000);
        })();
    </script>
    @endscript
</div>
