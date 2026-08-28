<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Communication;
use App\Models\ProjectMessage;
use App\Models\staff;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Turns a saved calendar event into notifications for the people it actually
 * concerns. It reads the event's data — assigned staff, linked project, linked
 * client contact, organizer — decides whether it is a staff<->client or a
 * staff<->admin conversation, and delivers the full details (with a link) to
 * each side through the channels they already watch:
 *
 *   - the project chat thread (system message)      -> staff + client + admin
 *   - the activity log / dashboards (Communication)  -> client sees it on their
 *                                                       portal, admin on theirs
 *
 * Re-running is safe: the project message is de-duplicated and the Communication
 * row is updated in place, so a reschedule edits the same records.
 */
class EventNotifier
{
    public function sync(CalendarEvent $event): void
    {
        $event->loadMissing([
            'assignedTo.user',
            'project.staff.user',
            'project.company.contacts.user',
            'contact.user',
            'createdBy',
        ]);

        $staff = $this->staffUsers($event);
        $clients = $this->clientUsers($event);
        $organizer = $event->createdBy;

        $cancelled = $event->status === 'cancelled';
        $between = $this->betweenLabel($event, $staff, $clients);
        $body = $this->body($event, $between, $cancelled);

        // 1) Project thread — everyone on the project (staff + client + admin) reads this.
        if ($event->project_id) {
            $this->postToProjectThread($event, $body, $organizer);
        }

        // 2) Activity log entry — surfaces on the client portal dashboard feed and
        //    the admin activity log / "coming up". Kept in sync via communication_id.
        $this->syncCommunication($event, $body, $cancelled);

        // 3) In-app notification (bell + popup + reminder) for staff and client.
        $this->pushAlerts($event, $staff, $clients, $between, $cancelled);

        $event->forceFill(['notified_digest' => $event->currentDigest()])->saveQuietly();
    }

    /**
     * Staff users tied to the event: the assignee plus the project team — or
     * every active staff member when the event is flagged "notify all".
     */
    private function staffUsers(CalendarEvent $event): Collection
    {
        if ($event->notify_all) {
            return staff::where('status', 'active')->with('user')
                ->get()->pluck('user')->filter()->unique('id')->values();
        }

        return collect()
            ->when($event->assignedTo?->user, fn ($c) => $c->push($event->assignedTo->user))
            ->merge(optional($event->project)->staff?->pluck('user')->filter() ?? collect())
            ->filter()
            ->unique('id')
            ->values();
    }

    private function pushAlerts(CalendarEvent $event, Collection $staff, Collection $clients, string $between, bool $cancelled): void
    {
        $type = ucfirst($event->event_type);
        $verb = $cancelled ? 'cancelled' : ($event->wasRecentlyCreated ? 'scheduled' : 'updated');
        $title = "{$type} {$verb} — {$event->title}";
        $sub = $event->start_at->format('D, M j · g:i A')
            . ($event->join_link ? ' · online' : (filled($event->location) ? ' · ' . $event->location : ''));

        $opts = [
            'body' => $sub,
            'icon' => $event->event_type === 'call' ? 'fa-phone' : 'fa-calendar-day',
            'level' => $cancelled ? 'warning' : 'info',
            'actor' => $event->createdBy,
            'dedupe_minutes' => 2,
        ];

        \App\Services\Notifier::push($staff, $title, $opts + [
            'url' => $event->project_id ? route('projects.show', $event->project_id) : route('calendar.events'),
        ]);
        \App\Services\Notifier::push($clients, $title, $opts + [
            'url' => $event->project_id ? route('client.project-show', $event->project_id) : route('client.dashboard'),
        ]);
    }

    /** Client users tied to the event: the linked contact plus the project company's contacts. */
    private function clientUsers(CalendarEvent $event): Collection
    {
        return collect()
            ->when($event->contact?->user, fn ($c) => $c->push($event->contact->user))
            ->merge(optional($event->project?->company)->contacts?->pluck('user')->filter() ?? collect())
            ->filter(fn ($u) => $u && $u->role === 'client')
            ->unique('id')
            ->values();
    }

