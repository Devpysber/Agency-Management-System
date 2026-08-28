<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\contact;
use App\Models\deal;
use App\Models\Project;
use App\Models\staff;
use App\Models\Service;
use App\Models\Product;
use App\Models\PortfolioItem;
use App\Models\Testimonial;
use App\Models\Estimate;
use App\Models\Quotation;
use App\Models\PricingPlan;
use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seeds the full demo dataset (the same seeder DatabaseSeeder runs in
 * production setup) and hits every {id}-based show/edit route with a real
 * record. SidebarRoutesTest only covers the index/add pages; this closes
 * the gap on detail pages, whose relation/accessor calls only blow up
 * once a real row with real related rows exists.
 */
class AllDetailPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'admin')->firstOrFail();
    }

    public static function detailRoutes(): array
    {
        return [
            ['contacts.show', contact::class],
            ['contacts.edit', contact::class],
            ['companies.show', company::class],
            ['companies.edit', company::class],
            ['deals.view', deal::class], // deals show page — named 'view', not 'show'
            ['deals.edit', deal::class],
            ['projects.show', Project::class],
            ['projects.edit', Project::class],
            ['staff.show', staff::class],
            ['staff.edit', staff::class],
            ['services.show', Service::class],
            ['services.edit', Service::class],
            ['products.show', Product::class],
            ['products.edit', Product::class],
            ['portfolio.show', PortfolioItem::class],
            ['portfolio.edit', PortfolioItem::class],
            ['testimonials.edit', Testimonial::class],
            ['estimates.show', Estimate::class],
            ['estimates.edit', Estimate::class],
            ['quotations.show', Quotation::class],
            ['pricing.edit', PricingPlan::class],
            ['blog.show', BlogPost::class],
            ['blog.edit', BlogPost::class],
        ];
    }

    /**
     * @dataProvider detailRoutes
     */
    public function test_detail_route_loads(string $routeName, string $modelClass): void
    {
        $record = $modelClass::first();

        if (!$record) {
            $this->markTestSkipped("No seeded {$modelClass} row to test {$routeName} with.");
        }

        $response = $this->actingAs($this->admin)->get(route($routeName, $record->id));

        $response->assertOk();
    }
}
