# Changelog

Short, chronological, meaningful changes only. No code dumps.

## 2026-08-28 (batch 13)
- Deployment prep. Repo repointed `origin` -> github.com/Devpysber/Agency-Management-System
  (old asadmukhtarr remote dropped). Committed full project snapshot (`3b31619`) +
  deploy kit (`a9ca0f4`): `DEPLOY.md`, `.env.production.example`,
  `deploy/first-setup.sh` (Ubuntu: nginx + PHP 8.3-FPM + MySQL + Node 20 +
  Composer + Supervisor + cron), `deploy/update.sh` (redeploy), `deploy/nginx-psyber.conf`
  (has Cloudflare real-IP ranges), `deploy/agency-worker.conf`. Removed stray
  committed junk file `rest`. Target: Hostinger VPS srv1891796.hstgr.cloud, root
  SSH, MySQL, served at psyber.in root. DNS stays on Cloudflare (do NOT move NS to
  Hostinger); Cloudflare "Under Attack Mode" must be turned OFF. `git push` +
  actual server run are blocked here (classifier / no SSH) — user does those.
- `composer run dev` unusable on Windows: `php artisan pail` aborts (`pcntl`
  ext absent on Windows PHP) and the script's `--kill-others` then kills server +
  queue + vite too. Added `dev:win` script to `composer.json` — server + queue +
  vite only, `--kill-others-on-fail`. Verified: server 200, queue processing,
  vite ready. Plain `dev` left intact for Linux/Mac/WSL.
- Site-wide dark/light theme. Admin panel + staff portal now honour the shared
  `cp-theme` localStorage key (same convention as login page + client portal).
  `layouts/partials/head.blade.php`: no-FOUC inline script sets `data-theme` +
  `data-bs-theme` on `<html>` before paint; `window.toggleAppTheme()` +
  `syncThemeIcons()` (re-run on `livewire:navigated`). The inline `<style>` shell
  colours moved onto CSS tokens (`:root` = original palette 1:1, `:root[data-theme
  ="dark"]` = client-portal/project-chat dark palette). Bootstrap 5.3
  `data-bs-theme="dark"` themes all native components. Toggle button (`.theme-toggle`,
  moon/sun) added to `layouts/app.blade.php` + `layouts/portal.blade.php` headers;
  `#adminClock` inline hex swapped to tokens. Smoke-tested authed: /dashboard,
  /projects/all, /calendar/schedule, /settings/general, all 200, no exceptions.
  Residual: per-page `⚡` blades with hardcoded inline `background:#fff` etc. don't
  fully flip yet (~50 files) — chrome + BS components do.
- Attendance auto-absence was sticky: the 15-min sweep marked staff `absent`
  before their first page load, then `recordStaffActivity` set check_in but left
  status `absent` (+ stale "no check-in by 10:30" note). Now both
  `recordStaffActivity` and `evaluateAutoAbsence` REPAIR such a row — an auto row
  that is `absent` but has a check_in (or live activity) flips to present/late and
  the auto note is cleared. `evaluateAutoAbsence` also returns early whenever a
  check_in already exists.
- Attendance ERP: inactive staff (status != active, e.g. Hannah Brooks) excluded
  from the roster, the "mark all present" loop, and the head-count / not-marked KPIs.
- Add Company form: GSTIN + PAN fields (regex-validated, auto-uppercase); Tags are
  real toggle chips bound to `tags[]` (saved to `company_tags`); the dead "Quick
  Actions" card replaced by "Client Portal Access" — optional switch to create a
  client login (User role=client + Contact linked to the new company, optional
  project re-assign, temp password shown in the success flash, welcome UserAlert).

## 2026-08-28 (batch 12)
- Admin dashboard charts (Revenue Trend / Deals by Stage) rendered blank on
  wire:navigate — inline init now: waits for Chart.js (retry loop), paints on a
  double rAF, and `destroyChart()` also kills any chart Chart.js still owns on the
  canvas (`Chart.getChart`) so wire:ignore re-visits don't throw "canvas in use".
  (Tried @script wrapper first — it broke Livewire single-root detection; reverted.)
