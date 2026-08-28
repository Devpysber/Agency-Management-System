<?php

namespace App\Http\Middleware;

use App\Models\ClientPortalVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordClientVisit
{
    /**
     * Logs client-portal attendance: one row per client user per day, with a
     * per-day hit counter. Only acts on 'client' role GET requests so admin
     * traffic and Livewire XHR posts don't inflate the numbers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'client' && $request->isMethod('GET') && ! $request->hasHeader('X-Livewire')) {
            try {
                ClientPortalVisit::touchFor($user);
            } catch (\Throwable $e) {
                report($e); // never break the request over attendance logging
            }
        }

        return $next($request);
    }
}
