<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocale::class, // Multi-language support
            // \App\Http\Middleware\LoadTheme::class, // Theme System v2 - Disabled
        ]);

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'check.permission' => \App\Http\Middleware\CheckPermission::class,
            'turnstile' => \App\Http\Middleware\VerifyCloudfareTurnstile::class,
            'throttle.login' => \App\Http\Middleware\ThrottleLogin::class,
            'check.blocked.ip' => \App\Http\Middleware\CheckBlockedIp::class,
            // Payment security middleware
            'payment.ratelimit' => \App\Http\Middleware\PaymentRateLimiter::class,
            'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
            'webhook.verify' => \App\Http\Middleware\VerifyWebhookSignature::class,
            // Two-Factor Authentication middleware
            'two-factor' => \App\Http\Middleware\RequireTwoFactor::class,
        ]);

        // Global middleware for IP blocking
        $middleware->append(\App\Http\Middleware\CheckBlockedIp::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
