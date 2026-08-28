<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\staff;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Floating reminder stack shown on the admin panel. Surfaces the current
 * user's scheduled calendar events that are overdue or due within 24h, and
 * keeps showing them (every poll / page load) until they are marked done.
 */
class EventReminders extends Component
{
    public bool $collapsed = false;

    private function staffId(): ?int
    {
        return staff::where('user_id', auth()->id())->value('id');
    }

    public function markDone(int $id): void
    {
        $event = CalendarEvent::find($id);
        if (! $event) {
            return;
        }
        $isAdmin = auth()->user()->role === 'admin';
        if ($isAdmin || $event->assigned_to == $this->staffId()) {
            $event->update(['status' => 'completed']);
            unset($this->events);
            $this->dispatch('toast', message: 'Marked done');
        }
    }

    public bool $readOnly = false;

    #[Computed]
    public function events()
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $query = CalendarEvent::where('status', 'scheduled')
            ->where('start_at', '<=', now()->addDay())
            ->where('start_at', '>=', now()->subDay())
            ->with('assignedTo:id,name')
            ->orderBy('start_at');

        if ($user->role === 'client') {
            // The client only ever sees meetings/calls booked with them.
            $this->readOnly = true;
            $companyId = $user->contact?->company_id;
            if (! $companyId) {
                return collect();
            }
            $projectIds = \App\Models\Project::where('company_id', $companyId)->pluck('id');
            $contactIds = \App\Models\Contact::where('company_id', $companyId)->pluck('id');
            $query->whereIn('event_type', ['meeting', 'call'])
                ->where(fn ($q) => $q->whereIn('project_id', $projectIds)->orWhereIn('contact_id', $contactIds));
        } elseif ($user->role !== 'admin') {
            $sid = $this->staffId();
            if (! $sid) {
                return collect();
            }
            $query->where(fn ($q) => $q->where('assigned_to', $sid)
                ->orWhere('notify_all', true)
                ->orWhereHas('project', fn ($p) => $p->whereHas('staff', fn ($s) => $s->where('staff.id', $sid))));
        }

        return $query->limit(8)->get();
    }

    public function render()
    {
        return view('livewire.event-reminders');
    }
}
