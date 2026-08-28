<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\ServiceCategory;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsAndCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        Storage::fake('public');
    }

    public function test_general_settings_save_persists_and_stores_logo(): void
    {
        $logo = UploadedFile::fake()->create('logo.png', 10, 'image/png');

        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.general')
            ->set('business_name', 'Acme Agency')
            ->set('contact_email', 'hello@acme.test')
            ->set('business_logo', $logo)
            ->call('save');

        $settings = CompanySetting::first();
        $this->assertSame('Acme Agency', $settings->business_name);
        $this->assertSame('hello@acme.test', $settings->contact_email);
        $this->assertNotNull($settings->business_logo);
        Storage::disk('public')->assertExists($settings->business_logo);
    }

    public function test_service_category_crud(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test('pages::admin.services.categories')
            ->call('openAddModal')
            ->set('name', 'Web Design')
            ->call('save');

        $category = ServiceCategory::where('name', 'Web Design')->first();
        $this->assertNotNull($category);

        $component->call('edit', $category->id)
            ->set('name', 'Web Design & Dev')
            ->call('save');

        $this->assertSame('Web Design & Dev', $category->fresh()->name);

        $component->call('delete', $category->id);
        $this->assertDatabaseMissing('service_categories', ['id' => $category->id]);
    }

    public function test_testimonial_create_and_approve(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.testimonials.add')
            ->set('client_name', 'Happy Client')
            ->set('message', 'Great work!')
            ->set('rating', 5)
            ->set('status', 'pending')
            ->call('form_submit')
            ->assertRedirect(route('testimonials.all'));

        $testimonial = Testimonial::where('client_name', 'Happy Client')->first();
        $this->assertNotNull($testimonial);
        $this->assertSame('pending', $testimonial->status);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.testimonials.all')
            ->call('approve', $testimonial->id);

        $this->assertSame('approved', $testimonial->fresh()->status);
    }
}
