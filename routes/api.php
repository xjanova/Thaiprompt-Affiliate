<?php

use App\Http\Controllers\Api\AiGenController;
use App\Http\Controllers\Api\AiGenPackageController;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\CarbonCreditController;
use App\Http\Controllers\Api\CertificationController;
use App\Http\Controllers\Api\CoinExchangeController;
use App\Http\Controllers\Api\CryptoWalletApiController;
use App\Http\Controllers\Api\FoodPassportController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\NFCCardApiController;
use App\Http\Controllers\Api\NFCPairingApiController;
use App\Http\Controllers\Api\QualityController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\Api\TraceabilityController;
use App\Http\Controllers\Api\TrendApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TranslateController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\Api\VideoQuestController;
use App\Http\Controllers\Api\VideoRewardController;
use App\Http\Controllers\Api\VideoWatchController;
use App\Http\Controllers\LineWebhookController;
// SnakeGameSyncController ถูกลบแล้ว — multiplayer ย้ายไป Dedicated Game Server
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 🔐 (2026-07-17) OAuth2 SSO user profile — GET /api/user (top-level, ไม่อยู่ใน v1)
//    auth ผ่าน Passport guard 'api-oauth' (ไม่ใช่ sanctum) + scope read,profile,email
//    juntraweb ThaipromptClient::fetchUser() เรียกที่นี่หลังแลก token
//    (มี /api/v1/me [sanctum] เป็น fallback ของ juntra อยู่แล้ว — ไม่กระทบ)
Route::middleware(['auth:api-oauth', 'scopes:read,profile,email'])
    ->get('/user', [\App\Http\Controllers\Api\OAuthProfileController::class, 'show'])
    ->name('api.oauth.user');

// Webhooks (no CSRF, no auth)
// LINE Webhook with rate limiting and signature verification
Route::post('/webhook/line', [LineWebhookController::class, 'handle'])
    ->middleware(['line.webhook.throttle'])
    ->name('api.line.webhook');

// GitHub Release Webhook (auto-clear version cache)
Route::post('/webhooks/github/release', [\App\Http\Controllers\Api\WebhookController::class, 'handleGitHubRelease'])
    ->name('api.webhook.github.release');

// Thai Address API (ข้อมูลที่อยู่ไทย - ไม่ต้องล็อกอิน)
Route::prefix('thai-addresses')->name('api.thai-addresses.')->group(function () {
    Route::get('/provinces', [\App\Http\Controllers\Api\ThaiAddressController::class, 'provinces'])->name('provinces');
    Route::get('/districts/{provinceCode}', [\App\Http\Controllers\Api\ThaiAddressController::class, 'districts'])->name('districts');
    Route::get('/sub-districts/{districtCode}', [\App\Http\Controllers\Api\ThaiAddressController::class, 'subDistricts'])->name('sub-districts');
});

// Cookie Consent API (no auth required)
Route::post('/cookie-consent', [\App\Http\Controllers\CookieConsentController::class, 'store']);
Route::get('/cookie-consent', [\App\Http\Controllers\CookieConsentController::class, 'getConsent']);
Route::post('/cookie-track-page', [\App\Http\Controllers\CookieConsentController::class, 'trackPage']);
Route::post('/cookie-track-keyword', [\App\Http\Controllers\CookieConsentController::class, 'trackKeyword']);
Route::post('/cookie-track-product', [\App\Http\Controllers\CookieConsentController::class, 'trackProduct']);

