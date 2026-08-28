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

        return redirect()->route('client.dashboard');
    }
}