- Back/forward-cache logout hole. New `App\Http\Middleware\PreventBackHistory`
  (appended to web group) sends `Cache-Control: no-store` on authenticated responses;
  new `App\Http\Middleware\RedirectIfAuthenticated` (overrides `guest` alias) sends a
  signed-in user hitting /login etc. to their own dashboard (client vs admin). Login
  page reloads itself on `pageshow` when restored from bfcache.
- `StaffLoginSeeder` — every staff row now has its own User (role staff), *.agency.test
  passwords reset to `password`; frees `test@example.com` (admin) from staff "Alex
  Johnson" (was sharing user id 1). Added to DatabaseSeeder after StaffSeeder.
- Client profile (`pages::client.profile`) is now READ-ONLY for Account Details and
  Business & Billing (definition-list, "managed by your account manager"); `saveProfile`
  / `saveCompany` removed. Password change is the only thing the client may still do.

## 2026-08-28 (batch 11)
- In-app notification system. `user_alerts` table + `UserAlert` model + `App\Services\Notifier`
  (`push($users,$title,$opts)`, de-dupes unread same title+url; `projectAudience()`,
  `companyClients()`). `App\Support\AlertHooks::register()` (called from AppServiceProvider)
  turns admin/staff changes into client-portal alerts: project progress, project status,
  milestone completed, payment created/paid, new project-chat message (both directions),
  estimate/quotation status change — each with a deep link.
- `App\Livewire\AlertBell` (variant client|admin) — bell + unread badge + dropdown + a 6s
  DOM-marker watcher that fires a toast for anything new. Added to the client header.
  Admin/staff: `UserAlert` rows merged into `AdminHeaderMenu::alerts()` + same toast watcher
  in its view (poll 90s->30s).
- Client portal: grouped professional sidebar (Overview / Delivery / Documents / Finance /
  Account) with an unread badge on the new "Updates" item. New `client.updates` page
  (`pages::client.updates`, route `client.updates`) — full alert history, All/Unread filter,
  mark read / mark all read.
- EventNotifier now also `Notifier::push`es meeting/call scheduled|updated|cancelled to
  staff + client (popup + bell + reminder). Calendar event form: "All staff (notify
  everyone)" option -> `calendar_events.notify_all`; EventNotifier fans to every active
  staff user when set; digest includes it.
- `EventReminders` floating popup now also serves clients — shows meetings/calls booked
  with them (read-only: Open + Join buttons, no "Mark done"). Staff view widened to
  notify_all + project-team events.

## 2026-08-28 (batch 10)
- Calendar events fan out notifications. New service `App\Services\EventNotifier` +
  `CalendarEvent::booted()` saved-hook. Migration adds `project_id` / `contact_id` /
  `meeting_url` / `notified_digest` / `communication_id` to `calendar_events`.
- On create / reschedule / cancel of a meeting|call|deadline|reminder the notifier:
  resolves staff users (assignee + project team) and client users (linked contact +
  project company contacts), works out a "Between X and Y (client)" label, posts one
  `author_role=system` message to the project chat thread (deduped), and upserts a
  `Communication` row it then keeps in sync via `communication_id`. That row is what the
  client sees on their portal dashboard feed and the admin sees in the activity log.
  Recursion blocked with a static `$notifying` flag + `saveQuietly()`.
- Calendar events form: Project / Client contact / Meeting link fields added.
- Client dashboard: "Upcoming Meetings & Calls" card (Join button + Details link),
  reads `CalendarEvent` scoped by the client's projects / company contacts.
- Admin bell event rows: show time · place · project, link to the join URL when present.