// Payment Gateway Webhooks
Route::prefix('webhook')->name('api.webhook.')->group(function () {
    Route::post('/paysolutions', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePaySolutions'])
        ->middleware('webhook.verify:paysolutions')
        ->name('paysolutions');

    Route::post('/promptpay', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePromptPay'])
        ->middleware('webhook.verify:promptpay')
        ->name('promptpay');

    Route::post('/stripe', [\App\Http\Controllers\PaymentWebhookController::class, 'handleStripe'])
        ->middleware('webhook.verify:stripe')
        ->name('stripe');

    Route::post('/omise', [\App\Http\Controllers\PaymentWebhookController::class, 'handleOmise'])
        ->middleware('webhook.verify:omise')
        ->name('omise');

    Route::post('/paypal', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePayPal'])
        ->middleware('webhook.verify:paypal')
        ->name('paypal');

    Route::post('/razorpay', [\App\Http\Controllers\PaymentWebhookController::class, 'handleRazorpay'])
        ->middleware('webhook.verify:razorpay')
        ->name('razorpay');

    Route::post('/truemoney', [\App\Http\Controllers\PaymentWebhookController::class, 'handleTrueMoney'])
        ->middleware('webhook.verify:truemoney')
        ->name('truemoney');
});

// API v1
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // Register (public - for mobile app)
    Route::post('/register', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'register']);

    // LINE Login สำหรับ Mobile App (public)
    Route::prefix('auth/line')->group(function () {
        Route::get('/status', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'lineStatus']);
        Route::get('/mobile-url', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getLineLoginUrl']);
        Route::post('/mobile-callback', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'lineLoginCallback']);
    });

    // Web-Based Mobile Authentication (PKCE) - สำหรับ mobile app login ผ่านเว็บ
    Route::prefix('auth/mobile')->group(function () {
        Route::post('/init', [\App\Http\Controllers\Api\V1\MobileAuthController::class, 'init']);
        Route::post('/exchange', [\App\Http\Controllers\Api\V1\MobileAuthController::class, 'exchange']);
        Route::get('/status', [\App\Http\Controllers\Api\V1\MobileAuthController::class, 'status']);
        Route::post('/cancel', [\App\Http\Controllers\Api\V1\MobileAuthController::class, 'cancel']);
    });

    // Native LINE Login สำหรับ Mobile App (ใช้ LINE SDK โดยตรง)
    Route::prefix('auth/line-native')->group(function () {
        Route::get('/config', [\App\Http\Controllers\Api\V1\MobileLineAuthController::class, 'config']);
        Route::post('/verify', [\App\Http\Controllers\Api\V1\MobileLineAuthController::class, 'verify']);
    });

    // App settings (public)
    Route::get('/settings', [DashboardController::class, 'settings']);

    // Ranks (public - for marketing tools)
    Route::get('/ranks', [RankController::class, 'index']);

    // App Configuration (public)
    Route::prefix('app')->group(function () {
        Route::get('/maintenance-status', [AppConfigController::class, 'maintenanceStatus']);

        // NOTE: the previous Emergency Alert Banner public routes referenced
        // App\Http\Controllers\Admin\AppBannerController which never landed in
        // the codebase (only the AppBanner model exists). They were silently
        // overridden by the legacy banner routes inside the protected v1
        // group below, so removing them is a no-op for live behavior — just
        // unbreaks `php artisan route:list`.

        // Mobile app control plane (public; private keys filtered server-side)
        Route::get('/config',         [\App\Http\Controllers\Api\V1\AppConfigApiController::class, 'config'])->name('api.v1.app.config');
        Route::get('/flags',          [\App\Http\Controllers\Api\V1\AppConfigApiController::class, 'flags'])->name('api.v1.app.flags');
        Route::get('/menus',          [\App\Http\Controllers\Api\V1\AppMenuApiController::class, 'menus'])->name('api.v1.app.menus');
        Route::get('/sliders',        [\App\Http\Controllers\Api\V1\AppMenuApiController::class, 'sliders'])->name('api.v1.app.sliders');
        Route::get('/promotions',     [\App\Http\Controllers\Api\V1\AppMenuApiController::class, 'promotions'])->name('api.v1.app.promotions');
        Route::get('/latest-version', [\App\Http\Controllers\Api\V1\AppReleaseApiController::class, 'latest'])->name('api.v1.app.latest-version');
    });

    // ========= THAIPROMPT_APP_AI_PUBLIC (v1.0.21 recovery) =========
    // App-facing AI endpoints called without user auth. Per-IP rate
    // limited. These were previously hot-patched onto prod via tinker
    // and got wiped by a `git reset --hard` during deploy — now tracked
    // in git so they survive. See session_handoff_v1.0.21.
    Route::get('/ai/nong-ying/persona',
        [\App\Http\Controllers\Api\V1\NongYingController::class, 'persona'])
        ->middleware('throttle:60,1')
        ->name('api.v1.ai.nong-ying.persona');

    Route::get('/ai/nong-ying/knowledge',
        [\App\Http\Controllers\Api\V1\NongYingController::class, 'knowledgeSearch'])
        ->middleware('throttle:30,1')
        ->name('api.v1.ai.nong-ying.knowledge');

    // On-device Gemma .task download proxy. Local-first: Nginx serves
    // straight from public/ai-models/ when synced, else streams from HF
    // with server-side HF_TOKEN. GET + HEAD both forwarded.
    Route::match(['get', 'head'], '/ai/models/{tier}',
        [\App\Http\Controllers\Api\AiModelProxyController::class, 'download'])
        ->where('tier', 'gemma4_e2b|gemma4_e4b')
        ->middleware('throttle:30,1')
        ->name('api.v1.ai.models.download');

    // Metadata (size, modified, HF URL) — guardAdmin() inside controller.
    Route::get('/ai/models/{tier}/info',
        [\App\Http\Controllers\Api\AiModelAdminController::class, 'show'])
        ->where('tier', 'gemma4_e2b|gemma4_e4b')
        ->name('api.v1.ai.models.info');
    // ======= END THAIPROMPT_APP_AI_PUBLIC (v1.0.21 recovery) =======

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Mobile app analytics ingestion + AI fallback (protected + rate-limited)
        Route::post('/events/batch', [\App\Http\Controllers\Api\V1\AnalyticsApiController::class, 'batch'])
            ->middleware('throttle:60,1')
            ->name('api.v1.events.batch');
        Route::post('/ai/chat', [\App\Http\Controllers\Api\V1\AiChatApiController::class, 'chat'])
            ->middleware('throttle:20,1')
            ->name('api.v1.ai.chat');
        Route::post('/ai/tts', [\App\Http\Controllers\Api\V1\AiTtsApiController::class, 'speak'])
            ->middleware('throttle:20,1')
            ->name('api.v1.ai.tts');

        // Admin-only model sync (gated by controller's guardAdmin()).
        Route::post('/admin/ai/models/{tier}/sync',
            [\App\Http\Controllers\Api\AiModelAdminController::class, 'sync'])
            ->where('tier', 'gemma4_e2b|gemma4_e4b')
            ->name('api.v1.admin.ai.models.sync');

        // Dashboard
        Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);
        Route::get('/dashboard/commissions', [DashboardController::class, 'commissions']);
        Route::get('/dashboard/referrals', [DashboardController::class, 'referrals']);
        Route::get('/dashboard/charts', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getDashboardCharts']);
        Route::get('/dashboard/referral-link', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getReferralLink']);

        // Profile (Mobile App)
        Route::prefix('profile')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getProfile']);
            Route::put('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'updateProfile']);
            Route::post('/change-password', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'changePassword']);
            Route::get('/referral-code', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getReferralCode']);
            // Avatar Upload/Delete สำหรับ Mobile App
            Route::post('/avatar', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'uploadAvatar']);
            Route::delete('/avatar', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'deleteAvatar']);
        });

        // Products (Mobile App E-commerce)
        Route::prefix('products')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getProducts']);
            Route::get('/categories', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getProductCategories']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getProduct']);
        });

        // Cart (Mobile App) - คำนวณทุกอย่างที่ server
        Route::prefix('cart')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getCart']);
            Route::post('/add', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'addToCart']);
            Route::put('/items/{itemId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'updateCartItem']);
            Route::delete('/items/{itemId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'removeFromCart']);
            Route::delete('/clear', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'clearCart']);
            Route::post('/promo', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'applyPromoCode']);
            Route::post('/checkout', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'checkout']);
        });

        // Wallet (Mobile App)
        Route::prefix('wallet')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getWallet']);
            Route::get('/balance', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getWalletBalance']);
            Route::get('/transactions', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getWalletTransactions']);
            Route::post('/topup', [\App\Http\Controllers\Api\V1\PaymentApiController::class, 'initializeWalletTopup']);
            // ค้นหากระเป๋าเงินจาก wallet address (สำหรับ QR scan)
            Route::get('/lookup', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'lookupWalletAddress']);
            // โอนเงินระหว่างกระเป๋า (ต้องใช้ PIN)
            Route::post('/transfer', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'transferMoney']);
        });

        // Orders (Mobile App)
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'store']);
            Route::get('/unread-messages', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'getUnreadMessageCount']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'show']);
            Route::post('/{id}/cancel', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'cancel']);
            // Order Tracking
            Route::get('/{id}/tracking', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'getTracking']);
            // Order Chat
            Route::get('/{id}/messages', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'getMessages']);
            Route::post('/{id}/messages', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'sendMessage']);
        });

        // Shipping Providers (Mobile App)
        Route::get('/shipping-providers', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'getShippingProviders']);

        // Payment (Mobile App)
        Route::prefix('payment')->group(function () {
            Route::get('/methods', [\App\Http\Controllers\Api\V1\PaymentApiController::class, 'getMethods']);
            Route::get('/deposit-methods', [\App\Http\Controllers\Api\V1\PaymentApiController::class, 'getDepositMethods']);
            Route::post('/order', [\App\Http\Controllers\Api\V1\PaymentApiController::class, 'initializeOrderPayment']);
            Route::get('/{transactionId}/status', [\App\Http\Controllers\Api\V1\PaymentApiController::class, 'checkStatus']);
            Route::get('/history', [\App\Http\Controllers\Api\V1\PaymentApiController::class, 'getHistory']);
        });

        // KYC (Mobile App)
        Route::prefix('kyc')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getKycStatus']);
            Route::post('/submit', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'submitKyc']);
            Route::post('/upload', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'uploadKycImage']);
            Route::post('/confirm', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'confirmKycSubmission']);
        });

        // Rider (Mobile App)
        Route::prefix('rider')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getRiderStatus']);
            Route::post('/register', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'registerRider']);
            Route::post('/document', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'uploadRiderDocument']);
            Route::post('/permissions', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'updateRiderPermissions']);
            Route::post('/availability', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'setRiderAvailability']);
            Route::post('/location', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'updateRiderLocation']);
            Route::get('/jobs/available', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getAvailableJobs']);
            Route::get('/jobs/current', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getCurrentJob']);
            Route::post('/jobs/{jobId}/accept', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'acceptJob']);
            Route::post('/jobs/{jobId}/status', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'updateJobStatus']);
        });

        // Web Session (สำหรับเปิดหน้าเว็บจาก Mobile App พร้อม authentication)
        Route::post('/web-session', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'generateWebSessionToken']);

        // Support Tickets (Mobile App)
        Route::prefix('tickets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getTickets']);
            Route::post('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'createTicket']);
            Route::get('/{ticketId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getTicket']);
            Route::post('/{ticketId}/reply', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'replyTicket']);
            Route::post('/{ticketId}/rate', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'rateTicket']);
        });

        // Notifications (Mobile App)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getNotifications']);
            Route::get('/unread-count', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getUnreadNotificationCount']);
            Route::post('/mark-all-read', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'markAllNotificationsRead']);
            Route::post('/{notificationId}/read', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'markNotificationRead']);
            Route::delete('/{notificationId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'deleteNotification']);
        });

        // Push Notification Token (Mobile App)
        Route::prefix('push')->group(function () {
            Route::post('/token', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'registerPushToken']);
            Route::delete('/token', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'removePushToken']);
        });

        // GPS Sharing (Mobile App) - แชร์ตำแหน่งให้ Admin ดู GPS Monitor
        Route::prefix('mobile/gps')->group(function () {
            Route::post('/share', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'shareGpsLocation']);
            Route::post('/stop', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'stopGpsSharing']);
        });

        // Store Listing (Mobile App) - รายการร้านค้า
        Route::prefix('mobile/stores')->group(function () {
            Route::get('/official', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getOfficialStores']);
            Route::get('/featured', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getFeaturedStores']);
            Route::get('/{storeId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getStoreDetail']);
            Route::get('/{storeId}/products', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getStoreProducts']);
        });

        // Premium Store (Mobile App) - ร้านพรีเมี่ยม (Official Shop)
        Route::prefix('mobile/premium-store')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getPremiumStore']);
            Route::get('/products', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getPremiumStoreProducts']);
        });

        // Academy (Mobile App) - ระบบการเรียนรู้
        Route::prefix('mobile/academy')->group(function () {
            Route::get('/courses', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getCourses']);
            Route::get('/courses/{courseId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getCourseDetail']);
            Route::get('/my-courses', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getMyCourses']);
        });

        // Watch & Earn (Mobile App) - ดูคลิปได้เงิน
        Route::prefix('mobile/watch-earn')->group(function () {
            Route::get('/videos', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getVideos']);
            Route::get('/earnings', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getVideoEarnings']);
            Route::post('/submit', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'submitVideoWatch']);
        });

        // Seller Order Management (Mobile App) - จัดการ Orders สำหรับผู้ขาย
        Route::prefix('seller/orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getSellerOrders']);
            Route::get('/{orderId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getSellerOrderDetail']);
            Route::post('/{orderId}/tracking', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'updateOrderTracking']);
            Route::post('/{orderId}/tracking-history', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'addOrderTrackingHistory']);
        });

        // Shipping Providers (Mobile App) - รายการบริษัทขนส่ง
        Route::get('/seller/shipping-providers', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getShippingProviders']);

        // Rank System (Mobile App)
        Route::prefix('mobile/ranks')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getRanks']);
            Route::get('/progress', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getUserRankProgress']);
            Route::get('/leaderboard', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getLeaderboard']);
            Route::get('/{rankId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getRankDetail']);
        });

        // MLM / Affiliate Network (Mobile App)
        Route::prefix('mobile/affiliate')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getMyAffiliate']);
            Route::get('/referrals', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getDirectReferrals']);
            Route::get('/team-tree', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getTeamTree']);
        });

        // ⭐ MLM Tree routes สำหรับ Mobile App (alias ของ affiliate routes)
        Route::prefix('mobile/mlm')->group(function () {
            Route::get('/tree', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getTeamTree']);
            Route::get('/tree/{userId}/children', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getTeamTreeChildren']);
            Route::get('/search', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'searchTeamMember']);
            Route::get('/member/{userId}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getMemberProfile']);
        });

        // Commission System (Mobile App)
        Route::prefix('mobile/commissions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getCommissions']);
            Route::get('/earnings', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getEarningsSummary']);
        });

        // =====================================================
        // Mobile App Admin Control (3 อย่างเท่านั้น)
        // 1. Device Registration/Heartbeat
        // 2. Banner โฆษณา
        // 3. Push Token
        // =====================================================

        // Device Registration & Analytics
        Route::prefix('mobile/device')->group(function () {
            Route::post('/register', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'register']);
            Route::post('/heartbeat', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'heartbeat']);
        });

        // Banner โฆษณา
        Route::prefix('mobile/banners')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'banners']);
            Route::post('/{bannerId}/click', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'bannerClick']);
        });

        // Push Token
        Route::post('mobile/push-token', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'registerPushToken']);

        // Push Notification Delivery Tracking (สำหรับ retry mechanism)
        Route::prefix('mobile/push')->group(function () {
            // ยืนยันว่าได้รับ notification แล้ว
            Route::post('/confirm', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'confirmPushDelivery']);
            // ดึง pending notifications (เมื่อ device กลับมา online)
            Route::get('/pending', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'getPendingNotifications']);
            // Bulk confirm หลาย notifications
            Route::post('/bulk-confirm', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'bulkConfirmDelivery']);
            // สถิติการส่ง (สำหรับ admin analytics)
            Route::get('/analytics', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'pushAnalytics']);
        });

        // Translation (Google Translate API)
        Route::prefix('translate')->group(function () {
            Route::post('/', [TranslateController::class, 'translate']);
            Route::post('/batch', [TranslateController::class, 'translateBatch']);
            Route::post('/detect', [TranslateController::class, 'detect']);
            Route::get('/languages', [TranslateController::class, 'languages']);
            Route::get('/status', [TranslateController::class, 'status']);
        });

        // Tree (Organization Chart)
        Route::prefix('tree')->group(function () {
            Route::get('/user', [TreeController::class, 'getUserTree']);
            Route::get('/admin/{affiliateId?}', [TreeController::class, 'getAdminTree']);

            // Binary Tree
            Route::get('/binary', [TreeController::class, 'getUserBinaryTree']);
            Route::get('/binary/admin/{affiliateId?}', [TreeController::class, 'getAdminBinaryTree']);
        });

        // Ranks
        Route::prefix('ranks')->group(function () {
            Route::get('/', [RankController::class, 'index']);
            Route::get('/{rank}', [RankController::class, 'show']);
            Route::get('/user/progress', [RankController::class, 'userProgress']);
            Route::get('/leaderboard', [RankController::class, 'leaderboard']);
            Route::get('/user/eligibility', [RankController::class, 'checkEligibility']);
            Route::post('/promotions/request', [RankController::class, 'requestPromotion']);
        });

        // Investments & Staking
        Route::prefix('investments')->group(function () {
            Route::get('/plans', [InvestmentController::class, 'plans']);
            Route::get('/plans/{plan}', [InvestmentController::class, 'showPlan']);
            Route::post('/calculate-roi', [InvestmentController::class, 'calculateROI']);
            Route::post('/invest', [InvestmentController::class, 'store']);
            Route::get('/summary', [InvestmentController::class, 'summary']);
            Route::get('/positions', [InvestmentController::class, 'positions']);
            Route::get('/positions/{position}', [InvestmentController::class, 'showPosition']);
            Route::post('/positions/{position}/withdraw', [InvestmentController::class, 'withdraw']);
            Route::get('/distributions', [InvestmentController::class, 'distributions']);
        });

        // Crypto Wallet API
        Route::prefix('crypto')->group(function () {
            Route::post('/verify-signature', [CryptoWalletApiController::class, 'verifySignature']);
            Route::get('/balances', [CryptoWalletApiController::class, 'getBalances']);
            Route::get('/address/{currency}', [CryptoWalletApiController::class, 'getAddress']);
            Route::get('/prices', [CryptoWalletApiController::class, 'getPrices']);
            Route::get('/transaction/{txHash}', [CryptoWalletApiController::class, 'checkTransaction']);
            Route::get('/gas-price', [CryptoWalletApiController::class, 'getGasPrice']);
        });

        // NFC Card API
        Route::prefix('nfc')->group(function () {
            Route::get('/cards', [NFCCardApiController::class, 'index']);
            Route::get('/cards/{cardId}', [NFCCardApiController::class, 'show']);
            Route::post('/cards/verify', [NFCCardApiController::class, 'verify']);
            Route::post('/cards/payment', [NFCCardApiController::class, 'processPayment']);
            Route::get('/cards/{cardId}/transactions', [NFCCardApiController::class, 'transactions']);
            Route::get('/cards/{cardId}/balance', [NFCCardApiController::class, 'balance']);
            Route::get('/readers/nearby', [NFCCardApiController::class, 'nearbyReaders']);

            // NFC Card Pairing via QR Code
            Route::prefix('pairing')->group(function () {
                Route::post('/generate', [NFCPairingApiController::class, 'generatePairingToken']);
                Route::post('/verify', [NFCPairingApiController::class, 'verifyPairingToken']);
                Route::post('/pair', [NFCPairingApiController::class, 'pairCard']);
                Route::get('/available-cards', [NFCPairingApiController::class, 'availableCards']);
            });

            // NFC Device (App-Backend) Pairing
            Route::prefix('devices')->group(function () {
                Route::post('/generate-qr', [\App\Http\Controllers\Api\NFCDeviceApiController::class, 'generatePairingQR']);
                Route::post('/register', [\App\Http\Controllers\Api\NFCDeviceApiController::class, 'registerDevice']);
                Route::get('/status', [\App\Http\Controllers\Api\NFCDeviceApiController::class, 'deviceStatus']);
                Route::delete('/revoke', [\App\Http\Controllers\Api\NFCDeviceApiController::class, 'revokeDevice']);
            });
        });

        // TPIX Native Blockchain API
        Route::prefix('tpix')->group(function () {
            Route::get('/network-info', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getNetworkInfo']);
            Route::get('/balance', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getBalance']);
            Route::get('/block-number', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getBlockNumber']);
            Route::get('/transaction', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getTransaction']);
            Route::get('/transaction-receipt', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getTransactionReceipt']);
            Route::post('/send-raw-transaction', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'sendRawTransaction']);
            Route::post('/estimate-gas', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'estimateGas']);
            Route::get('/gas-price', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getGasPrice']);
            Route::get('/transaction-count', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'getTransactionCount']);
            Route::post('/validate-address', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'validateAddress']);
            Route::post('/to-wei', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'toWei']);
            Route::post('/from-wei', [\App\Http\Controllers\Api\TPIXBlockchainController::class, 'fromWei']);
        });

        // App Configuration (protected, legacy)
        // The mobile app's NEW control plane lives in the public block above
        // (`/v1/app/config`, `/v1/app/flags`, ...). The two `/config` routes
        // collided — Laravel kept the LATER definition, which silently
        // hijacked the new public endpoint and made it return 401. The new
        // endpoint is canonical going forward, so the legacy `/config` and
        // `/banners` shadows are removed here. The remaining theme/settings/
        // features endpoints are kept in case any web admin tooling still
        // hits them.
        Route::prefix('app')->group(function () {
            Route::get('/settings', [AppConfigController::class, 'settings']);
            Route::get('/theme', [AppConfigController::class, 'theme']);
            Route::get('/complete-theme', [AppConfigController::class, 'completeTheme']);
            Route::get('/control-sections', [AppConfigController::class, 'controlSections']);
            Route::get('/component-settings', [AppConfigController::class, 'componentSettings']);
            Route::get('/features', [AppConfigController::class, 'features']);
        });

        // AI Gen - Image & Video Generation
        Route::prefix('ai-gen')->group(function () {
            // Dashboard & User Info
            Route::get('/dashboard', [AiGenController::class, 'dashboard']);

            // Generation
            Route::post('/generate', [AiGenController::class, 'generate']);
            Route::get('/generations', [AiGenController::class, 'myGenerations']);
            Route::get('/generations/{generationId}', [AiGenController::class, 'getGeneration']);
            Route::get('/generations/{generationId}/status', [AiGenController::class, 'checkStatus']);
            Route::post('/generations/{generationId}/favorite', [AiGenController::class, 'toggleFavorite']);
            Route::delete('/generations/{generationId}', [AiGenController::class, 'deleteGeneration']);

            // Packages
            Route::get('/packages', [AiGenPackageController::class, 'index']);
            Route::get('/packages/{packageId}', [AiGenPackageController::class, 'show']);
            Route::post('/packages/{packageId}/purchase', [AiGenPackageController::class, 'purchase']);
        });

        // Video Reward System
        Route::prefix('video-rewards')->group(function () {
            // Dashboard & Overview
            Route::get('/dashboard', [VideoRewardController::class, 'dashboard']);
            Route::get('/statistics', [VideoRewardController::class, 'statistics']);
            Route::get('/leaderboard', [VideoRewardController::class, 'leaderboard']);

            // Channels & Videos
            Route::get('/channels', [VideoRewardController::class, 'channels']);
            Route::get('/channels/{channelId}/videos', [VideoRewardController::class, 'channelVideos']);
            Route::get('/videos/{videoId}', [VideoRewardController::class, 'videoDetails']);

            // Video Watching
            Route::post('/watch/start', [VideoWatchController::class, 'startWatch']);
            Route::post('/watch/heartbeat', [VideoWatchController::class, 'heartbeat']);
            Route::post('/watch/end', [VideoWatchController::class, 'endWatch']);
            Route::post('/watch/claim-reward', [VideoWatchController::class, 'claimReward']);

            // Quests
            Route::get('/quests', [VideoQuestController::class, 'index']);
            Route::get('/quests/{questId}', [VideoQuestController::class, 'show']);
            Route::post('/quests/{questId}/claim', [VideoQuestController::class, 'claimReward']);
            Route::get('/quests/history', [VideoQuestController::class, 'history']);

            // Coin Exchange
            Route::get('/exchange/rates', [CoinExchangeController::class, 'rates']);
            Route::post('/exchange/calculate', [CoinExchangeController::class, 'calculate']);
            Route::post('/exchange/request', [CoinExchangeController::class, 'exchange']);
            Route::get('/exchange/history', [CoinExchangeController::class, 'history']);
            Route::get('/exchange/requests/{requestId}', [CoinExchangeController::class, 'show']);
        });

        // Viral Trend Detection API
        Route::prefix('trends')->group(function () {
            Route::get('/dashboard', [TrendApiController::class, 'dashboard']);
            Route::get('/', [TrendApiController::class, 'index']);
            Route::get('/{trend}', [TrendApiController::class, 'show']);
            Route::post('/{trend}/generate-content', [TrendApiController::class, 'generateContent']);

            // Keywords
            Route::get('/keywords/trending', [TrendApiController::class, 'trendingKeywords']);
            Route::get('/keywords/emerging', [TrendApiController::class, 'emergingKeywords']);
            Route::get('/keywords/{keyword}/related', [TrendApiController::class, 'relatedKeywords']);

            // Sources
            Route::get('/sources', [TrendApiController::class, 'sources']);
            Route::post('/sources', [TrendApiController::class, 'createSource']);
            Route::put('/sources/{source}', [TrendApiController::class, 'updateSource']);
            Route::delete('/sources/{source}', [TrendApiController::class, 'deleteSource']);

            // Analytics
            Route::get('/analytics', [TrendApiController::class, 'analytics']);
        });

        // LINE OA Signup Analytics API ถูกลบแล้ว (ใช้ LINE Message Analytics แทน)

        // Taskbar Shortcuts API
        Route::prefix('taskbar-shortcuts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'store']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'destroy']);
            Route::post('/reorder', [\App\Http\Controllers\Api\TaskbarShortcutController::class, 'reorder']);
        });

        // LINE Bot Keywords API
        Route::prefix('line-bot/keywords')->name('api.line-bot.keywords.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'index'])->name('index');
            Route::get('/active', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'activeKeywords'])->name('active');
            Route::get('/statistics', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'statistics'])->name('statistics');
            Route::post('/test', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'test'])->name('test');
            Route::post('/', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'store'])->name('store');
            Route::get('/{keyword}', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'show'])->name('show');
            Route::put('/{keyword}', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'update'])->name('update');
            Route::delete('/{keyword}', [\App\Http\Controllers\Api\V1\LineBotKeywordController::class, 'destroy'])->name('destroy');
        });
    });

    // ===== Fresh Market API (ตลาดสดไทยพร๊อม) =====
    Route::prefix('fresh-market')->name('fresh-market.')->group(function () {
        // Public endpoints
        Route::get('/categories', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'categories'])->name('categories');
        Route::get('/listings', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'listings'])->name('listings');
        Route::get('/listings/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'showListing'])->name('listings.show');
        Route::get('/nearby', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'nearby'])->name('nearby');
        Route::get('/sellers/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'showSeller'])->name('sellers.show');

        // Auth endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/listings', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'storeListing'])->name('listings.store');
            Route::put('/listings/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'updateListing'])->name('listings.update');
            Route::post('/orders', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'storeOrder'])->name('orders.store');
            Route::get('/orders', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'orders'])->name('orders');
            Route::get('/orders/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'showOrder'])->name('orders.show');
            Route::put('/orders/{id}/status', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'updateOrderStatus'])->name('orders.status');
            Route::get('/seller/orders', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'sellerOrders'])->name('seller.orders');
            Route::get('/seller/dashboard', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'sellerDashboard'])->name('seller.dashboard');

            // ===== Rider GPS API =====
            Route::prefix('rider/gps')->group(function () {
                Route::post('/update', [\App\Http\Controllers\Api\V1\RiderGpsController::class, 'updateLocation']);
                Route::post('/lost', [\App\Http\Controllers\Api\V1\RiderGpsController::class, 'reportGpsLost']);
                Route::post('/confirm-off', [\App\Http\Controllers\Api\V1\RiderGpsController::class, 'confirmGpsOff']);
                Route::post('/resume', [\App\Http\Controllers\Api\V1\RiderGpsController::class, 'resumeGps']);
                Route::get('/customer/{jobId}', [\App\Http\Controllers\Api\V1\RiderGpsController::class, 'getCustomerLocation']);
                Route::get('/tracking/{jobId}', [\App\Http\Controllers\Api\V1\RiderGpsController::class, 'getTrackingInfo']);
            });
        });
    });
});

