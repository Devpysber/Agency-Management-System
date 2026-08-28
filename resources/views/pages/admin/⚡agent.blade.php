<?php

use Livewire\Component;
use App\Models\AgentConversation;
use App\Models\AgentMessage;
use App\Services\AdminAgent;

new class extends Component
{
    public ?int $conversationId = null;
    public string $prompt = '';
    /** @var array<int,array{role:string,text:string,steps?:array}> */
    public array $history = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $latest = AgentConversation::where('user_id', auth()->id())->latest('updated_at')->first();
        if ($latest) {
            $this->openConversation($latest->id);
        }
    }

    public function newChat(): void
    {
        $this->conversationId = null;
        $this->history = [];
        $this->prompt = '';
    }

    public function openConversation(int $id): void
    {
        $conv = AgentConversation::where('user_id', auth()->id())->with('messages')->find($id);
        if (! $conv) {
            return;
        }
        $this->conversationId = $conv->id;
        $this->history = $conv->messages->map(fn ($m) => [
            'role' => $m->role,
            'text' => $m->content,
            'steps' => $m->steps ?? [],
        ])->all();
        $this->dispatch('agent-replied');
    }

    public function deleteConversation(int $id): void
    {
        AgentConversation::where('user_id', auth()->id())->where('id', $id)->delete();
        if ($this->conversationId === $id) {
            $this->newChat();
        }
    }

    public function send(AdminAgent $agent): void
    {
        $text = trim($this->prompt);
        if ($text === '') {
            return;
        }
        $this->prompt = '';

        $conv = $this->conversationId
            ? AgentConversation::where('user_id', auth()->id())->find($this->conversationId)
            : AgentConversation::create([
                'user_id' => auth()->id(),
                'title' => \Illuminate\Support\Str::limit($text, 42, ''),
            ]);
        $this->conversationId = $conv->id;

        $conv->messages()->create(['role' => 'user', 'content' => $text]);
        $this->history[] = ['role' => 'user', 'text' => $text];

        if (! $agent->configured()) {
            $err = 'AI is not configured. Add OPENROUTER_API_KEY to .env and run php artisan config:clear.';
            $conv->messages()->create(['role' => 'error', 'content' => $err]);
            $this->history[] = ['role' => 'error', 'text' => $err];
            return;
        }

        $prior = collect($this->history)
            ->filter(fn ($m) => in_array($m['role'], ['user', 'assistant'], true))
            ->map(fn ($m) => ['role' => $m['role'] === 'user' ? 'user' : 'assistant', 'content' => $m['text']])
            ->slice(0, -1)   // exclude the just-added user turn (passed separately)
            ->values()->all();

        try {
            $answer = $agent->run($text, $prior);
            $steps = collect($agent->log)->pluck('text')->all();
            $conv->messages()->create(['role' => 'assistant', 'content' => $answer, 'steps' => $steps]);
            $this->history[] = ['role' => 'assistant', 'text' => $answer, 'steps' => $steps];
        } catch (\Throwable $e) {
            report($e);
            $conv->messages()->create(['role' => 'error', 'content' => $e->getMessage()]);
            $this->history[] = ['role' => 'error', 'text' => $e->getMessage()];
        }

        $conv->touch();
        $this->dispatch('agent-replied');
    }

    public function render(AdminAgent $agent)
    {
        return $this->view([
            'configured' => $agent->configured(),
            'conversations' => AgentConversation::where('user_id', auth()->id())->latest('updated_at')->limit(40)->get(),
        ])->layout('layouts.app');
    }
};
?>