## 2026-08-28 (batch 9)
- Admin dashboard `adminData()` + `pages/admin/dashboard/admin.blade.php` reworked.
  LOGIC: "Recent Activity" now `occurred_at <= now` only (future comms were rendering as
  "1 day from now"); future comms shown in a separate "Coming Up" list. Week windows
  (`emailsThisWeek`, `newContactsThisWeek`) capped at now, week = `startOfWeek()`.
  Added month-over-month revenue delta, overdue-task count, and a live team-presence strip
  (on shift / inactive / appeals / outstanding $). Dead `.alert-flash` markup removed.
- Agent token/credit fix: `services.openrouter.max_tokens` config (env `OPENROUTER_MAX_TOKENS`,
  default 700, was hard-coded 1200). `AdminAgent::chat()` takes `?int $maxTokens` and, on an
  OpenRouter "can only afford N" / "more credits" failure, retries once at a fitted cap.
  Prior turns trimmed 12->6, tool results `Str::limit(…,800)`.
- New agent tool `mark_all_attendance` — one call for "mark everyone present" (was 8 costly
  loop turns); skips admin-linked staff.
- Inactive-staff bell alert: only `source=auto` rows AND `presenceState==inactive` (not
  offline), skips admin-linked — kills false "X inactive ~2h" notifications.

## 2026-08-28 (batch 8)
- FIX: `layouts/app.blade.php` 500 ParseError — an inline `@php $x = ...; @endphp` on one
  line swallowed the following `@endif`. Use `@php(...)` form for one-liners.
- Global toast popups: `#toast-stack` + `window.showToast()` in `layouts/app.blade.php`.
  `.alert-flash { display:none !important }` hides all the old inline banners; session
  flash (success/ok/error/status/warning) is seeded as `.toast-seed` divs and flushed to
  toasts on load / `livewire:navigated`; Livewire `toast`/`cp-toast`/`notify` events also
  route here. Livewire in-place actions should `$this->dispatch('toast', message:, type:)`.
- Inactive-staff alert: `AdminHeaderMenu::alerts()` (admin) flags any staff whose today
  record has check_in + check_out <= 1h ago and who isn't back online — "X inactive for
  ~Nh — check why", links to their attendance calendar.
- General Settings: Bootstrap `data-bs-toggle="tab"` tabs -> Alpine (`x-data="{ tab }"`,
  `x-show`/`:class`) so the active tab survives Livewire re-renders after Save. `[x-cloak]`
  rule added. save() now dispatches a toast instead of session flash.

## 2026-08-28 (batch 7)
- Agent chat history (ChatGPT-style): `agent_conversations` + `agent_messages` tables,
  `AgentConversation`/`AgentMessage` models. `/assistant` page has a left rail listing the
  admin's past chats (title = first 42 chars, "New chat", click to load & continue, trash to
  delete). `AdminAgent::run($instruction, $priorTurns)` now takes the last 12 user/assistant
  turns for context. Verified: conversation + messages persist and continue.
  NOTE: OpenRouter account hit "would exceed your available credits" — LLM calls fail until
  the balance is topped up (or ANTHROPIC_API_KEY is set); the history/persistence code is fine.
- Sidebar fully regrouped to the user's spec: Dashboard, Admin Assistant, then sections
  CLIENT MANAGEMENT (Contacts/Companies/Deals/Communications), PROJECT MANAGEMENT
  (Projects/Tasks/Calendar), SALES (Estimates/Quotations), CATALOG (Services/Products/
  Pricing), MARKETING (Portfolio/Testimonials/Blog), TEAM (Staff/Attendance), REPORTS,
  SETTINGS, Logout. Driven by a `$navGroups` array in `layouts/app.blade.php` with per-link
  permission checks (admin auto-passes). The old commented-out sections were replaced.

## 2026-08-28 (batch 6)
- Agent expanded: read tools attendance_status / project_details / pending_appeals /
  list_deals; write tools update_project / create_company / create_deal / approve_appeal.
  Fuller system prompt listing every capability + rules (no delete). UI rebuilt as a
  fixed-height flex chat (`height: calc(100vh - 128px)`, internal scroll — the page itself
  never scrolls), quick-prompt chips, tab-hidden freezes motion.
