<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\staff;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'test@example.com')->first() ?? User::first();
        $alex = staff::where('email', 'alex.johnson@agency.test')->first();
        $priya = staff::where('email', 'priya.singh@agency.test')->first();

        $events = [
            [
                'title' => 'Client kickoff call',
                'description' => 'Introduce the team and walk through project scope.',
                'event_type' => 'call',
                'start_at' => now()->addDay()->setTime(10, 0),
                'end_at' => now()->addDay()->setTime(10, 30),
                'status' => 'scheduled',
                'assigned_to' => $alex?->id,
            ],
            [
                'title' => 'Design review meeting',
                'description' => 'Review homepage mockups with the client.',
                'event_type' => 'meeting',
                'start_at' => now()->addDays(2)->setTime(14, 0),
                'end_at' => now()->addDays(2)->setTime(15, 0),
                'location' => 'Zoom',
                'status' => 'scheduled',
                'assigned_to' => $priya?->id,
            ],
            [
                'title' => 'Quarterly report deadline',
                'description' => 'Submit the Q3 performance report to management.',
                'event_type' => 'deadline',
                'start_at' => now()->addDays(5)->setTime(17, 0),
                'status' => 'scheduled',
                'assigned_to' => $alex?->id,
            ],
            [
                'title' => 'Follow up with overdue client',
                'description' => 'Client feedback is overdue, needs a nudge.',
                'event_type' => 'reminder',
                'start_at' => now()->subDays(1)->setTime(9, 0),
                'status' => 'scheduled',
                'assigned_to' => $priya?->id,
            ],
            [
                'title' => 'Weekly team sync',
                'description' => 'Standup covering progress across all active projects.',
                'event_type' => 'meeting',
                'start_at' => now()->subDays(3)->setTime(11, 0),
                'end_at' => now()->subDays(3)->setTime(11, 30),
                'status' => 'completed',
                'assigned_to' => $alex?->id,
            ],
        ];

        foreach ($events as $data) {
            CalendarEvent::firstOrCreate(
                ['title' => $data['title']],
                array_merge($data, ['created_by' => $adminUser?->id])
            );
        }
    }
}
