<?php

namespace Tests\Feature;

use App\Models\company;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * projects/show and projects/payments let anyone who could merely *view*
 * a project add milestones/payments — addMilestone()/addPayment() had no
 * permission check at all, unlike completeMilestone() and every delete()
 * elsewhere in the app. Fixed to require Projects/Edit; this locks that in.
 */
class ProjectPermissionGapTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(): Project
    {
        $company = company::create(['company_name' => 'Gap Co', 'status' => 'active']);

        return Project::create([
            'company_id' => $company->id,
            'name' => 'Gap Project',
            'status' => 'in_progress',
        ]);
    }

    public function test_view_only_user_cannot_add_milestone_from_project_show(): void
    {
        Role::create([
            'name' => 'view-only-projects',
            'permissions' => ['Projects' => ['View' => true, 'Create' => false, 'Edit' => false, 'Delete' => false]],
        ]);
        $user = User::factory()->create(['role' => 'view-only-projects']);
        $project = $this->makeProject();

        Livewire::actingAs($user)
            ->test('pages::admin.projects.show', ['id' => $project->id])
            ->set('milestone_title', 'Sneaky Milestone')
            ->call('addMilestone');

        $this->assertDatabaseMissing('project_milestones', ['title' => 'Sneaky Milestone']);
    }

    public function test_view_only_user_cannot_add_payment_from_project_show(): void
    {
        Role::create([
            'name' => 'view-only-projects-2',
            'permissions' => ['Projects' => ['View' => true, 'Create' => false, 'Edit' => false, 'Delete' => false]],
        ]);
        $user = User::factory()->create(['role' => 'view-only-projects-2']);
        $project = $this->makeProject();

        Livewire::actingAs($user)
            ->test('pages::admin.projects.show', ['id' => $project->id])
            ->set('payment_amount', 999)
            ->call('addPayment');

        $this->assertDatabaseMissing('project_payments', ['amount' => 999]);
    }

    public function test_view_only_user_cannot_add_payment_from_payments_page(): void
    {
        Role::create([
            'name' => 'view-only-projects-3',
            'permissions' => ['Projects' => ['View' => true, 'Create' => false, 'Edit' => false, 'Delete' => false]],
        ]);
        $user = User::factory()->create(['role' => 'view-only-projects-3']);
        $project = $this->makeProject();

        Livewire::actingAs($user)
            ->test('pages::admin.projects.payments')
            ->set('new_project_id', $project->id)
            ->set('payment_amount', 777)
            ->call('addPayment');

        $this->assertDatabaseMissing('project_payments', ['amount' => 777]);
    }

    public function test_user_with_projects_edit_can_add_milestone_and_payment(): void
    {
        Role::create([
            'name' => 'project-editor',
            'permissions' => ['Projects' => ['View' => true, 'Edit' => true]],
        ]);
        $user = User::factory()->create(['role' => 'project-editor']);
        $project = $this->makeProject();

        Livewire::actingAs($user)
            ->test('pages::admin.projects.show', ['id' => $project->id])
            ->set('milestone_title', 'Real Milestone')
            ->call('addMilestone')
            ->set('payment_amount', 500)
            ->set('payment_currency', 'USD')
            ->set('payment_status', 'pending')
            ->call('addPayment');

        $this->assertDatabaseHas('project_milestones', ['title' => 'Real Milestone']);
        $this->assertDatabaseHas('project_payments', ['amount' => 500]);
    }
}
