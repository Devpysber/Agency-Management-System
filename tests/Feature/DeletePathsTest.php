<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\company;
use App\Models\contact;
use App\Models\deal;
use App\Models\Estimate;
use App\Models\PortfolioItem;
use App\Models\PricingPlan;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Service;
use App\Models\staff;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every "all" list page exposes a permission-gated delete($id). This seeds
 * the full demo dataset and, for each module, deletes the first real
 * record through its actual Livewire component (not Model::destroy()
 * directly), so a broken relation/accessor touched only during the
 * delete's own re-render would still surface.
 */
class DeletePathsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'admin')->firstOrFail();
    }

    public static function deletableModules(): array
    {
        return [
            ['pages::admin.companies.all', company::class],
            ['pages::admin.contacts.allcontacts', contact::class],
            ['pages::admin.deals.all', deal::class],
            ['pages::admin.staff.all', staff::class],
            ['pages::admin.services.all', Service::class],
            ['pages::admin.products.all', Product::class],
            ['pages::admin.portfolio.all', PortfolioItem::class],
            ['pages::admin.testimonials.all', Testimonial::class],
            ['pages::admin.estimates.all', Estimate::class],
            ['pages::admin.quotations.all', Quotation::class],
            ['pages::admin.pricing.all', PricingPlan::class],
            ['pages::admin.blog.all', BlogPost::class],
            ['pages::admin.projects.all', Project::class],
        ];
    }

    /**
     * @dataProvider deletableModules
     */
    public function test_admin_can_delete_a_record_through_its_list_page(string $component, string $modelClass): void
    {
        $record = $modelClass::first();

        if (!$record) {
            $this->markTestSkipped("No seeded {$modelClass} row to delete.");
        }

        Livewire::actingAs($this->admin)
            ->test($component)
            ->call('delete', $record->id);

        $this->assertDatabaseMissing((new $modelClass)->getTable(), ['id' => $record->id]);
    }

    public function test_deleting_a_company_does_not_delete_its_contacts_or_deals(): void
    {
        $company = company::create(['company_name' => 'Delete Me Co', 'status' => 'active']);
        $contact = contact::create([
            'first_name' => 'Still',
            'last_name' => 'Here',
            'email' => 'still.here@example.test',
            'company_id' => $company->id,
        ]);
        $deal = deal::create([
            'deal_name' => 'Orphaned Deal',
            'deal_value' => 1000,
            'deal_stage' => 'lead',
            'deal_status' => 'active',
            'company_id' => $company->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.companies.all')
            ->call('delete', $company->id);

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'company_id' => null]);
        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'company_id' => null]);
    }
}
