<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Route-name prefix -> Roles & Permissions module name.
     * Prefixes not listed here (dashboard, calendar) aren't part of the
     * matrix, so they carry no restriction.
     */
    protected array $moduleMap = [
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

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role === 'admin') {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName || !str_contains($routeName, '.')) {
            return $next($request);
        }

        // attendance.person carries its own record-level rule (self OR
        // company-wide-view permission) inside the component's mount() —
        // finer-grained than this module gate, so it's exempt here.
        if ($routeName === 'attendance.person') {
            return $next($request);
        }

        [$prefix, $suffix] = explode('.', $routeName, 2);

        $module = $this->moduleMap[$prefix] ?? null;

        if (!$module) {
            return $next($request);
        }

        $action = match (true) {
            str_contains($suffix, 'add'), str_contains($suffix, 'create') => 'Create',
            str_contains($suffix, 'edit') => 'Edit',
            default => 'View',
        };

        if (!$user->hasPermission($module, $action)) {
            session()->flash('error', "You don't have permission to access {$module}.");

            // Build the RedirectResponse directly. Inside a Livewire page
            // request the redirect() / response()->redirectToRoute() helpers
            // resolve to Livewire\Features\SupportRedirects\Redirector, which
            // is NOT a Symfony Response and violates this method's `: Response`
            // return type — a hard 500 for every permission-denied non-admin.
            return new \Illuminate\Http\RedirectResponse(route('dashboard'));
        }

        return $next($request);
    }
}
