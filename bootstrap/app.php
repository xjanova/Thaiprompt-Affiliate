<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/hotel-admin.php'));
        },
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
            \App\Http\Middleware\TrackVendorStoreVisit::class, // Track vendor store visits
            \App\Http\Middleware\TrackRequestMetrics::class, // Track request/response metrics
            // \App\Http\Middleware\LoadTheme::class, // Theme System v2 - Disabled
        ]);

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'hotel-admin' => \App\Http\Middleware\HotelAdminMiddleware::class,
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
            // Crypto wallet middleware
            'crypto.wallet.exists' => \App\Http\Middleware\EnsureCryptoWalletExists::class,
            'crypto.wallet.active' => \App\Http\Middleware\CheckCryptoWalletStatus::class,
            // API Access Control middleware
            'api.access' => \App\Http\Middleware\ApiAccessControl::class,
        ]);

        // Global middleware for IP blocking
        $middleware->append(\App\Http\Middleware\CheckBlockedIp::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
