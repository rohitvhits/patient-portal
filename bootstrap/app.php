<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Deployed behind a load balancer that terminates TLS (AWS-style setup) —
        // without this, Laravel has no way to know the original request was https,
        // so request()->fullUrl()/isSecure()/route() can all silently generate http://
        // URLs even though the page itself loaded over https. That mismatch is what
        // turned the appointments/detail pages' own fetch() calls into a
        // browser-blocked "mixed content" request with no server-side trace at all —
        // trusting the proxy's forwarded headers fixes scheme detection app-wide.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('appointments.index'));

        $middleware->alias([
            'patient.idle' => \App\Http\Middleware\PatientIdleTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
