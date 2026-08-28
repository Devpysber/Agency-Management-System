<div wire:poll.60s>
    @php $events = $this->events; @endphp
    @if ($events->isNotEmpty())
        @php
            $overdueCount = $events->filter(fn ($e) => $e->start_at && $e->start_at->isPast())->count();
        @endphp
        <div wire:key="evr-{{ $events->count() }}-{{ $events->first()?->id }}-{{ $events->first()?->updated_at?->timestamp }}"
             x-data="{
                full: true,
                timer: null,
                arm() { clearTimeout(this.timer); this.timer = setTimeout(() => { this.full = false; }, 10000); },
                expand() { this.full = true; this.arm(); },
             }"
             x-init="
                arm();
                window.addEventListener('livewire:navigated', () => expand());
             ">
            <style>
                .evr, .evr-bell-btn { position: fixed; right: 22px; bottom: 22px; z-index: 1250; }
                .evr {
                    width: 348px; max-width: calc(100vw - 44px);
                    display: flex; flex-direction: column; gap: 10px;
                    transform-origin: bottom right;
                    animation: evr-pop .32s cubic-bezier(.34,1.56,.64,1) both;
                }
                @keyframes evr-pop { from { opacity: 0; transform: translateY(20px) scale(.9); } to { opacity: 1; transform: none; } }
                .evr-head {
                    display: flex; align-items: center; gap: 8px;
                    background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff;
                    padding: 11px 15px; border-radius: 13px; font-size: 13px; font-weight: 700;
                    box-shadow: 0 12px 34px rgba(79,70,229,.4);
                }
                .evr-head .evr-x { margin-left: auto; cursor: pointer; opacity: .8; }
                .evr-head .evr-x:hover { opacity: 1; }
                .evr-count { background: rgba(255,255,255,.25); border-radius: 999px; padding: 1px 9px; font-size: 12px; }
                .evr-ring { animation: evr-ring 2.4s ease-in-out infinite; transform-origin: 50% 0; }
                @keyframes evr-ring { 0%,72%,100%{transform:rotate(0)} 76%{transform:rotate(16deg)} 81%{transform:rotate(-13deg)} 86%{transform:rotate(9deg)} 91%{transform:rotate(-5deg)} }
                .evr-list { display: flex; flex-direction: column; gap: 8px; max-height: 62vh; overflow-y: auto; padding-right: 2px; }
                .evr-item {
                    background: #fff; border: 1px solid #e5e7eb; border-left: 4px solid #6b7280;
                    border-radius: 13px; padding: 12px 14px; box-shadow: 0 10px 28px rgba(16,24,40,.14);
                    animation: evr-slide .3s ease both;
                }
                @keyframes evr-slide { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: none; } }
                .evr-item.is-overdue { border-left-color: #ef4444; background: #fef2f2; }
                .evr-item.is-soon    { border-left-color: #f59e0b; }
                .evr-item.is-upcoming{ border-left-color: #4f46e5; }
                .evr-t { display: flex; align-items: center; gap: 7px; font-weight: 650; font-size: 13px; color: #1f2937; }
                .evr-meta { font-size: 11.5px; color: #6b7280; margin-top: 3px; }
                .evr-meta .evr-when { font-weight: 700; }
                .evr-item.is-overdue .evr-when { color: #dc2626; }
                .evr-actions { margin-top: 9px; display: flex; gap: 8px; }
                .evr-btn { border: 0; border-radius: 9px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; }
                .evr-btn-done { background: #10b981; color: #fff; }
                .evr-btn-open { background: #eef2ff; color: #4338ca; text-decoration: none; }

                .evr-bell-btn {
                    width: 54px; height: 54px; border-radius: 50%;
                    background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff;
                    border: 0; cursor: pointer; font-size: 20px;
                    display: flex; align-items: center; justify-content: center;
                    box-shadow: 0 14px 36px rgba(79,70,229,.45);
                    animation: evr-bell-in .3s ease both;
                }
                @keyframes evr-bell-in { from { opacity: 0; transform: scale(.5); } to { opacity: 1; transform: none; } }
                .evr-bell-btn:hover { filter: brightness(1.08); }
                .evr-bell-badge {
                    position: absolute; top: -3px; right: -3px; min-width: 20px; height: 20px;
                    background: #ef4444; color: #fff; border-radius: 999px; font-size: 11px; font-weight: 800;
                    display: flex; align-items: center; justify-content: center; padding: 0 5px;
                    box-shadow: 0 0 0 2px #fff;
                    animation: evr-badge-pulse 1.8s ease-out infinite;
                }
                @keyframes evr-badge-pulse { 0%{box-shadow:0 0 0 0 rgba(239,68,68,.55),0 0 0 2px #fff} 70%{box-shadow:0 0 0 10px transparent,0 0 0 2px #fff} 100%{box-shadow:0 0 0 0 transparent,0 0 0 2px #fff} }
                @media (prefers-reduced-motion: reduce) {
                    .evr, .evr-item, .evr-ring, .evr-bell-btn, .evr-bell-badge { animation: none !important; }
                }
            </style>

            {{-- Full popup --}}
            <div class="evr" x-show="full" x-transition.opacity
                 @mouseenter="clearTimeout(timer)" @mouseleave="arm()" @click="arm()">
                <div class="evr-head">
                    <i class="fas fa-bell evr-ring"></i>
                    Reminders
                    <span class="evr-count">{{ $events->count() }}</span>
                    <i class="fas fa-minus evr-x" title="Minimise" @click.stop="full = false; clearTimeout(timer)"></i>
                </div>
                <div class="evr-list">
                    @foreach ($events as $ev)
                        @php
                            $overdue = $ev->start_at && $ev->start_at->isPast();
                            $soon = ! $overdue && $ev->start_at && $ev->start_at->lte(now()->addHours(3));
                            $cls = $overdue ? 'is-overdue' : ($soon ? 'is-soon' : 'is-upcoming');
                        @endphp
                        <div class="evr-item {{ $cls }}" wire:key="evr-i-{{ $ev->id }}">
                            <div class="evr-t"><i class="fas {{ $ev->type_badge['icon'] ?? 'fa-circle' }}"></i> {{ $ev->title }}</div>
                            <div class="evr-meta">
                                <span class="evr-when">{{ $overdue ? 'Overdue · ' : '' }}{{ optional($ev->start_at)->diffForHumans() }}</span>
                                · {{ optional($ev->start_at)->format('M d, H:i') }}
                                @if ($ev->assignedTo) · {{ $ev->assignedTo->name }} @endif
                            </div>
                            <div class="evr-actions">
                                @unless ($this->readOnly)
                                    <button class="evr-btn evr-btn-done" wire:click="markDone({{ $ev->id }})"><i class="fas fa-check"></i> Mark done</button>
                                @endunless
                                @php
                                    $evrOpen = $this->readOnly
                                        ? ($ev->project_id ? route('client.project-show', $ev->project_id) : route('client.dashboard'))
                                        : route('calendar.events');
                                @endphp
                                <a class="evr-btn evr-btn-open" href="{{ $evrOpen }}">Open</a>
                                @if ($this->readOnly && $ev->join_link)
                                    <a class="evr-btn evr-btn-open" href="{{ $ev->join_link }}" target="_blank" rel="noopener" style="background:#4f46e5;color:#fff;">
                                        <i class="fas fa-video"></i> Join
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Collapsed bell --}}
            <button class="evr-bell-btn" x-show="!full" x-transition @click="expand()" style="position:fixed;">
                <i class="fas fa-bell evr-ring"></i>
                <span class="evr-bell-badge">{{ $overdueCount ?: $events->count() }}</span>
            </button>
        </div>
    @endif
</div>
