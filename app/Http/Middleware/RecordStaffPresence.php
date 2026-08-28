<?php

namespace App\Http\Middleware;

use App\Models\AttendanceRecord;
use App\Models\staff;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Presence" tracking for agency staff, like an always-on status light.
 * While a staff member has the admin panel open, each page load:
 *  - stamps them "online" for the next 5 minutes (cache), and
 *  - keeps today's attendance record's check-in / check-out / worked minutes
 *    up to date automatically (source = auto), so working hours are measured
 *    from real activity, not a manual clock.
 */
class RecordStaffPresence
{
    public const ONLINE_TTL = 120; // seconds — kept fresh by the JS heartbeat while the tab is visible

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // The admin ("the leader") is not tracked staff — no presence, no
        // attendance record, even if a staff row happens to be linked.
        $isTracked = $user
            && ! in_array($user->role, ['client', 'admin'], true)
            && $request->isMethod('GET')
            && ! $request->hasHeader('X-Livewire');

        if ($isTracked) {
            $staffId = staff::where('user_id', $user->id)->value('id');
            if ($staffId) {
                try {
                    AttendanceRecord::recordStaffActivity((int) $staffId);
                    Cache::put('staff_online_' . $staffId, now()->timestamp, self::ONLINE_TTL);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        // No-show sweep runs for any staff/admin panel view (cache-locked to
        // once per 15 min) so absences are caught even if the admin is the only
        // one online.
        if ($user && $user->role !== 'client' && $request->isMethod('GET') && ! $request->hasHeader('X-Livewire')) {
            try {
                if (Cache::add('att_absence_sweep', 1, 900)) {
                    foreach (staff::where('status', 'active')->get() as $member) {
                        if ($member->user && $member->user->role === 'admin') {
                            continue; // the linked admin is not tracked
                        }
                        AttendanceRecord::evaluateAutoAbsence($member);
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