<div class="agent-page" x-data
     x-init="$nextTick(() => { const b = $refs.log; if (b) b.scrollTop = b.scrollHeight; })"
     @agent-replied.window="$nextTick(() => { const b = $refs.log; if (b) b.scrollTop = b.scrollHeight; })">
    <style>
        .agent-page { display:flex; flex-direction:column; height: calc(100vh - 128px); }
        .agent-head { flex-shrink:0; margin-bottom:12px; }
        .agent-head h1 { font-size:21px; font-weight:700; }
        .agent-head p { color:#6b7280; font-size:12.5px; margin:2px 0 0; }
        .agent-body { flex:1; min-height:0; display:flex; gap:14px; }

        .agent-rail { width:250px; flex-shrink:0; background:#fff; border:1px solid #e5e7eb; border-radius:14px; display:flex; flex-direction:column; overflow:hidden; }
        .agent-rail-top { padding:10px; border-bottom:1px solid #eef0f3; }
        .agent-new { width:100%; border:0; border-radius:9px; background:#4f46e5; color:#fff; padding:9px; font-size:13px; font-weight:600; cursor:pointer; }
        .agent-new:hover { background:#4338ca; }
        .agent-convs { flex:1; overflow-y:auto; padding:6px; }
        .agent-conv { display:flex; align-items:center; gap:6px; padding:8px 9px; border-radius:9px; cursor:pointer; font-size:12.5px; color:#374151; }
        .agent-conv:hover { background:#f4f4f7; }
        .agent-conv.active { background:#eef2ff; color:#4338ca; font-weight:600; }
        .agent-conv .t { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .agent-conv .x { opacity:0; color:#9ca3af; font-size:11px; }
        .agent-conv:hover .x { opacity:1; }
        .agent-conv .x:hover { color:#dc2626; }
        .agent-conv-when { font-size:10px; color:#9ca3af; }

        .agent-card { flex:1; min-width:0; display:flex; flex-direction:column; background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; }
        .agent-log { flex:1; min-height:0; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:14px; }
        .agent-foot { flex-shrink:0; border-top:1px solid #eef0f3; padding:14px 16px; background:#fafbfc; }
        .agent-foot form { display:flex; gap:8px; }
        .agent-foot .form-control { border-radius:11px; }
        .agent-chip { display:inline-block; margin:3px; padding:5px 10px; border:1px solid #e5e7eb; border-radius:999px; font-size:11.5px; color:#4b5563; cursor:pointer; background:#fff; }
        .agent-chip:hover { border-color:#4f46e5; color:#4338ca; }
        .msg-user { align-self:flex-end; max-width:78%; background:#4f46e5; color:#fff; padding:9px 13px; border-radius:14px 14px 4px 14px; font-size:13.5px; white-space:pre-wrap; }
        .msg-err { align-self:flex-start; max-width:82%; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; padding:9px 13px; border-radius:14px; font-size:13px; }
        .msg-bot { align-self:flex-start; max-width:82%; }
        .msg-bot .body { background:#f3f4f6; color:#1f2937; padding:10px 13px; border-radius:14px 14px 14px 4px; font-size:13.5px; white-space:pre-wrap; }
        body.tab-hidden .agent-page * { animation:none !important; }
        @media (max-width:820px) { .agent-rail { display:none; } }
    </style>

    <div class="agent-head a-reveal">
        <h1><i class="fas fa-robot text-primary me-2"></i> Admin Assistant</h1>
        <p>Run the back office by chat — projects, staff &amp; client accounts, teams, progress, deals, attendance, appeals.</p>
    </div>

    @unless ($configured)
        <div class="alert alert-warning" style="flex-shrink:0;"><i class="fas fa-triangle-exclamation me-1"></i>
            AI is not configured. Add <code>OPENROUTER_API_KEY</code> to <code>.env</code> and run <code>php artisan config:clear</code>.
        </div>
    @endunless

    <div class="agent-body a-reveal">
        {{-- Conversation rail --}}
        <div class="agent-rail">
            <div class="agent-rail-top">
                <button class="agent-new" wire:click="newChat"><i class="fas fa-plus"></i> New chat</button>
            </div>
            <div class="agent-convs">
                @forelse ($conversations as $c)
                    <div class="agent-conv {{ $conversationId === $c->id ? 'active' : '' }}" wire:key="conv-{{ $c->id }}"
                         wire:click="openConversation({{ $c->id }})">
                        <i class="fas fa-message" style="font-size:10px;"></i>
                        <span class="t">
                            {{ $c->title ?: 'Chat' }}
                            <span class="agent-conv-when d-block">{{ $c->updated_at->diffForHumans() }}</span>
                        </span>
                        <i class="fas fa-trash x" wire:click.stop="deleteConversation({{ $c->id }})"
                           wire:confirm="Delete this conversation?"></i>
                    </div>
                @empty
                    <div style="padding:16px;text-align:center;color:#9ca3af;font-size:12px;">No chats yet</div>
                @endforelse
            </div>
        </div>

        {{-- Chat --}}
        <div class="agent-card">
            <div x-ref="log" class="agent-log">
                @forelse ($history as $m)
                    @if ($m['role'] === 'user')
                        <div class="msg-user">{{ $m['text'] }}</div>
                    @elseif ($m['role'] === 'error')
                        <div class="msg-err"><i class="fas fa-circle-exclamation me-1"></i> {{ $m['text'] }}</div>
                    @else
                        <div class="msg-bot">
                            @if (!empty($m['steps']))
                                <details style="margin-bottom:6px;">
                                    <summary style="cursor:pointer;font-size:11px;color:#9ca3af;">{{ count($m['steps']) }} action(s)</summary>
                                    <div style="font-size:11px;color:#6b7280;font-family:monospace;background:#f9fafb;border:1px solid #eee;border-radius:8px;padding:8px;margin-top:4px;white-space:pre-wrap;">{{ implode("\n", $m['steps']) }}</div>
                                </details>
                            @endif
                            <div class="body">{{ $m['text'] }}</div>
                        </div>
                    @endif
                @empty
                    <div style="margin:auto;text-align:center;color:#9ca3af;font-size:13px;max-width:560px;">
                        <i class="fas fa-robot fa-2x mb-2 d-block"></i>
                        <div class="mb-2">Ask it to run something. It reads and acts on projects, staff, clients, companies, deals and attendance.</div>
                        <div>
                            <span class="agent-chip" @click="$wire.prompt = 'Show me today\'s attendance status'">Today's attendance</span>
                            <span class="agent-chip" @click="$wire.prompt = 'Create a project Website Revamp for Willow & Bean Cafe with budget 12000'">Create a project</span>
                            <span class="agent-chip" @click="$wire.prompt = 'Add staff Ravi Kumar ravi@agency.test as Developer'">Add a staff member</span>
                            <span class="agent-chip" @click="$wire.prompt = 'Mark Priya Singh late today'">Mark attendance</span>
                            <span class="agent-chip" @click="$wire.prompt = 'List pending absence appeals'">Pending appeals</span>
                        </div>
                    </div>
                @endforelse

                <div wire:loading wire:target="send" style="align-self:flex-start;color:#6b7280;font-size:13px;">
                    <i class="fas fa-spinner fa-spin me-1"></i> Working…
                </div>
            </div>

            <div class="agent-foot">
                <form wire:submit="send">
                    <input type="text" class="form-control" wire:model="prompt" placeholder="Tell the assistant what to do…" autocomplete="off"
                           @unless($configured) disabled @endunless>
                    <button class="btn btn-primary" type="submit" wire:loading.attr="disabled" wire:target="send" @unless($configured) disabled @endunless>
                        <span wire:loading.remove wire:target="send"><i class="fas fa-paper-plane"></i></span>
                        <span wire:loading wire:target="send"><i class="fas fa-spinner fa-spin"></i></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
