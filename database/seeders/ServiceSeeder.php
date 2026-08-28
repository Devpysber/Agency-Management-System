<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $webDev = ServiceCategory::where('name', 'Web Development')->first();
        $marketing = ServiceCategory::where('name', 'Digital Marketing')->first();
        $design = ServiceCategory::where('name', 'Design & Branding')->first();

        $services = [
            [
                'service_category_id' => $webDev?->id,
                'name' => 'WordPress Website',
                'description' => 'Custom-built WordPress website with responsive design and CMS training.',
                'price' => 1200.00,
            ],
            [
                'service_category_id' => $webDev?->id,
                'name' => 'Mobile App Development',
                'description' => 'Native or cross-platform mobile app design and development.',
                'price' => 4500.00,
            ],
            [
                'service_category_id' => $marketing?->id,
                'name' => 'SEO Optimization',
                'description' => 'On-page and off-page SEO to improve search engine rankings.',
                'price' => 600.00,
            ],
            [
                'service_category_id' => $marketing?->id,
                'name' => 'Social Media Management',
                'description' => 'Content planning, posting, and engagement across social platforms.',
                'price' => 450.00,
            ],
            [
                'service_category_id' => $marketing?->id,
                'name' => 'Pay-Per-Click Advertising',
                'description' => 'Managed PPC campaigns across Google and social ad networks.',
                'price' => 800.00,
            ],
            [
                'service_category_id' => $design?->id,
                'name' => 'Logo Design',
                'description' => 'Custom logo design with multiple concepts and revisions.',
                'price' => 300.00,
            ],
            [
                'service_category_id' => $design?->id,
                'name' => 'Brand Identity Package',
                'description' => 'Complete branding package including logo, color palette, and style guide.',
                'price' => 1500.00,
            ],
            [
                'service_category_id' => $design?->id,
                'name' => 'Print Design',
                'description' => 'Business cards, flyers, and brochure design for print media.',
                'price' => 250.00,
            ],
        ];

        foreach ($services as $service) {
            Service::create(array_merge($service, ['status' => 'active']));
        }
    }
}
