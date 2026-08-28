<?php

namespace Tests\Feature;

use App\Mail\StaffCredentialsMail;
use App\Models\Role;
use App\Models\staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Guards against the account-creation permission gap: only an admin (or a
 * role explicitly granted Staff/Edit) may generate, reset, or revoke a
 * staff member's login — no other authenticated user can self-serve one.
 */
class StaffLoginAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStaff(): staff
    {
        return staff::create([
            'name' => 'Jamie Rivera',
            'email' => 'jamie.rivera@example.test',
            'status' => 'active',
        ]);
    }

    public function test_non_admin_without_permission_cannot_generate_staff_login(): void
    {
        $member = $this->makeStaff();
        $user = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($user)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('generateLogin');

        $this->assertNull($member->fresh()->user_id);
    }

    public function test_admin_can_generate_staff_login(): void
    {
        $member = $this->makeStaff();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('generateLogin');

        $this->assertNotNull($member->fresh()->user_id);
    }

    public function test_role_granted_staff_edit_can_generate_staff_login(): void
    {
        Role::create([
            'name' => 'hr',
            'description' => 'HR role',
            'permissions' => ['Staff' => ['Edit' => true]],
        ]);

        $member = $this->makeStaff();
        $user = User::factory()->create(['role' => 'hr']);

        Livewire::actingAs($user)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('generateLogin');

        $this->assertNotNull($member->fresh()->user_id);
    }

    public function test_new_login_email_is_not_worded_as_a_reset(): void
    {
        Mail::fake();
        $member = $this->makeStaff();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('generateLogin')
            ->call('sendCredentials');

        Mail::assertSent(StaffCredentialsMail::class, fn ($mail) => $mail->isReset === false);
    }

    public function test_reset_password_email_is_worded_as_a_reset(): void
    {
        Mail::fake();
        $member = $this->makeStaff();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('generateLogin')
            ->call('resetPassword')
            ->call('sendCredentials');

        Mail::assertSent(StaffCredentialsMail::class, fn ($mail) => $mail->isReset === true);
    }

    public function test_non_admin_without_permission_cannot_reset_staff_password(): void
    {
        $member = $this->makeStaff();
        $loginUser = User::create([
            'name' => $member->name,
            'email' => $member->email,
            'password' => bcrypt('original-password'),
            'role' => 'staff',
        ]);
        $member->update(['user_id' => $loginUser->id]);
        $originalHash = $loginUser->password;

        $attacker = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($attacker)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('resetPassword');

        $this->assertSame($originalHash, $loginUser->fresh()->password);
    }

    public function test_non_admin_without_permission_cannot_revoke_staff_login(): void
    {
        $member = $this->makeStaff();
        $loginUser = User::create([
            'name' => $member->name,
            'email' => $member->email,
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);
        $member->update(['user_id' => $loginUser->id]);

        $attacker = User::factory()->create(['role' => 'staff']);

        Livewire::actingAs($attacker)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('revokeLogin');

        $this->assertNotNull($member->fresh()->user_id);
        $this->assertNotNull($loginUser->fresh());
    }

    public function test_admin_can_revoke_staff_login(): void
    {
        $member = $this->makeStaff();
        $loginUser = User::create([
            'name' => $member->name,
            'email' => $member->email,
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);
        $member->update(['user_id' => $loginUser->id]);

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.staff.show', ['id' => $member->id])
            ->call('revokeLogin');

        $this->assertNull($member->fresh()->user_id);
        $this->assertNull(User::find($loginUser->id));
    }

    public function test_register_route_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Nobody',
            'email' => 'nobody@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
