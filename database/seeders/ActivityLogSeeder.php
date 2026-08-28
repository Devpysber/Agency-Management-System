<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'test@example.com')->first() ?? User::first();

        $items = [
            ['log_name' => 'deal', 'description' => 'created a new deal "Website Redesign Package"', 'created_at' => now()->subDays(6)],
            ['log_name' => 'contact', 'description' => 'added a new contact', 'created_at' => now()->subDays(5)],
            ['log_name' => 'task', 'description' => 'completed task "Set up staging environment"', 'created_at' => now()->subDays(5)],
            ['log_name' => 'project', 'description' => 'moved project to "in progress"', 'created_at' => now()->subDays(4)],
            ['log_name' => 'communication', 'description' => 'logged an outbound call with a client', 'created_at' => now()->subDays(4)],
            ['log_name' => 'staff', 'description' => 'added new staff member "Priya Singh"', 'created_at' => now()->subDays(3)],
            ['log_name' => 'deal', 'description' => 'marked deal "Brand Identity Project" as won', 'created_at' => now()->subDays(2)],
            ['log_name' => 'company', 'description' => 'updated company details', 'created_at' => now()->subDays(2)],
            ['log_name' => 'calendar', 'description' => 'scheduled a design review meeting', 'created_at' => now()->subDay()],
            ['log_name' => 'task', 'description' => 'created task "Fix responsive layout bug on pricing page"', 'created_at' => now()->subHours(10)],
        ];

        foreach ($items as $data) {
            ActivityLog::firstOrCreate(
                ['description' => $data['description']],
                [
                    'log_name' => $data['log_name'],
                    'causer_id' => $adminUser?->id,
                    'causer_name' => $adminUser?->name,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['created_at'],
                ]
            );
        }
    }
}