// Public Crypto Wallet API (no auth required)
Route::post('/crypto/generate-nonce', [CryptoWalletApiController::class, 'generateNonce']);

/*
|--------------------------------------------------------------------------
| Bot Automation API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/bot-automation')->middleware('auth:sanctum')->name('api.bot-automation.')->group(function () {
    // Automations
    Route::apiResource('automations', \App\Http\Controllers\Api\BotAutomationApiController::class);
    Route::post('automations/{automation}/execute', [\App\Http\Controllers\Api\BotAutomationApiController::class, 'execute'])->name('automations.execute');
    Route::get('automations/{automation}/statistics', [\App\Http\Controllers\Api\BotAutomationApiController::class, 'statistics'])->name('automations.statistics');

    // Marketplace
    Route::get('marketplace', [\App\Http\Controllers\Api\BotAutomationApiController::class, 'marketplace'])->name('marketplace');
    Route::get('marketplace/{listing}', [\App\Http\Controllers\Api\BotMarketplaceApiController::class, 'show'])->name('marketplace.show');
    Route::post('marketplace/{listing}/subscribe', [\App\Http\Controllers\Api\BotMarketplaceApiController::class, 'subscribe'])->name('marketplace.subscribe');
    Route::post('marketplace/{listing}/review', [\App\Http\Controllers\Api\BotMarketplaceApiController::class, 'review'])->name('marketplace.review');

    // Platform Connections
    Route::get('platforms', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'index'])->name('platforms.index');
    Route::get('platforms/available', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'available'])->name('platforms.available');
    Route::post('platforms/connect', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'connect'])->name('platforms.connect');
    Route::delete('platforms/{connection}', [\App\Http\Controllers\Api\BotPlatformApiController::class, 'disconnect'])->name('platforms.disconnect');

    // Templates
    Route::apiResource('templates', \App\Http\Controllers\Api\BotTemplateApiController::class);
    Route::post('templates/{template}/duplicate', [\App\Http\Controllers\Api\BotTemplateApiController::class, 'duplicate'])->name('templates.duplicate');

    // Support
    Route::get('support/conversations', [\App\Http\Controllers\Api\BotSupportApiController::class, 'conversations'])->name('support.conversations');
    Route::get('support/conversations/{conversation}', [\App\Http\Controllers\Api\BotSupportApiController::class, 'show'])->name('support.show');
    Route::post('support/conversations/{conversation}/messages', [\App\Http\Controllers\Api\BotSupportApiController::class, 'sendMessage'])->name('support.send');

    // Sales
    Route::get('sales/conversations', [\App\Http\Controllers\Api\BotSalesApiController::class, 'conversations'])->name('sales.conversations');
    Route::get('sales/conversations/{conversation}', [\App\Http\Controllers\Api\BotSalesApiController::class, 'show'])->name('sales.show');
    Route::post('sales/conversations/{conversation}/recommend', [\App\Http\Controllers\Api\BotSalesApiController::class, 'recommend'])->name('sales.recommend');

    // Analytics
    Route::get('analytics/overview', [\App\Http\Controllers\Api\BotAnalyticsApiController::class, 'overview'])->name('analytics.overview');
    Route::get('analytics/posts', [\App\Http\Controllers\Api\BotAnalyticsApiController::class, 'posts'])->name('analytics.posts');
    Route::get('analytics/platforms', [\App\Http\Controllers\Api\BotAnalyticsApiController::class, 'platforms'])->name('analytics.platforms');
});

// Bot Automation Webhooks (no auth)
Route::prefix('webhook/bot')->name('api.webhook.bot.')->group(function () {
    Route::post('facebook', [\App\Http\Controllers\Api\BotWebhookController::class, 'facebook'])->name('facebook');
    Route::post('instagram', [\App\Http\Controllers\Api\BotWebhookController::class, 'instagram'])->name('instagram');
    Route::post('twitter', [\App\Http\Controllers\Api\BotWebhookController::class, 'twitter'])->name('twitter');
    Route::post('tiktok', [\App\Http\Controllers\Api\BotWebhookController::class, 'tiktok'])->name('tiktok');
});

/*
|--------------------------------------------------------------------------
| Chatbot Rental System API Routes
|--------------------------------------------------------------------------
| ระบบให้เช่าบอทแชทอัจฉริยะแบบไฮบริด
| - Keyword-based + AI Fallback
| - Multi-platform Integration
| - Auto Content Posting
| - Marketplace
*/

