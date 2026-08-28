<?php

namespace App\Livewire;

use App\Models\UserAlert;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Notification bell used in both the client portal header and the staff panel
 * header. Polls, badges the unread count, lists recent alerts, and emits a
 * toast for anything that arrived since the component last rendered.
 */
class AlertBell extends Component
{
    /** 'client' or 'admin' — only changes the toast channel + link target. */
    public string $variant = 'admin';

    public function markRead(int $id): void
    {
        UserAlert::where('user_id', auth()->id())->where('id', $id)->whereNull('read_at')
            ->update(['read_at' => now()]);
        unset($this->items, $this->unread);
    }

    public function markAllRead(): void
    {
        UserAlert::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        unset($this->items, $this->unread);
    }

    #[Computed]
    public function items()
    {
        return UserAlert::with('actor:id,name')
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(15)
            ->get();
    }

    #[Computed]
    public function unread(): int
    {
        return UserAlert::where('user_id', auth()->id())->whereNull('read_at')->count();
    }

    public function render()
    {
        return view('livewire.alert-bell');
    }
}
