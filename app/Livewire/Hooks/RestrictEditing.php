<?php

namespace App\Livewire\Hooks;

use App\Support\EditGate;
use Livewire\ComponentHook;

/**
 * Read-only enforcement for the admin panel. Any Livewire action on a
 * `pages::admin.*` component whose name looks like a write (save/update/delete/
 * approve/…) is blocked unless the current user is an admin, a manager
 * (legacy EditGate — see App\Support\EditGate), OR holds the specific
 * `module.action` permission for that module (the real per-designation
 * grid — see database/seeders/RoleSeeder.php). The page still renders for
 * everyone, so staff keep full read access either way.
 *
 * Without the permission-grid bypass, this hook silently overrides every
 * designation's Role row for anything it doesn't also happen to have its
 * own internal hasPermission() check for (most write methods added before
 * the per-designation grid existed don't) — found the hard way while
 * building COO/HR/Account Manager/BDM/Sales Executive/Project Manager: e.g.
 * BDM has Deals.Edit=true but a deal edit silently no-opped anyway.
 */
class RestrictEditing extends ComponentHook
{
    private const WRITE_PREFIXES = [
        'save', 'store', 'update', 'delete', 'destroy', 'create', 'remove',
        'approve', 'reject', 'accept', 'decline', 'assign', 'unassign',
        'convert', 'archive', 'unarchive', 'restore', 'import', 'duplicate',
        'form_submit', 'submitform', 'markas', 'complete', 'cancel', 'add',
        'attach', 'detach', 'toggle', 'bulk', 'move', 'send', 'publish',
        'revoke', 'grant', 'block', 'unblock', 'disable', 'enable', 'reset', 'regenerate',
        'sync', 'link', 'unlink', 'respond',
    ];

    /**
     * Exact method names to block that don't fit a prefix above without also
     * catching something staff should keep (e.g. marking their OWN assigned
     * task/milestone done, which stays allowed — see canUpdateProgress()).
     */
    private const WRITE_METHODS = [
        'markcompleted', 'markcancelled',
    ];

    /**
     * Methods that already self-gate (assigned staff may act on THEIR OWN
     * project/task even without manager rights) — never blanket-blocked here.
     */
    private const SELF_GATED_METHODS = [
        'updateprogress', 'completemilestone',
        'marktaskinprogress', 'marktaskcomplete', 'markcomplete',
        // Attendance appeal approval: gated on Attendance.Approve, not the
        // blanket admin/manager EditGate — CEO has Approve without Edit.
        'approveappeal', 'rejectappeal',
        'approveappealfromdashboard', 'rejectappealfromdashboard',
        // Finance actions live on the dashboard component (no module map
        // entry for "dashboard"), gated on Finance.Create/Approve directly.
        'recordpayment', 'approverefund',
        // Staff<->CEO direct chat — no module map entry for "messages";
        // it's a closed 1:1 channel by design (mount() resolves the CEO
        // dynamically by designation, openThread()/send() are scoped to
        // the two participants), not a CRM record needing module.action.
        'send', 'openthread',
        // projects/show sub-item forms: both already self-gate on
        // Projects.Edit inside the method. Left to the generic 'add' prefix
        // they'd be mapped to Projects.Create and block a Projects.Edit user.
        'addmilestone', 'addpayment',
    ];

    /** Component-name second segment ("pages::admin.<this>.xxx") -> Roles & Permissions module. */
    private const MODULE_MAP = [
        'contacts' => 'Contacts',
        'companies' => 'Companies',
        'deals' => 'Deals',
        'projects' => 'Projects',
        'tasks' => 'Tasks',
        'bugs' => 'Bugs',
        'communications' => 'Communications',
        'staff' => 'Staff',
        'attendance' => 'Attendance',
        'services' => 'Services',
        'products' => 'Products',
        'portfolio' => 'Portfolio',
        'testimonials' => 'Testimonials',
        'estimates' => 'Estimates',
        'quotations' => 'Quotations',
        'pricing' => 'Pricing',
        'blog' => 'Blog',
        'reports' => 'Reports',
        'settings' => 'Settings',
    ];

    public function call($method, $params, $returnEarly, ...$rest)
    {
        $name = (string) $this->component->getName();

        // Only guard admin-panel pages.
        if (! str_starts_with($name, 'pages::admin.') && ! str_starts_with($name, 'admin.')) {
            return;
        }

        if (EditGate::allows()) {
            return;
        }

        $m = strtolower((string) $method);

        // Allow harmless read helpers that happen to share a prefix.
        if (in_array($m, ['updatedsearch', 'updatedfilterstatus', 'updatedfiltertype', 'updateddate', 'updatingsearch', 'updatedsort', 'updatedq', 'updatedpage'], true)) {
            return;
        }
        if (str_starts_with($m, 'updated') || str_starts_with($m, 'updating')) {
            return;
        }
        if (in_array($m, self::SELF_GATED_METHODS, true)) {
            return;
        }

        $isWrite = in_array($m, self::WRITE_METHODS, true);
        if (! $isWrite) {
            foreach (self::WRITE_PREFIXES as $prefix) {
                if (str_starts_with($m, $prefix)) {
                    $isWrite = true;
                    break;
                }
            }
        }
        if (! $isWrite) {
            return;
        }

        // Real per-designation grid: does this user hold the specific
        // module.action this write needs? (e.g. BDM has Deals.Edit=true.)
        if ($this->hasModulePermission($name, $m)) {
            return;
        }

        $this->component->dispatch('toast', message: 'Read-only — you don\'t have permission to make changes here.', type: 'error');
        $returnEarly(null);
    }

    private function hasModulePermission(string $componentName, string $method): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // 'pages::admin.deals.edit' -> ['pages::admin', 'deals', 'edit'] -> 'deals'
        $segments = explode('.', $componentName);
        $prefix = $segments[1] ?? null;
        $module = self::MODULE_MAP[$prefix] ?? null;
        if (! $module) {
            return false;
        }

        // 'save'/'submit' forms serve BOTH create and update — the component's
        // own method decides which (e.g. designations save() checks
        // Staff.Create for a new row, Staff.Edit for an existing one). Accept
        // either grant here so a Create-only role isn't blocked before that
        // check runs.
        if (in_array($method, ['save', 'store', 'submit', 'submitform', 'form_submit', 'storeform'], true)) {
            return $user->hasPermission($module, 'Create') || $user->hasPermission($module, 'Edit');
        }

        $action = match (true) {
            str_starts_with($method, 'delete'), str_starts_with($method, 'destroy'), str_starts_with($method, 'remove') => 'Delete',
            str_starts_with($method, 'approve'), str_starts_with($method, 'accept'), str_starts_with($method, 'reject'), str_starts_with($method, 'decline') => 'Approve',
            str_starts_with($method, 'assign'), str_starts_with($method, 'unassign') => 'Assign',
            str_starts_with($method, 'store'), str_starts_with($method, 'create'), str_starts_with($method, 'add') => 'Create',
            default => 'Edit',
        };

        return $user->hasPermission($module, $action);
    }
}
