<div class="pchat" wire:poll.6s
     x-data="{
        scroll() { this.$nextTick(() => { const b = this.$refs.body; if (b) b.scrollTop = b.scrollHeight; }); }
     }"
     x-init="scroll()"
     @chat-message-sent.window="scroll()">
    <style>
        .pchat {
            --pc-bg: #ffffff; --pc-alt: #f5f6fb; --pc-border: #e6e7ef; --pc-text: #1b1f2a;
            --pc-soft: #656c7b; --pc-me: #4f46e5; --pc-me-text: #ffffff;
            border: 1px solid var(--pc-border); border-radius: 16px; background: var(--pc-bg);
            display: flex; flex-direction: column; overflow: hidden; font-size: 13px;
        }
        @media (prefers-color-scheme: dark) {
            .pchat:not([data-force-light]) {
                --pc-bg: #171a22; --pc-alt: #1f232e; --pc-border: #2a2f3c; --pc-text: #e9ebf2;
                --pc-soft: #a3a9b8; --pc-me: #7c74ff;
            }
        }
        :root[data-theme="dark"] .pchat { --pc-bg:#171a22;--pc-alt:#1f232e;--pc-border:#2a2f3c;--pc-text:#e9ebf2;--pc-soft:#a3a9b8;--pc-me:#7c74ff; }
        :root[data-theme="light"] .pchat { --pc-bg:#fff;--pc-alt:#f5f6fb;--pc-border:#e6e7ef;--pc-text:#1b1f2a;--pc-soft:#656c7b;--pc-me:#4f46e5; }

        .pchat-head {
            display: flex; align-items: center; gap: 9px;
            padding: 13px 16px; border-bottom: 1px solid var(--pc-border);
            font-weight: 650; color: var(--pc-text);
        }
        .pchat-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; position: relative; }
        .pchat-dot::after {
            content: ""; position: absolute; inset: -4px; border-radius: 50%;
            background: #10b981; opacity: 0.35; animation: pc-pulse 1.8s ease-out infinite;
        }
        @keyframes pc-pulse { 0% { transform: scale(0.6); opacity: 0.5; } 100% { transform: scale(2.2); opacity: 0; } }
        .pchat-head small { margin-left: auto; font-weight: 500; color: var(--pc-soft); }

        .pchat-body {
            padding: 16px; display: flex; flex-direction: column; gap: 12px;
            max-height: 440px; min-height: 220px; overflow-y: auto; background: var(--pc-bg);
        }
        .pchat-empty { margin: auto; text-align: center; color: var(--pc-soft); font-size: 12.5px; padding: 24px; }
        .pchat-empty i { font-size: 24px; display: block; margin-bottom: 8px; opacity: 0.5; }

        .pc-msg { display: flex; gap: 10px; max-width: 82%; animation: pc-in 0.25s ease; }
        @keyframes pc-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .pc-msg.me { margin-left: auto; flex-direction: row-reverse; }
        .pc-avatar {
            width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
        }
        .pc-msg.role-client .pc-avatar { background: linear-gradient(135deg, #0ea5e9, #22d3ee); }
        .pc-msg.role-staff .pc-avatar  { background: linear-gradient(135deg, #f59e0b, #f97316); }
        .pc-bubble {
            background: var(--pc-alt); color: var(--pc-text);
            border: 1px solid var(--pc-border);
            border-radius: 14px; padding: 8px 12px; line-height: 1.5;
        }
        .pc-msg.me .pc-bubble { background: var(--pc-me); color: var(--pc-me-text); border-color: transparent; }
        .pc-meta { font-size: 10.5px; color: var(--pc-soft); margin-bottom: 3px; display: flex; gap: 6px; align-items: center; }
        .pc-msg.me .pc-meta { justify-content: flex-end; }
        .pc-role {
            font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;
            padding: 1px 5px; border-radius: 5px; background: rgba(127,127,127,0.15);
        }
        .pc-body-text { white-space: pre-wrap; word-break: break-word; }

        .pchat-foot { border-top: 1px solid var(--pc-border); padding: 12px 14px; background: var(--pc-bg); }
        .pchat-form { display: flex; gap: 8px; align-items: flex-end; }
        .pchat-input {
            flex: 1; resize: none; font-family: inherit; font-size: 13px; color: var(--pc-text);
            background: var(--pc-alt); border: 1px solid var(--pc-border); border-radius: 11px;
            padding: 9px 12px; max-height: 120px; line-height: 1.5;
        }
        .pchat-input:focus { outline: 0; border-color: var(--pc-me); box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
        .pchat-send {
            border: 0; border-radius: 11px; background: var(--pc-me); color: #fff;
            width: 40px; height: 38px; cursor: pointer; font-size: 14px; flex-shrink: 0;
            transition: filter 0.15s;
        }
        .pchat-send:hover { filter: brightness(1.08); }
        .pchat-send:disabled { opacity: 0.5; cursor: not-allowed; }
        .pchat-readonly { padding: 11px 14px; font-size: 12px; color: var(--pc-soft); border-top: 1px solid var(--pc-border); }
        .pchat-err { font-size: 11px; color: #ef4444; margin-top: 5px; }
    </style>

    @if (! $canView)
        <div class="pchat-head"><i class="fas fa-comments"></i> Project chat</div>
        <div class="pchat-empty"><i class="fas fa-lock"></i> You don't have access to this conversation.</div>
    @else
        <div class="pchat-head">
            <span class="pchat-dot"></span>
            <i class="fas fa-comments"></i> Project Chat
            <small>{{ $this->thread->count() }} message{{ $this->thread->count() === 1 ? '' : 's' }}</small>
        </div>

        <div class="pchat-body" x-ref="body">
            @forelse ($this->thread as $msg)
                @php
                    $mine = $msg->user_id === auth()->id();
                    $role = $msg->author_role ?: ($msg->user->role ?? 'staff');
                    $name = $msg->user->name ?? 'User';
                    $initials = collect(explode(' ', trim($name)))->filter()->take(2)
                        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <div class="pc-msg role-{{ $role }} {{ $mine ? 'me' : '' }}" wire:key="msg-{{ $msg->id }}">
                    <div class="pc-avatar">{{ $initials ?: 'U' }}</div>
                    <div>
                        <div class="pc-meta">
                            <strong>{{ $mine ? 'You' : $name }}</strong>
                            <span class="pc-role">{{ $role }}</span>
                            <span>{{ $msg->created_at->diffForHumans(null, true) }} ago</span>
                        </div>
                        <div class="pc-bubble"><span class="pc-body-text">{{ $msg->body }}</span></div>
                    </div>
                </div>
            @empty
                <div class="pchat-empty">
                    <i class="fas fa-comment-dots"></i>
                    No messages yet. Start the conversation about this project.
                </div>
            @endforelse
        </div>

        @if ($canPost)
            <div class="pchat-foot">
                <form class="pchat-form" wire:submit="send">
                    <textarea class="pchat-input" rows="1" wire:model="body"
                              placeholder="Write a message…"
                              x-on:keydown.enter.prevent="$wire.send()"></textarea>
                    <button type="submit" class="pchat-send" wire:loading.attr="disabled" wire:target="send" aria-label="Send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                @error('body') <div class="pchat-err">{{ $message }}</div> @enderror
            </div>
        @elseif ($locked)
            <div class="pchat-readonly"><i class="fas fa-lock"></i> This project is closed — the conversation is read-only for you now.</div>
        @else
            <div class="pchat-readonly"><i class="fas fa-eye"></i> Read-only — you are not a participant on this project.</div>
        @endif
    @endif
</div>
