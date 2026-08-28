<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPortalVisit extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'visited_on',
        'first_seen_at',
        'last_seen_at',
        'hits',
    ];

    // visited_on is intentionally NOT cast to 'date' — the date cast serialises
    // back to the DB as 'Y-m-d H:i:s', which breaks string range comparisons
    // (whereBetween on 'Y-m-d' bounds). Store and compare it as a plain 'Y-m-d'.
    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'hits' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id');
    }

    /**
     * Record that $user is on the portal right now. One row per user per day;
     * `hits` counts page loads that day. Cheap enough to call per request.
     */
    public static function touchFor(User $user): void
    {
        $today = now()->toDateString();

        $visit = static::where('user_id', $user->id)
            ->where('visited_on', $today)
            ->first();

        if ($visit) {
            // Count a fresh "visit" only after a 2-minute gap, so background
            // polling / SPA navigation don't inflate the number into the hundreds.
            $isNewVisit = ! $visit->last_seen_at || $visit->last_seen_at->lt(now()->subMinutes(2));
            $visit->forceFill([
                'last_seen_at' => now(),
                'hits' => $visit->hits + ($isNewVisit ? 1 : 0),
            ])->save();
            return;
        }

        try {
            static::create([
                'user_id' => $user->id,
                'company_id' => $user->contact?->company_id,
                'visited_on' => $today,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'hits' => 1,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Concurrent first request of the day already inserted the row.
            static::where('user_id', $user->id)
                ->where('visited_on', $today)
                ->increment('hits', 1, ['last_seen_at' => now()]);
        }
    }
}
