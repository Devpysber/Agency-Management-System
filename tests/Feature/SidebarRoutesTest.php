<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider sidebarRoutes
     */
    public function test_sidebar_route_loads(string $routeName): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertOk();
    }

    public static function sidebarRoutes(): array
    {
        return [
            ['dashboard'],
            ['staff.create'],
            ['staff.all'],
            ['staff.add'],
            ['staff.designations'],
            ['contacts.all'],
            ['contacts.add'],
            ['contacts.import'],
            ['contacts.groups'],
            ['companies.all'],
            ['companies.add'],
            ['deals.pipeline'],
            ['deals.all'],
            ['deals.add'],
            ['deals.lost'],
            ['projects.all'],
            ['projects.add'],
            ['projects.payments'],
            ['tasks.my'],
            ['tasks.all'],
            ['tasks.create'],
            ['tasks.completed'],
            ['calendar.schedule'],
            ['calendar.events'],
            ['communications.emails'],
            ['communications.calls'],
            ['communications.meetings'],
            ['communications.activity-log'],
            ['reports.sales'],
            ['reports.activity'],
            ['reports.performance'],
            ['services.all'],
            ['services.add'],
            ['services.categories'],
            ['products.all'],
            ['products.add'],
            ['products.categories'],
            ['portfolio.all'],
            ['portfolio.add'],
            ['testimonials.all'],
            ['testimonials.add'],
            ['estimates.all'],
            ['estimates.add'],
            ['quotations.all'],
            ['quotations.add'],
            ['pricing.all'],
            ['pricing.add'],
            ['blog.all'],
            ['blog.add'],
            ['blog.categories'],
            ['settings.general'],
            ['settings.user-management'],
            ['settings.roles-permissions'],
            ['settings.payment-gateways'],
        ];
    }
}