// Chatbot Rental System (Protected Routes)
Route::prefix('v1/chatbot')->middleware('auth:sanctum')->name('api.chatbot.')->group(function () {

    // Bot Management
    Route::prefix('bots')->name('bots.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/test', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'test'])->name('test');
        Route::post('/{id}/clone', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'clone'])->name('clone');
        Route::get('/{id}/stats', [\App\Http\Controllers\Api\Chatbot\BotManagementController::class, 'stats'])->name('stats');

        // Keyword Responses
        Route::prefix('{botId}/keywords')->name('keywords.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'destroy'])->name('destroy');
            Route::post('/update-order', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'updateOrder'])->name('update-order');
            Route::post('/test-match', [\App\Http\Controllers\Api\Chatbot\KeywordResponseController::class, 'testMatch'])->name('test-match');
        });

        // Platform Integrations
        Route::prefix('{botId}/integrations')->name('integrations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/verify', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'verify'])->name('verify');
            Route::get('/{id}/webhook-url', [\App\Http\Controllers\Api\Chatbot\PlatformIntegrationController::class, 'getWebhookUrl'])->name('webhook-url');
        });

        // Auto Content Posting
        Route::prefix('{botId}/auto-content')->name('auto-content.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'store'])->name('store');
            Route::post('/{id}/generate', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'generate'])->name('generate');
            Route::post('/{id}/schedule', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'schedule'])->name('schedule');
            Route::post('/{id}/post-now', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'postNow'])->name('post-now');
            Route::put('/{id}', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\Chatbot\AutoContentController::class, 'destroy'])->name('destroy');
        });
    });

    // Marketplace
    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'show'])->name('show');
        Route::post('/{id}/rent', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'rent'])->name('rent');
        Route::get('/my-rentals', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'myRentals'])->name('my-rentals');
        Route::get('/my-earnings', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'myEarnings'])->name('my-earnings');
        Route::post('/rentals/{rentalId}/cancel', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'cancelRental'])->name('cancel-rental');
        Route::post('/rentals/{rentalId}/renew', [\App\Http\Controllers\Api\Chatbot\MarketplaceController::class, 'renewRental'])->name('renew-rental');
    });
});

// Chatbot Webhooks (Public - No Auth)
Route::prefix('webhook/chatbot')->name('api.webhook.chatbot.')->group(function () {
    Route::post('{platform}/{integration_id}', function ($platform, $integrationId) {
        // Handle webhook from different platforms
        // This will be implemented based on each platform's webhook specification
        return response()->json(['success' => true]);
    })->name('handle');
});

/*
|--------------------------------------------------------------------------
| TPIX Token System API Routes
|--------------------------------------------------------------------------
| Complete REST API for TPIX Token Management
| - Token marketplace
| - Token creation & deployment
| - Trading (buy/sell)
| - Portfolio management
| - Staking
| - Referrals
*/