    private function betweenLabel(CalendarEvent $event, Collection $staff, Collection $clients): string
    {
        $staffNames = $staff->pluck('name')->filter()->implode(', ');

        if ($clients->isNotEmpty()) {
            $clientName = optional($event->project?->company)->company_name
                ?? optional($event->contact)->full_name
                ?? $clients->pluck('name')->filter()->implode(', ');

            return trim(($staffNames ?: 'the team') . ' and ' . $clientName . ' (client)');
        }

        if ($staffNames !== '') {
            return $staffNames . ' and management';
        }

        return optional($event->createdBy)->name ?? 'the organizer';
    }

    private function body(CalendarEvent $event, string $between, bool $cancelled): string
    {
        $type = ucfirst($event->event_type);
        $start = $event->start_at;
        $when = $start->format('D, M j Y · g:i A');
        if ($event->end_at) {
            $when .= ' – ' . $event->end_at->format('g:i A')
                . ' (' . $start->diffForHumans($event->end_at, ['parts' => 2, 'short' => true, 'syntax' => true]) . ')';
        }

        $lines = [];
        $lines[] = ($cancelled ? "\u{274C} CANCELLED — " : "\u{1F4C5} ") . $type . ': ' . $event->title;
        $lines[] = 'When: ' . $when;
        if (filled($event->location) && ! filter_var($event->location, FILTER_VALIDATE_URL)) {
            $lines[] = 'Where: ' . $event->location;
        }
        if ($link = $event->join_link) {
            $lines[] = 'Join: ' . $link;
        }
        $lines[] = 'Between: ' . $between;
        if ($event->createdBy) {
            $lines[] = 'Organizer: ' . $event->createdBy->name;
        }
        if (filled($event->description)) {
            $lines[] = 'Agenda: ' . trim($event->description);
        }
        if ($event->project) {
            $lines[] = 'Project: ' . $event->project->name;
        }

        return implode("\n", $lines);
    }

    private function postToProjectThread(CalendarEvent $event, string $body, ?User $organizer): void
    {
        $last = ProjectMessage::where('project_id', $event->project_id)
            ->where('author_role', 'system')
            ->latest('id')
            ->first();

        if ($last && trim($last->body) === trim($body)) {
            return; // identical notice already the latest system message
        }

        ProjectMessage::create([
            'project_id' => $event->project_id,
            'user_id' => $organizer?->id ?? $event->created_by ?? User::where('role', 'admin')->value('id'),
            'author_role' => 'system',
            'body' => $body,
        ]);
    }

    private function syncCommunication(CalendarEvent $event, string $body, bool $cancelled): void
    {
        // Communication only knows email|call|meeting — everything else logs as a meeting.
        $type = $event->event_type === 'call' ? 'call' : 'meeting';
        $status = match ($event->status) {
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'scheduled',
        };

        $companyId = optional($event->project?->company)->id ?? optional($event->contact)->company_id;
        $contactId = $event->contact_id
            ?? optional($event->project?->company)->contacts?->firstWhere('user.role', 'client')?->id
            ?? optional($event->project?->company)->contacts?->first()?->id;

        $attrs = [
            'type' => $type,
            'direction' => 'outbound',
            'subject' => $event->title,
            'notes' => $body,
            'status' => $status,
            'duration_minutes' => $event->end_at ? $event->start_at->diffInMinutes($event->end_at) : null,
            'occurred_at' => $event->start_at,
            'contact_id' => $contactId,
            'company_id' => $companyId,
            'staff_id' => $event->assigned_to,
            'created_by' => $event->created_by,
        ];

        if ($event->communication_id && ($row = Communication::find($event->communication_id))) {
            $row->update($attrs);
            return;
        }

        $row = Communication::create($attrs);
        $event->forceFill(['communication_id' => $row->id])->saveQuietly();
    }
}
