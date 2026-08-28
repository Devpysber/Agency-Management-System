<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\company;
use App\Models\contact;
use App\Models\deal;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectPayment;
use App\Models\Task;
use App\Models\staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The main /dashboard route branches its view by the logged-in user's
 * staff.designation, so this asserts each designation renders its own
 * dashboard (and that an admin with no staff record, or a staff/manager
 * with no recognized designation, still gets a working fallback) rather
 * than erroring or silently falling through to someone else's view.
 */
class DesignationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public static function designations(): array
    {
        return [
            ['CEO', 'CEO Dashboard'],
            ['Project Manager', 'Project Manager Dashboard'],
            ['Developer', 'Developer Dashboard'],
            ['Designer', 'Designer Dashboard'],
            ['Sales Executive', 'Sales Executive Dashboard'],
        ];
    }

    /**
     * @dataProvider designations
     */
    public function test_staff_sees_their_designations_dashboard(string $designation, string $expectedHeading): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        staff::create([
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '+1 555-0000',
            'designation' => $designation,
            'joining_date' => now()->subMonths(6),
            'user_id' => $user->id,
            'salary' => 3000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($expectedHeading);
    }

    public function test_admin_without_staff_record_sees_org_wide_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Welcome back');
    }

    public function test_staff_without_recognized_designation_sees_generic_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        staff::create([
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '+1 555-0000',
            'designation' => 'Intern',
            'joining_date' => now()->subMonths(1),
            'user_id' => $user->id,
            'salary' => 1500,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_staff_with_no_linked_staff_record_sees_generic_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('No staff profile is linked');
    }

    /**
     * Exercises the Developer branch's real task list — including the
     * priority/status badge accessors and the mark-in-progress/complete
     * actions — with an actual assigned task, not just an empty state.
     */
    public function test_developer_sees_their_assigned_task_and_can_act_on_it(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $dev = staff::create([
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '+1 555-0000',
            'designation' => 'Developer',
            'joining_date' => now()->subMonths(6),
            'user_id' => $user->id,
            'salary' => 3000,
            'status' => 'active',
        ]);

        $task = Task::create([
            'title' => 'Fix the login bug',
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => now()->addDays(3),
            'assigned_to' => $dev->id,
            'created_by' => $user->id,
        ]);

        CalendarEvent::create([
            'title' => 'Sprint planning',
            'event_type' => 'meeting',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'assigned_to' => $dev->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Fix the login bug');
        $response->assertSee('Sprint planning');

        \Livewire\Livewire::actingAs($user)
            ->test('pages::admin.dashboard')
            ->call('markTaskInProgress', $task->id);

        $this->assertSame('in_progress', $task->fresh()->status);
    }

    /**
     * Exercises the CEO branch's real deal/project/payment aggregates and
     * relations (top deals -> company, staff-by-designation breakdown)
     * rather than just the all-zero empty state.
     */
    public function test_ceo_sees_real_company_wide_numbers(): void
    {
        $ceoUser = User::factory()->create(['role' => 'manager']);
        staff::create([
            'name' => $ceoUser->name,
            'email' => $ceoUser->email,
            'whatsapp' => '+1 555-0000',
            'designation' => 'CEO',
            'joining_date' => now()->subYears(2),
            'user_id' => $ceoUser->id,
            'salary' => 9000,
            'status' => 'active',
        ]);

        $acme = company::create(['company_name' => 'Acme Corp', 'status' => 'active']);
        deal::create([
            'deal_name' => 'Acme Website Revamp',
            'deal_value' => 15000,
            'deal_stage' => 'negotiation',
            'deal_status' => 'active',
            'company_id' => $acme->id,
            'created_by' => $ceoUser->id,
        ]);

        $project = Project::create([
            'company_id' => $acme->id,
            'name' => 'Acme Website',
            'status' => 'in_progress',
            'progress' => 40,
            'created_by' => $ceoUser->id,
        ]);
        ProjectPayment::create([
            'project_id' => $project->id,
            'amount' => 2500,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($ceoUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('CEO Dashboard');
        $response->assertSee('Acme Website Revamp');
        $response->assertSee('Acme Corp');
        $response->assertSee('$2,500.00'); // total revenue stat
    }

    /**
     * Exercises the Sales Executive branch's own-deals/own-contacts
     * scoping (created_by / assigned_to) with real, related rows.
     */
    public function test_sales_executive_sees_only_their_own_deals_and_contacts(): void
    {
        $repUser = User::factory()->create(['role' => 'staff']);
        $rep = staff::create([
            'name' => $repUser->name,
            'email' => $repUser->email,
            'whatsapp' => '+1 555-0000',
            'designation' => 'Sales Executive',
            'joining_date' => now()->subMonths(6),
            'user_id' => $repUser->id,
            'salary' => 3200,
            'status' => 'active',
        ]);

        $otherUser = User::factory()->create(['role' => 'staff']);

        $myCompany = company::create(['company_name' => 'My Client Co', 'status' => 'active']);
        deal::create([
            'deal_name' => 'My Deal',
            'deal_value' => 5000,
            'deal_stage' => 'proposal',
            'deal_status' => 'active',
            'company_id' => $myCompany->id,
            'created_by' => $repUser->id,
        ]);
        deal::create([
            'deal_name' => 'Someone Elses Deal',
            'deal_value' => 9000,
            'deal_stage' => 'proposal',
            'deal_status' => 'active',
            'created_by' => $otherUser->id,
        ]);

        contact::create([
            'first_name' => 'Jane',
            'last_name' => 'Client',
            'email' => 'jane.client@example.test',
            'assigned_to' => $rep->id,
        ]);

        $response = $this->actingAs($repUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Sales Executive Dashboard');
        $response->assertSee('My Deal');
        $response->assertDontSee('Someone Elses Deal');
        $response->assertSeeText('1'); // My Contacts stat = 1
    }

    /**
     * Exercises the Project Manager branch's own-projects scoping
     * (created_by) plus a real milestone.
     */
    public function test_project_manager_sees_their_own_projects_and_milestones(): void
    {
        $pmUser = User::factory()->create(['role' => 'manager']);
        staff::create([
            'name' => $pmUser->name,
            'email' => $pmUser->email,
            'whatsapp' => '+1 555-0000',
            'designation' => 'Project Manager',
            'joining_date' => now()->subMonths(10),
            'user_id' => $pmUser->id,
            'salary' => 4300,
            'status' => 'active',
        ]);

        $client = company::create(['company_name' => 'Client Co', 'status' => 'active']);
        $project = Project::create([
            'company_id' => $client->id,
            'name' => 'Client Rebrand',
            'status' => 'in_progress',
            'progress' => 60,
            'created_by' => $pmUser->id,
        ]);
        ProjectMilestone::create([
            'project_id' => $project->id,
            'title' => 'Logo Approval',
            'due_date' => now()->addWeek(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($pmUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Project Manager Dashboard');
        $response->assertSee('Client Rebrand');
        $response->assertSee('Logo Approval');
    }
}