Route::prefix('v1/tpix')->name('api.tpix.')->group(function () {

    // Public endpoints
    Route::get('/tokens', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'index'])->name('tokens.index');
    Route::get('/tokens/{id}', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'show'])->name('tokens.show');
    Route::get('/tokens/{id}/transactions', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'transactions'])->name('tokens.transactions');

    // Protected endpoints
    Route::middleware('auth:sanctum')->group(function () {

        // Token Management
        Route::post('/tokens', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'store'])
            ->middleware('rate_limit_token_operations:create')
            ->name('tokens.store');
        Route::post('/tokens/{id}/deploy', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'deploy'])
            ->middleware(['check_token_ownership', 'rate_limit_token_operations:deploy'])
            ->name('tokens.deploy');

        // Token Trading
        Route::post('/tokens/{id}/transfer', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'transfer'])
            ->middleware(['verify_token_deployment', 'rate_limit_token_operations:transfer'])
            ->name('tokens.transfer');
        Route::post('/tokens/{id}/buy', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'buy'])
            ->middleware(['verify_token_deployment', 'rate_limit_token_operations:trade'])
            ->name('tokens.buy');
        Route::post('/tokens/{id}/sell', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'sell'])
            ->middleware(['verify_token_deployment', 'rate_limit_token_operations:trade'])
            ->name('tokens.sell');

        // Portfolio
        Route::get('/portfolio', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'portfolio'])->name('portfolio');
        Route::get('/balances', [\App\Http\Controllers\Api\V1\TokenApiController::class, 'balances'])->name('balances');
    });

    // DEX Routes
    Route::prefix('dex')->name('dex.')->group(function () {
        // Public endpoints
        Route::get('/pools', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'pools'])->name('pools');
        Route::get('/pools/{id}', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'poolDetails'])->name('pools.show');
        Route::get('/statistics', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'statistics'])->name('statistics');

        // Protected endpoints (requires authentication)
        Route::middleware('auth:sanctum')->group(function () {
            // Quote
            Route::post('/quote', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'quote'])
                ->name('quote');

            // Swap
            Route::post('/swap', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'swap'])
                ->middleware('rate_limit_token_operations:trade')
                ->name('swap');

            // Liquidity
            Route::post('/liquidity/add', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'addLiquidity'])
                ->middleware('rate_limit_token_operations:trade')
                ->name('liquidity.add');

            Route::post('/liquidity/remove', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'removeLiquidity'])
                ->middleware('rate_limit_token_operations:trade')
                ->name('liquidity.remove');

            // User positions & history
            Route::get('/my/positions', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'myPositions'])->name('my.positions');
            Route::get('/my/swaps', [\App\Http\Controllers\Api\V1\DEXApiController::class, 'mySwaps'])->name('my.swaps');
        });
    });

    // Staking Routes
    Route::prefix('staking')->name('staking.')->group(function () {
        // Public endpoints
        Route::get('/pools', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getPools'])->name('pools');
        Route::get('/pools/{poolId}', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getPool'])->name('pools.show');
        Route::get('/pools/{poolId}/recent', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getRecentStakes'])->name('pools.recent');

        // Protected endpoints (requires authentication)
        Route::middleware('auth:sanctum')->group(function () {
            // Get user stake
            Route::get('/my-stake/{poolId}', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getMyStake'])->name('my-stake');

            // Stake actions
            Route::post('/stake', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'stake'])
                ->middleware('tpix.rate.limit:trade')
                ->name('stake');

            Route::post('/unstake', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'unstake'])
                ->middleware('tpix.rate.limit:trade')
                ->name('unstake');

            Route::post('/claim', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'claim'])
                ->name('claim');

            // History
            Route::get('/history', [\App\Http\Controllers\Api\V1\StakingApiController::class, 'getHistory'])->name('history');

            // Individual stake details
            Route::get('/stakes/{stakeId}', function ($stakeId) {
                $stake = \App\Models\TPIXStake::with('pool.token')->findOrFail($stakeId);

                // Ensure user owns this stake
                if ($stake->user_id !== auth()->id()) {
                    abort(403, 'Unauthorized');
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $stake->id,
                        'pool' => [
                            'id' => $stake->pool->id,
                            'pair' => $stake->pool->token->name,
                            'token' => $stake->pool->token,
                        ],
                        'amount' => (float) $stake->staked_amount,
                        'lock_period_days' => (int) $stake->lock_period_days,
                        'apy' => (float) $stake->apy,
                        'exchange_rate' => '1 '.$stake->pool->token->symbol,
                        'price_impact' => 0,
                        'slippage_tolerance' => 0,
                        'created_at' => $stake->created_at->toISOString(),
                        'unlock_date' => $stake->unlock_date ? $stake->unlock_date->toISOString() : null,
                        'status' => $stake->status,
                        'blockchain_tx_hash' => $stake->blockchain_tx_hash,
                        'blockchain_tx_url' => $stake->blockchain_tx_hash
                            ? config('tpix.blockchain.explorer_url').'/tx/'.$stake->blockchain_tx_hash
                            : null,
                    ],
                ]);
            })->name('stakes.show');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Food Passport API Routes
|--------------------------------------------------------------------------
| ระบบ Food Passport - ตรวจสอบคุณภาพและความปลอดภัยอาหาร
| - Farm-to-Fork Traceability
| - Quality Control & Certifications
| - Carbon Footprint & Credits
| - IoT Sensor Integration
| - Google Maps Integration
| - Blockchain Verification
*/

// Public Food Passport Routes (No Auth)
Route::prefix('v1/food-passport')->middleware('food-passport.ratelimit:public')->name('api.food-passport.')->group(function () {

    // Public Product Scan (QR Code)
    Route::get('/scan/{passportId}', [FoodPassportController::class, 'scan'])
        ->name('scan');

    // Public Product View
    Route::get('/products/{id}', [FoodPassportController::class, 'show'])
        ->name('products.show');

    // Public Product Search
    Route::get('/products', [FoodPassportController::class, 'index'])
        ->name('products.index');

    // Public Certification Verification
    Route::get('/certifications/{id}/verify', [CertificationController::class, 'verify'])
        ->name('certifications.verify');

    // Public Carbon Data View
    Route::get('/products/{id}/carbon', [FoodPassportController::class, 'carbonData'])
        ->name('products.carbon');

    // Public Journey Tracking
    Route::get('/products/{id}/journey', [TraceabilityController::class, 'journey'])
        ->name('products.journey');
});

// Protected Food Passport Routes (Auth Required)
Route::prefix('v1/food-passport')->middleware(['auth:sanctum', 'food-passport.ratelimit'])->name('api.food-passport.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Product Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->name('products.')->group(function () {
        // Create new food product (Farmers & Admins)
        Route::post('/', [FoodPassportController::class, 'store'])
            ->middleware(['food-passport.ratelimit:write', 'can:create,App\Models\FoodProduct'])
            ->name('store');

        // Update product (Owner or Admin)
        Route::put('/{id}', [FoodPassportController::class, 'update'])
            ->middleware(['food-passport.ratelimit:write', 'can:update,id'])
            ->name('update');

        // Delete product (Owner or Super Admin)
        Route::delete('/{id}', [FoodPassportController::class, 'destroy'])
            ->middleware(['food-passport.ratelimit:write', 'can:delete,id'])
            ->name('destroy');

        // Bulk import products (Verified Farmers & Admins)
        Route::post('/bulk-import', [FoodPassportController::class, 'bulkImport'])
            ->middleware(['food-passport.ratelimit:write', 'can:bulkImport,App\Models\FoodProduct'])
            ->name('bulk-import');

        // Statistics & Dashboard
        Route::get('/statistics', [FoodPassportController::class, 'statistics'])
            ->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | Traceability & Journey Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('traceability')->name('traceability.')->group(function () {
        // Add journey stage
        Route::post('/products/{id}/journey', [TraceabilityController::class, 'addStage'])
            ->middleware('food-passport.ratelimit:write')
            ->name('add-stage');

        // Update journey stage
        Route::put('/journey/{journeyId}', [TraceabilityController::class, 'updateStage'])
            ->middleware('food-passport.ratelimit:write')
            ->name('update-stage');

        // Complete journey stage
        Route::post('/journey/{journeyId}/complete', [TraceabilityController::class, 'completeStage'])
            ->middleware('food-passport.ratelimit:write')
            ->name('complete-stage');

        // IoT Sensor Data Recording
        Route::post('/journey/{journeyId}/sensor-data', [TraceabilityController::class, 'recordSensorData'])
            ->middleware('food-passport.ratelimit:iot')
            ->name('record-sensor-data');

        // Bulk IoT Sensor Data Upload
        Route::post('/journey/{journeyId}/sensor-data/bulk', [TraceabilityController::class, 'bulkRecordSensorData'])
            ->middleware('food-passport.ratelimit:iot')
            ->name('bulk-sensor-data');

        // Google Maps Integration
        Route::post('/geocode', [TraceabilityController::class, 'geocodeLocation'])
            ->name('geocode');

        Route::post('/calculate-distance', [TraceabilityController::class, 'calculateDistance'])
            ->name('calculate-distance');

        // Get current location
        Route::get('/products/{id}/current-location', [TraceabilityController::class, 'currentLocation'])
            ->name('current-location');
    });

    /*
    |--------------------------------------------------------------------------
    | Quality Control & Checkpoints
    |--------------------------------------------------------------------------
    */
    Route::prefix('quality')->name('quality.')->group(function () {
        // Create quality checkpoint
        Route::post('/products/{id}/checkpoints', [QualityController::class, 'createCheckpoint'])
            ->middleware(['food-passport.ratelimit:write', 'can:create,App\Models\QualityCheckpoint'])
            ->name('create-checkpoint');

        // Update checkpoint
        Route::put('/checkpoints/{id}', [QualityController::class, 'updateCheckpoint'])
            ->middleware(['food-passport.ratelimit:write', 'can:update,id'])
            ->name('update-checkpoint');

        // Add corrective actions
        Route::post('/checkpoints/{id}/corrective-actions', [QualityController::class, 'addCorrectiveActions'])
            ->middleware('food-passport.ratelimit:write')
            ->name('add-corrective-actions');

        // Require retest
        Route::post('/checkpoints/{id}/retest', [QualityController::class, 'requireRetest'])
            ->middleware('food-passport.ratelimit:write')
            ->name('require-retest');

        // Get product quality history
        Route::get('/products/{id}/history', [QualityController::class, 'qualityHistory'])
            ->name('quality-history');

        // Quality statistics
        Route::get('/statistics', [QualityController::class, 'statistics'])
            ->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | Carbon Credits & Trading
    |--------------------------------------------------------------------------
    */
    Route::prefix('carbon')->name('carbon.')->group(function () {
        // Issue carbon credit (Admins & Verifiers)
        Route::post('/products/{id}/issue-credit', [CarbonCreditController::class, 'issueCredit'])
            ->middleware(['food-passport.ratelimit:blockchain', 'can:issue,App\Models\CarbonCredit'])
            ->name('issue-credit');

        // View marketplace
        Route::get('/marketplace', [CarbonCreditController::class, 'marketplace'])
            ->name('marketplace');

        // View credit details
        Route::get('/credits/{id}', [CarbonCreditController::class, 'show'])
            ->name('credits.show');

        // Trade credit
        Route::post('/credits/{id}/trade', [CarbonCreditController::class, 'trade'])
            ->middleware(['food-passport.ratelimit:trading', 'can:trade,id'])
            ->name('credits.trade');

        // Purchase credit
        Route::post('/credits/{id}/purchase', [CarbonCreditController::class, 'purchase'])
            ->middleware(['food-passport.ratelimit:trading', 'can:purchase,id'])
            ->name('credits.purchase');

        // Retire credit
        Route::post('/credits/{id}/retire', [CarbonCreditController::class, 'retire'])
            ->middleware(['food-passport.ratelimit:trading', 'can:retire,id'])
            ->name('credits.retire');

        // My credits
        Route::get('/my-credits', [CarbonCreditController::class, 'myCredits'])
            ->name('my-credits');

        // Credit statistics
        Route::get('/statistics', [CarbonCreditController::class, 'statistics'])
            ->name('statistics');

        // Transaction history
        Route::get('/credits/{id}/transactions', [CarbonCreditController::class, 'transactionHistory'])
            ->name('credits.transactions');
    });

    /*
    |--------------------------------------------------------------------------
    | Certifications Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('certifications')->name('certifications.')->group(function () {
        // List certifications
        Route::get('/', [CertificationController::class, 'index'])
            ->name('index');

        // Create certification (Certification Bodies & Admins)
        Route::post('/', [CertificationController::class, 'store'])
            ->middleware(['food-passport.ratelimit:write', 'can:create,App\Models\FoodCertification'])
            ->name('store');

        // View certification details
        Route::get('/{id}', [CertificationController::class, 'show'])
            ->name('show');

        // Update certification
        Route::put('/{id}', [CertificationController::class, 'update'])
            ->middleware(['food-passport.ratelimit:write', 'can:update,id'])
            ->name('update');

        // Renew certification
        Route::post('/{id}/renew', [CertificationController::class, 'renew'])
            ->middleware(['food-passport.ratelimit:write', 'can:renew,id'])
            ->name('renew');

        // Revoke certification
        Route::post('/{id}/revoke', [CertificationController::class, 'revoke'])
            ->middleware(['food-passport.ratelimit:write', 'can:revoke,id'])
            ->name('revoke');

        // Upload documents
        Route::post('/{id}/documents', [CertificationController::class, 'uploadDocument'])
            ->middleware(['food-passport.ratelimit:write', 'can:uploadDocuments,id'])
            ->name('upload-document');

        // Generate QR code
        Route::post('/{id}/generate-qr', [CertificationController::class, 'generateQRCode'])
            ->middleware(['food-passport.ratelimit:write', 'can:generateQRCode,id'])
            ->name('generate-qr');

        // Record on blockchain
        Route::post('/{id}/blockchain', [CertificationController::class, 'recordOnBlockchain'])
            ->middleware(['food-passport.ratelimit:blockchain', 'can:recordOnBlockchain,id'])
            ->name('blockchain');

        // Get product certifications
        Route::get('/products/{productId}', [CertificationController::class, 'productCertifications'])
            ->name('product-certifications');

        // Certification statistics
        Route::get('/statistics/overview', [CertificationController::class, 'statistics'])
            ->name('statistics');
    });
});

/*
|--------------------------------------------------------------------------
| Snake.io Multiplayer Game API
|--------------------------------------------------------------------------
*/
// ✅ Snake.io Game API - เฉพาะ save score + wallet + skin (multiplayer ย้ายไป Dedicated Server แล้ว)
Route::middleware(['web'])->prefix('games/snake-io')->name('api.games.snake-io.')->group(function () {
    $controller = \App\Http\Controllers\SnakeGameController::class;

    // บันทึกคะแนน (ต้อง auth และมี wallet เพียงพอ)
    Route::post('/save-score', [$controller, 'saveScore'])
        ->name('save-score');

    // ตรวจสอบ wallet
    Route::get('/check-wallet', [$controller, 'checkWallet'])
        ->name('check-wallet');

    // บันทึก skin preference (สำหรับสมาชิก)
    Route::post('/save-skin-preference', [$controller, 'saveSkinPreference'])
        ->middleware('auth:web')
        ->name('save-skin-preference');

    // ดึง skin preference (สำหรับสมาชิก)
    Route::get('/get-skin-preference', [$controller, 'getSkinPreference'])
        ->middleware('auth:web')
        ->name('get-skin-preference');
});

// ✅ 8 Ball Pool Game API - wallet, betting, save result
Route::middleware(['web'])->prefix('games/8ball-pool')->name('api.games.8ball-pool.')->group(function () {
    $controller = \App\Http\Controllers\PoolGameController::class;

    Route::get('/check-wallet', [$controller, 'checkWallet'])->name('check-wallet');
    Route::post('/place-bet', [$controller, 'placeBet'])->middleware('auth:web')->name('place-bet');
    Route::post('/settle-match', [$controller, 'settleMatch'])->middleware('auth:web')->name('settle-match');
    Route::post('/save-result', [$controller, 'saveResult'])->middleware('auth:web')->name('save-result');
});

// ✅ Game Configuration API - ดึงค่า config จาก database
Route::get('/games/config', function () {
    try {
        // ดึงค่า config ทั้งหมดของเกมจากทุกกลุ่ม
        $groups = [
            'snake_io_server',
            'snake_io_world',
            'snake_io_movement',
            'snake_io_camera',
            'snake_io_scoring',
            'snake_io_food',
            'snake_io_bots',
            'snake_io_powerups',
            'snake_io_powerup_magnet',
            'snake_io_powerup_speed',
            'snake_io_powerup_multiplier',
            'snake_io_powerup_zoom',
            'snake_io_music',
        ];

        $allConfig = [];

        // ✅ ตรวจสอบว่าตาราง game_settings มีอยู่จริง
        if (\Illuminate\Support\Facades\Schema::hasTable('game_settings')) {
            foreach ($groups as $group) {
                $groupConfig = \App\Models\GameSetting::getGroup($group);
                // ✅ FIX: Collection ต้องแปลงเป็น array ก่อน array_merge (PHP 8.3 TypeError)
                $configArray = $groupConfig instanceof \Illuminate\Support\Collection
                    ? $groupConfig->toArray()
                    : (array) $groupConfig;
                $allConfig = array_merge($allConfig, $configArray);
            }
        }

        // จัดรูปแบบ response ให้เป็น camelCase และจัดกลุ่ม
        return response()->json([
            'success' => true,
            'data' => [
                // Server Configuration
                'server' => [
                    'ip' => $allConfig['snake_io_server_ip'] ?? '123.253.62.250',
                    'port' => (int) ($allConfig['snake_io_server_port'] ?? 8080),
                    'enabled' => filter_var($allConfig['snake_io_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'max_players_per_room' => (int) ($allConfig['snake_io_max_players_per_room'] ?? 30),
                ],

                // World Settings
                'world' => [
                    'size' => (int) ($allConfig['world_size'] ?? 200),
                    'initial_snake_length' => (int) ($allConfig['initial_snake_length'] ?? 5),
                    'segment_size' => (float) ($allConfig['segment_size'] ?? 0.5),
                    'collision_distance' => (float) ($allConfig['collision_distance'] ?? 0.6),
                ],

                // Movement Settings
                'movement' => [
                    'speed' => (float) ($allConfig['movement_speed'] ?? 0.15),
                    'boost_speed' => (float) ($allConfig['boost_speed'] ?? 0.3),
                    'turn_speed' => (float) ($allConfig['turn_speed'] ?? 0.05),
                ],

                // Camera Settings
                'camera' => [
                    'initial_distance' => (int) ($allConfig['camera_initial_distance'] ?? 15),
                    'zoomed_out_distance' => (int) ($allConfig['camera_zoomed_out_distance'] ?? 50),
                    'zoom_speed' => (float) ($allConfig['camera_zoom_speed'] ?? 0.05),
                ],

                // Scoring System
                'scoring' => [
                    'food_value' => (int) ($allConfig['food_value'] ?? 1),
                    'points_per_growth' => (int) ($allConfig['points_per_growth'] ?? 10),
                    'score_multiplier_base' => (int) ($allConfig['score_multiplier_base'] ?? 1),
                    'save_score_enabled' => filter_var($allConfig['save_score_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ],

                // Food Settings
                'food' => [
                    'count' => (int) ($allConfig['food_count'] ?? 100),
                    'spawn_rate' => (float) ($allConfig['food_spawn_rate'] ?? 0.5),
                    'lifetime' => (int) ($allConfig['food_lifetime'] ?? 0),
                    'value_min' => (int) ($allConfig['food_value_min'] ?? 1),
                    'value_max' => (int) ($allConfig['food_value_max'] ?? 1),
                ],

                // Bot Settings
                'bots' => [
                    'count' => (int) ($allConfig['bot_count'] ?? 30),
                    'max_spawn_per_frame' => (int) ($allConfig['bot_max_spawn_per_frame'] ?? 2),
                    'intelligence_level' => (int) ($allConfig['bot_intelligence_level'] ?? 5),
                ],

                // Powerups - General
                'powerups' => [
                    'max_count' => (int) ($allConfig['powerup_max_count'] ?? 4),
                    'spawn_rate' => (float) ($allConfig['powerup_spawn_rate'] ?? 0.02),
                    'global_lifetime' => (int) ($allConfig['powerup_global_lifetime'] ?? 30000),

                    // Magnet
                    'magnet' => [
                        'enabled' => filter_var($allConfig['magnet_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'duration' => (int) ($allConfig['magnet_duration'] ?? 10000),
                        'spawn_chance' => (float) ($allConfig['magnet_spawn_chance'] ?? 0.25),
                        'lifetime' => (int) ($allConfig['magnet_lifetime'] ?? 30000),
                        'range' => (int) ($allConfig['magnet_range'] ?? 10),
                    ],

                    // Speed Boost
                    'speed' => [
                        'enabled' => filter_var($allConfig['speed_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'duration' => (int) ($allConfig['speed_duration'] ?? 10000),
                        'spawn_chance' => (float) ($allConfig['speed_spawn_chance'] ?? 0.25),
                        'lifetime' => (int) ($allConfig['speed_lifetime'] ?? 30000),
                        'multiplier' => (float) ($allConfig['speed_multiplier'] ?? 2),
                    ],

                    // Score Multiplier
                    'multiplier' => [
                        'enabled' => filter_var($allConfig['multiplier_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'duration' => (int) ($allConfig['multiplier_duration'] ?? 10000),
                        'spawn_chance' => (float) ($allConfig['multiplier_spawn_chance'] ?? 0.25),
                        'lifetime' => (int) ($allConfig['multiplier_lifetime'] ?? 30000),
                        'value' => (int) ($allConfig['multiplier_value'] ?? 2),
                    ],

                    // Zoom Out
                    'zoom' => [
                        'enabled' => filter_var($allConfig['zoom_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'duration' => (int) ($allConfig['zoom_duration'] ?? 15000),
                        'spawn_chance' => (float) ($allConfig['zoom_spawn_chance'] ?? 0.25),
                        'lifetime' => (int) ($allConfig['zoom_lifetime'] ?? 30000),
                        'distance' => (int) ($allConfig['zoom_distance'] ?? 50),
                    ],
                ],

                // Music & Sound Settings
                // ✅ FIX: ป้องกัน double-encoded JSON สำหรับ tracks
                'music' => [
                    'enabled' => filter_var($allConfig['music_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'default_volume' => (float) ($allConfig['music_default_volume'] ?? 0.5),
                    'title_tracks' => (function () use ($allConfig) {
                        $raw = $allConfig['music_title_tracks'] ?? '[]';
                        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                        // ถ้า decode แล้วได้ string อีก = double-encoded
                        if (is_string($decoded)) {
                            $decoded = json_decode($decoded, true);
                        }

                        return is_array($decoded) ? $decoded : [];
                    })(),
                    'gameplay_tracks' => (function () use ($allConfig) {
                        $raw = $allConfig['music_gameplay_tracks'] ?? '[]';
                        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                        if (is_string($decoded)) {
                            $decoded = json_decode($decoded, true);
                        }

                        return is_array($decoded) ? $decoded : [];
                    })(),
                    'sfx_enabled' => filter_var($allConfig['sfx_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'sfx_default_volume' => (float) ($allConfig['sfx_default_volume'] ?? 0.7),
                ],
            ],
        ]);

    } catch (\Exception $e) {
        // ✅ ถ้า database ไม่พร้อม ส่งค่า default กลับเป็น JSON (ไม่ใช่ HTML error)
        return response()->json([
            'success' => true,
            'data' => [
                'server' => [
                    'ip' => '123.253.62.250',
                    'port' => 8080,
                    'enabled' => true,
                    'max_players_per_room' => 30,
                ],
                'world' => ['size' => 200, 'initial_snake_length' => 5, 'segment_size' => 0.5, 'collision_distance' => 0.6],
                'movement' => ['speed' => 0.15, 'boost_speed' => 0.3, 'turn_speed' => 0.05],
                'camera' => ['initial_distance' => 15, 'zoomed_out_distance' => 50, 'zoom_speed' => 0.05],
                'scoring' => ['food_value' => 1, 'points_per_growth' => 10, 'score_multiplier_base' => 1, 'save_score_enabled' => true],
                'food' => ['count' => 100, 'spawn_rate' => 0.5, 'lifetime' => 0, 'value_min' => 1, 'value_max' => 1],
                'bots' => ['count' => 30, 'max_spawn_per_frame' => 2, 'intelligence_level' => 5],
                'powerups' => [
                    'max_count' => 4, 'spawn_rate' => 0.02, 'global_lifetime' => 30000,
                    'magnet' => ['enabled' => true, 'duration' => 10000, 'spawn_chance' => 0.25, 'lifetime' => 30000, 'range' => 10],
                    'speed' => ['enabled' => true, 'duration' => 10000, 'spawn_chance' => 0.25, 'lifetime' => 30000, 'multiplier' => 2],
                    'multiplier' => ['enabled' => true, 'duration' => 10000, 'spawn_chance' => 0.25, 'lifetime' => 30000, 'value' => 2],
                    'zoom' => ['enabled' => true, 'duration' => 15000, 'spawn_chance' => 0.25, 'lifetime' => 30000, 'distance' => 50],
                ],
                'music' => ['enabled' => true, 'default_volume' => 0.5, 'title_tracks' => [], 'gameplay_tracks' => [], 'sfx_enabled' => true, 'sfx_default_volume' => 0.7],
            ],
            'fallback' => true,
            'error' => $e->getMessage(),
        ]);
    }
})->name('api.games.config');

// ✅ Admin Snake.io API ถูกย้ายไปที่ Dedicated Game Server (C# WPF) แล้ว
// ✅ Snake Sync API ถูกย้ายไปที่ Dedicated Game Server (C# WebSocket) แล้ว

// ============================================
// Google Maps API (V1)
// ============================================

Route::prefix('v1/maps')->middleware('auth:sanctum')->name('api.maps.')->group(function () {
    // Reverse Geocode: พิกัด → ที่อยู่
    Route::post('/reverse-geocode', [\App\Http\Controllers\Api\GoogleMapsController::class, 'reverseGeocode'])
        ->name('reverse-geocode');

    // Forward Geocode: ที่อยู่ → พิกัด
    Route::post('/geocode', [\App\Http\Controllers\Api\GoogleMapsController::class, 'geocode'])
        ->name('geocode');

    // คำนวณเส้นทางและระยะทาง
    Route::post('/directions', [\App\Http\Controllers\Api\GoogleMapsController::class, 'getDirections'])
        ->name('directions');

    // คำนวณระยะทางหลายจุดพร้อมกัน
    Route::post('/distance-matrix', [\App\Http\Controllers\Api\GoogleMapsController::class, 'getDistanceMatrix'])
        ->name('distance-matrix');

    // ค้นหาสถานที่ใกล้เคียง
    Route::post('/search-nearby', [\App\Http\Controllers\Api\GoogleMapsController::class, 'searchNearby'])
        ->name('search-nearby');

    // รายละเอียดสถานที่
    Route::get('/place/{placeId}', [\App\Http\Controllers\Api\GoogleMapsController::class, 'getPlaceDetails'])
        ->name('place.details');

    // สถานะ API
    Route::get('/status', [\App\Http\Controllers\Api\GoogleMapsController::class, 'getStatus'])
        ->name('status');
});

// ============================================
// Service Booking System API (V1)
// ============================================

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Service Categories & Services (Public with auth)
    Route::get('/service-categories', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'categories']);
    Route::get('/service-categories/{category}/services', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'categoryServices']);
    Route::get('/services/{service}', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'showService']);
    Route::post('/services/calculate-price', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'calculatePrice']);

    // Customer Bookings
    Route::prefix('bookings')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'createBooking']);
        Route::get('/', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'myBookings']);
        Route::get('/{booking}', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'showBooking']);
        Route::get('/{booking}/track', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'trackBooking']);
        Route::post('/{booking}/cancel', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'cancelBooking']);
        // Live Tracking - User อัพเดทตำแหน่งตัวเอง
        Route::post('/{booking}/update-location', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'userUpdateLocation']);
        // Live Tracking - ดึงตำแหน่งทั้งสองฝ่าย
        Route::get('/{booking}/live-tracking', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'liveTracking']);

        // Anti-Abuse APIs
        // Background Location Logging (Service Worker)
        Route::post('/{booking}/log-location', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'logLocation']);
        // ยกเลิกพร้อมค่าปรับ
        Route::post('/{booking}/cancel-with-penalty', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'cancelWithPenalty']);
        // ดูค่าปรับก่อนยกเลิก
        Route::get('/{booking}/cancellation-fee', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'previewCancellationFee']);
        // รายงานปัญหา/ร้องเรียน
        Route::post('/{booking}/report', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'reportIssue']);
        // ดูประวัติตำแหน่ง (หลักฐาน)
        Route::get('/{booking}/location-history', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'getLocationHistory']);
    });

    // User Trust Score & Protection
    Route::get('/user/trust-score', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'getUserTrustScore']);
    Route::post('/providers/{provider}/block', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'blockProvider']);

    // Provider APIs
    Route::prefix('provider/bookings')->group(function () {
        Route::post('/{booking}/accept', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'providerAccept']);
        Route::post('/{booking}/reject', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'providerReject']);
        Route::post('/{booking}/update-location', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'providerUpdateLocation']);
        // Provider tracking - ดูตำแหน่งลูกค้า
        Route::get('/{booking}/track', [\App\Http\Controllers\Api\V1\ServiceBookingController::class, 'providerTrackBooking']);
    });
});

