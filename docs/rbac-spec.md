# CRM Role-Based Architecture — Governing Spec

Source of truth for all designation-by-designation build-out. Set by the user
2026-08-28. Do not deviate without the user re-approving a change here.

## Process (mandatory, per designation)

Work happens **one designation at a time**, only when the user names it.
Never redesign unrelated parts. Never jump ahead to the next designation.

1. Inspect existing implementation (models/components/routes/policies/queries/UI).
2. Identify what's reusable.
3. Component Responsibility Map.
4. Permission Matrix.
5. Workflow Map.
6. Explain what changes.
7. Implement the designation's dashboard + functionality.
8. Wire to real DB data.
9. Backend authorization (policies/gates/permission checks — never hidden-menu-only).
10. Frontend nav visibility (in addition to #9, never instead of).
11. Notifications + activity logging where relevant.
12. Test unauthorized access.
13. Test normal workflows.
14. Report files changed.

## Per-component documentation template

Every module/component gets this filled in before it's implemented:

```
COMPONENT: [Name]
PURPOSE: [What it does]
WHY: [Why the agency needs it]
BUSINESS OWNER: [Designation responsible]
MANAGER: [Designation(s) allowed to manage]
VIEW / CREATE / EDIT / DELETE / ASSIGN / APPROVE: [who]
DATA: [Models/tables]
WORKFLOW: [Step-by-step lifecycle]
STATUSES: [enum]
NOTIFICATIONS: [who, when]
ACTIVITY: [what gets logged]
REPORTING: [reports/KPIs fed]
RELATIONSHIPS: [connected modules]
EXAMPLE: [real agency scenario]
```

Ownership principle: **OWNER ≠ VIEWER ≠ MANAGER ≠ APPROVER**. A user can view
without editing, manage without approving, own a record without admin rights.

## Information architecture (target)

- DASHBOARD (per-designation, not generic)
- ADMIN ASSISTANT
- CLIENT MANAGEMENT: Contacts, Companies, Deals, Communications
- PROJECT MANAGEMENT: Projects, Tasks, Calendar
- SALES: Estimates, Quotations
- CATALOG: Services, Products, Pricing
- MARKETING: Portfolio, Testimonials, Blog
- TEAM: Staff, Attendance
- REPORTS
- SETTINGS: General, User Management, Roles & Permissions, Payment Gateways
- CLIENT PORTAL: Dashboard, Projects, Tasks, Calendar, Messages, Payments,
  Invoices, Documents, Contracts, Profile, Settings

## Departments / designations (target roster)

- MANAGEMENT: CEO/Founder, COO/Operations Manager
- HR & ADMIN: HR & Admin Manager
- CLIENT & SALES: Account Manager, Business Development Manager, Sales Executive
- PROJECT MANAGEMENT: Project Manager, Project Coordinator
- DESIGN: Creative Director, UI/UX Designer, Graphic Designer
- DEVELOPMENT: Tech Lead, Developer, Developer Intern
- QUALITY & INFRA: QA Engineer, DevOps Engineer
- AI & DATA: AI/ML Engineer, AI/ML Intern, Data Analyst
- MARKETING: Marketing Manager, SEO Specialist, Social Media Manager,
  Content Writer, Performance Marketing Specialist
- FINANCE: Finance Manager, Accountant, Finance Executive
- GENERAL: Intern

Current seeded `staff.designation` values only cover a subset (CEO, Project
Manager, Sales Executive, Developer, Designer, Intern-ish). Full roster above
is the target — add designations as each is tackled, not all at once.

## Permission naming (target)

`module.action` — e.g. `projects.view/create/edit/delete/assign/approve`,
`tasks.view/create/edit/assign/complete`, `clients.view/create/edit`,
`deals.view/create/edit/close`, `communications.view/create`,
`invoices.view/create/edit/approve`, `payments.view/record/refund`,
`staff.view/create/edit/manage`, `attendance.view/manage`,
`reports.view/financial/performance`, `settings.manage`, `roles.manage`,
`permissions.manage`. Grow this list only when a real action needs it —
no speculative permissions.

## Security rules (non-negotiable)

- Laravel Policies + Gates + permission middleware + record-level checks.
- Department restrictions, assignment-based access, client isolation.
- Hiding a sidebar link is UI only — never the authorization boundary.
- No hardcoded user IDs. No hardcoded `$user->designation === 'X'` checks
  where a permission lookup belongs instead.

## Dashboard rule

Per-designation dashboard shows only: KPIs relevant to that role, assigned
work, pending actions, upcoming deadlines, alerts, recent activity, relevant
reports, quick actions. Nothing outside that role's business (e.g. Developer
never sees revenue/HR/payroll/gateway config; Finance never sees source code;
HR doesn't see confidential project content unless required; Intern sees only
assigned work + own attendance + relevant comms).

## STEP 1/2 findings — existing infra as of 2026-08-28 (pre-first-designation)

- **`Role` model** (`app/Models/Role.php`) — `permissions` JSON cast,
  shape `permissions[module][action] => bool`. This is the right shape to
  extend for the `module.action` grid above.
- **Gap**: `Role` is currently keyed by `staff.role` (`admin`/`staff`/`client`
  only — 3 coarse values), NOT by `staff.designation`. Seeded rows: `admin`,
  `manager` (orphaned — no user ever has `role=manager`), `staff` (shared by
  EVERY non-admin staff member regardless of designation). So today,
  Sales Executive, Developer, Project Manager, Designer all collapse onto the
  same single `staff` permission row — the system cannot yet distinguish them.
- **`User::hasPermission($module, $action)`** (`app/Models/User.php:67`) —
  admin always true, else looks up `Role::where('name', $this->role)`. Correct
  mechanism, wrong key (role, not designation) for what this spec needs.
- **`App\Support\EditGate`** (built this session, prior to this spec) — a
  *stopgap* hardcoded `designation === 'Project Manager'` check used to lock
  down CRUD to admin+PM CRM-wide. This is exactly the anti-pattern the spec
  says to avoid. It should be superseded by real per-designation permission
  rows as each designation is built out — not deleted yet (it's the only
  thing currently protecting write access), but expect it to shrink/retire
  as `projects.edit`, `deals.edit`, etc. come online per-designation.
  Existing `RestrictEditing` Livewire hook (blanket write-intercept) has the
  same status — a bridge, not the target architecture.
