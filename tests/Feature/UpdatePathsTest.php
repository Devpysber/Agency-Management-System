<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\contact;
use App\Models\deal;
use App\Models\staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Complements WritePathsTest (create) and DeletePathsTest (delete) with
 * the third leg — edit pages' update(), mounted against a real record
 * and re-saved with a changed field, asserted persisted.
 */
class UpdatePathsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_company_edit_persists_changes(): void
    {
        $company = company::create(['company_name' => 'Old Name', 'company_email' => 'old@example.test', 'status' => 'active']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.companies.edit', ['id' => $company->id])
            ->set('company_name', 'New Name')
            ->call('update')
            ->assertRedirect(route('companies.all'));

        $this->assertSame('New Name', $company->fresh()->company_name);
    }

    public function test_contact_edit_persists_changes(): void
    {
        $contact = contact::create(['first_name' => 'Old', 'last_name' => 'Name', 'email' => 'old.name@example.test']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.contacts.edit', ['id' => $contact->id])
            ->set('first_name', 'New')
            ->call('update');

        $this->assertSame('New', $contact->fresh()->first_name);
    }

    public function test_deal_edit_persists_changes(): void
    {
        $deal = deal::create([
            'deal_name' => 'Old Deal',
            'deal_value' => 1000,
            'deal_stage' => 'lead',
            'deal_status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.deals.edit', ['id' => $deal->id])
            ->set('deal_stage', 'qualified')
            ->call('update');

        $this->assertSame('qualified', $deal->fresh()->deal_stage);
    }

    public function test_staff_edit_persists_changes(): void
    {
        $member = staff::create([
            'name' => 'Old Staffer',
            'email' => 'old.staffer@agency.test',
            'designation' => 'Developer',
            'joining_date' => now()->subMonths(3),
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.staff.edit', ['id' => $member->id])
            ->set('designation', 'Project Manager')
            ->call('update');

        $this->assertSame('Project Manager', $member->fresh()->designation);
    }
}
