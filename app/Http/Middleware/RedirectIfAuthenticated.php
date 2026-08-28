<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aliased as `guest`. An already-signed-in user who lands on /login, /register
 * etc. (typically via the browser Back button) is bounced to their own home
 * screen instead of being shown a usable auth form.
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // Direct construction: the redirect() helper resolves to
                // Livewire's Redirector inside a Livewire request, which is not
                // a Symfony Response and breaks this method's `: Response` hint.
                return new \Illuminate\Http\RedirectResponse(
                    $user && $user->role === 'client' ? '/client/dashboard' : '/dashboard'
                );
            }
        }

        return $next($request);
    }
}
