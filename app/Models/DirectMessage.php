<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{
    protected $fillable = ['from_user_id', 'to_user_id', 'body', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function from()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /** Every message either direction between these two users, oldest first. */
    public function scopeBetween($query, int $userA, int $userB)
    {
        return $query->where(function ($q) use ($userA, $userB) {
            $q->where('from_user_id', $userA)->where('to_user_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('from_user_id', $userB)->where('to_user_id', $userA);
        });
    }

    /**
     * The CEO's user, resolved dynamically — never a hardcoded id/email.
     * Prefers the system admin account: admin and CEO are one combined
     * identity/panel elsewhere in this app (same dashboard, same sidebar),
     * and a single canonical recipient keeps every staff member's thread
     * (and the CEO-side inbox) consistent instead of splitting across two
     * accounts. Falls back to whoever holds the CEO designation if this
     * install has no admin account for some reason.
     */
    public static function resolveCeoUser(): ?User
    {
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            return $admin;
        }
        $ceoUserId = staff::where('designation', 'CEO')->whereNotNull('user_id')->value('user_id');
        return $ceoUserId ? User::find($ceoUserId) : null;
    }
}
