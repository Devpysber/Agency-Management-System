# Current Task

## Now (2026-08-28, latest — prod 500 #2: livewire compile-dir perms) — DONE, verified
Symptom: only /login loaded; correct creds -> 500 ("Service Unavailable") on
/dashboard + every authed page. CAUSE: `storage/framework/views/livewire/` was
`root:root 755` — created when I ran `php artisan tinker` render-sims AS ROOT
while debugging prod-500 #1. Livewire 4 compiles component classes there per
request via Filesystem::replace() -> tempnam(dir) -> www-data can't write ->
PHP "file created in the system's temporary directory" warning -> Laravel
promotes to ErrorException -> 500. /login barely uses Livewire so it stayed up.
FIX: `chown -R www-data:www-data storage bootstrap/cache` + `chmod -R ug+rwX` +
view:clear/view:cache as www-data. `deploy/update.sh` rewritten: ALL artisan
calls now `sudo -u www-data`, chown/chmod moved to the very end (commit db57a34,
deployed). LESSON: never run `php artisan` as root on the VPS without re-chowning
— or use `sudo -u www-data php artisan ...`.
Also: created prod admin `test@example.com` / `password` (user id 1, role admin)
— user asked for it; TELL THEM TO CHANGE IT.
Verified: 12 authed pages 200 via real HTTPS, 0 errors, livewire dir www-data.

