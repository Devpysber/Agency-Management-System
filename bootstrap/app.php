<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'module.access' => \App\Http\Middleware\CheckModuleAccess::class,
            'client.scope' => \App\Http\Middleware\EnsureClientScope::class,
            'client.visit' => \App\Http\Middleware\RecordClientVisit::class,
            'staff.presence' => \App\Http\Middleware\RecordStaffPresence::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);

        // No back/forward cache for signed-in responses (Back after logout must
        // not show a live dashboard).
        $middleware->web(append: [
            \App\Http\Middleware\PreventBackHistory::class,
        ]);

        // Presence heartbeat is a fire-and-forget cache write; skip CSRF so a
        // slightly stale token in a long-open tab doesn't 419 it.
        $middleware->validateCsrfTokens(except: ['heartbeat']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