- Navbar badges are now UNREAD counts only: `users.notifications_seen_at` /
  `messages_seen_at`; opening a dropdown calls `markAlertsSeen`/`markMessagesSeen` (badge
  clears). `wire:poll.90s` keeps them fresh. Own messages don't count.
- Active hours: `attendance_records.active_minutes` accrued by `AttendanceRecord::accrueActive()`
  on every heartbeat / page load — only continuous gaps <=90s count (idle time excluded).
  ERP "Avg Hours", staff-row hours, staff show + dashboard cards now display ACTIVE hours,
  not the check_in→check_out span.
- Sidebar: Services / Products / Portfolio / Pricing / Blog sections wrapped in
  `{{-- HIDDEN (kept for future use) --}}` Blade comments (routes still work, just no link).
- `resources/js/app.js`: guard `app.mount('#app')` with `if (document.getElementById('app'))`
  — kills the "mount target #app returned null" Vue warning on admin/client panels.

## 2026-08-28 (batch 5)
- Admin Assistant: `App\Services\AdminAgent` — OpenRouter tool-calling agent. Tools:
  list_projects/staff/companies, create_project, create_staff (with login), create_client
  (portal account + contact), assign_staff_to_project, set_project_progress, mark_attendance.
  Max 6-step loop, never deletes. Console at `/assistant` (`pages::admin.agent`, admin only,
  sidebar "Admin Assistant"). NOTE: tool `parameters.properties` MUST be cast `(object)` —
  an empty PHP `[]` serialises as a JSON array and OpenRouter rejects it. Verified: created
  a project + set progress in one instruction.
- Header menu (`AdminHeaderMenu`) fixed: Alpine `:class="{ open }"` on `.ahm-wrap` (was on
  the wrong element + duplicate bindings), dropped `wire:poll`, links use `wire:navigate`,
  added Roles & Permissions link. Bell/envelope/profile all open + click through now.
- Sidebar: sections remember open/closed in localStorage + auto-open the one containing the
  current page (`restoreSidebar()` on load + `livewire:navigated`) — no more "refresh /
  collapse" on navigation. Removed the all-duplicate "CLIENT MANAGEMENT" section.
- Project edit: "Assigned Employees" is now a checkbox card grid (was an unreadable raw
  multi-select), full-width, live count.

## 2026-08-28 (batch 4)
- Presence is 3-state now: `AttendanceRecord::presenceState()` -> online (heartbeat <90s) /
  inactive (checked in today, tab closed, still inside shift window) / offline. A staff who
  checked in on time and then closes the tab is INACTIVE, never absent (auto-absence already
  skips any record with a check_in). ERP + staff show + admin "My Profile" card show the
  coloured state (green/amber/grey) + "seen Xm ago"; ERP header shows "N online · M inactive".
- User Management: role option "client" added; when adding a NEW staff or client user an
  optional "Assign to Project" picker appears. staff -> creates a `staff` record (+designation,
  shift/hours defaults) and attaches to `project_staff`; client -> links the user to the
  project company's contacts (`contact.user_id`). Verified via Livewire::test.
- Roles & Permissions: "Attendance" module added to the matrix; role cards now show a
  per-module V/C/E/D grid instead of a plain chip. Attendance sidebar section gated by
  `hasPermission('Attendance','View')` (admin auto-passes).

## 2026-08-28 (batch 3)
- Admin is NOT tracked staff: `test@example.com` was linked to staff "Alex Johnson" — that
  link is now ignored for `role=admin` in `RecordStaffPresence`, the heartbeat route, the
  admin dashboard "My Profile" card, and the absence sweep. Admin dashboard also now
  correctly falls to `adminData()` instead of Alex's PM view.
