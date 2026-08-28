<div>
    @php $st = $this->state; @endphp
    @if ($st)
        @php
            $appeal = $st['appeal'];
            $pending = $appeal && $appeal->status === 'pending';
            $rejected = $appeal && $appeal->status === 'rejected';
        @endphp
        <div class="abp-scrim" x-data x-init="$el.classList.add('abp-in')">
            <style>
                .abp-scrim {
                    position: fixed; inset: 0; z-index: 2000;
                    background: rgba(10, 12, 20, 0.55); backdrop-filter: blur(3px);
                    display: flex; align-items: center; justify-content: center; padding: 20px;
                    opacity: 0; transition: opacity .3s ease;
                }
                .abp-scrim.abp-in { opacity: 1; }
                .abp-card {
                    width: 420px; max-width: 100%; background: #fff; border-radius: 18px;
                    box-shadow: 0 30px 80px rgba(0,0,0,.35); overflow: hidden;
                    transform: translateY(24px) scale(.96); transition: transform .35s cubic-bezier(.4,0,.2,1);
                }
                .abp-scrim.abp-in .abp-card { transform: none; }
                .abp-top {
                    background: linear-gradient(135deg,#ef4444,#dc2626); color: #fff;
                    padding: 22px; text-align: center;
                }
                .abp-top .abp-ico {
                    width: 54px; height: 54px; border-radius: 50%; background: rgba(255,255,255,.2);
                    display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 10px;
                    animation: abp-pulse 1.8s ease-out infinite;
                }
                @keyframes abp-pulse { 0%{box-shadow:0 0 0 0 rgba(255,255,255,.5)} 70%{box-shadow:0 0 0 16px transparent} 100%{box-shadow:0 0 0 0 transparent} }
                .abp-top h3 { font-size: 17px; font-weight: 750; margin: 0; }
                .abp-body { padding: 20px 22px; }
                .abp-body p { font-size: 13px; color: #4b5563; margin: 0 0 12px; line-height: 1.55; }
                .abp-note { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 9px 12px; font-size: 12px; color: #991b1b; margin-bottom: 14px; }
                .abp-status { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; padding: 10px 12px; border-radius: 10px; }
                .abp-status.pend { background: #fffbeb; color: #92400e; }
                .abp-status.rej { background: #fef2f2; color: #991b1b; }
                .abp-ta { width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; font-size: 13px; font-family: inherit; resize: vertical; min-height: 78px; }
                .abp-ta:focus { outline: 0; border-color: #4f46e5; box-shadow: 0 0 0 3px #eef2ff; }
                .abp-err { color: #dc2626; font-size: 11.5px; margin-top: 4px; }
                .abp-btn { margin-top: 12px; width: 100%; border: 0; border-radius: 11px; padding: 11px; font-size: 14px; font-weight: 650; color: #fff; background: #4f46e5; cursor: pointer; }
                .abp-btn:hover { background: #4338ca; }
                @media (prefers-reduced-motion: reduce) { .abp-scrim, .abp-card, .abp-ico { transition: none !important; animation: none !important; opacity: 1 !important; transform: none !important; } }
            </style>

            <div class="abp-card">
                <div class="abp-top">
                    <div class="abp-ico"><i class="fas fa-user-xmark"></i></div>
                    <h3>You are marked ABSENT for today</h3>
                </div>
                <div class="abp-body">
                    <div class="abp-note"><i class="fas fa-circle-info"></i> {{ $st['rec']->note ?: 'No check-in was recorded within the grace window.' }}</div>

                    @if ($pending)
                        <div class="abp-status pend"><i class="fas fa-hourglass-half"></i> Appeal sent — waiting for the CEO to review.</div>
                        <p style="margin-top:12px;">You'll be marked present and your hours counted once it's approved.</p>
                    @else
                        @if ($rejected)
                            <div class="abp-status rej" style="margin-bottom:12px;">
                                <i class="fas fa-circle-xmark"></i> Previous appeal was declined{{ $appeal->review_note ? ' — ' . $appeal->review_note : '' }}
                            </div>
                        @endif
                        <p>If this is a mistake, send an explanation to the CEO. When they approve it, you'll be marked present and this message will stop showing.</p>
                        <form wire:submit="appeal">
                            <textarea class="abp-ta" wire:model="message" placeholder="Explain why you should be marked present today…"></textarea>
                            @error('message') <div class="abp-err">{{ $message }}</div> @enderror
                            <button type="submit" class="abp-btn" wire:loading.attr="disabled" wire:target="appeal">
                                <i class="fas fa-paper-plane"></i> Send appeal to CEO
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
