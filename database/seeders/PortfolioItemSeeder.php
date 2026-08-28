<?php

namespace Database\Seeders;

use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class PortfolioItemSeeder extends Seeder
{
    /**
     * Generic placeholder graphics (real files under storage/app/public/portfolio)
     * cycled across seeded items — no seeder should reference image paths that
     * don't actually exist on disk.
     */
    protected array $placeholders = [
        'portfolio/placeholder-blue.svg',
        'portfolio/placeholder-green.svg',
        'portfolio/placeholder-purple.svg',
        'portfolio/placeholder-orange.svg',
    ];

    public function run(): void
    {
        $items = [
            [
                'title' => 'Corporate Website Redesign',
                'description' => 'A complete overhaul of a corporate website focused on modern design and conversion optimization.',
                'client_name' => 'Northbridge Holdings',
                'completed_date' => '2026-03-14',
                'status' => 'published',
            ],
            [
                'title' => 'Brand Identity for a Boutique Cafe',
                'description' => 'Full brand identity package including logo, color palette, and packaging design.',
                'client_name' => 'Willow & Bean Cafe',
                'completed_date' => '2026-02-02',
                'status' => 'published',
            ],
            [
                'title' => 'E-commerce Platform Launch',
                'description' => 'End-to-end build and launch of a scalable e-commerce storefront.',
                'client_name' => 'Marlowe Goods Co.',
                'completed_date' => '2026-01-20',
                'status' => 'published',
            ],
            [
                'title' => 'Social Media Campaign Assets',
                'description' => 'A set of campaign visuals and short-form video assets for a product launch.',
                'client_name' => 'Vantage Fitness',
                'completed_date' => '2025-12-10',
                'status' => 'draft',
            ],
            [
                'title' => 'Mobile App UI/UX Overhaul',
                'description' => 'Redesigned the core user flows and visual language of an existing mobile app.',
                'client_name' => 'Pulse Health',
                'completed_date' => '2025-11-05',
                'status' => 'published',
            ],
            [
                'title' => 'Trade Show Booth & Print Collateral',
                'description' => 'Designed booth graphics, banners, and print collateral for an industry trade show.',
                'client_name' => 'Ferro Industrial Supply',
                'completed_date' => null,
                'status' => 'draft',
            ],
        ];

        foreach ($items as $index => $item) {
            PortfolioItem::updateOrCreate(
                ['title' => $item['title']],
                array_merge($item, [
                    'images' => [$this->placeholders[$index % count($this->placeholders)]],
                    'service_category_id' => ServiceCategory::inRandomOrder()->first()?->id,
                    // Link only a couple of items to an existing project; leave the rest unlinked.
                    'project_id' => $index < 2 ? Project::inRandomOrder()->first()?->id : null,
                ])
            );
        }
    }
}