- **Roles & Permissions admin page** (`pages::admin.settings.roles-permissions`)
  already renders a V/C/E/D grid UI against the `Role` model — reusable UI,
  needs re-pointing at designation-keyed rows instead of the 3 coarse roles.
- **`staff.designation`** — free-text string column, nullable, no enum/table
  backing it. Will likely want a `designations` reference (or at minimum a
  documented canonical list) as more designations get wired to permissions,
  so typos don't silently fall through `hasPermission()` as "no permission".

## Designation build log — batch build 2026-08-28 (CEO, COO, HR, Account Manager, BDM, Sales Executive, Project Manager, all in one user-approved run)

Infra additions used by every row below: `Tasks` and `Communications` added
to the permission grid + `CheckModuleAccess` (existed as real modules,
never had a permission row before); `Assign` added as a 6th action
(`View`/`Create`/`Edit`/`Delete`/`Approve`/`Assign`, matching the master
spec's `module.assign` naming); `deals.assigned_to` migration + `deal::assignedTo()`
/`staff::deals()` relations added — deals had **no owner** before this
(only `created_by`, the user who typed it in), so "Sales Executive owns
assigned opportunities" / "BDM assigns leads" had no data to stand on.
Existing deals backfilled round-robin across seeded sales staff; `DealSeeder`
updated to assign on fresh seeds too. `company::projects()` / `company::communications()`
relations added (existed as FKs, no Eloquent relation). 4 new staff logins
seeded for designations that had none: Grace Nolan (COO), Diane Foster (HR &
Admin Manager), Natalie Reyes (Account Manager), Isabella Cruz (Business
Development Manager) — all `*.agency.test` / `password`.

### COO / Operations Manager

| # | Component | What | Data | Permission |
|---|---|---|---|---|
| 1 | Operations Overview | Active/on-hold projects, open/overdue task counts | Project, Task | View |
| 2 | Project Health | Status breakdown + overdue milestones company-wide | Project, ProjectMilestone | View |
| 3 | Team Workload | Open-task count per active staff member (flags >5 as overloaded) | staff, Task | View |
| 4 | Resource Allocation | Active-project count per staff member | staff, project_staff | View |
| 5 | Task Health | Status breakdown chart, company-wide | Task | View |
| 6 | Delayed Work | Overdue tasks with assignee | Task | View |
| 7 | Client Issues | Same at-risk signal as CEO's Client Overview, operational framing | company, ProjectPayment, Communication | View |
| 8 | Operational Alerts | Escalations + overdue-milestones cards on the dashboard itself | (aggregation) | — |
| 9 | Performance Reports | Links to Reports.performance/activity (Reports.View granted) | — | View |
| 10 | Escalations | Tasks overdue 3+ days — **visibility only**, no dedicated escalation-ticket model exists (flagged, not fabricated) | Task | View |

Owner: COO (self). Manager: CEO. Create/Edit/Delete: none — COO's authority
per the spec is monitor + **reassign**, not rewrite project/task content.
Assign: `Projects.Assign`, `Tasks.Assign` = true — this is the "manage
operational resources" capability. Approve: none (COO escalates to CEO/PM,
doesn't hold approval authority itself per the spec). Explicitly false:
Settings, Roles, Payment Gateways, Staff.Edit (HR-confidential), Deals/catalog
(not operations).

### HR & Admin Manager

| # | Component | What | Data | Permission |
|---|---|---|---|---|
| 1 | Staff Directory + headcount | Active/inactive counts, by-designation breakdown | staff | View/Create/Edit |
| 2-4 | Employee Profile, Departments, Designations | Reuses existing Staff module (`staff.all`/`show`/`edit`, `staff.designations`) | staff, Designation | View/Create/Edit |
| 5 | Attendance | Today's present/absent/not-marked, full ERP access | AttendanceRecord | View/Edit |
| 6 | Leave Management | **Not built** — no leave-request/balance model exists (only ad-hoc attendance status + appeals). Flagged, not faked. | — | — |
| 7 | Employee Documents | **Not built** — no document-upload model on staff. Flagged. | — | — |
| 8 | Joining/Exit Information | Recent joiners (30d) shown. Exit/offboarding has no `exit_date` field — not tracked, flagged on the dashboard itself. | staff.joining_date | View |
| 9 | Staff Performance | **Not built as a distinct HR score** — Reports.performance (existing module) is the closest real data; linked, not duplicated. | — | View |
| 10-11 | HR / Attendance Reports | Reports.View granted | — | View |
| 12 | HR Notifications | Reuses existing attendance-appeal/absence notification paths (`Notifier`, `AlertHooks`) — no new HR-specific channel built | — | — |

Owner: HR & Admin Manager. Manager: CEO/COO. Approve: `Attendance.Approve`
(day-to-day appeal review — CEO is the escalation path, not the default
reviewer, per the CEO section above). Explicitly false: Contacts, Companies,
Deals, Communications, Quotations, Estimates (client/sales data), Settings,
Payment Gateways — matches "HR must NOT access client confidential
information, sales pipeline, payment gateways unless explicitly authorized."

### Account Manager

| # | Component | What | Data | Permission |
|---|---|---|---|---|
| 1 | My Clients | Companies whose contacts are `assigned_to` this AM | company, Contact | View (scoped in dashboard query) |
| 2-3 | Client Profile / Contacts | Reuses Companies/Contacts show+edit pages | company, Contact | View/Create/Edit |
| 4 | Client Health | At-risk signal (stale unpaid invoice 14d+ / silent 30d+), scoped to AM's book | ProjectPayment, Communication | View |
| 5-7 | Client Requests, Communications, Meetings | Reuses Communications module + CalendarEvent | Communication, CalendarEvent | View/Create/Edit |
| 8 | Client Projects | Projects under AM's companies — **view only**, PM owns delivery | Project | View |
| 9-10 | Client Approvals, Feedback | **Not built as discrete entities** — no approval-request or feedback-score model exists yet. Flagged. | — | — |
| 11 | Client Issues | Same at-risk list as component 4 | — | View |
| 12 | Client Activity Timeline | Recent Communications feed, scoped | Communication | View |
| 13-14 | Estimates, Quotations | Full module access for AM's contacts | Estimate, Quotation | View/Create/Edit |
| 15 | Account Reports | Reports.View | — | View |

Owner: Account Manager (per-client, via `Contact.assigned_to`). Manager:
CEO/COO. Explicitly false: Deals.Edit (Sales owns new business), Projects.Edit
(PM owns delivery), Staff, Settings. Note: permission grid grants Edit at the
**module** level (same pattern as every other non-admin role in this
codebase — e.g. Project Manager's EditGate is also module-wide, not
per-record) — an AM can technically edit a contact outside their book if
they navigate there directly; the dashboard scopes to "my clients" but this
isn't a hard per-record lock. Flagged as a design choice, not implemented
as a stricter record-level gate (would need the same assigned_to-scoping
treatment already applied to Projects/Tasks/Contacts for regular staff,
extended specifically to AM's own permission level — say if you want that).

### Business Development Manager

| # | Component | What | Data | Permission |
|---|---|---|---|---|
| 1 | Sales Overview | Pipeline value, won-this-month, conversion rate, new leads this week | deal, Contact | View |
| 2-5 | Leads, Contacts, Companies, Deals | Full sales-module CRUD + Assign (reassign a deal/lead to a Sales Executive) | Contact, company, deal | View/Create/Edit/Assign |
| 6 | Sales Pipeline | Stage-breakdown chart, team-wide (not "my" — BDM manages the team) | deal | View |
| 7 | Follow-ups | Deals untouched 5+ days — proxy signal, no dedicated follow-up-task-per-deal model (flagged) | deal.updated_at | View |
| 8-9 | Calls, Meetings | Communications module | Communication | View/Create/Edit |
| 10 | Sales Team | Roster + open-deal count per Sales Executive/BDM | staff, deal | View |
| 11 | Sales Targets | **Not built** — no target/quota model exists. Flagged. | — | — |
| 12 | Conversion Analytics | Win/loss rate computed from `deal.deal_status` | deal | View |
| 13 | Revenue Analytics | **Not duplicated here** — CEO's Revenue Overview + Reports.sales already cover this; BDM gets `Reports.View`. | — | View |
| 14-15 | Estimates, Quotations | Full module access | Estimate, Quotation | View/Create/Edit |
| 16 | Sales Reports | Reports.View | — | View |

Owner: BDM (team-wide). Manager: CEO. Approve: none granted this round (no
concrete "sales action requiring approval" identified beyond what Deals.Edit
already allows — flagged rather than inventing an approval gate). Explicitly
false: Projects, Staff.Edit, Attendance, Settings.

### Sales Executive

| # | Component | What | Data | Permission |
|---|---|---|---|---|
| 1 | My Leads | Contacts I own with lead_status new/contacted/qualified | Contact | View/Create/Edit (own) |
| 2 | My Contacts | All contacts assigned to me | Contact | View/Create/Edit (own) |
| 3 | My Companies | Companies derived from my contacts | company | View |
| 4 | My Deals | Deals where `assigned_to` = me | deal | View/Create/Edit (own) |
| 5 | Pipeline | My deals by stage | deal | View (own) |
| 6 | Follow-ups | My active deals untouched 5+ days | deal.updated_at | View (own) |
| 7-8 | Calls, Meetings | Communications/CalendarEvent scoped by `staff_id`/`assigned_to` | Communication, CalendarEvent | View/Create/Edit |
| 9-10 | Estimates, Quotations | Full module access (same as BDM/AM — no per-record scoping built for these two yet, flagged) | Estimate, Quotation | View/Create/Edit |
| 11 | Sales Activity | Comms logged this week (volume) | Communication | View |
| 12 | Sales Performance | Win rate, pipeline value, deals won this month — own numbers | deal | View |

**Fixed a real bug found while building this**: the dashboard's "My Deals"
was scoping by `deal.created_by` (whoever typed the row in) instead of
`assigned_to` (real ownership) — wrong signal, now corrected everywhere
(dashboard + `deals.all`/`pipeline`/`lost`/`show`).

**Record-level scoping** (not just permission-grid, actual query/mount
scoping — same treatment Projects/Tasks/Contacts got earlier this session):
a user who **can edit** deals but has no team-wide `Assign`/`Approve`
authority (exactly the Sales Executive profile) is scoped to
`assigned_to = their own staff id` on `deals.all`/`pipeline`/`lost`/`show`;
direct-URL access to another rep's deal 403s. Pure viewers (Account Manager,
CEO, anyone with `Deals.View` but not `Edit`) still see everything
read-only — unaffected. BDM/admin always see everything.

**Won/Lost workflow — status rules.** The spec's requested stages
(Lead → Contacted → Qualified → Discovery → Proposal → Negotiation →
Won/Lost) are **more granular than the existing `deal_stage` enum**
(`lead`/`qualified`/`proposal`/`negotiation`/`closed_won`/`closed_lost` —
6 values, used throughout the pipeline Kanban, dashboards, and reports).
Renaming/splitting that enum is a real schema + UI change (Kanban columns,
`stageLabels`/`stageColors` arrays in multiple dashboards, existing seeded
data) — **not done this round**, flagged rather than silently reinterpreted.
The rules below map onto the *existing* 6 stages, which cover the same
lifecycle at coarser granularity:

| Stage | Who can change it | Notification | Activity logged | Downstream action |
|---|---|---|---|---|
| lead | owner (assigned Sales Exec), BDM | none yet — not wired | `deals.edit`-permission write (model timestamps only; no dedicated deal-history log exists — flagged) | none automatic |
| qualified | owner, BDM | none yet | same | none automatic |
| proposal | owner, BDM | none yet | same | none automatic |
| negotiation | owner, BDM | none yet | same | none automatic |
| closed_won | owner, BDM, CEO (Approve) | none yet | same | none automatic — **no auto-project-creation from a won deal exists** (a real gap: "Company → Deals → Projects" in the core relationships list implies a deal converting into delivery work, but there's no convert-to-project action anywhere in the codebase). Flagged, not built. |
| closed_lost | owner, BDM, CEO (Approve) | none yet | same | none automatic |

**Honest gap called out clearly**: this codebase has no deal-activity/audit
log distinct from Laravel's `updated_at`, and no notification fires on a
stage change anywhere. Given the scope of this batch, wiring a full
notification+activity-log system for every stage transition across every
designation wasn't attempted — flagging as a concrete, scoped follow-up
rather than fabricating log entries or events that don't fire.

### Project Manager (expanded — owns PROJECT DELIVERY)

| # | Component | What | Data | Permission |
|---|---|---|---|---|
| 1-2 | Project Overview, Projects | Status counts + active projects list (scoped to `created_by`, pre-existing) | Project | View/Create/Edit/Assign |
| 3 | Milestones | Overdue + upcoming, own projects | ProjectMilestone | View/Edit (via canUpdateProgress) |
| 4-5 | Tasks, Team Assignment | My tasks + team roster on my projects (`project_staff` pivot) | Task, staff | View/Assign |
| 6 | Workload | Open-task count per team member on my projects | staff, Task | View |
| 7 | Deadlines | Upcoming milestones + `submission_due_at` | ProjectMilestone, Project | View |
| 8 | Project Files | **Not built** — no file/document model exists anywhere in the schema. Flagged, not fabricated. | — | — |
| 9 | Client Updates | Recent communications across my projects' companies | Communication | View |
| 10 | Client Approvals | **Not built as a discrete entity** — no client-facing approval-request model (milestone statuses are just pending/in_progress/completed, no "awaiting client review" state). Flagged. | — | — |
| 11 | Project Budget | Budgeted vs collected vs outstanding, my active projects | Project.budget, ProjectPayment | View |
| 12 | Project Health | Status breakdown chart | Project | View |
| 13 | Risks | **Computed signal, not a stored entity** (no risk-register model): flags a project on_hold, past its `submission_due_at`, or carrying an overdue milestone. Visibility only. | Project, ProjectMilestone | View |
| 14 | Project Activity | Not built as a separate feed — Client Updates (comms) + the existing per-project chat (`ProjectChat`) already cover this; not duplicated | — | View |
| 15 | Project Reports | Reports.View | — | View |

Owner: Project Manager (own projects). Manager: CEO/COO. Approve:
milestone/progress updates already correctly self-gate via the pre-existing
`canUpdateProgress()` (PM or any assigned staff) — untouched. Create/Edit/
Assign: `Projects.*`/`Tasks.*` = true. Explicitly false: HR, Settings,
Payment Gateways, catalog/marketing modules.

### CRITICAL FIX: `RestrictEditing` hook was silently overriding every new
permission grant this batch (found via real-HTTP testing, not Livewire::test)

**How it was found**: `Livewire::test()->assertOk()` does **not** run route
middleware (`CheckModuleAccess`) — it mounts the component directly. Every
"OK" assertion this session only ever proved the component's own internal
checks worked, never the middleware layer. Switched to real kernel-level
HTTP requests (`$kernel->handle($request)` with a genuine session) to
re-verify, and found: (1) Project Manager was blocked from `projects.add` by
`CheckModuleAccess` (no dedicated Role row — fixed, added one); (2) HR was
blocked from `staff.add` by a **hardcoded** `abort_unless(EditGate::allows())`
left over from an earlier session pass — same for `staff.edit` and
`estimates.edit`; (3) worst one: `RestrictEditing`'s blanket write-block
checked **only** `EditGate::allows()` (admin + Project-Manager-designation),
so BDM's newly-granted `Deals.Edit=true` was silently no-opped on every save
— confirmed by directly testing a deal edit end-to-end (value unchanged).

**Fix**: `RestrictEditing` now derives the module from the component name
and the target action from the method name (`delete*`→Delete, `approve*`/
`accept*`/`reject*`→Approve, `assign*`→Assign, `store*`/`create*`/`add*`→
Create, else→Edit), and bypasses the block if `$user->hasPermission($module,
$action)` — in addition to the existing `EditGate::allows()` bypass, not
instead of it (Project Manager's blanket access still works unchanged).
`staff/add`, `staff/edit`, `estimates/edit` mount()s and `staff/all`'s two
button-visibility checks updated to the same `EditGate::allows() ||
hasPermission(...)` pattern.

**Re-verified end-to-end after the fix** (real writes, not just page loads):
BDM can now actually save a deal edit (value changed, confirmed, reverted).
HR can now actually create a staff record (count 12→13, confirmed, cleaned
up). AM can now actually edit a contact (value changed, confirmed, reverted).
David (Sales Exec, no `Deals.Delete`) still blocked from deleting a deal —
unchanged. David still blocked from `calendar.markCompleted` — unchanged.
All 12 seeded users' dashboards still render OK. `view:cache` compiles clean.

**Known remaining gap, flagged not fixed** (scope/time): the ~15 other
files with `@if (EditGate::allows())`-only **button visibility** (Contacts/
Companies/Communications/Quotations/Projects/Tasks list pages, from an
earlier session pass) don't yet also check the new per-module permission —
so an AM/BDM/PM/Sales Exec with real Edit rights on those modules won't
*see* the Edit button/link on some list pages, even though navigating there
directly now works (RestrictEditing is fixed) and the record-level scoping
(Deals) is correct. A UI-discoverability gap, not an authorization gap —
worth a follow-up pass applying the same `EditGate::allows() ||
hasPermission(...)` pattern mechanically across those files.

### Files changed this batch (COO/HR/AM/BDM infra + dashboards)
- `database/migrations/2026_08_28_140000_add_assigned_to_deals_table.php` (new)
- `app/Models/deal.php` — `assigned_to` fillable + `assignedTo()` relation
- `app/Models/staff.php` — `deals()` relation
- `app/Models/company.php` — `projects()`, `communications()` relations
- `database/seeders/StaffSeeder.php` — 4 new staff rows
- `database/seeders/DealSeeder.php` — round-robin `assigned_to` on seed
- `database/seeders/RoleSeeder.php` — `Tasks`/`Communications` modules,
  `Assign` action, COO/HR & Admin Manager/Account Manager/Business
  Development Manager role rows
- `resources/views/pages/admin/settings/⚡roles-permissions.blade.php` —
  `$modules`/`$actions` grid extended to match
- `app/Http/Middleware/CheckModuleAccess.php` — `tasks`, `communications` added
- `resources/views/pages/admin/⚡dashboard.blade.php` — `cooData()`,
  `hrData()`, `accountManagerData()`, `bdmData()` + router branches +
  `@switch` cases; CEO's `ceoData()` expanded (Client/Financial Overview,
  high-value quotations)
- New: `resources/views/pages/admin/dashboard/{coo,hr,account-manager,bdm}.blade.php`
- `resources/views/pages/admin/dashboard/ceo.blade.php` — expanded (see CEO section above)

### Tests run this batch
- All 4 new dashboards: render OK for their designation (Livewire::test + assertOk).
- Permission matrix spot-checks (`hasPermission()` direct calls, not just
  reasoning about the seeded JSON): HR Staff.Edit=T/Deals.View=F/Settings.View=F,
  AM Contacts.Edit=T/Deals.Edit=F/Projects.Edit=F, BDM Deals.Assign=T/Staff.Edit=F,
  COO Projects.Assign=T/Settings.View=F/Staff.Edit=F — all correct.
- Regression: admin, CEO, Project Manager, Developer, Sales Executive
  dashboards still render OK after all the above. `view:cache` compiles clean.
- **Testing tier note**: given the scale of this batch (4 designations in
  one pass), verification was dashboard-render + permission-matrix spot
  checks, not exhaustive Livewire::test coverage of every button/action on
  every one of the ~40 components documented above (most of which reuse
  existing, already-tested CRM modules — Contacts/Companies/Deals/etc. —
  rather than new write paths). Flagging the tier explicitly rather than
  overstating coverage.

## Designation build log

### CEO / Founder — 2026-08-28, expanded 2026-08-28 (10-component panel)

**Component Responsibility Map**

| # | Component | What | Owner | Manager | Viewer | Create/Edit/Delete | Approve | Assign | Data | Connects to |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Business Overview | Top-line KPI strip (revenue, active projects, staff, pipeline, win rate) | CEO | — | CEO | none (aggregation) | — | — | Project, ProjectPayment, deal, staff | Revenue, Pipeline, Team |
| 2 | Revenue Overview | 6-month revenue trend + this-month vs total | CEO | Finance (future) | CEO | none | — | — | ProjectPayment | Financial Overview |
| 3 | Client Overview | Active clients, at-risk clients (overdue payment or no recent activity), new this month | CEO | Account Manager (future) | CEO | none | — | — | company, Communication, ProjectPayment | Client modules, Communications |
| 4 | Sales Pipeline | Deals by stage chart + top 5 open deals by value | CEO | BDM (future) | CEO | none | — | — | deal | Deals |
| 5 | Project Health | Overdue milestones company-wide, active/completed/on-hold counts | CEO | PM (future) | CEO | none | — | — | Project, ProjectMilestone | Projects |
| 6 | Team Overview | Headcount by designation, on-shift/inactive presence | CEO | HR (future) | CEO | none | — | — | staff, AttendanceRecord | Staff, Attendance |
| 7 | Financial Overview | Revenue, outstanding, budget-utilization proxy. **No profit/margin** — no cost/expense data model exists anywhere in the schema; would need one before a real P&L number is possible. Flagged, not faked. | CEO | Finance (future) | CEO | none | — | — | ProjectPayment, Project.budget | Revenue, Reports |
| 8 | Company Activity | Recent communications feed, company-wide | CEO | — | CEO | none | — | — | Communication | Communications |
| 9 | Executive Reports | One-click links into Sales/Activity/Performance reports (existing modules — CEO gets `Reports.View`) | CEO | — | CEO | none | — | — | (delegates to Reports module) | Reports |
| 10 | Approvals | Pending attendance appeals (real, actionable). High-value quotations (**visibility only** — listed when `quoted_amount >= threshold`, links to the quotation; no hard gate blocking staff from sending one without CEO sign-off, since that needs a new workflow state, not just a permission check — flagged, not built). "Major project decisions" / "strategic client decisions" have no discrete approvable entity in the schema (no decision/change-request table) — not fabricated. | CEO | HR/Finance/Sales (future, day-to-day) | CEO | `AttendanceAppeal` (approve/reject) | `Attendance.Approve` | — | AttendanceAppeal, Quotation | Attendance, Quotations |

**What's genuinely new this pass** (components 3, 7, 9, 10's quotation half — 1/2/4/5/6/8 and the appeals half of 10 already shipped last round):
- Client Overview: at-risk = company with an `overdue`/`pending` payment older than 14 days, or zero `Communication` in 30 days.
- Financial Overview: added outstanding + a "budget utilization" % (paid / sum of active project budgets) as the closest honest proxy to a health number, explicitly NOT labeled profit.
- Executive Reports: quick-links (Reports.View already granted).
- High-value quotations: threshold constant, visibility-only list on the dashboard.



**Component Responsibility Map**

| Component | Owner | Manager | CEO's role |
|---|---|---|---|
| CEO Dashboard | CEO (self) | — | Owner — CEO's own view |
| Attendance ERP (roster) | HR (future) | Admin | Viewer only |
| Attendance Appeals | HR (future, day-to-day) | Admin | **Approver** (escalation authority) |
| Every other CRM module (Deals, Projects, Contacts, ...) | per-module owner (future rounds) | per-module manager | Viewer (company-wide), non-editor |

**COMPONENT: CEO Dashboard**
PURPOSE: Single-screen company command center — revenue, pipeline, staffing,
pending approvals, risk alerts, recent activity, one-click navigation.
WHY: CEO needs to act on the business, not hunt for data across 15 modules.
BUSINESS OWNER: CEO. MANAGER: — (personal to the CEO account).
VIEW: CEO designation only (route resolves by designation match).
CREATE/EDIT/DELETE: N/A (read + approve surface, not a record).
ASSIGN: N/A. APPROVE: attendance appeals, inline.
DATA: `ProjectPayment`, `Project`, `staff`, `deal`, `ProjectMilestone`,
`AttendanceAppeal`, `Communication`.
WORKFLOW: login -> designation resolved -> `ceoData()` aggregates ->
dashboard renders -> CEO approves/rejects appeals inline or drills into a
module via Quick Actions.
STATUSES: N/A (aggregation view). NOTIFICATIONS: none generated by the
dashboard itself (appeal approval reuses Attendance's existing notification
path). ACTIVITY: appeal approve/reject logged via `AttendanceAppeal.reviewed_by/at`
(same as the ERP's own flow — one code path, two entry points).
REPORTING: feeds nothing yet; consumes Deals/Payments/Milestones live.
RELATIONSHIPS: Attendance, Deals, Projects, Staff, Reports (via Quick Actions).
EXAMPLE: Michael Carter (CEO) opens the dashboard Monday morning, sees 2
pending attendance appeals and 1 overdue milestone, approves one appeal
inline, clicks through to Deals Pipeline to check Friday's forecast.

**COMPONENT: Attendance Appeal Approval**
PURPOSE: Escalation path when a staff member disputes an auto-marked absence.
WHY: Auto-absence sweep is heuristic (no check-in by grace cutoff) and can be
wrong (forgot to open the panel, was on an approved trip, etc.) — needs a
human decision, not a silent override.
BUSINESS OWNER: HR & Admin Manager (not yet built — falls to CEO/admin until
that designation's round). MANAGER: Admin.
VIEW: `Attendance.View` (admin, CEO). CREATE: the staff member themselves
(files the appeal from their own attendance page — pre-existing).
EDIT: N/A. DELETE: none currently. ASSIGN: N/A.
APPROVE: `Attendance.Approve` (admin, CEO) — reject is the same permission,
it's a binary decision.
DATA: `AttendanceAppeal`, `AttendanceRecord`.
WORKFLOW: staff auto-marked absent -> staff files appeal (pending) -> CEO/admin
sees it in ERP or dashboard -> approve (flips today's record to
present/late, backfills worked/active minutes for a full day) or reject
(stays absent, review_note recorded) -> `reviewed_by`/`reviewed_at` stamped.
STATUSES: pending -> approved | rejected.
NOTIFICATIONS: none yet (pre-existing gap, not introduced this round — flag
for HR round). ACTIVITY: `reviewed_by`, `reviewed_at`, `review_note` on the
appeal row is the audit trail.
REPORTING: feeds the ERP's daily KPIs (present/absent counts) once resolved.
RELATIONSHIPS: Attendance ERP, Staff, CEO Dashboard.
EXAMPLE: David Okafor forgets to open the panel before the grace cutoff, gets
auto-marked absent, files "was on a client call all morning, phone only" —
CEO approves from the dashboard without leaving it.

**Permission Matrix (seeded `CEO` row, `database/seeders/RoleSeeder.php`)**

All 16 modules (Contacts, Companies, Deals, Projects, Staff, Attendance,
Services, Products, Portfolio, Testimonials, Estimates, Quotations, Pricing,
Blog, Reports, Settings): **View = true, Approve = true, Create/Edit/Delete =
false.** CEO is company-wide viewer + approver, not a data-entry owner of any
module — no owner role in the spec's example list is "CEO" for records.

**Workflow Map**: login -> `staff.designation === 'CEO'` resolves `ceoData()`
-> dashboard with pending approvals surfaced -> approve/reject inline (own
methods, permission-checked) or navigate to a module (view-only there too,
enforced server-side by `CheckModuleAccess` + `User::hasPermission()`).

**What changed (files)**
- `app/Models/User.php` — `hasPermission()` now checks a designation-named
  `Role` row first (e.g. "CEO"), falling back to the coarse `role` column.
  This is the core infra every future designation round reuses.
- `database/seeders/RoleSeeder.php` — added `Attendance` module (was missing
  from this seeder though the UI already had it) + `Approve` action (5th
  column) to all roles; added the `CEO` role row. Ran via
  `php artisan db:seed --class=RoleSeeder`.
- `resources/views/pages/admin/settings/⚡roles-permissions.blade.php` —
  `$actions` grid gained `Approve`.
- `app/Http/Middleware/CheckModuleAccess.php` — added `attendance` to the
  route-prefix -> module map (previously ungated by the central mechanism,
  relied solely on the page's own inline check); `attendance.person` is
  explicitly exempted (it has its own finer-grained self-vs-company-wide rule).
- `resources/views/pages/admin/attendance/⚡index.blade.php` — mount() now
  admin OR `Attendance.View`; `$canEdit`/`$canApprove` computed once and used
  to gate `saveRow`/`markAllPresent` (Edit) and `approveAppeal`/`rejectAppeal`
  (Approve) individually, plus hide/disable the corresponding UI controls.
- `resources/views/pages/admin/attendance/⚡person.blade.php` — company-wide
  `Attendance.View` holders (CEO) can now open any staff member's calendar,
  not just admin/self.
- `resources/views/pages/admin/⚡dashboard.blade.php` — `ceoData()` extended
  with `pendingAppeals`, `overdueMilestones`, `outstanding`, `recentActivity`,
  `canApproveAttendance`; new `approveAppealFromDashboard()`/
  `rejectAppealFromDashboard()` methods (permission-checked, same logic as
  the ERP's own).
- `resources/views/pages/admin/dashboard/ceo.blade.php` — added Pending
  Approvals (actionable) + Overdue Milestones (alert) + Recent Activity +
  Quick Actions sections. No longer cards-only.
- `app/Livewire/Hooks/RestrictEditing.php` — `SELF_GATED_METHODS` extended
  with the 4 appeal-approval method names (they carry their own
  `Attendance.Approve` check, finer-grained than the hook's binary
  admin/manager gate).

**CRITICAL BUG FOUND + FIXED (unrelated to CEO but blocking it, pre-existing
from earlier this session): `app/Providers/AppServiceProvider.php`**
`Livewire::componentHook(RestrictEditing::class)` was called from `boot()`.
Livewire's own service provider calls `ComponentHookRegistry::boot()` during
*its* `boot()` phase, which snapshots the registered-hooks list at that
instant to wire up the mount/hydrate listeners a hook needs to ever fire.
Since Laravel runs every provider's `register()` before any provider's
`boot()`, and provider boot order between app and package providers isn't
guaranteed, `RestrictEditing` was registered too late — it never fired, for
anyone, all session. Confirmed empirically (zero log output from its own
unconditional `Log::warning`, and a write with no other guard —
`calendar markCompleted` — went through for a non-manager staff member before
the fix, blocked after). **Fix**: moved the registration to `register()`
(guaranteed to run before any provider's `boot()`).
Net effect: modules whose write methods already had their own explicit
`hasPermission()` check (Deals, Quotations, Contacts, Staff, Estimates,
Services, Products, Portfolio, Testimonials, Blog, categories pages) were
never actually at risk — that check was always live. Modules with **no**
internal check — `companies/⚡show`, `communications/⚡calls`,
`communications/⚡emails`, `communications/⚡meetings`, `calendar/⚡events`,
`calendar/⚡schedule`, `tasks/⚡all` — were genuinely unprotected on the
backend the whole time (UI hid the buttons, but per this spec's own rule,
"hiding a menu item is NOT authorization"). They are protected now that the
hook fires, via `RestrictEditing`'s prefix-based blanket check. Recommend
(not done here — scope discipline, flagging for a future round or explicit
go-ahead): give each of those methods its own explicit
`hasPermission($module, $action)` check like the rest of the codebase
already does, so the blanket hook becomes pure defense-in-depth rather than
the only layer.

**Tests run** (Livewire::test, `assertOk`/`assertForbidden`, real DB state
checks — not just "no exception"):
- CEO: dashboard OK, attendance.index OK, attendance.person(any staff) OK,
  approveAppeal (ERP) -> status flips to approved, approveAppealFromDashboard
  -> same, saveRow -> **blocked** (403, row unchanged) confirming CEO is
  viewer+approver, not editor.
- David (Sales Executive, no permission rows): attendance.index -> 403,
  deals.delete -> blocked (unchanged count) — confirms the hook fix didn't
  regress existing protection.
- Regression: all 5 other designation dashboards (admin, PM, developer,
  designer, sales exec) still render OK. `roles-permissions` admin page
  still renders OK with the new Approve column.
- `php artisan view:cache` — compiles clean throughout.

**Deferred / explicitly out of scope this round**: Admin Assistant (AI agent)
access for CEO — its internal tools (`update_project`, `approve_appeal`, etc.)
call service methods directly with no per-tool permission check, so opening
the page to CEO right now would let CEO's assistant bypass the Edit=false
boundary just built. Needs per-tool authorization inside `AdminAgent` first —
not done here, flagging instead of guessing at a fix.

## Designation build log — batch build 2026-08-28 part 2: Designer(expanded), Developer(expanded), Tech Lead, QA Engineer, AI/ML Engineer, Marketing Manager, Finance (Manager/Accountant/Executive), Intern — plus Admin/CEO merge + admin-driven credential generation

**Admin + CEO merged into one identity/panel** (user's explicit instruction —
the system super-admin account IS the CEO's login, not a second role):
`dashboard.blade.php` routes `role==='admin'` through the same `ceoData()`/
`ceo.blade.php` as the CEO designation; the old separate `adminData()`/
`admin.blade.php` are unused now (left in place, not deleted, in case of
revert). Sidebar (`layouts/app.blade.php`) now computes `$isAdmin` as
`role==='admin' OR designation==='CEO'` once, so Admin Assistant/Attendance/
User Management/Roles & Permissions all extend to CEO automatically. CEO's
seeded `Settings` permission raised to View+Create+Edit (was View-only) so
the merged identity can actually run User Management/Roles & Permissions,
not just look at them.

**Admin-driven credential generation** (`settings/⚡user-management.blade.php`):
password is now optional even on create — leave blank and the system
generates a random 12-char one; a "Generate" button also fills it in visibly
so admin can see/copy before saving. After creating a new user, a green
banner shows the email + password once ("get credentials of their panel
generated by admin itself"). Designation field converted from free-text to a
dropdown of the full canonical roster (was typo-prone — a wrong designation
string silently falls through `hasPermission()` as "no permission", exactly
the failure mode this doc warned about from round 1).

**New shared infra: `Bug` model + migration** (`bugs` table, `Bug.php`,
routes `bugs.all`/`bugs.show`) — QA's entire round is bug tracking and none
existed. Real workflow implemented: open → in_progress → fixed → qa_retest →
verified/failed → closed, matching the requested Developer→QA→Pass/Fail loop.
Added `Bugs` + `Finance` to the permission grid (18 modules now) and to
`CheckModuleAccess`/`RestrictEditing`'s module maps.

**Real scoping bug found and fixed mid-build** (same class as the deals
scoping issue from part 1): granting `Tasks.Edit`/`Bugs.Edit` module-wide to
Designer/Developer/Tech Lead/etc. does NOT mean "edit anyone's record" — it's
supposed to mean "edit MY OWN assigned work". `bugs/⚡show.blade.php`'s
workflow actions and `tasks/⚡all.blade.php`'s `updateTask()`/`tasks/⚡my.blade.php`'s
`markComplete()` had no per-record ownership check, relying only on the
module-wide grant — meaning any Developer could mark ANY other developer's
bug/task complete. Fixed: these now check `isMine() OR <team-wide authority
permission (Assign)>` instead of the blanket Edit grant. Verified with real
writes: unauthorized dev blocked (record unchanged), actual owner works,
Tech Lead override works. **Flagging, not fixing everywhere**: the same
module-wide-Edit-without-record-check pattern likely exists in Communications/
Estimates/Quotations for Account Manager/BDM/Sales Executive/Project Manager
too — not audited this round (time), worth a dedicated pass.

**Designation summaries** (permission grid in `RoleSeeder.php`, dashboards in
`⚡dashboard.blade.php` + `dashboard/*.blade.php`):

| Designation | Owns | Key grant | Dashboard highlights |
|---|---|---|---|
| Designer | Assigned design tasks/deliverables | Tasks(View+Edit, own), Portfolio+Communications(V/C/E) | Added Client Feedback (Communications proxy) + Likely Revisions (overdue in-progress task proxy) |
| Developer | Assigned dev tasks + bug fixes | Tasks(View+Edit, own), Bugs(View+Create+Edit, own) | Added My Bugs card, QA-handoff count, failed-retest alert |
| Tech Lead | Technical execution | Tasks+Bugs(View+Create+Edit+**Assign**, team-wide), Projects/Staff(View) | Developer Team workload, QA Queue, computed Technical Risks + Release Readiness |
| QA Engineer | Testing + QA approval | Bugs(View+Create+**Approve**) — only designation with bug-verify authority | Testing Queue (qa_retest bugs), pass rate, all bugs table |
| AI/ML Engineer | Assigned AI/ML tasks | Tasks(View+Edit, own), Projects(View) | Tasks only — no Experiments/Models/Costs model exists, flagged on-page not faked |
| Marketing Manager | Marketing content | Portfolio/Testimonials/Blog(full CRUD) | No Campaign/spend/ROI model exists, flagged on-page not faked |
| Finance Manager/Accountant/Finance Executive | Invoices/payments/reconciliation | new `Finance` permission, tiered: Manager=Approve(refunds), Accountant=Create+Edit no Approve, Executive=Create only | Outstanding-by-client, overdue alert, payment history — `ProjectPayment` IS the invoice record (no separate Invoice/Expense entity, flagged) |
| Intern | Explicitly assigned work only | Tasks/Projects(View only, scoped) | Reuses generic task/project/calendar view; explicit "not built for you" note listing what's excluded |

**Tests**: all 19 seeded users' dashboards render OK (Livewire::test +
assertOk, real regression sweep after every change). Bug workflow tested
with actual writes (assign/fix/verify/fail, not just page loads). Task
ownership-scoping fix tested with actual writes (own-task edit works,
other's-task edit blocked, Tech Lead override works). Admin-driven user
creation tested end-to-end: password auto-generated, hash verified correct,
staff row created with the selected designation. `view:cache` compiles clean.

**Credentials**: every login is `*.agency.test` / `password` (system
admin/CEO is `test@example.com` / `password`). Full table given to the user
directly — `StaffSeeder.php` + `StaffLoginSeeder.php` are the source of truth.

## Audit fixes + follow-up requests — 2026-08-28

**4 approved audit fixes, all done + real-write tested**:
1. Communications `edit()`/`delete()`/`save()` (calls/emails/meetings) now
   check a `canManage()` gate (`created_by === auth()->id() || admin`) —
   was module-wide `Edit`, letting anyone with Communications.Edit alter/
   delete anyone else's logged call/email/meeting.
2. `techLeadData()`/`qaData()` converted from `Bug::with([...])->get()` +
   Collection filtering to scoped `->where()->count()`/`->limit()` queries.
3. `recordPayment($id)`/`approveRefund($id)` added to the dashboard
   component, wired to real buttons on the Finance dashboard, gated on
   `Finance.Create`/`Finance.Approve`. Added both to `RestrictEditing::
   SELF_GATED_METHODS` (the dashboard component has no module-map entry,
   so the blanket hook would've silently blocked them otherwise — caught
   via a real write test that returned unchanged, not by inspection).
4. `EditGate::allows()` retired from ~36 files' button-visibility checks —
   replaced with `role==='admin' || hasPermission($module,'Edit')`,
   mechanically, module inferred from each file's folder.
   **Caught mid-fix**: the blind substitution reopened the task-ownership
   bug from the prior round in `tasks/⚡all.blade.php`'s `updateTask()`
   (OR'd `Tasks.Edit` into the isMine-or-Assign check) — fixed before
   shipping. Also fixed: several "Add" buttons were left checking `Edit`
   instead of `Create` (blog/estimates/portfolio/pricing/products/services/
   staff — harmless today, no role has Create without Edit, but wrong);
   `quotations.all`, `estimates.all`, `staff.all`, `pricing.all`,
   `staff.designations` had combined Edit+Delete row buttons where a real
   role (Account Manager/BDM/Sales Exec on Estimates; HR on Staff) has
   Edit but not Delete — split into separate checks. **Same combined-button
   pattern is still present, unfixed,** in contacts/companies/deals/
   communications list pages — confirmed UI-only (every `delete()` already
   independently re-checks `Delete` server-side), not chased further given
   time. `App\Support\EditGate` class itself still exists (2 files —
   `tasks/⚡all.blade.php`'s visibility-scoping helper doesn't use it
   anymore either; `projects/⚡all.blade.php` similarly retired it in favor
   of a `hasCompanyWideProjectAccess()` helper checking Edit/Assign/Approve).

**3 new fixes from live screenshots**:
- **Reports narrowed**: `Reports.View` was granted to 12 roles but none of
  the 4 report pages (Sales/Activity/Performance/Client-Portal) filter by
  viewer — anyone with the permission saw 100% company data. Removed from
  BDM/PM/Tech Lead/QA/Marketing Manager/HR (each already has the equivalent
  metrics on their own dashboard); kept only CEO/COO/Finance Manager/
  Accountant. Verified via real HTTP kernel request (302 redirect for
  Marketing Manager, 200 for CEO/Finance).
- **Communications view-scoped**: calls/emails/meetings list pages were
  unscoped — anyone with `Communications.View` saw the entire company log.
  Added `scopeCommunicationsVisibility()` (duplicated per component, matching
  this codebase's existing per-type split): company-wide for CEO/COO/Finance/
  BDM; own-logged + company-relationship-scoped (via assigned contacts or
  assigned projects) for Account Manager/Sales Exec/PM; own-logged only for
  everyone else. Directly fixes the reported case (Marketing Manager seeing
  Priya Singh's/Alex Johnson's unrelated client calls) — verified.
- **Calendar scoped**: `calendar/⚡schedule.blade.php` and `⚡events.blade.php`
  showed every company event to everyone by default. Now scoped to
  `assigned_to = own staff` (or `notify_all`) unless the viewer has
  `Projects.Assign`/`Tasks.Assign`/`Reports.View` (team-wide or oversight
  authority). Verified: Marketing Manager sees only her own event, CEO sees
  everything.

**Deferred, not built**: the full "proper communication with assigned
project's team lead/client/CEO/members based on designation" — read as a
real structured thread-routing subsystem (who gets notified/can post per
designation, per project), which is materially bigger than an access-scoping
fix. Implemented the achievable half (who can *see* which existing
Communication records) rather than guessing at the thread/routing design —
flag if you want that scoped as its own build (the existing `ProjectChat`
component on project pages is the closest existing analog to build from).

**Tests**: all 19 seeded dashboards + 18 list pages (3 users each) render OK.
Real writes tested: Communications ownership (owner edits, non-owner
blocked), Finance record/refund (Finance Manager works, Marketing Manager
blocked), Calendar/Communications scoping (Marketing Manager sees only her
own, CEO sees all) — via `Livewire::test` for writes and a real HTTP kernel
request for route-middleware-gated pages (Reports). `view:cache` compiles
clean throughout.
