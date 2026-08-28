<?php

namespace Database\Seeders;

use App\Models\staff;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Seed the staff table.
     *
     * TaskSeeder already firstOrCreate()s two minimal staff rows
     * (alex.johnson@agency.test, priya.singh@agency.test) as a side effect
     * of assigning tasks. We complement those here with additional staff
     * covering the rest of the seeded designations, using firstOrCreate
     * by email so this stays safe to re-run.
     */
    public function run(): void
    {
        $staffMembers = [
            [
                'name' => 'Michael Carter',
                'email' => 'michael.carter@agency.test',
                'whatsapp' => '+1 555-0103',
                'designation' => 'CEO',
                'joining_date' => now()->subYears(3),
                'salary' => 9500,
                'status' => 'active',
            ],
            [
                'name' => 'Sofia Martinez',
                'email' => 'sofia.martinez@agency.test',
                'whatsapp' => '+1 555-0104',
                'designation' => 'Designer',
                'joining_date' => now()->subMonths(14),
                'salary' => 3600,
                'status' => 'active',
            ],
            [
                'name' => 'David Okafor',
                'email' => 'david.okafor@agency.test',
                'whatsapp' => '+1 555-0105',
                'designation' => 'Sales Executive',
                'joining_date' => now()->subMonths(6),
                'salary' => 3200,
                'status' => 'active',
            ],
            [
                'name' => 'Emily Chen',
                'email' => 'emily.chen@agency.test',
                'whatsapp' => '+1 555-0106',
                'designation' => 'Developer',
                'joining_date' => now()->subMonths(20),
                'salary' => 4100,
                'status' => 'active',
            ],
            [
                'name' => 'Ryan Kelly',
                'email' => 'ryan.kelly@agency.test',
                'whatsapp' => '+1 555-0107',
                'designation' => 'Project Manager',
                'joining_date' => now()->subMonths(10),
                'salary' => 4300,
                'status' => 'active',
            ],
            [
                'name' => 'Hannah Brooks',
                'email' => 'hannah.brooks@agency.test',
                'whatsapp' => '+1 555-0108',
                'designation' => 'Designer',
                'joining_date' => now()->subMonths(3),
                'salary' => 3400,
                'status' => 'inactive',
            ],
            [
                'name' => 'Grace Nolan',
                'email' => 'grace.nolan@agency.test',
                'whatsapp' => '+1 555-0109',
                'designation' => 'COO',
                'joining_date' => now()->subYears(2),
                'salary' => 8200,
                'status' => 'active',
            ],
            [
                'name' => 'Diane Foster',
                'email' => 'diane.foster@agency.test',
                'whatsapp' => '+1 555-0110',
                'designation' => 'HR & Admin Manager',
                'joining_date' => now()->subMonths(18),
                'salary' => 4500,
                'status' => 'active',
            ],
            [
                'name' => 'Natalie Reyes',
                'email' => 'natalie.reyes@agency.test',
                'whatsapp' => '+1 555-0111',
                'designation' => 'Account Manager',
                'joining_date' => now()->subMonths(9),
                'salary' => 4000,
                'status' => 'active',
            ],
            [
                'name' => 'Isabella Cruz',
                'email' => 'isabella.cruz@agency.test',
                'whatsapp' => '+1 555-0112',
                'designation' => 'Business Development Manager',
                'joining_date' => now()->subMonths(15),
                'salary' => 4600,
                'status' => 'active',
            ],
            [
                'name' => 'Marcus Bell',
                'email' => 'marcus.bell@agency.test',
                'whatsapp' => '+1 555-0113',
                'designation' => 'Tech Lead',
                'joining_date' => now()->subMonths(22),
                'salary' => 5200,
                'status' => 'active',
            ],
            [
                'name' => 'Priya Anand',
                'email' => 'priya.anand@agency.test',
                'whatsapp' => '+1 555-0114',
                'designation' => 'QA Engineer',
                'joining_date' => now()->subMonths(11),
                'salary' => 3700,
                'status' => 'active',
            ],
            [
                'name' => 'Oliver Kim',
                'email' => 'oliver.kim@agency.test',
                'whatsapp' => '+1 555-0115',
                'designation' => 'AI/ML Engineer',
                'joining_date' => now()->subMonths(7),
                'salary' => 4800,
                'status' => 'active',
            ],
            [
                'name' => 'Chloe Bennett',
                'email' => 'chloe.bennett@agency.test',
                'whatsapp' => '+1 555-0116',
                'designation' => 'Marketing Manager',
                'joining_date' => now()->subMonths(13),
                'salary' => 4200,
                'status' => 'active',
            ],
            [
                'name' => 'Robert Hayes',
                'email' => 'robert.hayes@agency.test',
                'whatsapp' => '+1 555-0117',
                'designation' => 'Finance Manager',
                'joining_date' => now()->subMonths(24),
                'salary' => 5000,
                'status' => 'active',
            ],
            [
                'name' => 'Zara Ahmed',
                'email' => 'zara.ahmed@agency.test',
                'whatsapp' => '+1 555-0118',
                'designation' => 'Intern',
                'joining_date' => now()->subMonths(1),
                'salary' => 1200,
                'status' => 'active',
            ],
        ];

        foreach ($staffMembers as $member) {
            $email = $member['email'];
            unset($member['email']);

            staff::firstOrCreate(
                ['email' => $email],
                $member
            );
        }
    }
}
