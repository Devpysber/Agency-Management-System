<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientScope
{
    /**
     * Confines a 'client' role user to the client.* route namespace (plus
     * logout). Staff/admin roles pass through untouched — this middleware
     * only ever acts on client logins.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'client') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        if ($routeName === 'logout' || str_starts_with($routeName, 'client.')) {
            return $next($request);
        }

        // Direct construction: the redirect() helper resolves to Livewire's
        // Redirector inside a Livewire request, which is not a Symfony Response
        // and breaks this method's `: Response` return type.
        return new \Illuminate\Http\RedirectResponse(route('client.dashboard'));
    }
}