CI (GitHub Actions "Tests"): was red at `npm ci` (ERESOLVE vite7/plugin-vue5).
Fixed -> `npm ci --legacy-peer-deps`, checkout/setup-node @v5 (commit e8e4ddd).
BUT suite still has 5 PRE-EXISTING failures (red since 3b31619, hidden because
CI never got past npm ci). Confirmed pre-existing via worktree @134cb52 —
ProjectPermissionGapTest fails identically without my changes. The 5:
- ProjectPermissionGapTest (x3): `Livewire::test('pages::admin.projects.show',
  ['id'=>$p->id])` -> "Invalid Livewire snapshot structure" — projects/show
  errors on mount for these role users (other page-component+id tests pass, so
  it's projects/show-specific, not a Livewire API issue).
- DesignationDashboardTest > "admin without staff record sees org wide dashboard"
  — stale assertSee; admin dashboard reworked many batches since.
- PermissionMatrixTest > "role granted staff create can save a designation".
NOT YET FIXED — separate from the incident. Offer to fix as follow-up.

## Prior (2026-08-28 — prod 500 #1: model-name casing) — DONE, live, verified
psyber.in was 500ing (`Class "App\Models\Company" not found` etc). Fixed via
`bootstrap/legacy_models.php` (composer files-autoload eager-loads the 4
lower-case legacy models) + PascalCase->lower-case ref cleanup + `class Contact`
-> `class contact`. VPS on commit 30e8470. See changelog batch 14 for the dead
ends (class_alias, spl shim — don't retry).
SSH now works from dev box: `ssh root@148.230.66.88` (key id_ed25519).

## Prior (2026-08-28 — GitHub push + VPS deploy) — DONE, live
https://psyber.in is LIVE on Hostinger VPS 148.230.66.88 (see changelog batch 13
for full deploy details, box layout, and the 4 gotchas hit).

OPEN FOLLOW-UPS:
1. DONE (commit dc28517, deployed): seeder model-case bug. 5 seeders had
   `use App\Models\Contact;` / `Company;` (capital) -> file is `contact.php` etc,
   breaks Linux case-sensitive autoload. Fixed to lowercase `use` line (body
   `Contact::` untouched — PHP resolves use-aliases + class names case-insensitively
   once the file loads). Files: Contact/Deal/Communication/Quotation/Estimate
   Seeder. App code (app/) was already clean. Prod re-seeded via
   `migrate:fresh --seed` — all 25 seeders green, full demo data loaded.
2. DONE: prod admin password changed off the public default (user set their own).
3. www over IPv6 => transient Cloudflare 403 (stale edge cache from when it was
   proxied); self-heals. If not: delete leftover AAAA/proxied www row in CF.
   (Low priority — apex + www-over-IPv4 both 200.)

Redeploy after a push: `bash /var/www/agency/deploy/update.sh` (as root on VPS).

STATE (historical):
- `origin` set to the Devpysber repo (old asadmukhtarr remote removed per user).
- Commits ready: `3b31619` (full snapshot, ~120 files) + `a9ca0f4` (deploy kit).
- `git push` BLOCKED here every time (auto-mode classifier, outward publish).
  User must run `git push -u origin main` (add `--force` if repo has auto-README).
- Deploy kit in repo: `DEPLOY.md`, `.env.production.example`, `deploy/first-setup.sh`,
  `deploy/update.sh`, `deploy/nginx-psyber.conf`, `deploy/agency-worker.conf`.
- Cannot deploy from here (no SSH to the VPS). User runs `deploy/first-setup.sh`
  on `srv1891796.hstgr.cloud` after cloning.

DNS SITUATION (important): psyber.in is on Cloudflare (acct Shah.antriksh@gmail.com,
Free, zone 924d0f8bc8c344ab67002b2739a2e45c). Hostinger is prompting to switch NS
to hermes/artemis.dns-parking.com — IGNORE that. Keep Cloudflare as DNS host.
Fix = in Cloudflare add `A psyber.in` + `A www` -> VPS IP (grey-cloud / DNS-only
first), and TURN OFF "Under Attack Mode" (currently ON = interstitial on every
visit). VPS public IP not yet known — user gets it from Hostinger VPS Overview.

NEXT STEP: user pushes, gets VPS IP, sets the two Cloudflare A records + disables
Under Attack Mode, SSHes in, `git clone` + `bash deploy/first-setup.sh`, then
certbot, then create first admin (tinker) or `db:seed --force`.

## Prior (2026-08-28 — site-wide dark theme + client-progress ask)
Two asks from a garbled paste. User picked "Both"; toggle = dark/light theme.

TASK B (theme toggle everywhere) — DONE for admin + staff portal.
- `layouts/partials/head.blade.php`: no-FOUC script sets `data-theme` +
  `data-bs-theme` on `<html>` from `localStorage['cp-theme']` (shared key);
  `window.toggleAppTheme()` + `syncThemeIcons()` (also on `livewire:navigated`).
  Inline `<style>` shell colours now CSS tokens: `:root` (light = original 1:1)
  + `:root[data-theme="dark"]` (matches client-portal/project-chat dark palette).
  `.theme-toggle` button style added.
- `layouts/app.blade.php`: toggle button in `.header-right` (before
  `<livewire:admin-header-menu />`); `#adminClock` inline hex -> tokens.
- `layouts/portal.blade.php`: toggle button in `.header-icons`.
- Login + password pages + client portal ALREADY had the toggle — untouched.
- Bootstrap 5.3.0 `data-bs-theme` handles native components (tables/modals/
  forms/dropdowns/badges). Residual: ~50 `⚡` page blades hardcode inline
  `background:#fff` / hex — chrome flips, those panels don't yet.
- Smoke-tested authed (test@example.com / password): /dashboard /projects/all
  /calendar/schedule /settings/general all 200, no Whoops.

TASK A (clients see project progress) — NO CODE WRITTEN. Investigated: already
fully built. `/client/projects` + `/client/projects/{id}` (route
`client.project-show`) show progress bar, milestone timeline, payments, live
submission countdown, project chat. Admin creates client logins in
`contacts/⚡show.blade.php` (createLogin ~line 64, revoke ~line 417) and in the
Add Company form (batch 13). Client scope = `contacts.user_id` -> company ->
`Project::where('company_id',...)`. BLOCKED: user's "Pasted text #47/#48"
(146 lines) never arrived — asked user to re-paste / describe the real gap.

NEXT STEP: wait for user to clarify Task A gap. If they confirm B is enough,
consider committing. Optional follow-up for B: sweep the worst offender page
blades (dashboard cards, tables) for hardcoded light hex if user wants full dark.

## Prior (2026-08-28 — calendar month-grid + live dashboard)
User asked: "common calendar to update days events" + "proper calendar view for
admin to show all types of user" + "advanced not simple, animations, live view,
all sidebar sections". Found the site-wide animation CSS (`.a-reveal`/`.a-stagger`/
count-up in `layouts/app.blade.php`) already fires UNCONDITIONALLY on any
`.dashboard .card`/`.stat-card`/`.table tbody tr` — no per-page opt-in needed,
so it was already covering every admin page; only true gap was live/real-time +
the calendar being a flat agenda list.
Rebuilt `calendar/⚡schedule.blade.php` from a flat agenda into a real month-grid
calendar: prev/next/today nav, 7x6 day-cell grid (CSS grid, `.cal-cell`), event
pills per day (click to edit), day-select side panel (view/add/edit/complete/
cancel for that day), reused same add/edit modal + validation from
`calendar/⚡events.blade.php`. Same `$seesAll` visibility rule as before
(admin/CEO always see everyone — "all types of user"; else own+notify_all only).
KPI counts (overdue/today/upcoming) now scoped too (were unscoped before — same
inconsistency class as the earlier Communications stats bug, fixed while
touching the file). `events.blade.php` (searchable/paginated table) untouched,
still cross-links to the new grid.
Dashboard (`⚡dashboard.blade.php`): added `wire:poll.20s="$refresh"` on the root
wrapper (verified no modal/form state to disrupt) + a "Live" pulse badge in the
header + `a-reveal` on the page header. New `@keyframes a-pulse` in
`layouts/app.blade.php`.
Verified via real HTTP-kernel requests (Livewire::test skips middleware, learned
earlier this session): `/calendar/schedule` and `/dashboard` 200 for both admin
and a non-admin staff user (priya.singh@agency.test), `/calendar/events` 200.
Probe scripts deleted after.
Deferred/not done: didn't touch every other sidebar module for "advanced" polish
(scope was ambiguous; animation infra already covers them structurally via the
unconditional CSS, so no further action taken there) — flag to user if they want
more.
Follow-up: user reported grid not responsive + stat cards 0/0/0. Verified via
probe: 0/0/0 is CORRECT (every seeded CalendarEvent is status=completed;
Overdue/Today/Upcoming scopes only count status=scheduled) — not a bug, no
change made there. Fixed responsiveness: grid wrapped in `.cal-wrap`
(overflow-x:auto, min-width safety net so it degrades to horizontal scroll
instead of collapsing), breakpoints added at 991px and 575px (shrinking cell
min-height/padding/font, hiding out-of-month cells + pills on phones, showing
just day numbers), month-nav header made `flex-wrap`.

