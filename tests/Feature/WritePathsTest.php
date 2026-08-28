<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\company;
use App\Models\Contact;
use App\Models\deal;
use App\Models\Estimate;
use App\Models\PortfolioItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\staff;
use App\Models\Task;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SidebarRoutesTest / AllDetailPagesRenderTest only prove pages *render*.
 * This exercises the actual write paths (save()/form_submit() on the Add
 * pages) end to end — form fill -> validate -> persist -> redirect — for
 * every module whose create flow has real complexity (file uploads,
 * nested line items, auto-generated fields) rather than a flat insert.
 */
class WritePathsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        Storage::fake('public');
    }

    public function test_company_add_persists_and_redirects(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.companies.add')
            ->set('company_name', 'Test Co')
            ->set('company_email', 'test-co@example.test')
            ->call('form_submit')
            ->assertRedirect(route('companies.all'));

        $this->assertDatabaseHas('companies', ['company_name' => 'Test Co']);
    }

    public function test_contact_add_persists_and_redirects(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.contacts.add')
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('email', 'jane.doe@example.test')
            ->call('save')
            ->assertRedirect(route('contacts.all'));

        $this->assertDatabaseHas('contacts', ['email' => 'jane.doe@example.test']);
    }

    public function test_deal_add_persists_and_redirects(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.deals.add')
            ->set('deal_name', 'New Website Deal')
            ->set('deal_value', 12000)
            ->set('deal_stage', 'lead')
            ->set('deal_status', 'active')
            ->call('save')
            ->assertRedirect(route('deals.all'));

        $this->assertDatabaseHas('deals', ['deal_name' => 'New Website Deal', 'deal_value' => 12000]);
    }

    public function test_deals_pipeline_shows_active_deals(): void
    {
        deal::create(['deal_name' => 'Active Deal', 'deal_value' => 5000, 'deal_stage' => 'lead', 'deal_status' => 'active']);
        deal::create(['deal_name' => 'Won Deal', 'deal_value' => 8000, 'deal_stage' => 'closed_won', 'deal_status' => 'won']);

        $names = Livewire::actingAs($this->admin)
            ->test('pages::admin.deals.pipeline')
            ->instance()
            ->fetchDeals()
            ->get()
            ->pluck('deal_name');

        $this->assertTrue($names->contains('Active Deal'));
        $this->assertFalse($names->contains('Won Deal'));
    }

    public function test_project_add_persists_and_redirects(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.projects.add')
            ->set('name', 'New Website Build')
            ->set('status', 'planning')
            ->call('form_submit')
            ->assertRedirect(route('projects.all'));

        $this->assertDatabaseHas('projects', ['name' => 'New Website Build']);
    }

    public function test_task_create_persists_with_polymorphic_relation(): void
    {
        $company = company::create(['company_name' => 'Related Co', 'status' => 'active']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.tasks.create')
            ->set('title', 'Follow up with client')
            ->set('priority', 'high')
            ->set('status', 'pending')
            ->set('related_type', 'company')
            ->set('related_to', $company->id)
            ->call('save');

        $task = Task::where('title', 'Follow up with client')->first();
        $this->assertNotNull($task);
        $this->assertSame('company', $task->related_type);
        $this->assertEquals($company->id, $task->related_to);

        // getRelatedModel() and the related() morphTo both need to actually
        // resolve the linked record, not just store the raw type/id pair.
        $this->assertTrue($task->getRelatedModel()->is($company));
        $this->assertTrue($task->related->is($company));
    }

    public function test_task_related_model_resolves_for_every_linkable_type(): void
    {
        $company = company::create(['company_name' => 'Linked Co', 'status' => 'active']);
        $contact = Contact::create(['first_name' => 'Linked', 'last_name' => 'Contact', 'email' => 'linked.contact@example.test']);
        $deal = deal::create(['deal_name' => 'Linked Deal', 'deal_value' => 1000, 'deal_stage' => 'lead', 'deal_status' => 'active']);
        $project = Project::create(['name' => 'Linked Project', 'status' => 'planning']);

        $cases = [
            ['company', $company],
            ['contact', $contact],
            ['deal', $deal],
            ['project', $project],
        ];

        foreach ($cases as [$type, $model]) {
            $task = Task::create([
                'title' => "Task linked to {$type}",
                'priority' => 'medium',
                'status' => 'pending',
                'related_type' => $type,
                'related_to' => $model->id,
            ]);

            $this->assertTrue($task->getRelatedModel()?->is($model), "getRelatedModel() failed for {$type}");
            $this->assertTrue($task->related?->is($model), "related() morphTo failed for {$type}");
        }
    }

    public function test_staff_add_stores_uploaded_photo(): void
    {
        $photo = UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg');

        Livewire::actingAs($this->admin)
            ->test('pages::admin.staff.add')
            ->set('name', 'New Hire')
            ->set('email', 'new.hire@agency.test')
            ->set('designation', 'Developer')
            ->set('joining_date', now()->format('Y-m-d'))
            ->set('photo', $photo)
            ->call('save')
            ->assertRedirect(route('staff.all'));

        $member = staff::where('email', 'new.hire@agency.test')->first();
        $this->assertNotNull($member);
        $this->assertNotNull($member->image);
        Storage::disk('public')->assertExists($member->image);
    }

    public function test_staff_edit_replacing_photo_cleans_up_old_one(): void
    {
        $original = UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg');

        Livewire::actingAs($this->admin)
            ->test('pages::admin.staff.add')
            ->set('name', 'Photo Swap')
            ->set('email', 'photo.swap@agency.test')
            ->set('designation', 'Developer')
            ->set('joining_date', now()->format('Y-m-d'))
            ->set('photo', $original)
            ->call('save');

        $member = staff::where('email', 'photo.swap@agency.test')->firstOrFail();
        $oldPath = $member->image;
        Storage::disk('public')->assertExists($oldPath);

        $replacement = UploadedFile::fake()->create('avatar2.jpg', 10, 'image/jpeg');

        Livewire::actingAs($this->admin)
            ->test('pages::admin.staff.edit', ['id' => $member->id])
            ->set('photo', $replacement)
            ->call('update');

        $member->refresh();
        $this->assertNotEquals($oldPath, $member->image);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($member->image);
    }

    public function test_portfolio_add_stores_uploaded_images_and_delete_cleans_them_up(): void
    {
        $images = [
            UploadedFile::fake()->create('shot1.jpg', 10, 'image/jpeg'),
            UploadedFile::fake()->create('shot2.jpg', 10, 'image/jpeg'),
        ];

        Livewire::actingAs($this->admin)
            ->test('pages::admin.portfolio.add')
            ->set('title', 'Rebrand Showcase')
            ->set('newImages', $images)
            ->call('form_submit')
            ->assertRedirect(route('portfolio.all'));

        $item = PortfolioItem::where('title', 'Rebrand Showcase')->first();
        $this->assertNotNull($item);
        $this->assertCount(2, $item->images);
        foreach ($item->images as $path) {
            Storage::disk('public')->assertExists($path);
        }

        // Deleting the model should clean up its files (PortfolioItem::booted).
        $paths = $item->images;
        $item->delete();
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_deleting_blog_post_cleans_up_featured_image(): void
    {
        $path = UploadedFile::fake()->create('img.jpg', 10, 'image/jpeg')->store('blog', 'public');
        $post = BlogPost::create(['title' => 'Cleanup Post', 'content' => 'x', 'featured_image' => $path]);

        Storage::disk('public')->assertExists($path);
        $post->delete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_testimonial_cleans_up_avatar(): void
    {
        $path = UploadedFile::fake()->create('img.jpg', 10, 'image/jpeg')->store('testimonials', 'public');
        $testimonial = Testimonial::create(['client_name' => 'Cleanup Client', 'message' => 'x', 'rating' => 5, 'avatar' => $path]);

        Storage::disk('public')->assertExists($path);
        $testimonial->delete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_product_cleans_up_image(): void
    {
        $path = UploadedFile::fake()->create('img.jpg', 10, 'image/jpeg')->store('products', 'public');
        $product = Product::create(['name' => 'Cleanup Product', 'status' => 'active', 'image' => $path]);

        Storage::disk('public')->assertExists($path);
        $product->delete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_service_cleans_up_image(): void
    {
        $path = UploadedFile::fake()->create('img.jpg', 10, 'image/jpeg')->store('services', 'public');
        $service = Service::create(['name' => 'Cleanup Service', 'status' => 'active', 'image' => $path]);

        Storage::disk('public')->assertExists($path);
        $service->delete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_staff_cleans_up_photo(): void
    {
        $path = UploadedFile::fake()->create('img.jpg', 10, 'image/jpeg')->store('staffs', 'public');
        $member = staff::create([
            'name' => 'Cleanup Staff', 'email' => 'cleanup.staff@agency.test',
            'designation' => 'Developer', 'joining_date' => now(), 'status' => 'active',
            'image' => $path,
        ]);

        Storage::disk('public')->assertExists($path);
        $member->delete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_product_bulk_delete_cleans_up_images(): void
    {
        $path = UploadedFile::fake()->create('img.jpg', 10, 'image/jpeg')->store('products', 'public');
        $product = Product::create(['name' => 'Bulk Cleanup Product', 'status' => 'active', 'image' => $path]);

        Storage::disk('public')->assertExists($path);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.all')
            ->set('selectedProducts', [(string) $product->id])
            ->call('deleteSelected');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_blog_add_auto_generates_unique_slug(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.blog.add')
            ->set('title', 'Our Launch Announcement')
            ->set('content', 'Body copy here.')
            ->set('status', 'draft')
            ->call('form_submit')
            ->assertRedirect(route('blog.all'));

        $post = BlogPost::where('title', 'Our Launch Announcement')->first();
        $this->assertNotNull($post);
        $this->assertSame('our-launch-announcement', $post->slug);

        // A second post with the same title must not collide on slug.
        Livewire::actingAs($this->admin)
            ->test('pages::admin.blog.add')
            ->set('title', 'Our Launch Announcement')
            ->set('content', 'Different body.')
            ->set('status', 'draft')
            ->call('form_submit');

        $this->assertDatabaseHas('blog_posts', ['slug' => 'our-launch-announcement-2']);
    }

    public function test_estimate_add_persists_line_items_and_computed_totals(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.estimates.add')
            ->set('client_name', 'Walk-in Client')
            ->set('tax', 10)
            ->set('items.0.description', 'Design work')
            ->set('items.0.qty', 2)
            ->set('items.0.unit_price', 100)
            ->call('addItem')
            ->set('items.1.description', 'Dev work')
            ->set('items.1.qty', 1)
            ->set('items.1.unit_price', 300)
            ->call('form_submit');

        $estimate = Estimate::where('client_name', 'Walk-in Client')->first();
        $this->assertNotNull($estimate);
        $this->assertCount(2, $estimate->items);
        // subtotal = 2*100 + 1*300 = 500, total = subtotal + tax
        $this->assertEquals(500, $estimate->subtotal);
        $this->assertEquals(510, $estimate->total);
    }
}
