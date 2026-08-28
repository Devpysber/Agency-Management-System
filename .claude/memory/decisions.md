# Decisions

Important technical decisions + why. No trivia.

## 2026-08-27 — Persistent memory system added
Added `.claude/memory/` + memory rules in `CLAUDE.md` so sessions resume without re-reading
the whole repo. Reason: token burn from rereading unchanged files each session.

## Registration disabled
`Auth::routes(['register' => false])`. Self-service sign-up intentionally off. New logins are
provisioned by admins: staff via staff module, client logins via a contact's `user_id`.

## Livewire 4 namespaced pages instead of controllers
App uses `Route::livewire('/path', 'pages::...')` for all screens. Old `HomeController` /
`pagesController` and marketing views (`home`, `about`, `contact`, `services`, `welcome`)
were deleted. No blade-only marketing site anymore — it's an authed app.

## Task polymorphism via morphMap short strings
`Task.related_type` stores `deal`/`contact`/`company`/`project`, not FQCNs. Requires
`Relation::morphMap` in `AppServiceProvider::boot`. Adding a new relatable type means adding
it to that map.

## Two-layer access control
`CheckModuleAccess` (admin module gating) + `EnsureClientScope` (company scoping for
client/staff portals) kept as separate middleware rather than one combined guard.

## 2026-08-28 — RestrictEditing Livewire hook was a no-op all session (found + fixed)
`Livewire::componentHook()` called from `AppServiceProvider::boot()` never actually
registered in time — Livewire's own provider snapshots hooks during ITS boot() to wire
mount/hydrate listeners; ours landed after that snapshot, so it silently never fired for
anyone, the whole session. Fixed by moving the call to `register()` (all providers'
register() runs before any boot()). Found while testing CEO's approval flow — see
[[rbac-spec]] CEO section for full blast-radius analysis (which modules were actually
unprotected vs already covered by their own internal `hasPermission()` checks).

## 2026-08-28 — Full RBAC governing spec (user-authored, binding)
User handed down a complete role-based CRM architecture: 20-point component doc template,
full department/designation roster, `module.action` permission naming, dashboard rules,
security rules (policies/gates, no hardcoded designation checks, no menu-only hiding).
Full text saved to `docs/rbac-spec.md` — READ THAT FILE FIRST before any designation work.
Process is strict: ONE designation at a time, only when user names it, never redesign
unrelated parts, never jump ahead. STEP 1/2 inspection already done and logged in that doc:
current `Role` model/`hasPermission()` is keyed by the coarse `staff.role` (admin/staff/client),
NOT by `staff.designation` — so today all non-admin staff share one permission row regardless
of designation. `EditGate`/`RestrictEditing` (built earlier this session, see [[current-task]])
are stopgaps to retire in favor of real per-designation permission rows as each designation
gets built out. See [[rbac-spec]] (docs/rbac-spec.md) for everything else — don't duplicate
the spec text here, this entry is just the pointer.
