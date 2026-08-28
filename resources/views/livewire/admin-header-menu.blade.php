<div class="ahm" wire:poll.30s>
    @php $alerts = $this->alerts; $msgs = $this->messages; $u = auth()->user();
        $aUnread = $this->alertsUnread; $mUnread = $this->messagesUnread; @endphp

    <span id="ahm-marker" data-newest="{{ $alerts->first()['at']?->timestamp ?? 0 }}"
          data-latest="{{ \Illuminate\Support\Str::limit($alerts->first()['text'] ?? '', 80) }}" style="display:none;"></span>
    @script
    <script>
        (function () {
            if (window.__ahmWatch) return;
            window.__ahmWatch = true;
            let seen = null;
            setInterval(function () {
                var m = document.getElementById('ahm-marker');
                if (!m) return;
                var n = Number(m.dataset.newest || 0);
                if (seen === null) { seen = n; return; }
                if (n > seen && window.showToast) { window.showToast('🔔 ' + (m.dataset.latest || 'New notification'), 'info'); seen = n; }
            }, 6000);
        })();
    </script>
    @endscript
    <style>
        .ahm { display: flex; align-items: center; gap: 6px; }
        .ahm-wrap { position: relative; }
        .ahm-btn {
            position: relative; width: 40px; height: 40px; border-radius: 11px;
            border: 1px solid #e5e7eb; background: #fff; color: #6b7280;
            display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px;
            transition: background .15s, color .15s, border-color .15s;
        }
        .ahm-btn:hover { background: #f3f4f6; color: #1f2937; border-color: #d1d5db; }
        .ahm-badge {
            position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px;
            background: #ef4444; color: #fff; border-radius: 999px; font-size: 10.5px; font-weight: 800;
            display: flex; align-items: center; justify-content: center; padding: 0 4px; box-shadow: 0 0 0 2px #fff;
        }
        .ahm-menu {
            position: absolute; top: calc(100% + 8px); right: 0; width: 320px;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            box-shadow: 0 18px 44px rgba(16,24,40,.16); z-index: 1300;
            opacity: 0; transform: translateY(-8px); pointer-events: none;
            transition: opacity .16s ease, transform .16s ease;
        }
        .ahm-wrap.open .ahm-menu { opacity: 1; transform: none; pointer-events: auto; }
        .ahm-mh { padding: 12px 14px; border-bottom: 1px solid #f0f0f3; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 7px; }
        .ahm-list { max-height: 340px; overflow-y: auto; }
        .ahm-item { display: flex; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #f5f5f7; text-decoration: none; color: #1f2937; }
        .ahm-item:last-child { border-bottom: 0; }
        .ahm-item:hover { background: #f9fafb; }
        .ahm-ic { width: 30px; height: 30px; border-radius: 9px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
        .ahm-item.danger .ahm-ic { background: #fef2f2; color: #dc2626; }
        .ahm-it { font-size: 12.5px; font-weight: 600; }
        .ahm-im { font-size: 11px; color: #9ca3af; }
        .ahm-empty { padding: 22px 14px; text-align: center; color: #9ca3af; font-size: 12.5px; }
        .ahm-user { display: flex; align-items: center; gap: 9px; padding: 4px 10px 4px 4px; border: 1px solid #e5e7eb; border-radius: 999px; background: #fff; cursor: pointer; }
        .ahm-user:hover { border-color: #d1d5db; }
        .ahm-av { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg,#4f46e5,#8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .ahm-un { line-height: 1.15; }
        .ahm-un b { font-size: 12.5px; display: block; }
        .ahm-un span { font-size: 10.5px; color: #9ca3af; }
        .ahm-mi { display: flex; align-items: center; gap: 9px; padding: 10px 14px; font-size: 13px; color: #374151; text-decoration: none; width: 100%; border: 0; background: 0; cursor: pointer; text-align: left; }
        .ahm-mi:hover { background: #f9fafb; }
        .ahm-mi.danger { color: #dc2626; }
        body.tab-hidden .ahm-badge { animation: none; }
        @media (max-width: 640px) { .ahm-un { display: none; } .ahm-menu { width: 280px; } }
    </style>

    {{-- Bell --}}
    <div class="ahm-wrap" x-data="{ open: false }" :class="{ open }" @click.outside="open = false" @keydown.escape.window="open = false">
        <button class="ahm-btn" type="button" @click="open = !open; if (open) $wire.markAlertsSeen()" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            @if ($aUnread > 0)<span class="ahm-badge">{{ $aUnread }}</span>@endif
        </button>
        <div class="ahm-menu">
            <div class="ahm-mh"><i class="fas fa-bell"></i> Notifications</div>
            <div class="ahm-list">
                @forelse ($alerts as $a)
                    <a href="{{ $a['url'] }}" wire:navigate class="ahm-item {{ $a['danger'] ? 'danger' : '' }}">
                        <span class="ahm-ic"><i class="fas {{ $a['icon'] }}"></i></span>
                        <span><span class="ahm-it">{{ $a['text'] }}</span><br><span class="ahm-im">{{ $a['meta'] }}</span></span>
                    </a>
                @empty
                    <div class="ahm-empty">You're all caught up 🎉</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Messages --}}
    <div class="ahm-wrap" x-data="{ open: false }" :class="{ open }" @click.outside="open = false" @keydown.escape.window="open = false">
        <button class="ahm-btn" type="button" @click="open = !open; if (open) $wire.markMessagesSeen()" aria-label="Messages">
            <i class="fas fa-envelope"></i>
            @if ($mUnread > 0)<span class="ahm-badge">{{ $mUnread }}</span>@endif
        </button>
        <div class="ahm-menu">
            <div class="ahm-mh"><i class="fas fa-comments"></i> Project Messages</div>
            <div class="ahm-list">
                @forelse ($msgs as $m)
                    <a href="{{ $m['url'] }}" wire:navigate class="ahm-item">
                        <span class="ahm-ic"><i class="fas fa-comment"></i></span>
                        <span>
                            <span class="ahm-it">{{ $m['who'] }} · {{ $m['project'] }}</span><br>
                            <span class="ahm-im">{{ $m['text'] }} — {{ $m['when'] }}</span>
                        </span>
                    </a>
                @empty
                    <div class="ahm-empty">No project messages yet</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Profile --}}
    <div class="ahm-wrap" x-data="{ open: false }" :class="{ open }" @click.outside="open = false" @keydown.escape.window="open = false">
        <div class="ahm-user" @click="open = !open">
            <span class="ahm-av">{{ strtoupper(substr($u->name ?? 'A', 0, 1)) }}</span>
            <span class="ahm-un"><b>{{ $u->name }}</b><span>{{ ucfirst($u->role) }}</span></span>
            <i class="fas fa-chevron-down" style="font-size:10px;color:#9ca3af;"></i>
        </div>
        <div class="ahm-menu" style="width:240px;">
            <div class="ahm-mh" style="flex-direction:column;align-items:flex-start;gap:2px;">
                <span>{{ $u->name }}</span>
                <span style="font-weight:400;font-size:11px;color:#9ca3af;">{{ $u->email }}</span>
            </div>
            <a href="{{ route('dashboard') }}" wire:navigate class="ahm-mi"><i class="fas fa-gauge"></i> Dashboard</a>
            @php $mySid = $u->role !== 'admin' ? \App\Models\staff::where('user_id', $u->id)->value('id') : null; @endphp
            @if ($mySid)
                <a href="{{ route('staff.show', $mySid) }}" wire:navigate class="ahm-mi"><i class="fas fa-id-badge"></i> My profile</a>
                <a href="{{ route('attendance.person', ['type' => 'staff', 'id' => $mySid]) }}" wire:navigate class="ahm-mi"><i class="fas fa-calendar-check"></i> My attendance</a>
            @endif
            @if ($u->role === 'admin')
                <a href="{{ route('settings.user-management') }}" wire:navigate class="ahm-mi"><i class="fas fa-users-gear"></i> User management</a>
                <a href="{{ route('settings.roles-permissions') }}" wire:navigate class="ahm-mi"><i class="fas fa-user-shield"></i> Roles &amp; permissions</a>
            @endif
            <div style="border-top:1px solid #f0f0f3;margin:4px 0;"></div>
            <button type="button" class="ahm-mi danger" @click="$refs.lo.submit()"><i class="fas fa-arrow-right-from-bracket"></i> Logout</button>
            <form x-ref="lo" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        </div>
    </div>
</div>
