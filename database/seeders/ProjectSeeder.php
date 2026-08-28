<?php

namespace Database\Seeders;

use App\Models\company;
use App\Models\PaymentGateway;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayment;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'];
        $gatewayIds = PaymentGateway::pluck('id')->toArray();

        $projectNames = [
            'Website Redesign',
            'Mobile App Development',
            'Brand Identity Overhaul',
            'E-commerce Platform Launch',
            'Marketing Automation Setup',
            'CRM Integration Project',
        ];

        foreach ($projectNames as $index => $name) {
            $status = $statuses[$index % count($statuses)];
            $progress = match ($status) {
                'planning' => 0,
                'in_progress' => rand(20, 80),
                'on_hold' => rand(10, 50),
                'completed' => 100,
                'cancelled' => rand(0, 40),
                default => 0,
            };

            $project = Project::create([
                'company_id' => company::inRandomOrder()->first()?->id,
                'name' => $name,
                'description' => 'Description for the ' . $name . ' engagement, covering scope, deliverables, and timeline.',
                'start_date' => now()->subDays(rand(30, 180)),
                'end_date' => now()->addDays(rand(10, 120)),
                'status' => $status,
                'progress' => $progress,
                'budget' => rand(5000, 150000),
                'created_by' => \App\Models\User::inRandomOrder()->first()?->id,
            ]);

            // Milestones (2-3 per project)
            $milestoneTitles = ['Discovery & Planning', 'Design Phase', 'Development Phase', 'Testing & QA', 'Launch & Handover'];
            $milestoneCount = rand(2, 3);
            $selectedMilestones = collect($milestoneTitles)->random($milestoneCount)->values();

            foreach ($selectedMilestones as $mIndex => $title) {
                $isCompleted = $mIndex === 0 && in_array($status, ['in_progress', 'completed']);
                ProjectMilestone::create([
                    'project_id' => $project->id,
                    'title' => $title,
                    'description' => 'Milestone covering ' . strtolower($title) . ' for ' . $name . '.',
                    'due_date' => now()->addDays(rand(-30, 90)),
                    'status' => $isCompleted ? 'completed' : ($status === 'completed' ? 'completed' : 'pending'),
                    'completed_at' => $isCompleted || $status === 'completed' ? now()->subDays(rand(1, 20)) : null,
                ]);
            }

            // Payments (1-2 per project)
            $paymentCount = rand(1, 2);
            for ($p = 0; $p < $paymentCount; $p++) {
                $possibleStatuses = ['pending', 'paid', 'failed'];
                $paymentStatus = $p === 0 ? 'paid' : $possibleStatuses[array_rand($possibleStatuses)];
                ProjectPayment::create([
                    'project_id' => $project->id,
                    'amount' => rand(1000, 50000),
                    'currency' => 'USD',
                    'payment_gateway_id' => !empty($gatewayIds) ? $gatewayIds[array_rand($gatewayIds)] : null,
                    'status' => $paymentStatus,
                    'reference' => 'REF-' . strtoupper(uniqid()),
                    'paid_at' => $paymentStatus === 'paid' ? now()->subDays(rand(1, 60)) : null,
                    'notes' => null,
                ]);
            }
        }
    }
}
