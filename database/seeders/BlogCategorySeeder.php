<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Web Design', 'Digital Marketing', 'Industry News'];

        foreach ($categories as $name) {
            BlogCategory::create([
                'name' => $name,
                'status' => 'active',
            ]);
        }
    }
}
