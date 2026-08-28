<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Project;
use App\Models\User;
use App\Models\UserAlert;
use Illuminate\Support\Collection;

/**
 * One entry point for in-app notifications. `push()` fans a message out to one
 * or more users as `user_alerts` rows; the AlertBell component (client portal +
 * staff panel) badges and pops them. De-dupes an identical unread alert raised
 * again within a short window so a burst of model saves doesn't spam the feed.
 */
class Notifier
{
    /**
     * @param  User|int|iterable  $users
     */
    public static function push($users, string $title, array $opts = []): void
    {
        $ids = collect(is_iterable($users) ? $users : [$users])
            ->map(fn ($u) => $u instanceof User ? $u->id : (int) $u)
            ->filter()
            ->unique();

        if ($ids->isEmpty()) {
            return;
        }

        $actor = $opts['actor'] ?? null;
        $actorId = $actor instanceof User ? $actor->id : $actor;
        $dedupeMin = $opts['dedupe_minutes'] ?? 10;
        $url = $opts['url'] ?? null;

        foreach ($ids as $uid) {
            if ($actorId && $actorId === $uid) {
                continue; // never notify the person who caused the change
            }

            $dupe = UserAlert::where('user_id', $uid)
                ->whereNull('read_at')
                ->where('title', $title)
                ->where('url', $url)
                ->where('created_at', '>=', now()->subMinutes($dedupeMin))
                ->exists();

            if ($dupe) {
                continue;
            }

            UserAlert::create([
                'user_id' => $uid,
                'actor_id' => $actorId,
                'icon' => $opts['icon'] ?? 'fa-bell',
                'level' => $opts['level'] ?? 'info',
                'title' => $title,
                'body' => $opts['body'] ?? null,
                'url' => $url,
            ]);
        }
    }

    /**
     * Everyone attached to a project, split into buckets so callers can target
     * "the client side" vs "the agency side" of a change.
     *
     * @return array{0:Collection,1:Collection,2:Collection}  [staffUsers, clientUsers, adminUsers]
     */
    public static function projectAudience(Project $project): array
    {
        $project->loadMissing(['staff.user', 'company.contacts.user']);

        $staff = $project->staff->pluck('user')->filter()->unique('id')->values();

        $clients = (optional($project->company)->contacts ?? collect())
            ->pluck('user')->filter()
            ->filter(fn ($u) => $u->role === 'client')
            ->unique('id')->values();

        $admins = User::where('role', 'admin')->get();

        return [$staff, $clients, $admins];
    }

    /** Portal-login users for a company. */
    public static function companyClients(?int $companyId): Collection
    {
        if (! $companyId) {
            return collect();
        }

        return Contact::where('company_id', $companyId)
            ->whereHas('user', fn ($q) => $q->where('role', 'client'))
            ->with('user')
            ->get()->pluck('user')->filter()->unique('id')->values();
    }
}
