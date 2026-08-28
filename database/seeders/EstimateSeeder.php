<?php

namespace Database\Seeders;

use App\Models\company;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class EstimateSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::inRandomOrder()->first()?->id;

        $estimates = [
            [
                'estimate_number' => 'EST-2026-0001',
                'client_name' => 'Northbridge Holdings',
                'client_email' => 'accounts@northbridge.test',
                'issue_date' => '2026-07-05',
                'valid_until' => '2026-08-05',
                'status' => 'approved',
                'tax' => 50.00,
                'notes' => 'Approved after initial revisions to the homepage layout.',
                'items' => [
                    ['description' => 'Website UX/UI Design', 'qty' => 1, 'unit_price' => 1500.00],
                    ['description' => 'Frontend Development', 'qty' => 1, 'unit_price' => 2200.00],
                    ['description' => 'SEO Setup', 'qty' => 1, 'unit_price' => 300.00],
                ],
            ],
            [
                'estimate_number' => 'EST-2026-0002',
                'client_name' => 'Willow & Bean Cafe',
                'client_email' => 'hello@willowbean.test',
                'issue_date' => '2026-07-12',
                'valid_until' => '2026-08-12',
                'status' => 'sent',
                'tax' => 15.00,
                'notes' => 'Awaiting client sign-off on branding direction.',
                'items' => [
                    ['description' => 'Logo Design', 'qty' => 1, 'unit_price' => 450.00],
                    ['description' => 'Packaging Design', 'qty' => 3, 'unit_price' => 120.00],
                ],
            ],
            [
                'estimate_number' => 'EST-2026-0003',
                'client_name' => 'Marlowe Goods Co.',
                'client_email' => 'ops@marlowegoods.test',
                'issue_date' => '2026-08-01',
                'valid_until' => '2026-09-01',
                'status' => 'draft',
                'tax' => 0.00,
                'notes' => 'Draft pending internal cost review.',
                'items' => [
                    ['description' => 'E-commerce Platform Setup', 'qty' => 1, 'unit_price' => 3200.00],
                    ['description' => 'Payment Gateway Integration', 'qty' => 1, 'unit_price' => 600.00],
                    ['description' => 'Product Catalog Migration', 'qty' => 2, 'unit_price' => 250.00],
                ],
            ],
            [
                'estimate_number' => 'EST-2026-0004',
                'client_name' => 'Vantage Fitness',
                'client_email' => 'marketing@vantagefitness.test',
                'issue_date' => '2026-08-10',
                'valid_until' => '2026-09-10',
                'status' => 'sent',
                'tax' => 25.00,
                'notes' => null,
                'items' => [
                    ['description' => 'Social Media Campaign Assets', 'qty' => 10, 'unit_price' => 45.00],
                    ['description' => 'Ad Copywriting', 'qty' => 5, 'unit_price' => 30.00],
                ],
            ],
            [
                'estimate_number' => 'EST-2026-0005',
                'client_name' => 'Pulse Health',
                'client_email' => 'projects@pulsehealth.test',
                'issue_date' => '2026-08-18',
                'valid_until' => '2026-09-18',
                'status' => 'draft',
                'tax' => 40.00,
                'notes' => 'Includes optional navigation redesign phase.',
                'items' => [
                    ['description' => 'Mobile App UX Audit', 'qty' => 1, 'unit_price' => 800.00],
                    ['description' => 'Navigation Redesign', 'qty' => 1, 'unit_price' => 950.00],
                    ['description' => 'Usability Testing Sessions', 'qty' => 4, 'unit_price' => 100.00],
                ],
            ],
        ];

        foreach ($estimates as $data) {
            $items = $data['items'];
            unset($data['items']);

            $subtotal = collect($items)->sum(function ($item) {
                return $item['qty'] * $item['unit_price'];
            });

            $tax = $data['tax'] ?? 0;

            $estimate = Estimate::create(array_merge($data, [
                'company_id' => Company::inRandomOrder()->first()?->id,
                'subtotal' => $subtotal,
                'total' => $subtotal + $tax,
                'created_by' => $userId,
            ]));

            foreach ($items as $item) {
                EstimateItem::create(array_merge($item, [
                    'estimate_id' => $estimate->id,
                ]));
            }
        }
    }
}
