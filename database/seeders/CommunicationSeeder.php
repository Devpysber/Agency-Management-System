<?php

namespace Database\Seeders;

use App\Models\Communication;
use App\Models\Contact;
use App\Models\User;
use App\Models\staff;
use Illuminate\Database\Seeder;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'test@example.com')->first() ?? User::first();
        $alex = staff::where('email', 'alex.johnson@agency.test')->first();
        $priya = staff::where('email', 'priya.singh@agency.test')->first();
        $contact = Contact::first();

        $items = [
            [
                'type' => 'email',
                'direction' => 'outbound',
                'subject' => 'Proposal follow-up',
                'notes' => 'Sent the revised proposal after the client\'s pricing questions.',
                'status' => 'completed',
                'occurred_at' => now()->subDays(2),
                'staff_id' => $alex?->id,
            ],
            [
                'type' => 'email',
                'direction' => 'inbound',
                'subject' => 'Question about invoice',
                'notes' => 'Client asked for a breakdown of the last invoice.',
                'status' => 'completed',
                'occurred_at' => now()->subDay(),
                'staff_id' => $priya?->id,
            ],
            [
                'type' => 'call',
                'direction' => 'outbound',
                'subject' => 'Onboarding call',
                'notes' => 'Walked the client through the onboarding checklist.',
                'status' => 'completed',
                'duration_minutes' => 25,
                'occurred_at' => now()->subDays(4),
                'staff_id' => $alex?->id,
            ],
            [
                'type' => 'call',
                'direction' => 'inbound',
                'subject' => 'Support call - login issue',
                'notes' => 'Client could not access the dashboard, resolved with a password reset.',
                'status' => 'completed',
                'duration_minutes' => 12,
                'occurred_at' => now()->subHours(6),
                'staff_id' => $priya?->id,
            ],
            [
                'type' => 'call',
                'subject' => 'Upcoming renewal discussion',
                'notes' => 'Scheduled to discuss contract renewal terms.',
                'status' => 'scheduled',
                'occurred_at' => now()->addDays(3)->setTime(15, 0),
                'staff_id' => $alex?->id,
            ],
            [
                'type' => 'meeting',
                'subject' => 'Project scoping session',
                'notes' => 'Defined milestones and deliverables for the new project.',
                'status' => 'completed',
                'duration_minutes' => 60,
                'occurred_at' => now()->subDays(6),
                'staff_id' => $priya?->id,
            ],
            [
                'type' => 'meeting',
                'subject' => 'Monthly strategy review',
                'notes' => 'Upcoming review of campaign performance and next steps.',
                'status' => 'scheduled',
                'occurred_at' => now()->addDays(4)->setTime(11, 0),
                'staff_id' => $alex?->id,
            ],
        ];

        foreach ($items as $data) {
            Communication::firstOrCreate(
                ['subject' => $data['subject']],
                array_merge($data, [
                    'contact_id' => $contact?->id,
                    'created_by' => $adminUser?->id,
                ])
            );
        }
    }
}
