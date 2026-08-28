<?php

namespace Database\Seeders;

use App\Models\staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Gives every staff member a login. Idempotent: creates the User when missing,
 * relinks it, and (for the seeded *.agency.test accounts only) resets the
 * password to a known dev value so the team can sign in. Never touches the
 * admin (test@example.com) — the admin is not tracked staff.
 */
class StaffLoginSeeder extends Seeder
{
    public const DEV_PASSWORD = 'password';

    public function run(): void
    {
        foreach (staff::all() as $member) {
            $email = $member->email ?: str(strtolower($member->name))->replace(' ', '.') . '@agency.test';

            $user = User::where('email', $email)->first();

            // staff row was pointing at the shared admin user — give it its own.
            $linkedIsAdmin = $member->user_id
                && optional(User::find($member->user_id))->role === 'admin';

            if (! $user) {
                $user = User::create([
                    'name' => $member->name,
                    'email' => $email,
                    'password' => Hash::make(self::DEV_PASSWORD),
                    'role' => 'staff',
                ]);
            } elseif (str_ends_with($email, '@agency.test')) {
                $user->forceFill([
                    'role' => 'staff',
                    'password' => Hash::make(self::DEV_PASSWORD),
                ])->save();
            }

            if ($member->user_id !== $user->id || $linkedIsAdmin) {
                $member->forceFill(['user_id' => $user->id])->save();
            }
        }
    }
}
