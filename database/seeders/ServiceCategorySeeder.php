<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Web Development', 'Digital Marketing', 'Design & Branding'];

        foreach ($categories as $name) {
            ServiceCategory::create([
                'name' => $name,
                'status' => 'active',
            ]);
        }
    }
}