/*
|--------------------------------------------------------------------------
| POS Terminal API Routes (สำหรับ POS Desktop App - MAUI)
|--------------------------------------------------------------------------
| ระบบ 2 กุญแจ:
| - Product Key: สร้างที่ POS device อัตโนมัติ
| - API Key: Admin ให้กับลูกค้า (ไม่ส่งกลับอัตโนมัติ)
|
| Flow:
| 1. POST /pos/register - ลงทะเบียน POS (ได้ terminal_id, รอ API Key)
| 2. POST /pos/verify - ยืนยันด้วย API Key
| 3. GET /pos/validate - ตรวจสอบสถานะก่อน sync
| 4. GET /pos/status - ตรวจสอบเป็นระยะว่าถูกบล็อกหรือไม่
*/
Route::prefix('pos')->name('api.pos.')->group(function () {
    // 🔔 Ping - ทดสอบการเชื่อมต่อ (Public)
    Route::get('/ping', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'ping'])
        ->name('ping');

    // 📝 ลงทะเบียน Device - สร้าง API Key อัตโนมัติ (Admin เห็นแต่ไม่ส่งกลับ)
    Route::post('/register-device', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'registerDevice'])
        ->name('register-device');

    // ⭐ Single-step activation (แนะนำ) - ลงทะเบียนและยืนยันในครั้งเดียว
    // ต้องระบุ: shop_code, api_key, product_key
    Route::post('/activate', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'activate'])
        ->name('activate');

    // (Legacy) ลงทะเบียน POS Terminal (Public - ไม่ต้อง auth)
    // ⚠️ ไม่ส่ง API Key กลับ! Admin ต้องให้ลูกค้าเอง
    Route::post('/register', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'register'])
        ->name('register');

    // (Legacy) ยืนยัน POS ด้วย API Key (Public)
    Route::post('/verify', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'verify'])
        ->name('verify');

    // ตรวจสอบสถานะ POS และ API Key (ต้องมี headers)
    // 🩹 (2026-05-08) action: validate → validateTerminal (PHP signature clash with Controller::validate)
    //   URL คงเดิม /validate เพื่อ backward-compat กับ POS app ที่ deploy แล้ว
    Route::get('/validate', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'validateTerminal'])
        ->name('validate');

    // ตรวจสอบสถานะเป็นระยะ (สำหรับแจ้งเตือนเมื่อถูกบล็อก)
    Route::get('/status', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'checkStatus'])
        ->name('status');

    // Health check (public)
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()]));

    // ======== Sync Routes (ต้องมี X-API-Key + X-Product-Key headers) ========
    // Sync สินค้าจาก Server
    Route::post('/sync/products', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'syncProducts'])
        ->name('sync.products');

    // Sync หมวดหมู่จาก Server
    Route::post('/sync/categories', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'syncCategories'])
        ->name('sync.categories');

    // อัพโหลดคำสั่งซื้อไป Server
    Route::post('/sync/orders', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'uploadOrders'])
        ->name('sync.orders');

    // รายงานยอดขายรายวัน
    Route::post('/report/sales', [\App\Http\Controllers\Api\V1\PosTerminalController::class, 'reportSales'])
        ->name('report.sales');
});

