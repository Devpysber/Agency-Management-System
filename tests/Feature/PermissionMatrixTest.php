<?php

namespace Tests\Feature;

use App\Models\Designation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider adminModuleRoutes
     */
    public function test_non_admin_without_matching_role_is_redirected(string $routeName): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public static function adminModuleRoutes(): array
    {
        return [
            ['companies.all'],
            ['settings.general'],
        ];
    }

    public function test_non_admin_with_role_granting_view_can_reach_companies(): void
    {
        Role::create([
            'name' => 'staff',
            'description' => 'Limited staff role',
            'permissions' => [
                'Companies' => ['View' => true],
            ],
        ]);

        $user = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user)->get(route('companies.all'));

        $response->assertOk();
    }

    public function test_non_admin_with_role_granting_view_can_reach_settings(): void
    {
        Role::create([
            'name' => 'staff',
            'description' => 'Limited staff role',
            'permissions' => [
                'Settings' => ['View' => true],
            ],
        ]);

        $user = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user)->get(route('settings.general'));

        $response->assertOk();
    }

    /**
     * The staff.designations route only requires Staff/View (its route
     * name doesn't match the add/edit action heuristic in
     * CheckModuleAccess), so save() must gate itself — same class of gap
     * as projects/show's milestone mutations.
     */
    public function test_view_only_role_cannot_save_a_designation(): void
    {
        Role::create([
            'name' => 'staff',
            'description' => 'View-only staff role',
            'permissions' => [
                'Staff' => ['View' => true],
            ],
        ]);

        $user = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($user)
            ->test('pages::admin.staff.designations')
            ->set('name', 'Sneaky Designation')
            ->call('save');

        $this->assertDatabaseMissing('designations', ['name' => 'Sneaky Designation']);
    }

    public function test_role_granted_staff_create_can_save_a_designation(): void
    {
        Role::create([
            'name' => 'staff',
            'description' => 'Staff role with create',
            'permissions' => [
                'Staff' => ['View' => true, 'Create' => true],
            ],
        ]);

        $user = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($user)
            ->test('pages::admin.staff.designations')
            ->set('name', 'Account Manager')
            ->call('save');

        $this->assertDatabaseHas('designations', ['name' => 'Account Manager']);
    }

    /**
     * services/products/blog "categories" pages, and contacts/import, all
     * sit on a View-only route (their route name doesn't match the
     * add/edit action heuristic in CheckModuleAccess) but mutate — same
     * class of gap as staff/designations above.
     */
    public function test_view_only_role_cannot_save_a_service_category(): void
    {
        Role::create(['name' => 'staff', 'description' => 'x', 'permissions' => ['Services' => ['View' => true]]]);
        $user = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($user)
            ->test('pages::admin.services.categories')
            ->set('name', 'Sneaky Category')
            ->call('save');

        $this->assertDatabaseMissing('service_categories', ['name' => 'Sneaky Category']);
    }

    public function test_view_only_role_cannot_save_a_product_category(): void
    {
        Role::create(['name' => 'staff', 'description' => 'x', 'permissions' => ['Products' => ['View' => true]]]);
        $user = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($user)
            ->test('pages::admin.products.categories')
            ->set('name', 'Sneaky Category')
            ->call('save');

        $this->assertDatabaseMissing('product_categories', ['name' => 'Sneaky Category']);
    }

    public function test_view_only_role_cannot_save_a_blog_category(): void
    {
        Role::create(['name' => 'staff', 'description' => 'x', 'permissions' => ['Blog' => ['View' => true]]]);
        $user = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($user)
            ->test('pages::admin.blog.categories')
            ->set('name', 'Sneaky Category')
            ->call('save');

        $this->assertDatabaseMissing('blog_categories', ['name' => 'Sneaky Category']);
    }

    public function test_view_only_role_cannot_import_contacts(): void
    {
        Storage::fake('local');
        Role::create(['name' => 'staff', 'description' => 'x', 'permissions' => ['Contacts' => ['View' => true]]]);
        $user = User::factory()->create(['role' => 'staff']);

        $csv = UploadedFile::fake()->createWithContent('contacts.csv', "name,email\nSneaky Contact,sneaky@example.test\n");

        Livewire::actingAs($user)
            ->test('pages::admin.contacts.import')
            ->set('csv_file', $csv)
            ->call('import');

        $this->assertDatabaseMissing('contacts', ['email' => 'sneaky@example.test']);
    }

    public function test_non_admin_with_role_but_no_matching_permission_is_still_redirected(): void
    {
        Role::create([
            'name' => 'staff',
            'description' => 'Limited staff role',
            'permissions' => [
                'Companies' => ['View' => true],
            ],
        ]);

        $user = User::factory()->create(['role' => 'staff']);

        // The role exists but grants nothing on Settings, so this must still redirect.
        $response = $this->actingAs($user)->get(route('settings.general'));

        $response->assertRedirect(route('dashboard'));
    }
}
