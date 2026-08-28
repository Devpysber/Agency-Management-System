<?php

namespace App\Support;

use App\Models\staff;

/**
 * Who may change CRM records. Admins always; staff only if their designation is
 * a management role. Everyone else (other staff, clients) is read-only.
 */
class EditGate
{
    /** Designations treated as "manager" for edit rights. */
    public const MANAGER_DESIGNATIONS = ['Project Manager'];

    public static function allows(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin') {
            return true;
        }
        if ($user->role === 'client') {
            return false;
        }

        $designation = staff::where('user_id', $user->id)->value('designation');

        return in_array($designation, self::MANAGER_DESIGNATIONS, true);
    }
}
