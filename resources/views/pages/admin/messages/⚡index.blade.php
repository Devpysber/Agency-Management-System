<?php

use Livewire\Component;
use App\Models\{User, DirectMessage, staff};

new class extends Component
{
    public ?int $activeUserId = null;
    public string $body = '';
    public string $search = '';

    public function mount(): void
    {
        // Default to the CEO's thread on first open — same entry point the
        // old "Message CEO" link gave, now just one contact among many.
        $ceo = DirectMessage::resolveCeoUser();
        if ($ceo && $ceo->id !== auth()->id()) {
            $this->activeUserId = $ceo->id;
        }
    }

    public function openThread(int $userId): void
    {
        // Any two logged-in accounts may talk — this is an internal team
        // chat, not a CRM record needing module.action scoping. The only
        // real boundary is "you can only read/send in your own thread",
        // enforced below and in send().
        $this->activeUserId = $userId;
        DirectMessage::where('from_user_id', $userId)
            ->where('to_user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function send(): void
    {
        $this->validate(['body' => 'required|string|max:2000']);
        abort_unless($this->activeUserId, 404);

        DirectMessage::create([
            'from_user_id' => auth()->id(),
            'to_user_id' => $this->activeUserId,
            'body' => trim($this->body),
        ]);

        $this->body = '';
    }

    public function render()
    {
        $me = auth()->user();

        // Directory: every other staff+admin user, so a new conversation can
        // always be started (not just people you've already messaged).
        $directory = User::where('id', '!=', $me->id)
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get();

        // Recency + unread-count per contact, for ordering/badges.
        $lastByUser = DirectMessage::where('from_user_id', $me->id)->orWhere('to_user_id', $me->id)
            ->get()
            ->groupBy(fn ($m) => $m->from_user_id === $me->id ? $m->to_user_id : $m->from_user_id)
            ->map(fn ($rows) => $rows->sortByDesc('created_at')->first());

        $unreadByUser = DirectMessage::where('to_user_id', $me->id)->whereNull('read_at')
            ->get()->groupBy('from_user_id')->map->count();

        $threads = $directory
            ->map(function ($u) use ($lastByUser, $unreadByUser) {
                $u->lastMessage = $lastByUser->get($u->id);
                $u->unreadCount = $unreadByUser->get($u->id, 0);
                return $u;
            })
            ->sortByDesc(fn ($u) => $u->lastMessage?->created_at ?? \Illuminate\Support\Carbon::createFromTimestamp(0))
            ->values();

        $messages = $this->activeUserId
            ? DirectMessage::between($me->id, $this->activeUserId)->orderBy('created_at')->get()
            : collect();

        $activeUser = $this->activeUserId ? User::find($this->activeUserId) : null;

        if ($this->activeUserId) {
            DirectMessage::where('from_user_id', $this->activeUserId)
                ->where('to_user_id', $me->id)->whereNull('read_at')->update(['read_at' => now()]);
        }

        $ceoId = DirectMessage::resolveCeoUser()?->id;

        return $this->view([
            'threads' => $threads,
            'messages' => $messages,
            'activeUser' => $activeUser,
            'ceoId' => $ceoId,
        ])->layout('layouts.app');
    }
};
?>
<div class="dashboard" wire:poll.10s>
    <div class="page-header">
        <div>
            <h1>Messages</h1>
            <p>Direct chat with anyone on the team.</p>
        </div>
    </div>

    <div class="card" style="height:68vh; display:flex; flex-direction:row; overflow:hidden;">
        <div style="width:280px; border-right:1px solid var(--cp-border, #e5e7eb); display:flex; flex-direction:column; flex-shrink:0;">
            <div style="padding:10px; border-bottom:1px solid #f1f2f4;">
                <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Search people...">
            </div>
            <div style="overflow-y:auto; flex:1;">
                @forelse ($threads as $t)
                    <div wire:click="openThread({{ $t->id }})"
                         style="cursor:pointer; padding:12px 16px; border-bottom:1px solid #f1f2f4; {{ $activeUserId === $t->id ? 'background:#f5f5ff;' : '' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">
                                {{ $t->name }}
                                @if ($t->id === $ceoId)<i class="fas fa-star text-warning ms-1" style="font-size:10px;" title="CEO"></i>@endif
                            </span>
                            @if ($t->unreadCount)
                                <span class="badge bg-primary rounded-pill">{{ $t->unreadCount }}</span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $t->lastMessage ? \Illuminate\Support\Str::limit($t->lastMessage->body, 40) : 'No messages yet' }}</small>
                    </div>
                @empty
                    <p class="text-muted small p-3">No one found.</p>
                @endforelse
            </div>
        </div>

        <div style="flex:1; display:flex; flex-direction:column;">
            @if ($activeUser)
                <div style="padding:12px 18px; border-bottom:1px solid var(--cp-border, #e5e7eb); font-weight:600;">
                    {{ $activeUser->name }}
                    @if ($activeUser->id === $ceoId)<span class="badge bg-warning text-dark ms-1">CEO</span>@endif
                </div>
                <div style="flex:1; overflow-y:auto; padding:16px 18px; display:flex; flex-direction:column; gap:10px;">
                    @forelse ($messages as $m)
                        <div style="max-width:70%; {{ $m->from_user_id === auth()->id() ? 'align-self:flex-end; background:var(--cp-primary,#4f46e5); color:#fff;' : 'align-self:flex-start; background:#f1f2f4;' }} padding:9px 13px; border-radius:12px; font-size:13.5px;">
                            {{ $m->body }}
                            <div style="font-size:10px; opacity:.7; margin-top:3px;">{{ $m->created_at->format('M d, H:i') }}</div>
                        </div>
                    @empty
                        <p class="text-muted small">No messages yet — say hello.</p>
                    @endforelse
                </div>
                <form wire:submit.prevent="send" style="padding:12px 18px; border-top:1px solid var(--cp-border, #e5e7eb); display:flex; gap:8px;">
                    <input type="text" class="form-control" wire:model="body" placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                </form>
                @error('body') <small class="text-danger px-3 pb-2">{{ $message }}</small> @enderror
            @else
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    Pick someone to message.
                </div>
            @endif
        </div>
    </div>
</div>
