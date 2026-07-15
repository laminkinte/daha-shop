<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    // Application::configure() enables Laravel's own automatic event
    // discovery by default (scanning app/Listeners for handle() methods),
    // completely independently of App\Providers\EventServiceProvider's own
    // $listen array and shouldDiscoverEvents() override below - the two
    // were BOTH registering every listener, so every event fired its
    // listener twice (double SMS, double email, on every notification in
    // the app). Disabling discovery here leaves the explicit $listen array
    // as the single source of truth, matching this app's existing intent.
    ->withEvents(discover: false)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
            'webhooks/opay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
