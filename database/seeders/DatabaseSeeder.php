<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->call([
            DesignationSeeder::class,
            RoleSeeder::class,
            StaffSeeder::class,
            StaffLoginSeeder::class,
            CompanySeeder::class,
            ContactSeeder::class,
            DealSeeder::class,
            PaymentGatewaySeeder::class,
            ProjectSeeder::class,
            ServiceCategorySeeder::class,
            ServiceSeeder::class,
            PricingPlanSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            PortfolioItemSeeder::class,
            TestimonialSeeder::class,
            EstimateSeeder::class,
            QuotationSeeder::class,
            BlogCategorySeeder::class,
            BlogPostSeeder::class,
            CompanySettingSeeder::class,
            TaskSeeder::class,
            CalendarEventSeeder::class,
            CommunicationSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
