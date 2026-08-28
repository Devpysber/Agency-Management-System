<?php

namespace Tests\Feature;

use App\Mail\ClientPortalCredentials;
use App\Models\company;
use App\Models\contact;
use App\Models\Estimate;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    private function makeClientWithCompany(): array
    {
        $company = company::create(['company_name' => 'Acme Inc']);

        $user = User::factory()->client()->create();

        $contact = contact::create([
            'first_name' => 'Cara',
            'last_name' => 'Client',
            'email' => 'cara.client@example.com',
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);

        return [$user, $company, $contact];
    }

    /**
     * Grant flow sends credentials synchronously — nothing in the deploy
     * setup runs a queue worker, so a queued mail would never go out even
     * though the page tells the admin it was sent.
     */
    public function test_granting_portal_access_sends_credentials_synchronously(): void
    {
        Mail::fake();

        $company = company::create(['company_name' => 'Acme Inc']);
        $contact = contact::create([
            'first_name' => 'Cara',
            'last_name' => 'Client',
            'email' => 'cara.client@example.com',
            'company_id' => $company->id,
        ]);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.contacts.show', ['id' => $contact->id])
            ->call('grantPortalAccess');

        $this->assertNotNull($contact->fresh()->user_id);
        Mail::assertSent(ClientPortalCredentials::class);
        Mail::assertNotQueued(ClientPortalCredentials::class);
    }

    /**
     * @dataProvider clientRoutes
     */
    public function test_client_with_linked_contact_can_reach_client_routes(string $routeName): void
    {
        [$user] = $this->makeClientWithCompany();

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertOk();
    }

    public static function clientRoutes(): array
    {
        return [
            ['client.dashboard'],
            ['client.projects'],
            ['client.estimates'],
            ['client.quotations'],
            ['client.payments'],
        ];
    }

    /**
     * @dataProvider adminOnlyRoutes
     */
    public function test_client_is_redirected_away_from_non_client_routes(string $routeName): void
    {
        [$user] = $this->makeClientWithCompany();

        $response = $this->actingAs($user)->get(route($routeName));

        // Admin-module routes get intercepted by module.access (permission
        // denial -> redirect to dashboard) before client.scope even runs;
        // routes outside the module matrix (dashboard, portal.dashboard) are
        // caught by client.scope itself and redirected to client.dashboard.
        // Either way, a client login must never see a 200 here.
        $response->assertStatus(302);
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public static function adminOnlyRoutes(): array
    {
        return [
            ['companies.all'],
            ['dashboard'],
            ['portal.dashboard'],
        ];
    }

    public function test_client_hitting_dashboard_is_redirected_to_client_dashboard(): void
    {
        [$user] = $this->makeClientWithCompany();

        // 'dashboard' has no module-matrix entry, so client.scope is the
        // middleware that catches it and sends the client to their own portal.
        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('client.dashboard'));
    }

    public function test_client_hitting_portal_dashboard_is_redirected_to_client_dashboard(): void
    {
        [$user] = $this->makeClientWithCompany();

        $this->actingAs($user)->get(route('portal.dashboard'))
            ->assertRedirect(route('client.dashboard'));
    }

    public function test_client_gets_404_for_another_companys_project(): void
    {
        [$clientUser, $companyA] = $this->makeClientWithCompany();
        $companyB = company::create(['company_name' => 'Other Co']);

        $projectB = Project::create([
            'company_id' => $companyB->id,
            'name' => 'Company B Project',
            'status' => 'planning',
        ]);

        $response = $this->actingAs($clientUser)->get(route('client.project-show', $projectB->id));

        $response->assertNotFound();
    }

    public function test_client_gets_404_for_another_companys_estimate(): void
    {
        [$clientUser, $companyA] = $this->makeClientWithCompany();
        $companyB = company::create(['company_name' => 'Other Co']);

        $estimateB = Estimate::create([
            'estimate_number' => 'EST-TEST-0001',
            'company_id' => $companyB->id,
            'status' => 'sent',
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
        ]);

        $response = $this->actingAs($clientUser)->get(route('client.estimate-show', $estimateB->id));

        $response->assertNotFound();
    }

    public function test_client_gets_404_for_another_companys_quotation(): void
    {
        [$clientUser, $companyA] = $this->makeClientWithCompany();
        $companyB = company::create(['company_name' => 'Other Co']);

        $quotationB = Quotation::create([
            'company_id' => $companyB->id,
            'name' => 'Other Contact',
            'email' => 'other@example.com',
            'status' => 'quoted',
        ]);

        $response = $this->actingAs($clientUser)->get(route('client.quotation-show', $quotationB->id));

        $response->assertNotFound();
    }

    public function test_client_can_reach_own_companys_project(): void
    {
        [$clientUser, $company] = $this->makeClientWithCompany();

        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Own Project',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($clientUser)->get(route('client.project-show', $project->id));

        $response->assertOk();
    }

    public function test_client_can_reach_own_companys_estimate(): void
    {
        [$clientUser, $company] = $this->makeClientWithCompany();

        $estimate = Estimate::create([
            'estimate_number' => 'EST-TEST-0002',
            'company_id' => $company->id,
            'status' => 'sent',
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
        ]);

        $response = $this->actingAs($clientUser)->get(route('client.estimate-show', $estimate->id));

        $response->assertOk();
    }

    public function test_client_can_reach_own_companys_quotation(): void
    {
        [$clientUser, $company] = $this->makeClientWithCompany();

        $quotation = Quotation::create([
            'company_id' => $company->id,
            'name' => 'Own Contact',
            'email' => 'own@example.com',
            'status' => 'quoted',
        ]);

        $response = $this->actingAs($clientUser)->get(route('client.quotation-show', $quotation->id));

        $response->assertOk();
    }

    public function test_admin_is_not_affected_by_client_scope(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('companies.all'))->assertOk();
    }
}