## Earlier (2026-08-28 — 4 approved audit fixes + Reports/Communications/Calendar scoping)
All 4 approved audit fixes done + verified with real writes: (1) Communications
edit/delete now ownership-scoped (`created_by`) via new `canManage()` on all 3
comm components; (2) Tech Lead/QA dashboards' bug queries converted from
`->get()`+Collection-filter to scoped SQL; (3) Finance dashboard got real
`recordPayment()`/`approveRefund()` actions wired to buttons, permission-
checked, tested end-to-end; (4) EditGate's hardcoded 'Project Manager' string
retired from ~36 files' button-visibility checks, replaced with
`role==='admin' || hasPermission($module,'Edit')` — mechanical script-driven,
but caught+fixed a real regression it introduced along the way (see below).
CRITICAL: the mechanical EditGate retirement blindly substituted 'Edit' at
every call site regardless of context — reopened the exact task-ownership
bug from last round in `tasks/all.blade.php`'s `updateTask()` (Tasks.Edit
OR'd into the isMine-or-Assign check, bypassing ownership) — found by
inspection and fixed before it shipped. Also found/fixed: several "Add"
buttons across blog/estimates/portfolio/pricing/products/services/staff
were checking 'Edit' instead of 'Create' (harmless today since no role has
Create without Edit, but wrong); quotations/estimates/staff/pricing/staff-
designations had combined Edit+Delete row-button checks where a real role
(Account Manager/BDM/Sales Exec have Edit-no-Delete on Estimates; HR has
Edit-no-Delete on Staff) would see a Delete button that silently 403s server-
side — split those. Same combined-button pattern still exists UN-fixed in
contacts/companies/deals/communications list pages (same UI-only risk,
confirmed every delete() independently re-checks Delete server-side) —
flagged, not chased further given scope.
Then 3 new fixes from screenshots: Reports.View narrowed from 12 roles to
just CEO/COO/Finance Manager/Accountant (was shown blanket to BDM/PM/Tech
Lead/QA/Marketing Manager/HR — none of the 4 report pages filter by viewer,
so anyone with the permission saw 100% company data); Communications
list pages (calls/emails/meetings) now view-scoped — company-wide for
CEO/COO/Finance/BDM, own+company-relationship-scoped for AM/SalesExec/PM,
own-only for everyone else (fixes the exact complaint: Marketing Manager
was seeing Priya/Alex's unrelated client calls); Calendar (schedule.blade.php
+ events.blade.php) same scoping — own events only unless
Projects.Assign/Tasks.Assign/Reports.View. All real HTTP-level or real-write
tested (learned last round: Livewire::test doesn't run middleware).
Deferred: full "structured project communication (team lead/client/CEO/
members by designation)" — the ask implies a real thread/routing subsystem;
implemented the achievable access-scoping half instead, flagged the rest.

## Earlier (2026-08-28 — batch 2: 8 more designations + admin/CEO merge)
Designer(expanded), Developer(expanded), Tech Lead(new), QA Engineer(new),
AI/ML Engineer(new), Marketing Manager(new), Finance Manager/Accountant/
Finance Executive(new), Intern(new) — ALL DONE. Plus: admin+CEO merged into
one panel (user's instruction — sidebar/dashboard unified, CEO's Settings
permission raised to Create+Edit); User Management now auto-generates
credentials on staff creation (password optional, "Generate" button, shown
once in a banner) + designation is now a dropdown of the full canonical
roster (was free-text, typo-risk). New `Bug` model/migration/routes
(bugs.all/show) — QA's whole round needed it, nothing existed. Real scoping
bug found+fixed: module-wide Tasks.Edit/Bugs.Edit grants don't mean "edit
anyone's" — bugs/show + tasks/all's updateTask + tasks/my's markComplete had
no per-record ownership check, any Developer could complete/resolve ANYONE's
task/bug. Fixed with isMine()-or-Assign-permission checks, verified with
real writes. 19 seeded logins now exist total — see docs/rbac-spec.md
"batch build 2026-08-28 part 2" for full designation summary table + every
credential. FLAGGED NOT BUILT (no schema exists, said so on-page rather than
faking): AI Experiments/Models/Costs, Marketing Campaigns/spend/ROI/SEO/
Social, separate Invoice/Expense entity (ProjectPayment IS the invoice).
Known follow-up: same ownership-scoping gap likely exists in Communications/
Estimates/Quotations for AM/BDM/SalesExec/PM — not audited this round.
BLOCKED on user naming anything further — all requested designations done.

