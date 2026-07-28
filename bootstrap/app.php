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
            'admin.permission' => \App\Http\Middleware\EnsureAdminHasPermission::class,
            'super-admin' => \App\Http\Middleware\EnsureIsSuperAdmin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
            'webhooks/opay',
        ]);

        // Needed so Laravel correctly sees requests as HTTPS when the local
        // dev server sits behind a tunnel (e.g. cloudflared/ngrok) - without
        // this, generated asset/redirect URLs come back http:// even though
        // the browser loaded the page over https://, which browsers then
        // block as mixed content.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
