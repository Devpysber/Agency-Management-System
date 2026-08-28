<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roles & Permissions and User Management are the two settings pages that
 * mutate access control itself, so their write paths get their own,
 * closer look rather than a generic smoke test: permission grid
 * persistence, role deletion, user creation/role change, password
 * hashing, and the self-delete guard.
 */
class SettingsMutationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_role_with_permission_grid(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.roles-permissions')
            ->call('openAddModal')
            ->set('name', 'Junior Staff')
            ->set('description', 'Limited access role')
            ->set('permissions.Contacts.View', true)
            ->set('permissions.Contacts.Delete', false)
            ->call('save');

        $role = Role::where('name', 'Junior Staff')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->permissions['Contacts']['View']);
        $this->assertFalse($role->permissions['Contacts']['Delete']);
    }

    public function test_admin_can_edit_role_permissions(): void
    {
        $role = Role::create([
            'name' => 'Editable Role',
            'permissions' => ['Deals' => ['View' => false, 'Create' => false, 'Edit' => false, 'Delete' => false]],
        ]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.roles-permissions')
            ->call('edit', $role->id)
            ->set('permissions.Deals.View', true)
            ->call('save');

        $this->assertTrue($role->fresh()->permissions['Deals']['View']);
    }

    public function test_admin_can_delete_role(): void
    {
        $role = Role::create(['name' => 'Disposable Role', 'permissions' => []]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.roles-permissions')
            ->call('delete', $role->id);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /**
     * A role's permission grid is what module.access actually checks —
     * this proves a role created here really does gate a real route.
     */
    public function test_created_role_permission_actually_gates_a_route(): void
    {
        $role = Role::create([
            'name' => 'view-only-companies',
            'permissions' => ['Companies' => ['View' => true, 'Create' => false, 'Edit' => false, 'Delete' => false]],
        ]);
        $user = User::factory()->create(['role' => $role->name]);

        $this->actingAs($user)->get(route('companies.all'))->assertOk();
        $this->actingAs($user)->get(route('companies.add'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_user_with_hashed_password(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.user-management')
            ->call('openAddModal')
            ->set('name', 'New Manager')
            ->set('email', 'new.manager@agency.test')
            ->set('role', 'manager')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('save');

        $user = User::where('email', 'new.manager@agency.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('manager', $user->role);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_admin_can_change_a_users_role_without_resetting_password(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $originalHash = $user->password;

        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.user-management')
            ->call('editUser', $user->id)
            ->set('role', 'manager')
            ->call('save');

        $user->refresh();
        $this->assertSame('manager', $user->role);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.user-management')
            ->call('deleteUser', $user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.settings.user-management')
            ->call('deleteUser', $this->admin->id);

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
