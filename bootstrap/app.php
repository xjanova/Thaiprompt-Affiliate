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
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Hotel admin routes
            Route::middleware('web')->group(base_path('routes/hotel-admin.php'));

            // Provider routes (Service Booking)
            Route::middleware('web')->group(base_path('routes/provider.php'));

            // Forum routes (Community Forum System)
            Route::middleware('web')->group(base_path('routes/forum.php'));

            // Fresh Market routes (ตลาดสดไทยพร๊อม)
            Route::middleware('web')->group(base_path('routes/taladsod.php'));

            // Admin Mobile API (Flutter app - /api/admin/*)
            // ใช้ middleware 'api' เพื่อ skip CSRF + ใช้ stateless auth
            Route::middleware('api')
                ->prefix('api/admin')
                ->group(base_path('routes/admin_api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ⚠️ CRITICAL: Trust Proxies สำหรับ Cloudflare และ Reverse Proxy
        // แก้ปัญหา redirect loop และ HTTPS detection
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // Exclude webhook and API webhook routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhook/*',
            '/webhook/*',
            'api/webhook/*',
            '/api/webhook/*',
        ]);

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
            \App\Http\Middleware\TrackPageView::class, // Track page views for analytics
            \App\Http\Middleware\CheckMaintenanceMode::class, // Maintenance mode check
            // \App\Http\Middleware\LoadTheme::class, // Theme System v2 - Disabled
        ]);

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin.api' => \App\Http\Middleware\AdminApiMiddleware::class,
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
            // LINE OA Security middleware
            'line.webhook.throttle' => \App\Http\Middleware\LineWebhookThrottle::class,
            // Provider LINE connection middleware
            'provider.line.required' => \App\Http\Middleware\EnsureProviderLineConnected::class,
            // User LINE connection middleware (general use)
            'require.line.uid' => \App\Http\Middleware\RequireLineUid::class,
            // Food Passport API Rate Limiting (CRITICAL for TPIX blockchain protection)
            'food-passport.ratelimit' => \App\Http\Middleware\FoodPassportRateLimiter::class,
            // Seller/Vendor KYC and Store verification middleware
            'kyc.verified' => \App\Http\Middleware\EnsureKycVerified::class,
            'has.vendor.store' => \App\Http\Middleware\EnsureHasVendorStore::class,
            // TPIX Blockchain & Token middleware
            'tpix.token.ownership' => \App\Http\Middleware\CheckTokenOwnership::class,
            'tpix.rate.limit' => \App\Http\Middleware\RateLimitTokenOperations::class,
            'tpix.token.deployed' => \App\Http\Middleware\VerifyTokenDeployment::class,
            'tpix.staking.eligible' => \App\Http\Middleware\CheckStakingEligibility::class,
            // Maintenance mode middleware
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            // Developer & Software License middleware
            'developer.approved' => \App\Http\Middleware\CheckDeveloperApproved::class,
            'license.owner' => \App\Http\Middleware\CheckLicenseOwnership::class,
            // SMS Payment Checker middleware
            'smschecker' => \App\Http\Middleware\VerifySmsCheckerDevice::class,
            // 🔐 Passport OAuth scope middleware (ไม่ auto-register บน Laravel 11 —
            //    ต้องประกาศเอง ไม่งั้น /api/user โยน "middleware [scopes] not found")
            'scopes' => \Laravel\Passport\Http\Middleware\CheckScopes::class,
            'scope' => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
            // 🌐 (2026-07-26) normalize ผู้ใช้ของ /api/v1/juntra/* ให้เป็น App\Models\User
            //    เสมอ ไม่ว่าจะเข้ามาด้วย Sanctum (แอป) หรือ Passport (SSO เว็บจันทรา)
            'juntra.user' => \App\Http\Middleware\ResolveJuntraUser::class,
        ]);

        // Global middleware for IP blocking
        $middleware->append(\App\Http\Middleware\CheckBlockedIp::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
