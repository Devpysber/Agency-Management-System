<?php

namespace Database\Seeders;

use App\Models\company;
use App\Models\Contact;
use App\Models\deal;
use App\Models\User;
use Illuminate\Database\Seeder;

class DealSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'test@example.com')->first() ?? User::first();

        $companyByName = fn (string $name) => company::where('company_name', $name)->first();
        $contactByEmail = fn (string $email) => Contact::where('email', $email)->first();

        $deals = [
            // Active pipeline deals (deal_status = active)
            [
                'deal_name' => 'Northbridge Annual Platform Renewal',
                'deal_notes' => 'Renewing the enterprise plan with an upsell to the analytics add-on.',
                'deal_value' => 48000,
                'deal_stage' => 'negotiation',
                'probability' => 70,
                'deal_status' => 'active',
                'expected_close_date' => now()->addDays(20),
                'company' => 'Northbridge Holdings',
                'contact' => 'laura.bennett@northbridgeholdings.test',
            ],
            [
                'deal_name' => 'Marlowe Goods Storefront Expansion',
                'deal_notes' => 'Adding a wholesale storefront alongside the existing retail site.',
                'deal_value' => 22000,
                'deal_stage' => 'proposal',
                'probability' => 50,
                'deal_status' => 'active',
                'expected_close_date' => now()->addDays(35),
                'company' => 'Marlowe Goods Co.',
                'contact' => 'daniel@marlowegoods.test',
            ],
            [
                'deal_name' => 'Pulse Health Patient Portal',
                'deal_notes' => 'Custom patient intake portal integrated with their EHR.',
                'deal_value' => 65000,
                'deal_stage' => 'negotiation',
                'probability' => 80,
                'deal_status' => 'active',
                'expected_close_date' => now()->addDays(15),
                'company' => 'Pulse Health',
                'contact' => 'oliver.reyes@pulsehealth.test',
            ],
            [
                'deal_name' => 'Vantage Fitness Loyalty App',
                'deal_notes' => 'Mobile loyalty rewards app tied into their membership system.',
                'deal_value' => 18500,
                'deal_stage' => 'qualified',
                'probability' => 40,
                'deal_status' => 'on_hold',
                'expected_close_date' => now()->addDays(45),
                'company' => 'Vantage Fitness',
                'contact' => 'nina.torres@vantagefitness.test',
            ],

            // Pipeline-stage deals (deal_status = pipeline — early, unqualified)
            [
                'deal_name' => 'Solstice Media Brand Refresh',
                'deal_notes' => 'Initial outreach after a referral; awaiting discovery call.',
                'deal_value' => 15000,
                'deal_stage' => 'lead',
                'probability' => 15,
                'deal_status' => 'pipeline',
                'expected_close_date' => now()->addDays(60),
                'company' => 'Solstice Media Group',
                'contact' => 'marcus.dupree@solsticemedia.test',
            ],
            [
                'deal_name' => 'Ferro Industrial Supplier Portal',
                'deal_notes' => 'Exploring a B2B ordering portal for their distributor network.',
                'deal_value' => 54000,
                'deal_stage' => 'qualified',
                'probability' => 30,
                'deal_status' => 'pipeline',
                'expected_close_date' => now()->addDays(50),
                'company' => 'Ferro Industrial Supply',
                'contact' => 'sara.whitfield@ferroindustrial.test',
            ],
            [
                'deal_name' => 'Marlowe Goods Loyalty Program',
                'deal_notes' => 'Early conversation about a points-based loyalty program.',
                'deal_value' => 9000,
                'deal_stage' => 'lead',
                'probability' => 10,
                'deal_status' => 'pipeline',
                'expected_close_date' => now()->addDays(70),
                'company' => 'Marlowe Goods Co.',
                'contact' => 'ben.alvarez@marlowegoods.test',
            ],

            // Won deals
            [
                'deal_name' => 'Willow & Bean Brand Identity Package',
                'deal_notes' => 'Full brand refresh delivered and signed off.',
                'deal_value' => 8500,
                'deal_stage' => 'closed_won',
                'probability' => 100,
                'deal_status' => 'won',
                'expected_close_date' => now()->subDays(10),
                'actual_close_date' => now()->subDays(8),
                'company' => 'Willow & Bean Cafe',
                'contact' => 'grace.kim@willowbean.test',
            ],
            [
                'deal_name' => 'Pulse Health Mobile App Overhaul',
                'deal_notes' => 'UI/UX redesign shipped ahead of schedule.',
                'deal_value' => 32000,
                'deal_stage' => 'closed_won',
                'probability' => 100,
                'deal_status' => 'won',
                'expected_close_date' => now()->subDays(25),
                'actual_close_date' => now()->subDays(20),
                'company' => 'Pulse Health',
                'contact' => 'oliver.reyes@pulsehealth.test',
            ],
            [
                'deal_name' => 'Northbridge Holdings Website Redesign',
                'deal_notes' => 'Corporate site relaunch — the project behind our top portfolio piece.',
                'deal_value' => 27500,
                'deal_stage' => 'closed_won',
                'probability' => 100,
                'deal_status' => 'won',
                'expected_close_date' => now()->subDays(40),
                'actual_close_date' => now()->subDays(35),
                'company' => 'Northbridge Holdings',
                'contact' => 'laura.bennett@northbridgeholdings.test',
            ],

            // Lost deals
            [
                'deal_name' => 'Crestline Logistics Fleet Tracker',
                'deal_notes' => 'Went with an in-house team instead after budget cuts.',
                'deal_value' => 41000,
                'deal_stage' => 'closed_lost',
                'probability' => 0,
                'deal_status' => 'lost',
                'expected_close_date' => now()->subDays(15),
                'actual_close_date' => now()->subDays(12),
                'company' => 'Crestline Logistics',
                'contact' => 'ivy.chan@crestlinelogistics.test',
            ],
            [
                'deal_name' => 'Ferro Industrial E-commerce Catalog',
                'deal_notes' => 'Lost to a lower-cost competitor during final negotiation.',
                'deal_value' => 19500,
                'deal_stage' => 'closed_lost',
                'probability' => 0,
                'deal_status' => 'lost',
                'expected_close_date' => now()->subDays(5),
                'actual_close_date' => now()->subDays(3),
                'company' => 'Ferro Industrial Supply',
                'contact' => 'sara.whitfield@ferroindustrial.test',
            ],
        ];

        // Sales-owned opportunities need an owner — round-robin across the
        // seeded sales staff so BDM/Sales Executive dashboards have real
        // "my deals" data instead of an empty state.
        $salesStaff = \App\Models\staff::whereIn('designation', ['Sales Executive', 'Business Development Manager'])
            ->pluck('id')->all();

        foreach ($deals as $i => $data) {
            $companyModel = $companyByName($data['company']);
            $contactModel = $contactByEmail($data['contact']);
            unset($data['company'], $data['contact']);

            deal::updateOrCreate(
                ['deal_name' => $data['deal_name']],
                array_merge($data, [
                    'currency' => 'USD',
                    'company_id' => $companyModel?->id,
                    'contact_id' => $contactModel?->id,
                    'assigned_to' => $salesStaff ? $salesStaff[$i % count($salesStaff)] : null,
                    'created_by' => $adminUser?->id,
                ])
            );
        }
    }
}
