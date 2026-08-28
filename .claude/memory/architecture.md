# Architecture

Update only when architecture actually changes. No source code here.

## Stack
- PHP 8.5, Laravel 12, Livewire 4 (`livewire/livewire ^4.3`), `laravel/ui ^4.6` scaffolding.
- DB: SQLite (`database/database.sqlite`, `DB_CONNECTION=sqlite`).
- Session + cache drivers: `database`. Mail: `log`. `APP_ENV=local`.
- Frontend: Vite 7, Tailwind 4, Bootstrap 5, Vue 3 available, SASS. Build: `npm run build` / dev `npm run dev`.
- Tests: PHPUnit 11 (`php artisan test`). 16 feature test files in `tests/Feature/`.
- Run app: `php artisan serve` (or `composer run dev` for server+queue+logs+vite).
  On Windows use `composer run dev:win` (server+queue+vite, no Pail — Pail needs
  the `pcntl` ext which Windows PHP lacks; the plain `dev` script's `--kill-others`
  then tears the whole stack down). Watch logs via `storage/logs/laravel.log`.

## Routing model
- All app pages are Livewire 4 namespaced page components: `Route::livewire('/path', 'pages::admin.<module>.<view>')`.
- View files: `resources/views/pages/**`, filenames prefixed with `⚡` (Livewire 4 single-file component convention).
- `/` returns `auth.login`. Self-service registration disabled (`Auth::routes(['register' => false])`).

## Layouts / theming
- Admin (`layouts/app.blade.php`) + staff portal (`layouts/portal.blade.php`) share
  `layouts/partials/head.blade.php` — CDN Bootstrap 5 + FontAwesome + Chart.js, and a large
  inline `<style>` block. Edit that partial only if you mean to change all shared panels.
- Client portal (`layouts/client.blade.php`) is SELF-CONTAINED — its own `<!doctype>`/`<head>`,
  does NOT include the shared partial. Styled by `resources/css/client-portal.css` (isolated
  `cp-*` design system, light + dark) + `resources/js/client-portal.js` (`window.clientPortal`).
  Both are Vite entries. Dark mode: `data-theme` on `<html>`, persisted in localStorage `cp-theme`.
- PDF: `barryvdh/laravel-dompdf` installed.
- Admin Assistant: `App\Services\AdminAgent` (OpenRouter tool-calling loop) + `/assistant`
  page with ChatGPT-style saved history (`agent_conversations` / `agent_messages`,
  `run($instruction, $priorTurns)`). Tools: create/update projects, create staff (with
  login) / client / company accounts, create deal, assign teams, set progress, mark
  attendance, approve appeal, + read tools (attendance_status, project_details,
  pending_appeals, list_*). Never deletes. Tool `parameters.properties` must be cast
  `(object)` or OpenRouter 400s on the empty `[]`.
- Admin sidebar: single `$navGroups` array in `layouts/app.blade.php` → CLIENT MANAGEMENT /
  PROJECT MANAGEMENT / SALES / CATALOG / MARKETING / TEAM / REPORTS / SETTINGS, each link
  permission-checked (`hasPermission`, admin auto-passes). `restoreSidebar()` JS persists
  open sections + auto-opens the active one.
- Currency: `App\Support\Money` + `@money()` Blade directive — client portal money is shown
  in the client's company currency (from `billing_country`/`company_country`). Payment rows
  keep their own stored `currency`.
- Staff presence: `RecordStaffPresence` middleware (alias `staff.presence`, on the admin
  route group) auto-tracks staff working hours into `attendance_records` (source=auto) from
  panel activity + a Cache "online" stamp. `AttendanceRecord::isOnline()/recordStaffActivity()`.
  Also runs a 15-min cache-locked no-show sweep: `evaluateAutoAbsence()` marks absent if no
  check-in by `staff.shift_start` + 1h30m. Disputed via `AbsencePopup` (Livewire, in
  app.blade; never shown to admin/CEO) -> `attendance_appeals` (`AttendanceAppeal`) ->
  approve in the Attendance ERP.
