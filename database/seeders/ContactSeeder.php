<?php

namespace Database\Seeders;

use App\Models\company;
use App\Models\Contact;
use App\Models\staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'test@example.com')->first() ?? User::first();
        $alex = staff::where('email', 'alex.johnson@agency.test')->first();
        $priya = staff::where('email', 'priya.singh@agency.test')->first();

        $byName = fn (string $name) => company::where('company_name', $name)->first();

        $contacts = [
            [
                'first_name' => 'Laura', 'last_name' => 'Bennett', 'email' => 'laura.bennett@northbridgeholdings.test',
                'phone' => '+1 555-0201', 'company' => 'Northbridge Holdings', 'job_title' => 'VP of Operations',
                'lead_status' => 'customer', 'lead_score' => 85, 'status' => 'active', 'assigned_to' => $alex?->id,
            ],
            [
                'first_name' => 'Grace', 'last_name' => 'Kim', 'email' => 'grace.kim@willowbean.test',
                'phone' => '+1 555-0212', 'company' => 'Willow & Bean Cafe', 'job_title' => 'Owner',
                'lead_status' => 'customer', 'lead_score' => 70, 'status' => 'active', 'assigned_to' => $priya?->id,
            ],
            [
                'first_name' => 'Daniel', 'last_name' => 'Marlowe', 'email' => 'daniel@marlowegoods.test',
                'phone' => '+1 555-0223', 'company' => 'Marlowe Goods Co.', 'job_title' => 'Founder & CEO',
                'lead_status' => 'qualified', 'lead_score' => 60, 'status' => 'active', 'assigned_to' => $alex?->id,
            ],
            [
                'first_name' => 'Nina', 'last_name' => 'Torres', 'email' => 'nina.torres@vantagefitness.test',
                'phone' => '+1 555-0234', 'company' => 'Vantage Fitness', 'job_title' => 'Marketing Director',
                'lead_status' => 'contacted', 'lead_score' => 45, 'status' => 'active', 'assigned_to' => $priya?->id,
            ],
            [
                'first_name' => 'Oliver', 'last_name' => 'Reyes', 'email' => 'oliver.reyes@pulsehealth.test',
                'phone' => '+1 555-0245', 'company' => 'Pulse Health', 'job_title' => 'Head of Product',
                'lead_status' => 'customer', 'lead_score' => 90, 'status' => 'active', 'assigned_to' => $alex?->id,
            ],
            [
                'first_name' => 'Sara', 'last_name' => 'Whitfield', 'email' => 'sara.whitfield@ferroindustrial.test',
                'phone' => '+1 555-0256', 'company' => 'Ferro Industrial Supply', 'job_title' => 'Procurement Manager',
                'lead_status' => 'qualified', 'lead_score' => 55, 'status' => 'active', 'assigned_to' => $priya?->id,
            ],
            [
                'first_name' => 'Marcus', 'last_name' => 'Dupree', 'email' => 'marcus.dupree@solsticemedia.test',
                'phone' => '+1 555-0267', 'company' => 'Solstice Media Group', 'job_title' => 'Creative Director',
                'lead_status' => 'new', 'lead_score' => 20, 'status' => 'active', 'assigned_to' => null,
            ],
            [
                'first_name' => 'Ivy', 'last_name' => 'Chan', 'email' => 'ivy.chan@crestlinelogistics.test',
                'phone' => '+1 555-0278', 'company' => 'Crestline Logistics', 'job_title' => 'Operations Lead',
                'lead_status' => 'lost', 'lead_score' => 10, 'status' => 'inactive', 'assigned_to' => null,
            ],
            [
                'first_name' => 'Ben', 'last_name' => 'Alvarez', 'email' => 'ben.alvarez@marlowegoods.test',
                'phone' => '+1 555-0289', 'company' => 'Marlowe Goods Co.', 'job_title' => 'Operations Manager',
                'lead_status' => 'contacted', 'lead_score' => 35, 'status' => 'active', 'assigned_to' => $priya?->id,
            ],
        ];

        foreach ($contacts as $data) {
            $companyModel = $byName($data['company']);
            unset($data['company']);

            Contact::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'company_id' => $companyModel?->id,
                    'source' => 'referral',
                    'created_by' => $adminUser?->id,
                ])
            );
        }
    }
}
