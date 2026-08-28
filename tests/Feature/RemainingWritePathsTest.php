<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\PricingPlan;
use App\Models\Product;
use App\Models\Service;
use App\Models\staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RemainingWritePathsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_pricing_plan_add_persists_newline_separated_arrays(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.pricing.add')
            ->set('name', 'Starter Plan')
            ->set('price', 49)
            ->set('billing_period', 'monthly')
            ->set('featuresText', "5 pages\nBasic SEO\nEmail support")
            ->set('countriesText', "US\nCanada")
            ->call('form_submit')
            ->assertRedirect(route('pricing.all'));

        $plan = PricingPlan::where('name', 'Starter Plan')->first();
        $this->assertNotNull($plan);
        $this->assertSame(['5 pages', 'Basic SEO', 'Email support'], $plan->features);
        $this->assertSame(['US', 'Canada'], $plan->countries);
    }

    public function test_blog_category_crud(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test('pages::admin.blog.categories')
            ->call('openAddModal')
            ->set('name', 'Company News')
            ->call('save');

        $category = BlogCategory::where('name', 'Company News')->first();
        $this->assertNotNull($category);

        $component->call('edit', $category->id)
            ->set('name', 'Announcements')
            ->call('save');
        $this->assertSame('Announcements', $category->fresh()->name);

        $component->call('delete', $category->id);
        $this->assertDatabaseMissing('blog_categories', ['id' => $category->id]);
    }

    public function test_service_add_persists(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.services.add')
            ->set('name', 'SEO Audit')
            ->set('price', 500)
            ->call('form_submit')
            ->assertRedirect(route('services.all'));

        $this->assertDatabaseHas('services', ['name' => 'SEO Audit']);
    }

    public function test_product_add_persists_with_unique_sku(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.add')
            ->set('name', 'Branded Mug')
            ->set('sku', 'MUG-001')
            ->set('price', 15)
            ->set('stock_quantity', 100)
            ->call('form_submit')
            ->assertRedirect(route('products.all'));

        $product = Product::where('sku', 'MUG-001')->first();
        $this->assertNotNull($product);
        $this->assertSame(100, $product->stock_quantity);

        // A second product with the same SKU must fail validation, not silently duplicate.
        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.add')
            ->set('name', 'Duplicate SKU Mug')
            ->set('sku', 'MUG-001')
            ->call('form_submit')
            ->assertHasErrors(['sku' => 'unique']);
    }

    /**
     * Closes the loop on StaffLoginAccessTest: not just that user_id gets
     * linked, but that the generated credential is a real, working login
     * with the right role and a usable (not just non-empty) password.
     */
    public function test_generated_staff_login_actually_works_end_to_end(): void
    {
        $member = staff::create([
            'name' => 'Login Test',
            'email' => 'login.test@example.test',
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('generateLogin');

        $password = $component->get('generatedPassword');
        $this->assertNotEmpty($password);

        $member->refresh();
        $this->assertNotNull($member->user_id);

        $loginUser = User::find($member->user_id);
        $this->assertSame('staff', $loginUser->role);
        $this->assertSame($member->email, $loginUser->email);

        $this->post('/logout'); // ensure clean session before attempting login
        $response = $this->post('/login', [
            'email' => $member->email,
            'password' => $password,
        ]);
        $this->assertAuthenticatedAs($loginUser);
    }
}