## Earlier (2026-08-28 — batch build of 7 designations)
User overrode the one-at-a-time rule and requested all 7 designations built in
one run: CEO(expanded 10-component panel), COO, HR & Admin Manager, Account
Manager, Business Development Manager, Sales Executive(expanded), Project
Manager(expanded). ALL DONE — see `docs/rbac-spec.md` "Designation build log
— batch build 2026-08-28" for full 20-point component docs, permission
matrices, files-changed, and tests run per designation.
Infra landed: `Assign` permission action added (6th column); `Tasks`/
`Communications` added to the permission grid (were real modules, never had
one); `deals.assigned_to` migration (deals had NO owner before — real gap,
"Sales Executive owns assigned opportunities" had no data to stand on);
`company::projects()`/`communications()`, `staff::deals()`, `deal::assignedTo()`
relations added. 4 new staff logins seeded (Grace Nolan=COO, Diane Foster=HR,
Natalie Reyes=Account Manager, Isabella Cruz=BDM, all `*.agency.test`/`password`).
Deal record-level scoping added (Sales Exec sees only assigned_to=self;
BDM/CEO/admin/pure-viewers see all) on deals.all/pipeline/lost/show.
CRITICAL FIX mid-build: `RestrictEditing` hook was silently blocking almost
every new permission just granted (checked only the old admin+PM EditGate,
not the new per-designation grid) — found via REAL HTTP kernel requests, not
Livewire::test (which doesn't run route middleware, a blind spot for all
prior "OK" assertions this session). Rewrote it to also check
`hasPermission($module,$action)` derived from component name + method name.
Also fixed 3 files with hardcoded `abort_unless(EditGate::allows())` mount
guards that ignored the new grid (staff/add, staff/edit, estimates/edit).
Re-verified end-to-end with actual writes (not just page-loads) for BDM/HR/AM.
FLAGGED NOT BUILT (genuinely new schema/features, called out in docs rather
than faked): Leave Management, Employee Documents, exit/offboarding tracking,
Project Files, Client Approvals (discrete entity), Sales Targets, Conversion-
analytics-as-distinct-module, deal-stage rename to the requested 7-stage
workflow (existing 6-stage enum kept, mapped conceptually), notification+
activity-log wiring for deal stage changes, Admin Assistant access for CEO
(its AI tools bypass permission checks internally — flagged last round too).
Known follow-up: ~15 files' button-visibility (not authorization) still
EditGate-only, not permission-grid-aware — cosmetic discoverability gap only.

