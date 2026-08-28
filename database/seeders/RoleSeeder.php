<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    protected $modules = [
        'Contacts', 'Companies', 'Deals', 'Projects', 'Tasks', 'Bugs', 'Communications',
        'Staff', 'Attendance', 'Services', 'Products', 'Portfolio', 'Testimonials',
        'Estimates', 'Quotations', 'Finance', 'Pricing', 'Blog', 'Reports', 'Settings',
    ];

    protected $actions = ['View', 'Create', 'Edit', 'Delete', 'Approve', 'Assign'];

    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Full access to all modules and system settings.',
                'permissions' => $this->buildPermissions(fn () => true),
            ],
            [
                'name' => 'manager',
                'description' => 'Manages day-to-day operations across most modules, without delete rights or access to system settings.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Settings') {
                        return false;
                    }
                    if ($action === 'Delete') {
                        return false;
                    }
                    return true;
                }),
            ],
            [
                'name' => 'staff',
                'description' => 'Limited, mostly view-only access to day-to-day modules.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    // Company-wide staff attendance roster/appeals is an
                    // HR/CEO-level view (attendance.person self-view is a
                    // separate, record-level check — not gated by this).
                    if (in_array($module, ['Settings', 'Reports', 'Attendance'], true)) {
                        return false;
                    }
                    return $action === 'View';
                }),
            ],
            [
                // Business role, not the account `role` column — matched via
                // staff.designation in User::hasPermission(). CEO is company-wide
                // VIEWER + APPROVER, not an owner/editor of any specific module
                // (no designation in the ownership model is "CEO" for records —
                // Project Manager owns delivery, Finance owns invoices, etc.).
                // Day-to-day create/edit/delete stays with each module's real
                // owner; CEO sees everything and has final approval authority.
                'name' => 'CEO',
                'description' => 'Company-wide visibility and approval authority, plus system administration (CEO and admin are one combined identity/panel).',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Settings') {
                        // CEO's account IS the admin account — needs to
                        // actually run User Management / Roles & Permissions,
                        // not just view them. Delete stays off (destructive).
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    return in_array($action, ['View', 'Approve'], true);
                }),
            ],
            [
                // COO owns OPERATIONS: delivery, workload, bottlenecks — not
                // finance/sales/HR-confidential/settings. "Can manage
                // operational resources" = Assign (reassign staff/tasks),
                // not Edit (doesn't rewrite project/task content) and not
                // Approve (no approval authority stated in the spec — that's
                // CEO/PM's, COO escalates to them instead).
                'name' => 'COO',
                'description' => 'Operations-wide visibility + resource (re)assignment. No approvals, no HR/financial/settings access.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if (in_array($module, ['Projects', 'Tasks'], true)) {
                        return in_array($action, ['View', 'Assign'], true);
                    }
                    if (in_array($module, ['Staff', 'Attendance', 'Communications', 'Contacts', 'Companies', 'Reports'], true)) {
                        return $action === 'View';
                    }
                    return false; // Deals/catalog/marketing/Settings — not operations' concern.
                }),
            ],
            [
                // HR owns employee records/attendance/lifecycle. Explicitly
                // NOT client data, sales pipeline, or payment gateways.
                'name' => 'HR & Admin Manager',
                'description' => 'Staff records, designations, and attendance. No client/sales/financial access.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Staff') {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    if ($module === 'Attendance') {
                        return in_array($action, ['View', 'Edit', 'Approve'], true);
                    }
                    // Reports NOT granted — HR's own dashboard already has
                    // headcount/attendance KPIs. See docs/rbac-spec.md.
                    return false;
                }),
            ],
            [
                // Account Manager owns the CLIENT RELATIONSHIP post-sale —
                // not new business (Sales/BDM) and not delivery (PM).
                'name' => 'Account Manager',
                'description' => 'Owns the client relationship: contacts, companies, communications, estimates/quotations follow-through. Views (not edits) project delivery.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if (in_array($module, ['Contacts', 'Companies', 'Communications', 'Estimates', 'Quotations'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    if (in_array($module, ['Projects', 'Deals'], true)) {
                        return $action === 'View'; // visibility into delivery/pipeline, not ownership
                    }
                    return false;
                }),
            ],
            [
                // BDM manages the SALES TEAM + pipeline strategy — a superset
                // of what a Sales Executive gets on their own assigned deals.
                'name' => 'Business Development Manager',
                'description' => 'Manages the sales team, pipeline, and lead assignment. Full sales-module authority; no delivery/HR/financial access.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if (in_array($module, ['Deals', 'Contacts'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit', 'Assign'], true);
                    }
                    if (in_array($module, ['Companies', 'Communications', 'Estimates', 'Quotations'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    // Reports intentionally NOT granted: BDM's own dashboard
                    // already surfaces pipeline/conversion/team metrics —
                    // the separate Reports module is reserved for
                    // CEO/COO/Finance (company-wide oversight), not
                    // duplicated out to every manager-tier designation.
                    if ($module === 'Staff') {
                        return $action === 'View';
                    }
                    return false;
                }),
            ],
            [
                // Sales Executive owns ASSIGNED opportunities — record-level
                // scoping (deals.all/pipeline/lost, Contact "My Leads") is
                // enforced in the component queries; this grant is
                // module-wide edit rights on the sales-facing modules,
                // matching the pattern already used for every other
                // non-manager role in this codebase.
                'name' => 'Sales Executive',
                'description' => 'Owns assigned leads/deals. No team-wide assign/approve authority (that\'s BDM/CEO).',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if (in_array($module, ['Deals', 'Contacts'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    if (in_array($module, ['Companies', 'Communications', 'Estimates', 'Quotations'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    return false;
                }),
            ],
            [
                // Project Manager owns PROJECT DELIVERY: create projects,
                // assign team/tasks, run milestones, communicate status.
                // Explicitly NOT HR, Roles, or Payment Gateways.
                'name' => 'Project Manager',
                'description' => 'Owns project delivery: create/edit projects, assign team & tasks, manage milestones. No HR/settings/payment-gateway access.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if (in_array($module, ['Projects', 'Tasks'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit', 'Assign'], true);
                    }
                    if (in_array($module, ['Communications', 'Contacts', 'Companies'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    // Reports NOT granted — PM's own dashboard already has
                    // project/budget/team metrics; company-wide Reports
                    // stays with CEO/COO/Finance. See docs/rbac-spec.md.
                    if ($module === 'Staff') {
                        return $action === 'View';
                    }
                    return false; // HR, Settings, Payment Gateways, catalog/marketing.
                }),
            ],
            [
                // Designer primarily manages assigned work: update own tasks,
                // upload/submit designs (Portfolio is the closest existing
                // deliverable-storage module), respond to feedback via
                // Communications. NOT global project management, staff, or
                // financial data.
                'name' => 'Designer',
                'description' => 'Manages assigned design tasks/deliverables. No global project/staff/financial access.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Tasks') {
                        return in_array($action, ['View', 'Edit'], true); // own tasks — scoped in component queries
                    }
                    if (in_array($module, ['Portfolio', 'Communications'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    return false;
                }),
            ],
            [
                // Developer owns assigned development tasks + fixing assigned
                // bugs. Submits to QA (bug status transitions self-gate on
                // ownership in bugs/show.blade.php, not this grid).
                'name' => 'Developer',
                'description' => 'Owns assigned development tasks and bug fixes. No global project/staff/financial access.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Tasks') {
                        return in_array($action, ['View', 'Edit'], true);
                    }
                    if ($module === 'Bugs') {
                        return in_array($action, ['View', 'Create', 'Edit'], true); // own — scoped in bugs queries
                    }
                    return false;
                }),
            ],
            [
                // Tech Lead owns TECHNICAL EXECUTION: assigns developers,
                // reviews/approves technical + QA work, escalates risk.
                // Separate from QA's testing authority and Developer's
                // implementation ownership (kept distinct per the spec).
                'name' => 'Tech Lead',
                'description' => 'Owns technical execution: assigns developers, reviews code/bugs, coordinates QA and release readiness.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Tasks') {
                        return in_array($action, ['View', 'Create', 'Edit', 'Assign'], true);
                    }
                    if ($module === 'Bugs') {
                        return in_array($action, ['View', 'Create', 'Edit', 'Assign'], true);
                    }
                    // Reports NOT granted — Tech Lead's dashboard already
                    // has dev-team/QA/release metrics. See docs/rbac-spec.md.
                    if (in_array($module, ['Projects', 'Staff'], true)) {
                        return $action === 'View';
                    }
                    return false;
                }),
            ],
            [
                // QA owns testing + QA approval — the ONLY designation with
                // Bugs.Approve (verify/fail-retest authority). Deliberately
                // no Assign (that's Tech Lead's) and no Edit on other
                // developers' tasks.
                'name' => 'QA Engineer',
                'description' => 'Owns testing and QA approval — verifies/fails bug retests. No task-assignment or delivery-management authority.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Bugs') {
                        return in_array($action, ['View', 'Create', 'Approve'], true);
                    }
                    // Reports NOT granted — QA's own dashboard has pass-rate/
                    // queue metrics. See docs/rbac-spec.md.
                    if (in_array($module, ['Tasks', 'Projects'], true)) {
                        return $action === 'View';
                    }
                    return false;
                }),
            ],
            [
                // AI/ML Engineer owns AI implementation. No dedicated AI
                // module exists in this schema (Experiments/Models/Costs —
                // see docs/rbac-spec.md); scoped to what's real: their own
                // tasks + project visibility.
                'name' => 'AI/ML Engineer',
                'description' => 'Owns assigned AI/ML implementation tasks. No AI-specific data model exists yet — see docs/rbac-spec.md.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Tasks') {
                        return in_array($action, ['View', 'Edit'], true);
                    }
                    if ($module === 'Projects') {
                        return $action === 'View';
                    }
                    return false;
                }),
            ],
            [
                // Marketing Manager owns marketing strategy: Portfolio/
                // Testimonials/Blog are the closest existing content
                // modules. No Campaign/spend/ROI model exists — see
                // docs/rbac-spec.md.
                'name' => 'Marketing Manager',
                'description' => 'Owns marketing content (Portfolio/Testimonials/Blog). No campaign/ad-spend model exists yet — see docs/rbac-spec.md.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if (in_array($module, ['Portfolio', 'Testimonials', 'Blog'], true)) {
                        return in_array($action, ['View', 'Create', 'Edit', 'Delete'], true);
                    }
                    // Reports NOT granted — no Sales/Activity/Performance/
                    // Client-Portal report is relevant to marketing content
                    // management, and the shared Reports module isn't
                    // filtered per viewer. See docs/rbac-spec.md.
                    if ($module === 'Communications') {
                        return $action === 'View';
                    }
                    return false;
                }),
            ],
            [
                // Finance owns invoices/payments/reconciliation/reporting.
                // ProjectPayment IS the invoice record in this schema (no
                // separate Invoice entity — see docs/rbac-spec.md). No
                // technical/source-code visibility (there isn't any Finance
                // could see anyway — this just keeps the grant honest).
                'name' => 'Finance Manager',
                'description' => 'Owns invoices/payments/reconciliation/financial reporting. Full financial authority including refunds.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Finance') {
                        return in_array($action, ['View', 'Create', 'Edit', 'Approve'], true);
                    }
                    if (in_array($module, ['Estimates', 'Quotations', 'Reports'], true)) {
                        return $action === 'View';
                    }
                    return false;
                }),
            ],
            [
                'name' => 'Accountant',
                'description' => 'Records payments/invoices and reconciles. No refund-approval authority — that stays with Finance Manager.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Finance') {
                        return in_array($action, ['View', 'Create', 'Edit'], true);
                    }
                    if (in_array($module, ['Estimates', 'Quotations', 'Reports'], true)) {
                        return $action === 'View';
                    }
                    return false;
                }),
            ],
            [
                'name' => 'Finance Executive',
                'description' => 'View + record payments only. No editing existing financial records, no approvals.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if ($module === 'Finance') {
                        return in_array($action, ['View', 'Create'], true);
                    }
                    return $action === 'View' && in_array($module, ['Estimates', 'Quotations'], true);
                }),
            ],
            [
                // Intern: highly restricted, per explicit instruction. Only
                // records assigned to them — enforced by the SAME
                // assigned_to scoping already built for Tasks/Projects/
                // Contacts (this grid just grants View on the two modules
                // that scoping applies to; no create/edit/delete anywhere).
                'name' => 'Intern',
                'description' => 'Highly restricted — assigned work + own attendance/calendar only. No financials, no client list, no sales pipeline, no staff/settings.',
                'permissions' => $this->buildPermissions(function ($module, $action) {
                    if (in_array($module, ['Tasks', 'Projects'], true)) {
                        return $action === 'View'; // scoped to assigned in component queries
                    }
                    return false;
                }),
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                [
                    'description' => $role['description'],
                    'permissions' => $role['permissions'],
                ]
            );
        }
    }

    protected function buildPermissions(callable $resolver): array
    {
        $permissions = [];
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                $permissions[$module][$action] = $resolver($module, $action);
            }
        }
        return $permissions;
    }
}
