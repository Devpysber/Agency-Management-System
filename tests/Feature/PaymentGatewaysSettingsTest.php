<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Payment gateways previously had no admin UI at all — seeded once by
 * PaymentGatewaySeeder and otherwise only readable as a dropdown source
 * on project payments. This adds Settings > Payment Gateways (CRUD,
 * permission-gated like every other settings page) and covers it here.
 */
class PaymentGatewaysSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_delete_a_gateway(): void
    {
        $admin = User::factory()->admin()->create();

        $component = Livewire::actingAs($admin)
            ->test('pages::admin.settings.payment-gateways')
            ->call('openAddModal')
            ->set('name', 'Razorpay')
            ->set('is_active', true)
            ->call('save');

        $gateway = PaymentGateway::where('name', 'Razorpay')->first();
        $this->assertNotNull($gateway);
        $this->assertTrue($gateway->is_active);

        $component->call('edit', $gateway->id)
            ->set('is_active', false)
            ->call('save');
        $this->assertFalse($gateway->fresh()->is_active);

        $component->call('delete', $gateway->id);
        $this->assertDatabaseMissing('payment_gateways', ['id' => $gateway->id]);
    }

    public function test_view_only_user_cannot_create_or_delete_a_gateway(): void
    {
        $user = User::factory()->create(['role' => 'staff']); // no Settings permission at all

        Livewire::actingAs($user)
            ->test('pages::admin.settings.payment-gateways')
            ->set('name', 'Sneaky Gateway')
            ->call('save');

        $this->assertDatabaseMissing('payment_gateways', ['name' => 'Sneaky Gateway']);

        $gateway = PaymentGateway::create(['name' => 'Existing Gateway', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('pages::admin.settings.payment-gateways')
            ->call('delete', $gateway->id);

        $this->assertDatabaseHas('payment_gateways', ['id' => $gateway->id]);
    }

    /**
     * Deleting a gateway must not break the payments that were logged
     * against it (payment_gateway_id is nullOnDelete).
     */
    public function test_deleting_a_gateway_does_not_delete_its_payments(): void
    {
        $admin = User::factory()->admin()->create();
        $gateway = PaymentGateway::create(['name' => 'Stripe', 'is_active' => true]);

        $company = \App\Models\company::create(['company_name' => 'Gateway Test Co', 'status' => 'active']);
        $project = \App\Models\Project::create(['company_id' => $company->id, 'name' => 'Gateway Test Project', 'status' => 'in_progress']);
        $payment = \App\Models\ProjectPayment::create([
            'project_id' => $project->id,
            'amount' => 200,
            'status' => 'paid',
            'payment_gateway_id' => $gateway->id,
        ]);

        Livewire::actingAs($admin)
            ->test('pages::admin.settings.payment-gateways')
            ->call('delete', $gateway->id);

        $this->assertDatabaseHas('project_payments', ['id' => $payment->id, 'payment_gateway_id' => null]);
    }
}