- Admin top-bar: `AdminHeaderMenu` Livewire component replaces the dead bell/envelope/avatar
  icons — bell dropdown (upcoming/overdue events + pending appeals), envelope dropdown
  (recent project chat messages, scoped to assigned projects for staff), profile dropdown
  (Dashboard / My profile / My attendance / User management / Logout). `wire:poll.60s`.
- Staff show page filled out: employment type, shift start, daily hours, masked Aadhaar/PAN,
  internship tenure (interns), formatted joining date, + an "Attendance — this month"
  snapshot card with online status and a link to the full calendar.
- Sidebar: new "CLIENT MANAGEMENT" section (Client Companies / Contacts / New Contact /
  Portal Activity / Estimates / Quotations / Projects).

## 2026-08-28 (later)
- Real tab-active presence: `POST /heartbeat` route (CSRF-exempt, `auth`). JS in
  layouts/app.blade.php pings every 45s ONLY while `document.visibilityState === 'visible'`,
  stops on tab hide / `pagehide`. `AttendanceRecord::isOnline()` window = 90s, middleware
  TTL 120s. A `body.tab-hidden` class (admin + client-portal.js) freezes all decorative
  animations when the tab is not active — "no animation unless the tab is live".
- AbsencePopup: never shown to `role=admin` or a `CEO` designation (they review appeals).
- Keep-logged-in: `LoginController::attemptLogin` always passes remember=true (per-panel,
  separate user records); `.env` SESSION_LIFETIME=43200, SESSION_EXPIRE_ON_CLOSE=false.
- Attendance calendar (`attendance.person`) restyled: gradient status cells, rounded,
  hover-lift, "today" ring; entrance animation plays once per load then disarms so month
  navigation / polling don't re-animate.
- "Intern" added to Designations. (The `attendance_appeals` 500 the user hit was a
  pre-migration request; migration `2026_08_27_170000` had since run — pages 200.)

## 2026-08-28
- Auto-absence + appeals: staff get `shift_start` + `daily_hours` (set at registration).
  `AttendanceRecord::evaluateAutoAbsence()` marks a staff member absent (source=auto) if no
  check-in / panel activity by shift_start + 1h30m; swept every 15 min via a cache lock in
  `RecordStaffPresence` and on every Attendance ERP load. `AbsencePopup` Livewire component
  (in app.blade) shows the affected staff a modal — they appeal to the CEO
  (`attendance_appeals` table + `AttendanceAppeal` model). ERP has an "Absence Appeals"
  section: approve -> record flips to present + worked_minutes filled (check_in/out or
  daily_hours), popup clears; reject -> staff can re-appeal.
- Event reminders popup reworked: full popup on load / every SPA navigation; auto-collapses
  to a pulsing bell button after 10s of no interaction (stays open while hovered/clicked);
  click bell to expand. Added to client layout too. Spring-pop + slide-in animations.
- Project completed => progress forced to 100 (admin edit + a self-heal on the show page +
  reconcile of existing rows). Fixes "Completed · 0%".
## 2026-08-27
- Staff identity fields: migration adds `aadhar`, `pan`, `employment_type`
  (full_time|intern|contract), `tenure_start`, `tenure_end` to `staff`. Add/Edit forms
  updated (PAN regex + uppercase, Aadhaar regex; internship dates show only when
  employment_type = intern). `staff` model: casts + `masked_aadhar`/`masked_pan` accessors
  + `isIntern()`. Admin dashboard: a read-only "My Profile" card for staff-linked users
  (masked IDs, tenure, this-month attendance summary, "online" badge, link to attendance
  calendar) — staff cannot edit these on their side. Staff list: name + row action link
  to `/attendance/staff/{id}`, Intern badge.
- Event reminders: `App\Livewire\EventReminders` embedded in `layouts/app.blade.php` —
  floating bottom-right stack (animated slide-in, ringing bell) of the user's scheduled
  calendar events that are overdue or due within 24h; stays until "Mark done". Polls 60s.
- AI insights: system prompt rewritten to a positive/reassuring client-success tone
  (leads with wins, no risk/warning/overdue language, outstanding balance mentioned
  neutrally). Model name no longer shown on dashboard / insights page.
