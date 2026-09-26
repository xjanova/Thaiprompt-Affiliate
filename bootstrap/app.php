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

        // 🛠️ (2026-09-26) ระหว่างเว็บปิดซ่อม (deploy.sh STEP 3→20) webhook ยังต้องเข้าได้
        //   รายการ path อยู่ใน App\Http\Middleware\PreventRequestsDuringMaintenance ที่เดียว
        //   — `artisan down` อ่านจากคลาสนั้นไปฝังในไฟล์ down, ฝั่ง HTTP ต้องใช้คลาสเดียวกัน
        $middleware->replace(
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class
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
            // 🔒 (2026-09-25) บัญชีที่ถูกระงับ (users.blocked_at) → ออกจากระบบทันทีทุกหน้า
            \App\Http\Middleware\EnsureAccountActive::class,
        ]);

        // 🔒 (2026-09-25) กลุ่ม api: บัญชีที่ถูกระงับ → เพิกถอน token + ตอบ 403 ACCOUNT_SUSPENDED
        //    ครอบทุก route ที่ใช้ Bearer token (ทุกกลุ่ม auth:sanctum ใน api.php + admin_api.php)
        $middleware->api(append: [
            \App\Http\Middleware\EnsureAccountActive::class,
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
            // 🔐 (2026-09-15) /api/v1/juntra/server/* — เซิร์ฟเวอร์ของจันทรา.online เท่านั้น
            //    (Passport client_credentials ของ client SSO ตัวเดิม ไม่ใช่ token ผู้ใช้)
            'juntra.server' => \App\Http\Middleware\AuthenticateJuntraServer::class,
            // 🔒 (2026-09-25) บัญชีถูกระงับ → 403 / ออกจากระบบ (ต่อท้ายกลุ่ม web+api อยู่แล้ว
            //    alias นี้ไว้ใช้กับ route ที่อยู่นอกสองกลุ่มนั้น)
            'account.active' => \App\Http\Middleware\EnsureAccountActive::class,
        ]);

        // Global middleware for IP blocking
        $middleware->append(\App\Http\Middleware\CheckBlockedIp::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 🔐 (2026-09-13) ห้าม flash ความลับกลับลง session ตอน validation ไม่ผ่าน
        //    (ค่าเริ่มต้นกันแค่ password — token บอท Telegram ที่เจ้าของกรอกในหน้าช่องทาง จะถูกเก็บ plaintext ใน session)
        $exceptions->dontFlash(['telegram_bot_token']);

        // 🚦 (2026-09-25) throttle แบบตัวเลข (throttle:N,M,prefix) ตอบ 429 เป็น JSON ภาษาไทยตามรูปแบบ API กลาง
        //    (ค่าเริ่มต้นของ Laravel คือข้อความอังกฤษ "Too Many Attempts.") — หน้าเว็บปกติใช้หน้า 429 เดิม
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'code' => 'TOO_MANY_REQUESTS',
                'message' => 'มีการเรียกใช้งานถี่เกินไป กรุณารอสักครู่แล้วลองใหม่',
            ], 429, $e->getHeaders());
        });
    })
    ->booted(function () {
        // 🚦 (2026-09-25) rate limiter 'api' สำหรับ throttle:api ที่หุ้มกลุ่ม /api/v1
        //    ⚠️ ห้ามใช้ throttle:api โดยไม่มีตัวนี้ — ThrottleRequests จะแปลง 'api' เป็นเลข 0
        //       = บล็อกทุก request ทันที
        //    - ล็อกอินแล้ว: นับต่อผู้ใช้ (config ratelimit.api.authenticated, ค่าเริ่มต้น 120/นาที)
        //    - ยังไม่ล็อกอิน: นับต่อ IP — เขียน (POST/PUT/DELETE) ตาม ratelimit.api.default (60/นาที)
        //      ส่วน GET ให้ 3 เท่า เพราะมือถือไทยใช้ CGNAT (หลายคนใช้ IP เดียว) และแอปยิง
        //      /app/config, /app/flags, /app/menus ... พร้อมกันตอนเปิดแอป
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            $thaiResponse = function (\Illuminate\Http\Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'code' => 'TOO_MANY_REQUESTS',
                    'message' => 'มีการเรียกใช้งานถี่เกินไป กรุณารอสักครู่แล้วลองใหม่',
                ], 429, $headers);
            };

            $user = $request->user();
            if (! $user && $request->bearerToken()) {
                // route สาธารณะที่แอปแนบ token มาด้วย — นับเป็นรายผู้ใช้ (guard cache ผู้ใช้ไว้แล้วจาก EnsureAccountActive)
                try {
                    $user = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
                } catch (\Throwable $e) {
                    $user = null;
                }
            }

            if ($user) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute((int) config('ratelimit.api.authenticated', 120))
                    ->by('api-user:'.$user->getAuthIdentifier())
                    ->response($thaiResponse);
            }

            $guestLimit = (int) config('ratelimit.api.default', 60);
            if ($request->isMethodSafe()) {
                $guestLimit *= 3;
            }

            return \Illuminate\Cache\RateLimiting\Limit::perMinute($guestLimit)
                ->by('api-ip:'.$request->ip().':'.($request->isMethodSafe() ? 'r' : 'w'))
                ->response($thaiResponse);
        });
    })
    ->create();