- Presence liveness: `POST /heartbeat` (CSRF-exempt, `auth`, skips role=admin) pinged by JS
  every 45s only while the tab is visible. `AttendanceRecord::presenceState()` -> online
  (heartbeat <90s) / inactive (checked in today + tab closed + within `shift_start`..
  `shift_start`+daily_hours+1h) / offline. Checked-in staff are never auto-absent even when
  inactive. `body.tab-hidden` class pauses decorative CSS animation while the tab isn't active.
- Sessions: always "remember" (`LoginController::attemptLogin`), SESSION_LIFETIME 43200,
  no expire-on-close — a login persists per panel until explicit logout.
- AI: `anthropic-ai/sdk` (official Anthropic PHP SDK). `App\Services\AccountInsights` calls
  `$client->messages->create(...)` with model from `config('services.anthropic.model')`
  (default `claude-opus-5`). Gated on `config('services.anthropic.key')` / env
  `ANTHROPIC_API_KEY`. Results stored in `account_insights` table, cached 24h per data digest.
  Surfaced on `/client/insights` and a dashboard card.

## Middleware (aliases in bootstrap/app.php)
- `module.access` = `App\Http\Middleware\CheckModuleAccess` — gates admin modules by role/permission.
- `client.scope` = `App\Http\Middleware\EnsureClientScope` — company-scopes client/staff portal access.

## Access tiers
- Admin panel: `['auth','module.access','client.scope']` group — full CRM modules.
- Staff portal: `/portal/dashboard`, middleware `['auth','client.scope']`.
- Client portal: `/client/*` (read-only, company-scoped, tied to `contacts.user_id`).

## Modules (admin)
contacts, companies, deals, projects (+payments/milestones), tasks, staff (+designations),
calendar (schedule/events), communications (emails/calls/meetings/activity-log), reports
(sales/activity/performance), services (+categories), products (+categories), portfolio,
testimonials, estimates (+items), quotations, pricing plans, blog (+categories),
settings (general/user-management/roles-permissions/payment-gateways).

## Models (app/Models/)
User, Role, staff, Designation, contact, company, deal, Task, Project, ProjectMilestone,
ProjectPayment, CalendarEvent, Communication, ActivityLog, Service, ServiceCategory,
Product, ProductCategory, PricingPlan, PortfolioItem, Testimonial, Estimate, EstimateItem,
Quotation, BlogPost, BlogCategory, PaymentGateway, CompanySetting.
Note: some model classes are lower-cased (`company`, `contact`, `deal`, `staff`) — legacy.

## Project collaboration
- `projects.submission_due_at` — admin-set client submission deadline; client sees a live countdown.
- `project_staff` pivot — employees assigned to a project (`Project::staff()` / `staff::projects()`).
  Assigned staff (or admin w/ Projects:Edit) can update project progress + milestones on the
  admin project "Team & Chat" tab.
- `project_messages` + `App\Livewire\ProjectChat` — one chat thread per project, shared by
  admin / assigned staff / client-of-company. `wire:poll.6s`. Messages exposed as a computed
  named `thread()` (NOT `messages()` — collides with Livewire validation-messages).
- `client_portal_visits` + `RecordClientVisit` middleware — client-portal attendance
  (`reports.client-attendance` admin report).
- Attendance ERP: `attendance_records` (`AttendanceRecord`) — daily staff attendance
  (`person_type` staff|client, `date` plain 'Y-m-d', status/check_in/out/worked_minutes).
  Admin module at `/attendance` (`attendance.index` board + `attendance.person/{type}/{id}`
  monthly calendar). Staff rows entered manually by admin; client rows derived from
  `client_portal_visits`. Sidebar section ATTENDANCE.

## Morph map (AppServiceProvider::boot)
`Task.related_type` stores short strings, resolved via `Relation::morphMap`:
`deal`, `contact`, `company`, `project`.

## Migrations
35 files in `database/migrations/`. Seeders per module in `database/seeders/` (run via `DatabaseSeeder`).