- Currency by client country: `App\Support\Money` + `@money()` Blade directive. Resolves
  the client's company `billing_country ?: company_country` to a currency symbol
  (₹/£/€/$/AED/…), applied to all derived money in the client portal + estimate/quotation
  PDFs. Payment rows keep their stored transaction currency.
- Project chat: once a project is completed/cancelled, the client is read-only ("project
  closed" notice); admin + assigned staff can still post. Recomputed each render so it
  locks within one poll.
- Milestone meta fixed ("Completed Completed" -> "Completed · on <date>").
- Client portal attendance: `hits` increment throttled to once per 2 minutes (polling was
  inflating it into the hundreds); "Logins" relabelled "Visits" across the reports.
- Staff presence (Discord-style): `RecordStaffPresence` middleware on the admin group
  keeps today's attendance record's check-in/out/worked-minutes auto-updated from real
  panel activity (source=auto, never overrides a manual status) + a 5-min "online" cache
  stamp. Attendance ERP shows a live green dot, "N online now", and auto-tracked hours;
  `wire:poll.30s`.
- Attendance ERP (admin): `attendance_records` table + `AttendanceRecord` model
  (person_type staff|client, date as plain 'Y-m-d', status present/late/remote/half_day/
  leave/absent/holiday, check_in/out, worked_minutes auto-computed, source, note).
  New sidebar section ATTENDANCE. `pages::admin.attendance.index` — date picker, KPI row,
  Employees tab (inline editable status/check-in/out/note per row + "Mark all present"),
  Clients tab (from `client_portal_visits`). `pages::admin.attendance.person` (`/attendance/
  {type}/{id}`) — monthly calendar grid + present/absent/late/rate summary for one
  staff or client. Old `reports.client-attendance` kept, linked under ATTENDANCE.
- Admin motion: entrance animations (`a-fade-up` on stat-cards/cards/table rows, staggered),
  `.a-reveal` scroll-reveal, `.stat-number` count-up — injected via `layouts/app.blade.php`,
  re-run on `livewire:navigated` + `morphed`. Respects prefers-reduced-motion.
- Client dashboard "Recent Activity" rebuilt as a real merged event stream: milestone
  completions, payments, estimate/quotation status changes, communications — each row shows
  the concrete data (amounts, names, dates), sorted newest-first, top 8.
- Logic fixes: (1) `Project::syncStatusToProgress()` — inline progress control +
  milestone completion now move status forward (planning->in_progress->completed) so the
  portal never shows "100% · Planning"; forward-only, never downgrades/un-completes.
  Reconciled existing rows. (2) `AccountInsights::isStale()`/`currentDigest()` — dashboard
  + insights page show an "Account data changed / Outdated" badge + prominent Update button
  when the stored insight's `input_digest` != current snapshot digest. (3) Dashboard
  "Active Projects" KPI: dropped the misleading %-delta pill (was comparing project-creation
  counts). (4) Client project-show milestones show "Completed <date>" instead of "Due <date>"
  when done.
- Client portal Phase 6 (polish): `wire:poll` live refresh on dashboard (30s) + all list
  pages (45s) + estimate/quotation detail (60s), with a "Live" pill in each page head.
  Motion system in client-portal.js: `initMotion()` scroll-reveal (`.cp-reveal`),
  `initCounters()` KPI count-up (load/navigate only, not on poll morphs). CSS: staggered
  fade-up on table rows / timeline / feed items, `.cp-live` pulse indicator. All gated by
  prefers-reduced-motion.
- Client portal Phase 5: live navbar clock (client + admin). Project submission deadline
  (`projects.submission_due_at`) set by admin, shown to client as a live JS countdown on
  project-show + dashboard. Project team via `project_staff` pivot (assigned on admin edit);
  assigned staff (or admin) can move the progress slider + complete milestones on the admin
  project "Team & Chat" tab. Project chat (`ProjectMessage` + `App\Livewire\ProjectChat`,
  6s poll) shared by admin / assigned staff / client, embedded on client + admin project
  detail. Entrance animations + count-up on the client portal. Dashboard AI card now shows
  the insight sections inline with a Generate/Refresh button.
- Client portal Phase 4: rebuilt login page (split-screen, self-contained, dark-aware).
  AI insights gained an OpenRouter provider path (`Http::` to openrouter.ai, preferred over
  the Anthropic SDK when `OPENROUTER_API_KEY` set; default model `anthropic/claude-sonnet-5`);
  added `composer/ca-bundle` for reliable SSL on Windows. New client-portal attendance
  tracking: `client_portal_visits` table + `RecordClientVisit` middleware on the client
  route group + admin report `reports.client-attendance` (KPIs + per-client table).
  Removed the client sidebar count badges. Fixed a date-cast storage bug (`visited_on`
  now stored as plain 'Y-m-d').
- Client portal Phase 3: AI account insights via Anthropic Messages API (official PHP SDK
  `anthropic-ai/sdk`). `AccountInsights` service + `AccountInsight` model/table, new
  `/client/insights` page (report + history), dashboard summary card. Feature inert until
  `ANTHROPIC_API_KEY` set. Profile page gained a "Business & Billing Details" card editing
  the company (GSTIN/PAN regex-validated, tax reg no, billing address block, company contact);
  migration added those columns to `companies`. Sidebar nav count badges kept but given
  tooltips; "AI Insights" nav item added.
- Client portal redesign Phase 2: rewrote all 7 remaining client pages on the cp-* theme.
  Lists got live search + status filter + sortable columns + per-page + KPI rows + shared
  pagination partial. Detail pages got stat strips, definition lists, status/milestone
  timelines, invoice-style estimate doc. New `client.profile` page (edit account/contact +
  change password), reached from a new header user dropdown. PDF download for
  estimate/quotation via `Client\DocumentController` + `resources/views/pdf/*` (dompdf).
  All 8 pages + 2 PDF routes smoke-tested 200; views compile clean.
- Client portal redesign Phase 1: new isolated theme (`resources/css/client-portal.css`,
  `resources/js/client-portal.js`, added to `vite.config.js`), rewrote
  `layouts/client.blade.php` as a self-contained shell (dark-mode toggle, live sidebar
  counts), rewrote client dashboard with KPI trend deltas, upcoming milestones, activity
  feed, restyled theme-aware charts. Admin panel untouched. Installed `barryvdh/laravel-dompdf`
  (not wired yet). Other 7 client pages still on old markup — Phase 2.
- Fixed Vite dev server binding to IPv6-only `[::1]:5173`, which broke asset loading
  (page on `127.0.0.1:8000` could not reach `[::1]:5173`, cross-origin). Added `server`
  block to `vite.config.js` pinning host/hmr to `127.0.0.1`, strictPort 5173.
- Added persistent memory system: `.claude/memory/` (current-task, architecture, decisions, changelog, context) + memory rules in `CLAUDE.md`.
- Verified app boots: `php artisan serve` -> http://127.0.0.1:8000, `/` and `/login` return 200.

## Uncommitted work in tree (as of 2026-08-27, not yet committed past 2395d65)
Large in-progress expansion beyond last commit "staff section with image upload and task section":
- New modules added: projects (+milestones, +payments), services (+categories), products
  (+categories), pricing plans, portfolio, testimonials, estimates (+items), quotations,
  blog (+categories), calendar events, communications, activity logs, roles, designations,
  company settings, payment gateways.
- New middleware: `CheckModuleAccess`, `EnsureClientScope`. Client + staff portals added.
- ~28 new migrations, ~24 new seeders, ~15 new feature tests.
- Marketing site removed (`HomeController`, `pagesController`, home/about/contact/services/welcome views).
- Docker setup added (`Dockerfile`, `docker-compose.yml`, `docker/`, `.env.docker`).
