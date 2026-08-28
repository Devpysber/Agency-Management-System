<?php

namespace Database\Seeders;

use App\Models\company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'company_name' => 'Northbridge Holdings',
                'company_email' => 'contact@northbridgeholdings.test',
                'company_phone' => '+1 555-0110',
                'company_website' => 'https://northbridgeholdings.test',
                'company_industry' => 'Finance',
                'company_size' => '201-500',
                'company_type' => 'Enterprise',
                'company_city' => 'Chicago',
                'company_state' => 'IL',
                'company_country' => 'USA',
                'company_owner' => 'Alex Johnson',
                'company_tags' => 'Finance, Enterprise',
                'status' => 'active',
            ],
            [
                'company_name' => 'Willow & Bean Cafe',
                'company_email' => 'hello@willowbean.test',
                'company_phone' => '+1 555-0121',
                'company_website' => 'https://willowbean.test',
                'company_industry' => 'Food & Beverage',
                'company_size' => '1-10',
                'company_type' => 'Small Business',
                'company_city' => 'Portland',
                'company_state' => 'OR',
                'company_country' => 'USA',
                'company_owner' => 'Priya Singh',
                'company_tags' => 'Retail, Local Business',
                'status' => 'active',
            ],
            [
                'company_name' => 'Marlowe Goods Co.',
                'company_email' => 'sales@marlowegoods.test',
                'company_phone' => '+1 555-0132',
                'company_website' => 'https://marlowegoods.test',
                'company_industry' => 'E-commerce',
                'company_size' => '11-50',
                'company_type' => 'SMB',
                'company_city' => 'Austin',
                'company_state' => 'TX',
                'company_country' => 'USA',
                'company_owner' => 'Alex Johnson',
                'company_tags' => 'E-commerce, B2C',
                'status' => 'active',
            ],
            [
                'company_name' => 'Vantage Fitness',
                'company_email' => 'info@vantagefitness.test',
                'company_phone' => '+1 555-0143',
                'company_website' => 'https://vantagefitness.test',
                'company_industry' => 'Health & Wellness',
                'company_size' => '11-50',
                'company_type' => 'SMB',
                'company_city' => 'Denver',
                'company_state' => 'CO',
                'company_country' => 'USA',
                'company_owner' => 'Michael Carter',
                'company_tags' => 'Fitness, Subscription',
                'status' => 'active',
            ],
            [
                'company_name' => 'Pulse Health',
                'company_email' => 'partnerships@pulsehealth.test',
                'company_phone' => '+1 555-0154',
                'company_website' => 'https://pulsehealth.test',
                'company_industry' => 'Healthcare',
                'company_size' => '51-200',
                'company_type' => 'Mid-Market',
                'company_city' => 'Boston',
                'company_state' => 'MA',
                'company_country' => 'USA',
                'company_owner' => 'Sofia Martinez',
                'company_tags' => 'Healthcare, SaaS',
                'status' => 'active',
            ],
            [
                'company_name' => 'Ferro Industrial Supply',
                'company_email' => 'sales@ferroindustrial.test',
                'company_phone' => '+1 555-0165',
                'company_website' => 'https://ferroindustrial.test',
                'company_industry' => 'Manufacturing',
                'company_size' => '201-500',
                'company_type' => 'Enterprise',
                'company_city' => 'Pittsburgh',
                'company_state' => 'PA',
                'company_country' => 'USA',
                'company_owner' => 'David Okafor',
                'company_tags' => 'Manufacturing, B2B',
                'status' => 'active',
            ],
            [
                'company_name' => 'Solstice Media Group',
                'company_email' => 'contact@solsticemedia.test',
                'company_phone' => '+1 555-0176',
                'company_website' => 'https://solsticemedia.test',
                'company_industry' => 'Media',
                'company_size' => '11-50',
                'company_type' => 'SMB',
                'company_city' => 'Los Angeles',
                'company_state' => 'CA',
                'company_country' => 'USA',
                'company_owner' => 'Emily Chen',
                'company_tags' => 'Media, Advertising',
                'status' => 'pending',
            ],
            [
                'company_name' => 'Crestline Logistics',
                'company_email' => 'ops@crestlinelogistics.test',
                'company_phone' => '+1 555-0187',
                'company_website' => 'https://crestlinelogistics.test',
                'company_industry' => 'Logistics',
                'company_size' => '51-200',
                'company_type' => 'Mid-Market',
                'company_city' => 'Memphis',
                'company_state' => 'TN',
                'company_country' => 'USA',
                'company_owner' => 'Ryan Kelly',
                'company_tags' => 'Logistics, B2B',
                'status' => 'inactive',
            ],
        ];

        foreach ($companies as $data) {
            company::updateOrCreate(
                ['company_name' => $data['company_name']],
                $data
            );
        }
    }
}
