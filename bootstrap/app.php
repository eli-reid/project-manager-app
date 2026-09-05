<?php

use App\Core\Identity\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\RedirectMobileRoutes;
use App\Http\Middleware\LoadSettings;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->trustHosts(subdomains: false);

        $middleware->group('mobile', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            EnsurePasswordChanged::class,
            RedirectMobileRoutes::class,
            AddSecurityHeaders::class,
        ]);

        $middleware->web(append: [
            EnsurePasswordChanged::class,
            RedirectMobileRoutes::class,
            AddSecurityHeaders::class,
            LoadSettings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