## Earlier (2026-08-28, CEO-only round — superseded by the batch build above)
RBAC spec: [[decisions]] + `docs/rbac-spec.md` (READ THIS before any designation work).
CEO / Founder designation DONE (first of the one-at-a-time rounds) — full writeup +
files-changed list + tests logged in the doc's "Designation build log". Summary:
`User::hasPermission()` now checks designation-named `Role` row first (real infra all
future rounds reuse); seeded `CEO` role (View+Approve all 16 modules, no Create/Edit/
Delete); Attendance ERP + appeals now permission-driven (admin OR Attendance.View to
see roster, Approve to decide appeals, Edit to touch rows — CEO gets View+Approve only);
CEO dashboard rebuilt with pending-approvals/alerts/recent-activity/quick-actions (not
cards-only, per spec's Dashboard Rule).
CRITICAL BUG FOUND+FIXED: `RestrictEditing` Livewire hook (built earlier this session)
never actually fired all session — registered in `AppServiceProvider::boot()`, too late
for Livewire's `ComponentHookRegistry::boot()` snapshot. Moved to `register()`. Modules
with their OWN `hasPermission()` check (deals/quotations/contacts/staff/etc.) were never
actually at risk; `companies/show`, `communications/*`, `calendar/*`, `tasks/all` had ZERO
backend enforcement until this fix (UI-only). Now protected via the hook (defense in
depth, not per-method checks — flagged as a future hardening item, not done).
BLOCKED on user naming the NEXT designation. Don't start one unprompted.

## Earlier (superseded context below)
Client portal Phases 1-6 + logic fixes + Attendance ERP + presence/currency/tone pass DONE.
Latest: positive AI tone + model name hidden; currency by client country (@money);
chat locks for client when project closed; login-count throttled + relabelled "Visits";
staff presence (live "online now" + auto working-hours) in the Attendance ERP.
Then: staff aadhar/pan/employment_type/tenure fields (add/edit forms + masked read-only
"My Profile" card on admin dashboard); staff list links to attendance calendar;
`EventReminders` floating popup (in layouts/app.blade.php) for overdue/soon calendar
events — persists until "Mark done".
Latest (2026-08-28): staff shift_start/daily_hours; auto-absence (`evaluateAutoAbsence`,
15-min sweep) + `AbsencePopup` modal (NOT shown to admin/CEO) + CEO appeal flow
(`attendance_appeals`, approve in Attendance ERP). Reminder popup: collapses to bell after
10s idle, re-expands on nav/click, also on client layout. Completed project => progress 100.
Real presence: `POST /heartbeat` (CSRF-exempt) pinged every 45s only while tab visible;
`body.tab-hidden` freezes decorative animations app-wide. Keep-logged-in via always-remember
+ long SESSION_LIFETIME. "Intern" designation added. Attendance calendar restyled + animation
disarms after first paint.
Batch 3: admin (role=admin) never treated as staff even though test@example.com is linked
to staff "Alex Johnson" (presence/heartbeat/dashboard-card/sweep all skip it; admin
dashboard uses adminData()). `AdminHeaderMenu` Livewire component = working bell/envelope/
profile dropdowns in the admin top bar. Staff show page shows all new fields + attendance
snapshot. New sidebar "CLIENT MANAGEMENT" section.
Batch 4: 3-state presence (online/inactive/offline via `AttendanceRecord::presenceState()`) —
checked-in + tab-closed within shift = Inactive, never Absent. User Management can add
staff/client tied to a project (auto project_staff attach / contact link). Roles &
Permissions: Attendance module added, cards show V/C/E/D grid.
Batch 5: `AdminAgent` OpenRouter tool-calling assistant at `/assistant` (create projects/
staff/clients, assign teams, set progress, mark attendance — no deletes). Fixed
`AdminHeaderMenu` dropdowns (Alpine class on wrong element). Sidebar remembers open
sections + auto-opens active (`restoreSidebar()`); removed duplicate CLIENT MANAGEMENT
section. Project-edit team picker = checkbox card grid.
Batch 6: agent got read tools (attendance_status/project_details/pending_appeals/list_deals)
+ write tools (update_project/create_company/create_deal/approve_appeal) + full prompt; UI
= fixed-height flex chat (no page scroll). Navbar badges = unread only, clear on open
(`notifications_seen_at`/`messages_seen_at`). `active_minutes` heartbeat accrual — admin
panel shows ACTIVE hours (idle excluded). app.js `#app` mount guarded.
Batch 7: agent chat history (`agent_conversations`/`agent_messages`) — ChatGPT-style rail on
/assistant, load & continue past chats, `run(instruction, priorTurns)`. Sidebar fully
regrouped per user spec via `$navGroups` array (CLIENT MANAGEMENT / PROJECT MANAGEMENT /
SALES / CATALOG / MARKETING / TEAM / REPORTS / SETTINGS).
BLOCKER (mitigated Batch 9): OpenRouter low balance — agent now sizes requests to fit.
Batch 9: admin dashboard `adminData()` + `pages/admin/dashboard/admin.blade.php` reworked.
LOGIC: "Recent Activity" filtered to `occurred_at <= now` (future-dated comms were showing
as "1 day from now"); future comms moved to a separate "Coming Up" list. `emailsThisWeek`
/`newContactsThisWeek` capped at now, week = `startOfWeek()`. Added month-over-month revenue
delta, overdue-task count, live team-presence strip (on shift / inactive / appeals /
outstanding $). Dead `.alert-flash` blocks removed (global toast handles flashes).
AGENT TOKENS: `services.openrouter.max_tokens` config (env `OPENROUTER_MAX_TOKENS`, default
700, was hard 1200). `AdminAgent::chat()` takes `?int $maxTokens`, and on an OpenRouter
"can only afford N" / "more credits" failure it retries once at a fitted smaller cap.
Prior turns 12->6, tool results `Str::limit(…,800)`. New bulk tool `mark_all_attendance`
(one call for "mark everyone present", skips admin-linked staff) — was 8 costly loops.
Inactive-staff bell alert now only fires for `source=auto` rows AND `presenceState==inactive`
(not offline), skips admin-linked — stops false "X inactive ~2h" spam from manual/seed rows.
Batch 10: calendar events now notify participants with full details + link, routed by data.
Migration `2026_08_28_130000_add_participants_to_calendar_events` adds `project_id`,
`contact_id`, `meeting_url`, `notified_digest`, `communication_id` to `calendar_events`.
`CalendarEvent::booted()` saved-hook fires `App\Services\EventNotifier::sync()` when the
digest (title/time/status/location/url/links) changes, for meeting|call|deadline|reminder;
`self::$notifying` guard + `saveQuietly()` prevent recursion. EventNotifier: resolves staff
users (assignee + project team) and client users (contact + project company contacts),
builds a "Between: X and Y (client)" label, posts ONE `author_role=system` ProjectMessage
to the project thread (dedup by identical latest body) AND upserts a `Communication` row
(kept via `communication_id`; type meeting/call, status mirrors event) so the client sees
it on the portal dashboard feed and admin on the activity log. Reschedule edits same rows;
cancel prefixes "CANCELLED". Calendar events form (`⚡events.blade.php`) gains Project /
Client contact / Meeting link fields + rules + a "participants notified automatically" note.
Client dashboard: new "Upcoming Meetings & Calls" card (reads CalendarEvent scoped by
project/company contacts) with a real Join button + Details link. Admin bell
(`AdminHeaderMenu::alerts()`) event rows now show time · place · project and link to the
join URL when there is one.
Batch 11: in-app notifications. `user_alerts` + `UserAlert` + `App\Services\Notifier` +
`App\Support\AlertHooks` (registered in AppServiceProvider) -> admin/staff changes (project
progress/status, milestone done, payment, chat message, estimate/quotation status) raise
client-portal alerts with deep links. `App\Livewire\AlertBell` (client header) = bell +
badge + dropdown + toast-on-new. Admin: UserAlert merged into `AdminHeaderMenu::alerts()`
+ toast watcher. Client sidebar regrouped (Overview/Delivery/Documents/Finance/Account) +
"Updates" item w/ unread badge + new `client.updates` page (history, filter, mark read).
EventNotifier pushes meeting scheduled/updated/cancelled alerts to staff+client;
calendar form "All staff" option -> `calendar_events.notify_all` (fans to all active staff).
`EventReminders` popup now also shows clients their booked meetings (read-only, Open/Join).
Batch 12: dashboard chart init hardened (Chart.js retry + double-rAF + Chart.getChart
destroy guard for wire:ignore re-visits) — Revenue Trend / Deals by Stage were blank.
`PreventBackHistory` middleware (web group) = no-store on authed responses;
`RedirectIfAuthenticated` overrides `guest` alias (login while signed in -> own dashboard);
login page self-reloads from bfcache. `StaffLoginSeeder` gives every staff a login
(*.agency.test / password) + frees admin from staff "Alex Johnson". Client profile page
is now fully READ-ONLY except password change (saveProfile/saveCompany deleted).
STAFF LOGINS (all pw `password`): michael.carter@agency.test=CEO, ryan.kelly@agency.test
+ alex.johnson@agency.test=Project Manager, emily.chen@agency.test + priya.singh@agency.test
=Developer, sofia.martinez@agency.test + hannah.brooks@agency.test(inactive)=Designer,
david.okafor@agency.test=Sales Executive. Admin: test@example.com / password.
Batch 13: attendance auto-absence no longer sticks — an auto `absent` row that has a
check_in (or live activity) is repaired to present/late by both `recordStaffActivity` and
`evaluateAutoAbsence`, stale "no check-in" note cleared. ERP excludes inactive staff from
roster/mark-all/KPIs. Add Company form: GSTIN+PAN fields, real Tag toggle chips, and a
"Client Portal Access" card (optional -> creates client User+Contact tied to the company,
optional project link, temp password in success flash).
Batch 8: fixed `layouts/app.blade.php` ParseError (inline `@php...@endphp` ate an `@endif`
— use `@php(...)`). Global toast system (`#toast-stack`/`window.showToast`) replaces
`.alert-flash` banners app-wide + bridges session flash + Livewire `toast` events.
Inactive-staff (>1h, on shift) alert in the admin bell. General Settings tabs -> Alpine
(survive Livewire re-render), save() dispatches toast.
- Attendance ERP: `/attendance` admin module — daily editable employee attendance +
  client portal attendance, per-person monthly calendar. `attendance_records` table +
  `AttendanceRecord` model. Sidebar section ATTENDANCE.
- Admin pages now animated (entrance + scroll-reveal + stat count-up via layouts/app.blade.php).
- Client dashboard "Recent Activity" = real merged event stream (milestones/payments/
  estimate+quotation status/communications) with concrete data per row.
Phase 6 = live `wire:poll` refresh + "Live" pill on dashboard/list/detail pages, full
motion system (scroll-reveal, KPI count-up, staggered row/timeline/feed animations).
Also answered: admin manages client accounts via Contacts (portal login) + Companies +
Settings/User Management; deals via the Deals module.

## SECURITY NOTE
User pasted a live OpenRouter API key in chat on 2026-08-27. Stored only in `.env`
(gitignored, verified). `.env.example` has a blank placeholder. Key is exposed in the
conversation transcript — user should rotate it at openrouter.ai after testing.

## Plan (user-approved 2026-08-27)
- Isolated client theme (standalone CSS, only in `layouts/client.blade.php`; admin untouched).
- Features this pass: table UX (search/sort/filter/paginate), richer dashboard, detail
  timelines, PDF download (dompdf).
- Dark mode with persisted toggle.
- Rollout: dashboard first -> user approves -> roll same system across other 7 pages.

## Completed — Phase 1
- `resources/css/client-portal.css` — full isolated design system, light + dark tokens.
- `resources/js/client-portal.js` — `window.clientPortal`: theme toggle (localStorage
  `cp-theme`), mobile sidebar, toasts, Chart.js theme-aware defaults. Loaded in client
  layout <head> so it survives `wire:navigate`.
- `vite.config.js` — added `resources/css/client-portal.css` + `resources/js/client-portal.js`
  to input.
- `resources/views/layouts/client.blade.php` — REWRITTEN, now fully self-contained
  (own <!doctype>/<head>, no longer includes `layouts.partials.head`). New shell: sidebar
  with live nav counts, sticky header w/ theme toggle + initials avatar, footer, mobile scrim.
- `resources/views/pages/client/⚡dashboard.blade.php` — REWRITTEN. Data layer extended:
  KPI trend deltas (this vs last month), outstanding balance, upcoming milestones
  (ProjectMilestone), activity feed (Communication scoped by company). Charts kept
  (payment bar + status doughnut), restyled, theme-aware via `@script` + `cp:theme-changed`.
- `composer require barryvdh/laravel-dompdf` (^3.1) — installed, NOT wired yet (Phase 2).

## Verified
- `php artisan view:cache` compiles clean (no Blade errors).
- Authed smoke test: login as client@example.com -> `/client/dashboard` 200, real data
  (Willow & Bean Cafe, 1 active project, $45,456.00 paid), all 7 sections render,
  client-portal.css/js served by Vite (127.0.0.1:5173).

## Completed — Phase 2
- All 7 remaining client pages rewritten with the `cp-*` theme:
  - Lists (projects/estimates/quotations/payments): live search (debounced), status filter,
    sortable columns (`#[Url]` sort/dir), per-page selector, KPI summary row, shared
    pagination partial `resources/views/partials/cp-pagination.blade.php`.
  - project-show: stat strip, definition list, milestone TIMELINE (done/active/pending),
    payments table.
  - estimate-show / quotation-show: awaiting-response callout (keeps existing
    approve/reject/accept actions), status TIMELINE, invoice-style document, Download PDF.
- New profile page `pages::client.profile` (route `client.profile`): edit User name/email +
  Contact phone/mobile/job_title/address/city/state/zip/country, plus password change
  (current-password check, min 8, confirmed). Reached from the header user dropdown.
- Header user chip -> dropdown menu (My Profile / Toggle theme / Sign out).
  `clientPortal.toggleMenu/closeMenu` in client-portal.js; outside-click + Esc close.
- PDF: `App\Http\Controllers\Client\DocumentController` (company-scoped) + routes
  `client.estimate-pdf`, `client.quotation-pdf`; templates `resources/views/pdf/{estimate,quotation}.blade.php`
  (table-only CSS for dompdf). Toolbar buttons link with target=_blank (NOT wire:navigate).

## Verified (authed smoke test, client@example.com company_id=4)
- All 8 pages 200: dashboard, projects, estimates, quotations, payments, profile,
  projects/1, estimates/5, quotations/4.
- estimates/5/pdf + quotations/4/pdf -> 200, valid PDF (~880KB, DejaVu font embed).
- `php artisan view:cache` compiles clean. Exception scan clean.
- Cross-company id (quotations/1) correctly 404s.

## Completed — Phase 3
- AI account insights (Anthropic Messages API via official PHP SDK `anthropic-ai/sdk`):
  - `config/services.php` -> `anthropic.key` / `anthropic.model` (env `ANTHROPIC_API_KEY`,
    `ANTHROPIC_MODEL` default `claude-opus-5`). Keys stubbed blank in `.env` + `.env.example`.
  - `app/Services/AccountInsights.php` — `configured()`, `snapshot(company)` (deterministic
    metrics), `forCompany(company, force)` caches on sha1(snapshot) < 24h else `generate()`.
    `generate()` calls `$client->messages->create(...)`, parses JSON reply, persists.
    All failures wrapped in RuntimeException (never fatal); UI catches + shows alert.
  - Migration `create_account_insights_table` + model `AccountInsight` (company_id, headline,
    summary, sections json, metrics json, model, input_digest, generated_by).
  - New page `pages::client.insights` (route `client.insights`, sidebar item "AI Insights"):
    setup notice when unconfigured / "Generate" when none / full report + history list.
    Generate + Regenerate buttons (method-injected service, wire:loading spinner).
  - Dashboard: `cp-ai-card` after KPI row — shows latest cached insight + "Full report"
    link, or a prompt, or an unconfigured note. Does NOT auto-call the API.
  - CSS: `.cp-ai-*` block in client-portal.css.
- Profile page: new "Business & Billing Details" card (only when company linked) editing the
  COMPANY: legal_entity_name, gstin (regex-validated 15-char), pan (regex 10-char),
  tax_registration_number, company_email/phone/website/registration_no/industry,
  billing_address/city/state/zip/country. `saveCompany()` + `updatedGstin/Pan` uppercase hooks.
  Migration `add_billing_fields_to_companies_table`; `company` model fillable extended.
- Sidebar nav: "AI Insights" item added; count badges now carry `title=` tooltips
  ("N active projects", "N awaiting your response", "N pending payments") — kept as live counts.

## Verified (Phase 3)
- Migrations ran. `view:cache` clean. All 7 client pages + insights + profile 200 (authed).
- AI wiring tested via tinker with a fake key: `configured()` true, `snapshot()` returns real
  data, `forCompany(force)` throws caught RuntimeException ("API Connection Error" — outbound
  HTTPS blocked in this sandbox), no PHP fatal. Real key in a real env will complete.

## Completed — Phase 4
- Login page (`resources/views/auth/login.blade.php`) rebuilt: self-contained, split-screen
  (gradient brand panel + form), uses `client-portal.css` tokens, dark-mode aware, password
  reveal toggle. Removed stray `x\`` garbage from `layouts/main.blade.php`.
- AI provider switch in `AccountInsights`: `driver()` -> 'openrouter' if `OPENROUTER_API_KEY`
  set (preferred), else 'anthropic' if `ANTHROPIC_API_KEY`, else null. `callOpenRouter()` uses
  Laravel `Http::` to `openrouter.ai/api/v1/chat/completions` (OpenAI-compatible); `callAnthropic()`
  keeps the SDK path. `config/services.php` -> `openrouter.key`/`openrouter.model`
  (default `anthropic/claude-sonnet-5`). Installed `composer/ca-bundle`; the OpenRouter call
  passes its bundled cacert.pem as Guzzle `verify` (Windows PHP has no curl.cainfo -> was
  cURL error 60). VERIFIED end-to-end: real analysis generated via OpenRouter.
- Client-portal attendance: `client_portal_visits` table (unique user_id+visited_on, `hits`
  counter), `ClientPortalVisit` model with `touchFor()`, middleware `RecordClientVisit`
  (alias `client.visit`, added to the client route group; client-role GET only, skips
  X-Livewire XHR, never breaks the request). Admin report page
  `pages::admin.reports.client-attendance` (route `reports.client-attendance`, sidebar item
  under REPORTS): KPIs (active today/7d/month, logins in range) + per-client table.
  NOTE: `visited_on` is deliberately NOT cast to 'date' and is written via `toDateString()`
  — the date cast serialises back as 'Y-m-d H:i:s' which broke `whereBetween` on 'Y-m-d'
  bounds (this bit twice during build).
- Client sidebar: removed the count badges entirely (+ their per-request COUNT queries).

## Verified (Phase 4)
- Migrations ran. Views compile clean. Login/client pages/admin attendance all 200.
- Attendance increments correctly (4 GETs -> hits=4); admin page shows the row + KPIs.
- OpenRouter AI generates a real briefing (model `anthropic/claude-sonnet-5`, 4 sections).

## Completed — Phase 5
- Migration `add_project_collab_tables`: `projects.submission_due_at`, pivot `project_staff`,
  table `project_messages` (project_id, user_id, author_role, body).
- Models: `Project` +submission_due_at cast, `staff()` belongsToMany, `messages()` hasMany,
  `submission_countdown` / `is_overdue` accessors. `staff::projects()`. New `ProjectMessage`.
- Navbar clock: live ticking time+date in the client header (`#cp-clock`,
  `clientPortal.startClock()`) and admin header (`#adminClock`, inline script in
  `layouts/app.blade.php`).
- Submission deadline: admin sets `submission_due_at` on project add/edit (datetime-local).
  Client sees a live JS countdown strip (`cp-deadline`, Alpine `cpCountdown()` in
  client-portal.js) on project-show and on the dashboard (up to 3 nearest). Overdue = red.
- Project team + progress: admin edit screen has a multi-select "Assigned Employees"
  (`project.staff()->sync()`). Admin project SHOW has a new "Team & Chat" tab: assigned
  staff list, deadline, and a progress slider — `updateProgress()` allowed for admin with
  Projects:Edit OR any staff assigned to the project (`canUpdateProgress()`).
  `completeMilestone()` now uses the same check.
- Project chat: `App\Livewire\ProjectChat` + `resources/views/livewire/project-chat.blade.php`,
  embedded as `<livewire:project-chat :project="$project">` on client project-show and admin
  project-show (Team & Chat tab). One thread per project; participants = admin | client of
  the company | assigned staff (non-assigned staff read-only). `wire:poll.6s` for live.
  NOTE: the messages collection is exposed as `#[Computed] thread()` — NOT `messages()`,
  which collides with Livewire's validation-messages convention (array_merge TypeError).
- Animations: `cp-fade-up` entrance on KPIs/cards (staggered), `cpCountUp()` number roll-up,
  pulse dot on chat header, progress-bar transitions. Respects prefers-reduced-motion.
- Dashboard AI card now renders the insight sections inline + a Generate/Refresh button
  (`generateInsight()` on the dashboard component) — not just a link.

## Verified (Phase 5)
- Migration ran. Views compile. All client + admin project pages 200.
- ProjectChat `send()` works (Livewire::test roundtrip). Admin `updateProgress()` sets
  progress (assigned-staff path). Clock/deadline/chat markup present on both sides.

## Deferred (told user)
- AI "agent type" (multi-tool loop) — current single structured OpenRouter call stays.
- SMTP / email notifications on chat — user said later.
- Real-time websockets — using 6s polling.
- Separate staff-portal project screens — staff use the admin panel.

## Blockers
None.

## Next step
User reviews (http://127.0.0.1:8000). AI live via OpenRouter. Run `composer audit`
before shipping (17 advisories flagged across recent installs). User should rotate the
pasted OpenRouter key.

## Relevant files
- `resources/css/client-portal.css`, `resources/js/client-portal.js`
- `resources/views/layouts/client.blade.php`
- `resources/views/pages/client/⚡*.blade.php` (dashboard, projects, project-show, estimates,
  estimate-show, quotations, quotation-show, payments, profile, insights)
- `resources/views/partials/cp-pagination.blade.php`, `resources/views/pdf/*.blade.php`
- `app/Http/Controllers/Client/DocumentController.php`
- `app/Services/AccountInsights.php`, `app/Models/AccountInsight.php`
- `config/services.php` (anthropic block), `.env` / `.env.example` (ANTHROPIC_*)
- `routes/web.php` (client.* group: insights, profile, *-pdf routes)
- migrations `2026_08_27_120000_*` (company billing), `2026_08_27_120100_*` (account_insights)

## Repo state note
Working tree has large uncommitted work beyond commit `2395d65`. Nothing committed this session.
Servers: backend `php artisan serve` :8000, Vite :5173 (both on 127.0.0.1).
