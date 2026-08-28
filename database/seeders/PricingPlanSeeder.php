<?php

namespace Database\Seeders;

use App\Models\PricingPlan;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class PricingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'price' => 99.00,
                'billing_period' => 'monthly',
                'features' => [
                    '1 project at a time',
                    'Basic support',
                    'Monthly performance report',
                ],
                'countries' => ['United States', 'Canada'],
                'status' => 'active',
            ],
            [
                'name' => 'Professional',
                'price' => 299.00,
                'billing_period' => 'monthly',
                'features' => [
                    'Up to 5 projects',
                    'Priority support',
                    'Weekly performance report',
                    'Dedicated account manager',
                ],
                'countries' => ['United States', 'United Kingdom', 'Canada'],
                'status' => 'active',
            ],
            [
                'name' => 'Business',
                'price' => 2999.00,
                'billing_period' => 'yearly',
                'features' => [
                    'Unlimited projects',
                    '24/7 support',
                    'Weekly performance report',
                    'Dedicated account manager',
                    'Custom integrations',
                ],
                'countries' => ['United States', 'United Kingdom', 'Canada', 'Australia'],
                'status' => 'active',
            ],
            [
                'name' => 'Enterprise',
                'price' => 4999.00,
                'billing_period' => 'one_time',
                'features' => [
                    'Unlimited projects',
                    '24/7 premium support',
                    'Custom reporting',
                    'Dedicated account manager',
                    'On-site consultation',
                ],
                'countries' => ['United States', 'United Kingdom'],
                'status' => 'inactive',
            ],
        ];

        foreach ($plans as $plan) {
            PricingPlan::create(array_merge($plan, [
                'service_category_id' => ServiceCategory::inRandomOrder()->first()?->id,
            ]));
        }
    }
}
