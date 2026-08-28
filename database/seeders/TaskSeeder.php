<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\staff;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'test@example.com')->first() ?? User::first();

        // Ensure at least a couple of staff records exist so tasks can be
        // meaningfully assigned. One is linked back to the seeded admin user
        // so "My Tasks" has something real to show.
        $alex = staff::firstOrCreate(
            ['email' => 'alex.johnson@agency.test'],
            [
                'name' => 'Alex Johnson',
                'whatsapp' => '+1 555-0101',
                'designation' => 'Project Manager',
                'joining_date' => now()->subYear(),
                'user_id' => $adminUser?->id,
                'salary' => 4500,
            ]
        );

        $priya = staff::firstOrCreate(
            ['email' => 'priya.singh@agency.test'],
            [
                'name' => 'Priya Singh',
                'whatsapp' => '+1 555-0102',
                'designation' => 'Developer',
                'joining_date' => now()->subMonths(8),
                'salary' => 3800,
            ]
        );

        $projectIds = Project::pluck('id');

        $tasks = [
            [
                'title' => 'Prepare project kickoff deck',
                'description' => 'Put together the kickoff presentation covering scope, timeline, and milestones.',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => now()->addDays(3),
                'assigned_to' => $alex->id,
            ],
            [
                'title' => 'Follow up on overdue client feedback',
                'description' => 'Client has not responded to the design review request from last week.',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'due_date' => now()->subDays(2),
                'assigned_to' => $alex->id,
            ],
            [
                'title' => 'Fix responsive layout bug on pricing page',
                'description' => 'Pricing cards overlap on small screens below 375px width.',
                'priority' => 'medium',
                'status' => 'in_progress',
                'due_date' => now()->addDays(5),
                'assigned_to' => $priya->id,
            ],
            [
                'title' => 'Set up staging environment',
                'description' => 'Provision staging server and deploy the latest build for QA.',
                'priority' => 'high',
                'status' => 'completed',
                'due_date' => now()->subDays(4),
                'completed_at' => now()->subDays(5),
                'assigned_to' => $priya->id,
            ],
            [
                'title' => 'Draft monthly performance report',
                'description' => 'Summarize deliverables, hours, and milestones completed this month.',
                'priority' => 'low',
                'status' => 'completed',
                'due_date' => now()->subDays(10),
                'completed_at' => now()->subDays(6),
                'assigned_to' => $alex->id,
            ],
            [
                'title' => 'Archive cancelled vendor contract review',
                'description' => 'Vendor engagement was called off; no further action required.',
                'priority' => 'low',
                'status' => 'cancelled',
                'due_date' => now()->subDays(15),
                'assigned_to' => null,
            ],
        ];

        foreach ($tasks as $index => $data) {
            Task::firstOrCreate(
                ['title' => $data['title']],
                [
                    'description' => $data['description'],
                    'priority' => $data['priority'],
                    'status' => $data['status'],
                    'due_date' => $data['due_date'],
                    'completed_at' => $data['completed_at'] ?? null,
                    'assigned_to' => $data['assigned_to'],
                    'created_by' => $adminUser?->id,
                    'related_type' => $projectIds->isNotEmpty() ? 'project' : null,
                    'related_to' => $projectIds->isNotEmpty() ? $projectIds[$index % $projectIds->count()] : null,
                ]
            );
        }
    }
}
