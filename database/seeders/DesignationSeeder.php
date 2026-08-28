<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = ['CEO', 'Project Manager', 'Developer', 'Designer', 'Sales Executive'];

        foreach ($designations as $name) {
            Designation::create([
                'name' => $name,
                'status' => 'active',
            ]);
        }
    }
}