// SMS Payment Checker Routes
require __DIR__.'/sms_payment_api.php';

// ─── Juntra (จันทราพยากรณ์) server-to-server API ──────────────────────────────
// Consumed by the juntraweb backend (จันทรา.online), which proxies the Juntra
// Flutter app and authenticates here with a per-user thaiprompt_token (Sanctum).
// Every juntraweb client (FortuneBotClient, MlmApiClient, TarotImporter) calls
// these under /api/v1/juntra/* — so the group is mounted at 'v1/juntra'.
// (Was 'v1', which 404'd every client: chat silently fell back to its degraded
// pipeline and MLM downline returned empty. The Flutter app talks only to
// juntraweb, never here directly, so no mobile path depended on the old prefix.)
// All AI calls reuse the FortuneAIService key pool — Juntra holds NO provider keys.
Route::prefix('v1/juntra')->middleware('auth:sanctum')->name('api.juntra.')->group(function () {

    Route::prefix('fortune')->name('fortune.')->group(function () {
        Route::get('/categories', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'categories'])->name('categories');
        Route::get('/spreads',    [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'spreads'])->name('spreads');
        Route::get('/credits',    [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'credits'])->name('credits');
        Route::get('/history',    [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'history'])->name('history');
        Route::post('/draw',      [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'draw'])->name('draw');
        Route::post('/read',      [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'read'])->name('read');
        Route::get('/readings/{id}', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'show'])->name('show');

        // คำทำนายไพ่ให้เว็บ จันทรา.online — juntraweb เปิดไพ่เองแล้วส่งไพ่มาให้
        // อ่านด้วย "คลังความรู้ไพ่ + คีย์พูล" ชุดเดียวกับบอท FB/LINE
        // (juntraweb หักเครดิตลูกค้าไปแล้ว ที่นี่ไม่คิดเงินซ้ำ)
        // throttle: อ่านไพ่หนึ่งครั้งกิน token เยอะ — กันยิงรัว
        Route::post('/tarot/interpret', \App\Http\Controllers\Api\Juntra\TarotInterpretController::class)
            ->middleware('throttle:20,1')->name('tarot.interpret');

        // ดูดวงเชิงลึก 39฿ ให้เว็บจันทรา — ตัวเดียวกับ READING_TYPE_DEEP ของบอท
        // (juntraweb หักเครดิตแล้วก่อนเรียก ที่นี่ไม่คิดเงินซ้ำ)
        Route::post('/deep', \App\Http\Controllers\Api\Juntra\DeepReadingController::class)
            ->middleware('throttle:20,1')->name('deep');
    });

    Route::prefix('chat/mae-mor')->name('chat.')->group(function () {
        Route::post('/start',        [\App\Http\Controllers\Api\Juntra\ChatController::class, 'start'])->name('start');
        Route::post('/send',         [\App\Http\Controllers\Api\Juntra\ChatController::class, 'send'])->name('send');
        Route::get('/sessions/{id}', [\App\Http\Controllers\Api\Juntra\ChatController::class, 'show'])->name('show');
    });

    Route::prefix('natal')->name('natal.')->group(function () {
        Route::post('/compute',      [\App\Http\Controllers\Api\Juntra\NatalController::class, 'compute'])->name('compute');
        Route::get('/daily-transit', [\App\Http\Controllers\Api\Juntra\NatalController::class, 'dailyTransit'])->name('transit');
    });

    Route::prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/dashboard',   [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'dashboard'])->name('dashboard');
        Route::get('/downline',    [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'downline'])->name('downline');
        Route::get('/commissions', [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'commissions'])->name('commissions');
        Route::get('/link',        [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'link'])->name('link');
    });

    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/methods',     [\App\Http\Controllers\Api\Juntra\PaymentController::class, 'methods'])->name('methods');
        Route::post('/initiate',   [\App\Http\Controllers\Api\Juntra\PaymentController::class, 'initiate'])->name('initiate');
        Route::get('/{id}/status', [\App\Http\Controllers\Api\Juntra\PaymentController::class, 'status'])->name('status');

        // ตรวจสลิปให้เว็บ จันทรา.online ด้วย SlipOK ตัวเดียวกับบอท (โควตา +
        // flood guard ก้อนเดียวกัน) — ตรวจอย่างเดียว juntraweb ตัดสินใจเรื่อง
        // เงินเอง เพราะบิลของเว็บอยู่ในวอลเลตของเว็บ ไม่ใช่ FortuneReading
        // throttle ต่ำเพราะแต่ละครั้งกินโควตา SlipOK จริง
        Route::post('/verify-slip', \App\Http\Controllers\Api\Juntra\SlipVerifyController::class)
            ->middleware('throttle:10,1')->name('verify-slip');

        // บัญชีรับเงินของแม่หมอ — เว็บ จันทรา.online ดึงไปสร้าง QR ให้ลูกค้า
        // จะได้ใช้บัญชีเดียวกับบอท FB/LINE เสมอ (ตั้งค่าที่เดียว) และตัวตรวจสลิป
        // อัตโนมัติจับคู่ปลายทางได้ถูก
        Route::get('/account', \App\Http\Controllers\Api\Juntra\PayoutAccountController::class)
            ->middleware('throttle:60,1')->name('account');
    });

    // ─── Admin/full-tree MLM read API (consumed by จันทรา.online website) ──
    // Reads ONLY fortune_commissions (no marketplace/NFC/TPIX) and walks
    // mlm_members.unilevel_path for the full downline, not just direct
    // invitees. Admin role may pass ?user_id= to view any user's tree.
    Route::prefix('mlm')->name('mlm.')->group(function () {
        Route::get('/tree',        [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'tree'])->name('tree');
        Route::get('/commissions', [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'commissions'])->name('commissions');
        Route::get('/stats',       [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'stats'])->name('stats');
        Route::get('/users',       [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'users'])->name('users');
        // จันทรา.online/r/{member_code} attribution — enroll the caller under
        // the inviter. Throttled: enrolls are one-shot; 6/min absorbs retries.
        Route::post('/claim-referral', [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'claimReferral'])
            ->middleware('throttle:6,1')->name('claim');
    });
});

// ─── Public tarot-card catalog for the จันทรา.online (juntraweb) importer ──────
// Rotation-proof {name_en, image_url} source for all active cards. Intentionally
// PUBLIC (card art is already public; the importer carries no user token) and
// OUTSIDE the auth:sanctum group above. Throttled + cached as DoS defense.
// Path matches juntraweb TarotImporter exactly: GET /api/v1/juntra/tarot/cards.
Route::get('v1/juntra/tarot/cards', [\App\Http\Controllers\Api\Juntra\TarotCatalogController::class, 'cards'])
    ->middleware('throttle:60,1')
    ->name('api.juntra.tarot.cards');
