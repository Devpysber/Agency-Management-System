<?php

namespace Database\Seeders;

use App\Models\company;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::inRandomOrder()->first()?->id;

        $quotations = [
            [
                'name' => 'Daniel Ortiz',
                'email' => 'daniel.ortiz@brightwave.test',
                'phone' => '+1 555-201-3344',
                'service_interest' => 'WordPress Website',
                'message' => 'We need a new company website with a blog and contact forms. Looking for a rough estimate before we commit budget.',
                'status' => 'pending',
                'quoted_amount' => null,
                'responded_at' => null,
            ],
            [
                'name' => 'Priya Nair',
                'email' => 'priya.nair@meridianretail.test',
                'phone' => '+1 555-482-9012',
                'service_interest' => 'Social Media Management',
                'message' => 'Interested in ongoing social media management for our retail brand across Instagram and Facebook.',
                'status' => 'pending',
                'quoted_amount' => null,
                'responded_at' => null,
            ],
            [
                'name' => 'Marcus Webb',
                'email' => 'marcus.webb@fernhollow.test',
                'phone' => '+1 555-773-6620',
                'service_interest' => 'SEO Optimization',
                'message' => 'Our site traffic has been flat for months. Would like a quote for a full SEO audit and ongoing optimization.',
                'status' => 'reviewed',
                'quoted_amount' => null,
                'responded_at' => now()->subDays(3),
            ],
            [
                'name' => 'Sofia Reyes',
                'email' => 'sofia.reyes@lumenstudio.test',
                'phone' => '+1 555-664-1187',
                'service_interest' => 'Mobile App Development',
                'message' => 'We want to build a companion mobile app for our existing platform. Please send over a quote with timeline.',
                'status' => 'quoted',
                'quoted_amount' => 5200.00,
                'responded_at' => now()->subDays(5),
            ],
            [
                'name' => 'James Okafor',
                'email' => 'james.okafor@northstarlogistics.test',
                'phone' => '+1 555-390-4471',
                'service_interest' => 'Design & Branding',
                'message' => 'Rebranding our logistics company - new logo, brand guidelines, and stationery. What would this cost?',
                'status' => 'accepted',
                'quoted_amount' => 2100.00,
                'responded_at' => now()->subDays(10),
            ],
            [
                'name' => 'Helena Choi',
                'email' => 'helena.choi@aspenventures.test',
                'phone' => null,
                'service_interest' => 'Mobile App Development',
                'message' => 'Requested a quote for an MVP mobile app but budget did not align with scope after review.',
                'status' => 'rejected',
                'quoted_amount' => 6800.00,
                'responded_at' => now()->subDays(7),
            ],
        ];

        foreach ($quotations as $data) {
            Quotation::create(array_merge($data, [
                'company_id' => Company::inRandomOrder()->first()?->id,
                'created_by' => $userId,
            ]));
        }
    }
}
