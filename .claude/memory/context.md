# Context

Concise snapshot to avoid rereading many files. Summaries + paths only, no source.

## How pages work
- Every screen = a Livewire 4 page component referenced as `pages::<dotpath>` in `routes/web.php`.
- To find a screen's view: `resources/views/pages/<dotpath>/⚡<name>.blade.php` (note the `⚡` prefix, and `dot` = `/`).
- Editing a page usually means editing that one blade + its co-located component logic. Check `routes/web.php` for the exact `pages::` string.

## Auth / portals
- Login only at `/` (view `auth.login`). No registration UI.
- Three audiences: admin (staff with module perms), staff portal (`/portal/*`), client portal (`/client/*`, read-only, scoped by `contacts.user_id` -> company).
- `EnsureClientScope` middleware forces company scoping; `CheckModuleAccess` gates admin modules.

## Data relationships worth knowing
- `Task` morphs to `deal` / `contact` / `company` / `project` via `related_type` short strings + `Relation::morphMap` in `AppServiceProvider`. Do not pass FQCNs there.
- Projects have milestones + payments (separate models/tables).
- Estimates and Quotations each have line items (Estimate -> EstimateItem).
- Staff have Designations.

## Environment / gotchas
- Windows host, PowerShell primary shell. Paths use `D:\Agency_management_system`.
- SQLite DB file already exists and is seeded. Mail goes to log, not sent.
- Blade page filenames contain a literal `⚡` (U+26A1) — quote paths in shell, git shows them escaped as `\342\232\241`.
- Lower-cased model class names (`company`, `contact`, `deal`, `staff`) are intentional legacy — match existing casing when referencing.
- Dev server: `php artisan serve` -> http://127.0.0.1:8000. Open the app here, NOT the Vite port.
- Vite (`npm run dev`) is asset/HMR only -> http://127.0.0.1:5173. Visiting it directly shows
  the "Vite dev server" placeholder page — that is not an error.
- `vite.config.js` `server.host`/`hmr.host` pinned to `127.0.0.1` (Windows was binding IPv6-only
  `[::1]`, breaking asset loads). Keep backend + browser on `127.0.0.1` too, not `localhost`.

## Test entry points
`tests/Feature/` covers: sidebar routes, detail pages render, permission matrix, client portal,
write/update/delete paths, settings mutations, payment gateways, designation dashboard, staff login access.
Run a subset: `php artisan test --filter <Name>`.
