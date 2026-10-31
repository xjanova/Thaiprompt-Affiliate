<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\CarbonCreditController;
use App\Http\Controllers\Api\CertificationController;
use App\Http\Controllers\Api\CoinExchangeController;
use App\Http\Controllers\Api\CryptoWalletApiController;
use App\Http\Controllers\Api\FoodPassportController;
use App\Http\Controllers\Api\NFCCardApiController;
use App\Http\Controllers\Api\NFCPairingApiController;
use App\Http\Controllers\Api\QualityController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\Api\TraceabilityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TranslateController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\Api\VideoQuestController;
use App\Http\Controllers\Api\VideoRewardController;
use App\Http\Controllers\Api\VideoWatchController;
use App\Http\Controllers\LineWebhookController;
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

// 🪪 (2026-10-09) ผล KYC สำหรับแอปพันธมิตร (TPIX TRADE) — GET /api/oauth/kyc
//    ต้องมี scope `kyc` ที่ลูกค้ากดอนุญาตเองบนหน้า consent · ส่งแค่สถานะ ไม่มีข้อมูลบัตร
//    (ดู OAuthKycStatusController) · TPIX ถามซ้ำเป็นระยะระหว่างรอลูกค้าทำ eKYC ในแอป
Route::middleware(['auth:api-oauth', 'scopes:kyc', 'throttle:60,1,oauth-kyc'])
    ->get('/oauth/kyc', [\App\Http\Controllers\Api\OAuthKycStatusController::class, 'show'])
    ->name('api.oauth.kyc');

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
// 🚦 (2026-09-25) CC-03: throttle:api ทั้งกลุ่ม (limiter 'api' นิยามใน bootstrap/app.php →booted())
//    ล็อกอินแล้วนับต่อผู้ใช้ · ยังไม่ล็อกอินนับต่อ IP — ห้ามย้าย webhook เข้ามาในกลุ่มนี้
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Public routes
    // 🔒 (2026-09-25) เดิมไม่มี throttle เลย = ทางเลี่ยง brute force ของหน้าเว็บ
    //    throttle.login (ล็อก IP/อีเมลหลังผิด 5 ครั้ง ตาม config/ratelimit.php) + ไม่เกิน 6 ครั้ง/นาที/IP
    // 🚦 throttle แบบตัวเลขต้องมี prefix (พารามิเตอร์ที่ 3) เสมอ — ไม่งั้นทุก route ใช้ตัวนับเดียวกันต่อผู้ใช้/IP
    //    (ThrottleRequests ใช้ key = prefix + sha1(user id หรือ IP)) → เรียก route หนึ่งแล้วอีก route โดน 429
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['throttle.login', 'throttle:6,1,api-login']);

    // Register (public - for mobile app)
    Route::post('/register', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'register'])
        ->middleware('throttle:6,1,api-register');

    // LINE Login สำหรับ Mobile App (public)
    Route::prefix('auth/line')->group(function () {
        Route::get('/status', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'lineStatus']);
        Route::get('/mobile-url', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getLineLoginUrl']);
        Route::post('/mobile-callback', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'lineLoginCallback']);
    });

    // 🪙 (2026-10-13) เหรียญ TPIX — ราคาจริงจากพูล DEX + สถานะ/ส่งเลขธุรกรรมของใบเสนอยอด (public: ใช้รหัสอ้างอิงสุ่ม 24 ตัว)
    //    เริ่มจ่ายผ่านเส้นเดิม: /cart/checkout · /payment/order · /wallet/topup ด้วย payment_method=tpix
    Route::prefix('tpix')->group(function () {
        Route::get('/price', [\App\Http\Controllers\Api\V1\TpixPayApiController::class, 'price'])
            ->middleware('throttle:60,1,api-tpix-price')->name('api.v1.tpix.price');
        Route::get('/payments/{reference}', [\App\Http\Controllers\Api\V1\TpixPayApiController::class, 'show'])
            ->middleware('throttle:40,1,api-tpix-status')->name('api.v1.tpix.payments.show');
        Route::post('/payments/{reference}/submit', [\App\Http\Controllers\Api\V1\TpixPayApiController::class, 'submit'])
            ->middleware('throttle:12,1,api-tpix-submit')->name('api.v1.tpix.payments.submit');
    });

    // 📲 เช็คอัปเดตแอป (APK จากเซิร์ฟเวอร์เรา) — public: แอปเช็คได้ตั้งแต่ยังไม่ล็อกอิน
    Route::get('/app/update', [\App\Http\Controllers\Api\V1\AppUpdateController::class, 'check'])
        ->middleware('throttle:30,1,api-app-update')
        ->name('api.v1.app.update');

    // 📱 ปุ่มเข้าสู่ระบบด้วย LINE / Facebook / Google ที่แอปควรแสดง (เปิดใช้ + ตั้งค่าครบ) — public
    Route::get('/auth/social/status', [\App\Http\Controllers\Api\V1\MobileAuthController::class, 'socialStatus'])
        ->name('api.v1.auth.social.status');

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

    // 🖼️ (2026-09-26) แบนเนอร์แคมเปญแอป (public) — แอดมินจัดการที่ /admin/app-banners
    //    GET ?placement=home|taladsod|rider|merchant|shop&audience=buyer|rider|merchant (cache 5 นาที, รูปเป็น absolute URL)
    //    ของเดิม /mobile/banners (ต้องล็อกอิน) ยังใช้ได้สำหรับแอปรุ่นเก่า
    Route::get('/banners', [\App\Http\Controllers\Api\V1\AppBannerApiController::class, 'index'])
        ->name('api.v1.banners.index');
    Route::post('/banners/impressions', [\App\Http\Controllers\Api\V1\AppBannerApiController::class, 'impressions'])
        ->middleware('throttle:30,1,api-banner-impressions')
        ->name('api.v1.banners.impressions');
    Route::post('/banners/{id}/click', [\App\Http\Controllers\Api\V1\AppBannerApiController::class, 'click'])
        ->whereNumber('id')
        ->middleware('throttle:30,1,api-banner-click')
        ->name('api.v1.banners.click');

    // App Configuration (public)
    Route::prefix('app')->group(function () {
        Route::get('/maintenance-status', [AppConfigController::class, 'maintenanceStatus']);

        // NOTE: the previous Emergency Alert Banner public routes referenced
        // App\Http\Controllers\Admin\AppBannerController which never landed in
        // the codebase (only the AppBanner model exists). They were silently
        // overridden by the legacy banner routes inside the protected v1
        // group below, so removing them is a no-op for live behavior — just
        // unbreaks `php artisan route:list`.
    });

    // 🔐 (2026-09-25) ไฟล์เอกสารไรเดอร์ของตัวเอง — เปิดได้เฉพาะ signed URL อายุ 30 นาที
    //    (สร้างจาก GET /rider/status และ /rider/documents เท่านั้น — ไฟล์อยู่บน private disk)
    Route::get('/rider/documents/file/{rider}/{type}', [\App\Http\Controllers\Api\V1\RiderApiController::class, 'documentFile'])
        ->middleware(['signed', 'throttle:60,1,api-rider-doc-file'])
        ->whereNumber('rider')
        ->where('type', 'id_card|driver_license|vehicle_registration|profile')
        ->name('api.v1.rider.documents.file');

    // 🔒 (2026-10-29) รูปเอกสาร KYC แบบเดิมของตัวเอง (บัตร/คู่บัตร) — เปิดได้เฉพาะ signed URL อายุ 30 นาที
    //    สร้างจาก POST /kyc/upload เท่านั้น (KycDocumentStorage::signedUrl) · ไฟล์อยู่บน private disk
    Route::get('/kyc/image/{kycVerification}/{kind}', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'kycImageFile'])
        ->middleware(['signed', 'throttle:120,1,api-kyc-image'])
        ->whereNumber('kycVerification')
        ->whereIn('kind', array_keys(\App\Services\Kyc\KycDocumentStorage::KINDS))
        ->name('api.v1.kyc.image');

    // 📸 (2026-10-04) ไรเดอร์รอบ 2: รูปโปรไฟล์พร้อมลายน้ำรหัสผู้ดู — เปิดได้เฉพาะ signed URL (~60 นาที)
    //    สร้างจาก ProfilePhotoService::urlFor() เท่านั้น · ไฟล์ต้นฉบับอยู่บน private disk ไม่มีทางเปิดตรง
    //    {viewer} = รหัส HMAC 6 ตัว (ไม่ใช่ user id) · {version} = เปลี่ยนทุกครั้งที่ถ่ายรูปใหม่
    Route::get('/media/profile-photo/{subject}/{viewer}/{version}', [\App\Http\Controllers\Api\V1\ProfilePhotoApiController::class, 'file'])
        ->middleware(['signed', 'throttle:240,1,api-profile-photo-file'])
        ->whereNumber('subject')
        ->where('viewer', '[A-Z0-9]{6}')
        ->where('version', '[a-f0-9]{12}')
        ->name('api.v1.media.profile-photo');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // 🗑️ (2026-09-25) PLAY-05: ลบบัญชีจากแอป (ปกปิด PII + soft delete + เพิกถอน token)
        Route::get('/account/deletion-check', [\App\Http\Controllers\Api\V1\AccountController::class, 'deletionCheck'])
            ->name('api.v1.account.deletion-check');
        Route::delete('/account', [\App\Http\Controllers\Api\V1\AccountController::class, 'destroy'])
            ->middleware('throttle:5,1,api-account-delete')
            ->name('api.v1.account.destroy');
        Route::post('/account/delete', [\App\Http\Controllers\Api\V1\AccountController::class, 'destroy'])
            ->middleware('throttle:5,1,api-account-delete')
            ->name('api.v1.account.delete');
        // 🔒 (2026-10-29) รหัสยืนยันการลบทางอีเมล (บัญชีที่ไม่รู้รหัสผ่าน)
        Route::post('/account/delete/send-code', [\App\Http\Controllers\Api\V1\AccountController::class, 'sendDeletionCode'])
            ->middleware('throttle:3,10,api-account-delete-code')
            ->name('api.v1.account.delete.send-code');

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

        // 🛒 (2026-09-25) Workstream D: สินค้า/ตะกร้า/checkout ย้ายไป MobileShopController
        //    ตะกร้าใน DB = แหล่งข้อมูลเดียวของแอป · checkout เส้นเดียว (ShopCheckoutService)
        // Products (Mobile App E-commerce)
        Route::prefix('products')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'products'])->name('api.v1.products.index');
            Route::get('/categories', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'categories'])->name('api.v1.products.categories');
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'product'])->whereNumber('id')->name('api.v1.products.show');
        });

        // Cart (Mobile App) - คำนวณทุกอย่างที่ server
        Route::prefix('cart')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'cart'])->name('api.v1.cart.show');
            Route::delete('/', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'clear'])->name('api.v1.cart.clear');
            Route::post('/items', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'addItem'])->middleware('throttle:60,1,api-cart-add')->name('api.v1.cart.items.store');
            Route::put('/items/{itemId}', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'updateItem'])->whereNumber('itemId')->name('api.v1.cart.items.update');
            Route::delete('/items/{itemId}', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'removeItem'])->whereNumber('itemId')->name('api.v1.cart.items.destroy');
            Route::post('/promo', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'applyPromo'])->middleware('throttle:20,1,api-cart-promo')->name('api.v1.cart.promo');
            Route::post('/checkout', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'checkout'])->middleware(['throttle:10,1,api-cart-checkout', 'profile.photo', 'ekyc.verified'])->name('api.v1.cart.checkout');
            // เส้นทางเดิมของแอปรุ่นก่อน (ชี้ไปตัวเดียวกัน — ใช้ตัวนับเดียวกับ /items)
            Route::post('/add', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'addItem'])->middleware('throttle:60,1,api-cart-add')->name('api.v1.cart.add');
            Route::delete('/clear', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'clear'])->name('api.v1.cart.clear-legacy');
        });

        // 📍 ที่อยู่จัดส่ง (Mobile App) — SHOP-08
        Route::prefix('addresses')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\AddressApiController::class, 'index'])->name('api.v1.addresses.index');
            Route::post('/', [\App\Http\Controllers\Api\V1\AddressApiController::class, 'store'])->middleware('throttle:20,1,api-address-store')->name('api.v1.addresses.store');
            Route::put('/{id}', [\App\Http\Controllers\Api\V1\AddressApiController::class, 'update'])->whereNumber('id')->name('api.v1.addresses.update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\V1\AddressApiController::class, 'destroy'])->whereNumber('id')->name('api.v1.addresses.destroy');
            Route::post('/{id}/default', [\App\Http\Controllers\Api\V1\AddressApiController::class, 'setDefault'])->whereNumber('id')->name('api.v1.addresses.default');
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

            // 💸 (2026-09-25) CC-06: ถอนเงิน + บัญชีรับเงิน + PIN กระเป๋า (WithdrawalService ตัวเดียวกับเว็บ)
            Route::get('/withdraw/info', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'info'])
                ->name('api.v1.wallet.withdraw.info');
            Route::get('/withdraw/preview', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'preview'])
                ->middleware('throttle:30,1,api-withdraw-preview')
                ->name('api.v1.wallet.withdraw.preview');
            Route::post('/withdraw', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'withdraw'])
                ->middleware(['throttle:5,1,api-withdraw', 'idempotency'])
                ->name('api.v1.wallet.withdraw');
            Route::get('/withdrawals', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'history'])
                ->name('api.v1.wallet.withdrawals');
            Route::post('/withdrawals/{id}/cancel', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'cancel'])
                ->whereNumber('id')
                ->middleware('throttle:10,1,api-withdraw-cancel')
                ->name('api.v1.wallet.withdrawals.cancel');
            Route::get('/bank-accounts', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'bankAccounts'])
                ->name('api.v1.wallet.bank-accounts');
            Route::post('/bank-accounts', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'storeBankAccount'])
                ->middleware('throttle:10,1,api-bank-account-store')
                ->name('api.v1.wallet.bank-accounts.store');
            Route::delete('/bank-accounts/{id}', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'destroyBankAccount'])
                ->whereNumber('id')
                ->middleware('throttle:10,1,api-bank-account-delete')
                ->name('api.v1.wallet.bank-accounts.destroy');
            Route::post('/bank-accounts/{id}/delete', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'destroyBankAccount'])
                ->whereNumber('id')
                ->middleware('throttle:10,1,api-bank-account-delete')
                ->name('api.v1.wallet.bank-accounts.delete');
            Route::post('/bank-accounts/{id}/default', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'setDefaultBankAccount'])
                ->whereNumber('id')
                ->middleware('throttle:20,1,api-bank-account-default')
                ->name('api.v1.wallet.bank-accounts.default');
            Route::post('/pin', [\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, 'setPin'])
                ->middleware('throttle:5,1,api-wallet-pin')
                ->name('api.v1.wallet.pin');
        });

        // Orders (Mobile App)
        // 🛒 (2026-09-25) SHOP-28: ลบ POST /orders (สร้างออเดอร์เส้นที่สอง) — สร้างผ่าน POST /cart/checkout เท่านั้น
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'index']);
            Route::get('/unread-messages', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'getUnreadMessageCount']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'show'])->whereNumber('id');
            Route::post('/{id}/cancel', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'cancel'])->whereNumber('id')->middleware('throttle:10,1,api-order-cancel');
            // CC-20: ยืนยันรับสินค้า + รีวิวสินค้าในออเดอร์
            Route::post('/{id}/confirm-received', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'confirmReceived'])
                ->whereNumber('id')->name('api.v1.orders.confirm-received');
            Route::post('/{orderId}/items/{itemId}/review', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'review'])
                ->whereNumber('orderId')->whereNumber('itemId')->middleware('throttle:10,1,api-order-review')->name('api.v1.orders.items.review');
            // Order Tracking
            Route::get('/{id}/tracking', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'getTracking'])->whereNumber('id');
            // Order Chat
            Route::get('/{id}/messages', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'getMessages'])->whereNumber('id');
            Route::post('/{id}/messages', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'sendMessage'])->whereNumber('id');
        });

        // 🛵 (2026-09-26) ผู้ซื้อติดตามไรเดอร์ของออเดอร์ตัวเอง + แชร์ตำแหน่งตัวเองให้ไรเดอร์ (ยินยอมทั้งสองฝ่าย)
        //    source = shop (ร้านค้า) | fresh-market (ตลาดสด) · ออเดอร์คนอื่น = 404 · งานจบแล้วไม่มีพิกัดไรเดอร์
        Route::get('/orders/{source}/{id}/rider-location', [\App\Http\Controllers\Api\V1\DeliveryTrackingApiController::class, 'riderLocation'])
            ->where('source', 'shop|fresh-market')
            ->whereNumber('id')
            ->middleware('throttle:60,1,api-order-rider-location')
            ->name('api.v1.orders.rider-location');
        Route::post('/orders/{source}/{id}/share-location', [\App\Http\Controllers\Api\V1\DeliveryTrackingApiController::class, 'shareLocation'])
            ->where('source', 'shop|fresh-market')
            ->whereNumber('id')
            ->middleware('throttle:20,1,api-order-share-location')
            ->name('api.v1.orders.share-location');

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

        // Rider (Mobile App) — 🏍️ (2026-09-25) ย้ายทั้งชุดไป RiderApiController
        //    ทุกการเปลี่ยนสถานะงานผ่าน RiderJobService (race-safe + state machine)
        //    throttle เฉพาะเส้นที่เขียนข้อมูล (ทั้งกลุ่มมี throttle:api อยู่แล้ว)
        Route::prefix('rider')->name('api.v1.rider.')->controller(\App\Http\Controllers\Api\V1\RiderApiController::class)->group(function () {
            Route::get('/status', 'status')->name('status');
            Route::post('/register', 'register')->middleware(['throttle:6,1,api-rider-register', 'ekyc.verified'])->name('register');
            Route::post('/document', 'uploadDocument')->middleware('throttle:20,1,api-rider-document')->name('document');
            Route::get('/documents', 'documents')->name('documents');
            Route::post('/permissions', 'permissions')->middleware('throttle:30,1,api-rider-permissions')->name('permissions');
            // (2026-09-26) ยินยอมให้ลูกค้าเห็นตำแหน่งระหว่างส่งงาน — ต้องทำ 1 ครั้งก่อนรับงานแรก
            Route::post('/consent', 'consent')->middleware('throttle:20,1,api-rider-consent')->name('consent');
            Route::put('/profile', 'updateProfile')->middleware('throttle:20,1,api-rider-profile')->name('profile');
            // 🪪 (2026-10-04) ด่าน eKYC บังคับเฉพาะตอนเปิดรับงาน — ปิดรับงาน (offline) ต้องทำได้เสมอ
            // 📲 (2026-10-13) เปิดรับงาน/รับงาน/ทำงานส่ง ต้องมาจากแอป X-App-Build ≥ config('rider.min_app_build') — ไม่งั้น 426 APP_UPDATE_REQUIRED
            //    (owner: ไรเดอร์รับงานผ่านแอปเท่านั้น · อ่านอย่างเดียว/สมัคร/เอกสาร/ตั้งค่า/ส่งตำแหน่ง ไม่โดนด่านนี้)
            Route::post('/availability', 'availability')->middleware(['throttle:20,1,api-rider-availability', \App\Http\Middleware\EnsureRiderAppBuild::class.':availability=online', 'profile.photo:availability=online', 'ekyc.verified:availability=online'])->name('availability');
            Route::post('/location', 'location')->middleware('throttle:40,1,api-rider-location')->name('location');
            Route::get('/earnings', 'earnings')->name('earnings');

            Route::get('/jobs/available', 'availableJobs')->name('jobs.available');
            Route::get('/jobs/current', 'currentJob')->name('jobs.current');
            Route::get('/jobs/history', 'history')->name('jobs.history');
            Route::get('/jobs/{id}', 'showJob')->whereNumber('id')->name('jobs.show');
            Route::post('/jobs/{id}/accept', 'accept')->whereNumber('id')->middleware(['throttle:30,1,api-rider-accept', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.accept');
            Route::post('/jobs/{id}/reject', 'reject')->whereNumber('id')->middleware(['throttle:30,1,api-rider-reject', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.reject');
            Route::post('/jobs/{id}/release', 'release')->whereNumber('id')->middleware(['throttle:10,1,api-rider-release', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.release');
            Route::post('/jobs/{id}/status', 'updateStatus')->whereNumber('id')->middleware(['throttle:30,1,api-rider-status', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.status');
            Route::post('/jobs/{id}/deliver', 'deliver')->whereNumber('id')->middleware(['throttle:20,1,api-rider-deliver', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.deliver');
            Route::post('/jobs/{id}/fail', 'fail')->whereNumber('id')->middleware(['throttle:10,1,api-rider-fail', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.fail');
            Route::post('/jobs/{id}/gps-lost', 'gpsLost')->whereNumber('id')->middleware(['throttle:20,1,api-rider-gps-lost', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.gps-lost');
            Route::post('/jobs/{id}/gps-off', 'gpsOff')->whereNumber('id')->middleware(['throttle:10,1,api-rider-gps-off', \App\Http\Middleware\EnsureRiderAppBuild::class])->name('jobs.gps-off');
        });

        // Web Session (สำหรับเปิดหน้าเว็บจาก Mobile App พร้อม authentication)
        Route::post('/web-session', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'generateWebSessionToken'])
            ->middleware('throttle:20,1,api-web-session')
            ->name('api.v1.web-session');

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
            // 🔔 (2026-09-25) CC-21: ถอด token ตอน logout แบบ POST (client ที่ส่ง body กับ DELETE ไม่ได้)
            Route::post('/token/remove', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'removePushToken'])
                ->name('api.v1.push.token.remove');
        });

        // GPS Sharing (Mobile App) - แชร์ตำแหน่งให้ Admin ดู GPS Monitor
        Route::prefix('mobile/gps')->group(function () {
            Route::post('/share', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'shareGpsLocation']);
            Route::post('/stop', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'stopGpsSharing']);
        });

        // Store Listing (Mobile App) - รายการร้านค้า (ตัวเลขจริง ไม่มีเรตติ้ง/อัตราตอบกลับสมมติ — SHOP-17/18)
        Route::prefix('mobile/stores')->group(function () {
            Route::get('/official', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'officialStores']);
            Route::get('/featured', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'featuredStores']);
            Route::get('/{storeId}', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'store'])->whereNumber('storeId');
            Route::get('/{storeId}/products', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'storeProducts'])->whereNumber('storeId');
        });

        // Premium Store (Mobile App) - ร้านพรีเมี่ยม (Official Shop)
        Route::prefix('mobile/premium-store')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'premiumStore']);
            Route::get('/products', [\App\Http\Controllers\Api\V1\MobileShopController::class, 'premiumStoreProducts']);
        });

        // 🗑️ (2026-10-13) mobile/academy/* (ข้อมูลหลักสูตรปลอม) ลบไปพร้อม Academy เดิมแล้ว

        // Watch & Earn (Mobile App) - ดูคลิปได้เงิน
        Route::prefix('mobile/watch-earn')->group(function () {
            Route::get('/videos', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getVideos']);
            Route::get('/earnings', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'getVideoEarnings']);
            Route::post('/submit', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'submitVideoWatch']);
        });

        // Seller Order Management (Mobile App) - จัดการ Orders สำหรับผู้ขาย
        // 🛒 (2026-09-25) SHOP-13 / SELLER-14: ร้านเห็น/แก้ได้เฉพาะออเดอร์ที่มีสินค้าของตัวเอง
        Route::get('/seller/summary', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'summary'])->name('api.v1.seller.summary');
        Route::prefix('seller/orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'index'])->name('api.v1.seller.orders.index');
            Route::get('/{orderId}', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'show'])->whereNumber('orderId')->name('api.v1.seller.orders.show');
            Route::post('/{orderId}/action', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'action'])
                ->whereNumber('orderId')->middleware('throttle:30,1,api-seller-order-action')->name('api.v1.seller.orders.action');
            Route::post('/{orderId}/tracking', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'updateTracking'])->whereNumber('orderId');
            Route::post('/{orderId}/tracking-history', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'addTrackingHistory'])->whereNumber('orderId');
        });

        // ===== แชทออเดอร์ฝั่งผู้ขาย (แอป) =====
        // 💬 (2026-09-26) ร้านคุยกับลูกค้าในออเดอร์ (order_messages) — ออเดอร์ร้านอื่น = 404 · แจ้งลูกค้าผ่าน push แอป ไม่ใช้ LINE
        Route::prefix('seller/orders/{orderId}/messages')->whereNumber('orderId')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'messages'])
                ->middleware('throttle:60,1,api-seller-order-messages')->name('api.v1.seller.orders.messages');
            Route::post('/', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'sendMessage'])
                ->middleware('throttle:20,1,api-seller-order-message-send')->name('api.v1.seller.orders.messages.send');
            Route::post('/read', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'markMessagesRead'])
                ->middleware('throttle:60,1,api-seller-order-message-read')->name('api.v1.seller.orders.messages.read');
        });

        // Shipping Providers (Mobile App) - รายการบริษัทขนส่ง
        Route::get('/seller/shipping-providers', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'shippingProviders']);

        // ===== ผู้ขาย: จัดการสินค้า + สมัครผู้ขาย (แอป) =====
        // 🏪 (2026-09-26) แทนการเด้งไปเว็บ /user/seller-apply · /seller/products · /seller/store/settings
        //    กติกาเดียวกับเว็บ (SellerApplicationService / SellerProductService / SellerStoreSettingsService)
        //    ด่าน SellerPanelGate = role seller + KYC + ร้านเปิดอยู่ + มีแพ็กเกจ · แตะได้เฉพาะของตัวเอง (ของคนอื่น = 404)
        Route::prefix('seller')->name('api.v1.seller.')->group(function () {
            Route::get('/application', [\App\Http\Controllers\Api\V1\SellerApplicationApiController::class, 'show'])->name('application.show');
            Route::post('/application', [\App\Http\Controllers\Api\V1\SellerApplicationApiController::class, 'store'])
                ->middleware(['throttle:10,1,api-seller-apply', 'profile.photo', 'ekyc.verified'])->name('application.store');

            Route::get('/store', [\App\Http\Controllers\Api\V1\SellerStoreApiController::class, 'show'])->name('store.show');
            Route::put('/store', [\App\Http\Controllers\Api\V1\SellerStoreApiController::class, 'update'])
                ->middleware('throttle:30,1,api-seller-store-update')->name('store.update');
            Route::post('/store/logo', [\App\Http\Controllers\Api\V1\SellerStoreApiController::class, 'uploadLogo'])
                ->middleware('throttle:10,1,api-seller-store-image')->name('store.logo');
            Route::post('/store/banner', [\App\Http\Controllers\Api\V1\SellerStoreApiController::class, 'uploadBanner'])
                ->middleware('throttle:10,1,api-seller-store-image')->name('store.banner');

            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'index'])->name('index');
                Route::get('/meta', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'meta'])->name('meta');
                Route::post('/quote', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'quote'])
                    ->middleware('throttle:120,1,api-seller-product-quote')->name('quote');
                Route::post('/', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'store'])
                    ->middleware('throttle:20,1,api-seller-product-create')->name('store');
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'show'])->whereNumber('id')->name('show');
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'update'])
                    ->whereNumber('id')->middleware('throttle:30,1,api-seller-product-write')->name('update');
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'destroy'])
                    ->whereNumber('id')->middleware('throttle:30,1,api-seller-product-write')->name('destroy');
                Route::post('/{id}/active', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'setActive'])
                    ->whereNumber('id')->middleware('throttle:60,1,api-seller-product-toggle')->name('active');
                Route::post('/{id}/stock', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'updateStock'])
                    ->whereNumber('id')->middleware('throttle:60,1,api-seller-product-toggle')->name('stock');
                Route::post('/{id}/images', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'addImages'])
                    ->whereNumber('id')->middleware('throttle:20,1,api-seller-product-images')->name('images.store');
                Route::post('/{id}/images/main', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'setMainImage'])
                    ->whereNumber('id')->middleware('throttle:30,1,api-seller-product-write')->name('images.main');
                Route::post('/{id}/images/order', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'reorderImages'])
                    ->whereNumber('id')->middleware('throttle:30,1,api-seller-product-write')->name('images.order');
                Route::delete('/{id}/images/{imageId}', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'deleteImage'])
                    ->whereNumber(['id', 'imageId'])->middleware('throttle:30,1,api-seller-product-write')->name('images.destroy');
            });
        });

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
        // 🔔 (2026-09-25) CC-21: แอปเรียก DELETE /mobile/push-token ตอน logout มานานแล้วแต่ไม่มี route (404)
        //    → ใช้ตัวเดียวกับ DELETE /push/token (ถอดทั้ง mobile_devices + user_notification_tokens)
        Route::delete('mobile/push-token', [\App\Http\Controllers\Api\V1\MobileApiController::class, 'removePushToken'])
            ->name('api.v1.mobile.push-token.remove');
        // 🔔 (2026-10-13) ปุ่ม "ทดสอบ" ในหน้าตั้งค่าแจ้งเตือน — ยิง push จริงจากเซิร์ฟเวอร์ไปทุกเครื่องของผู้ใช้
        //    (เดิมแอปยิงแจ้งเตือนในเครื่องเอง → ดูเหมือนใช้ได้ทั้งที่เซิร์ฟเวอร์ส่งไม่ถึง)
        Route::post('mobile/push-test', [\App\Http\Controllers\Api\V1\MobileDeviceController::class, 'sendTestPush'])
            ->middleware('throttle:3,1,api-push-test')
            ->name('api.v1.mobile.push-test');

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
        // คง theme/settings/features ไว้เผื่อเครื่องมือหลังบ้านบนเว็บยังเรียกอยู่
        Route::prefix('app')->group(function () {
            Route::get('/settings', [AppConfigController::class, 'settings']);
            Route::get('/theme', [AppConfigController::class, 'theme']);
            Route::get('/complete-theme', [AppConfigController::class, 'completeTheme']);
            Route::get('/control-sections', [AppConfigController::class, 'controlSections']);
            Route::get('/component-settings', [AppConfigController::class, 'componentSettings']);
            Route::get('/features', [AppConfigController::class, 'features']);
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
        Route::get('/config', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'config'])->name('config');
        Route::get('/categories', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'categories'])->name('categories');
        Route::get('/listings', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'listings'])->name('listings');
        Route::get('/listings/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'showListing'])->whereNumber('id')->name('listings.show');
        Route::get('/nearby', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'nearby'])->name('nearby');
        Route::get('/sellers/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'showSeller'])->whereNumber('id')->name('sellers.show');
        // (2026-09-26) หน้าร้าน + สถานะเปิด/ปิด + ตำแหน่งร้านสด (รถเข็น/ตลาดนัด — ตำแหน่งเฉพาะตอนร้านเปิด)
        // (2026-09-26) ร้านที่เปิดอยู่ใกล้คุณ (รถเข็น/ตลาดนัด = ตำแหน่งตอนนี้) — หน้าแรกตลาดสด
        Route::get('/shops/nearby', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'nearby'])
            ->middleware('throttle:60,1,api-fm-shops-nearby')
            ->name('shops.nearby');
        Route::get('/shops/{id}', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'show'])->whereNumber('id')->name('shops.show');
        Route::get('/shops/{id}/location', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'location'])
            ->whereNumber('id')
            ->middleware('throttle:120,1,api-fm-shop-location')
            ->name('shops.location');

        // Auth endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/listings', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'storeListing'])->name('listings.store');
            Route::put('/listings/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'updateListing'])->whereNumber('id')->name('listings.update');
            Route::get('/delivery-quote', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'deliveryQuote'])->middleware('throttle:30,1,api-fm-delivery-quote')->name('delivery-quote');

            // ผู้ซื้อ
            Route::post('/orders', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'storeOrder'])->middleware(['throttle:10,1,api-fm-order-store', 'profile.photo', 'ekyc.verified'])->name('orders.store');
            Route::get('/orders', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'orders'])->name('orders');
            Route::get('/orders/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'showOrder'])->whereNumber('id')->name('orders.show');
            Route::put('/orders/{id}/status', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'updateOrderStatus'])->whereNumber('id')->name('orders.status');
            Route::post('/orders/{id}/cancel', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'cancelOrder'])->whereNumber('id')->name('orders.cancel');
            Route::post('/orders/{id}/confirm', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'confirmOrder'])->whereNumber('id')->name('orders.confirm');
            Route::post('/orders/{id}/review', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'reviewOrder'])->whereNumber('id')->name('orders.review');

            // ผู้ขาย
            // 🪪 (2026-10-13) สมัครร้านตลาดสดได้ทุกช่องทางโดยไม่ต้องยืนยันตัวตนก่อน (เจ้าของสั่ง)
            //    แต่ร้าน "ขายได้" เมื่อเจ้าของร้านยืนยันตัวตนแล้ว — ดู FreshMarketSeller::initialVerifiedFor / promoteAfterOwnerKyc
            Route::post('/seller/register', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'registerSeller'])->name('seller.register');
            Route::get('/seller/profile', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'sellerProfile'])->name('seller.profile');
            Route::put('/seller/profile', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'updateSellerProfile'])->name('seller.profile.update');
            Route::post('/seller/subscribe', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'subscribe'])->name('seller.subscribe');
            Route::get('/seller/orders', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'sellerOrders'])->name('seller.orders');
            Route::get('/seller/orders/{id}', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'sellerOrderShow'])->whereNumber('id')->name('seller.orders.show');
            Route::post('/seller/orders/{id}/action', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'sellerOrderAction'])->whereNumber('id')->name('seller.orders.action');
            Route::get('/seller/dashboard', [\App\Http\Controllers\Api\V1\FreshMarketApiController::class, 'sellerDashboard'])->name('seller.dashboard');

            // (2026-09-26) ร้านรถเข็น/ตลาดนัด: เปิดร้านที่นี่วันนี้ / ตำแหน่งสด / ปิดร้าน + ผู้ซื้อติดตามร้าน
            Route::get('/seller/presence', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'sellerPresence'])->name('seller.presence');
            Route::post('/seller/open', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'open'])
                ->middleware('throttle:10,1,api-fm-seller-open')
                ->name('seller.open');
            Route::post('/seller/location', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'sellerLocation'])
                ->middleware('throttle:6,1,api-fm-seller-location')
                ->name('seller.location');
            Route::post('/seller/close', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'close'])
                ->middleware('throttle:10,1,api-fm-seller-close')
                ->name('seller.close');
            Route::post('/shops/{id}/follow', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'follow'])
                ->whereNumber('id')
                ->middleware('throttle:30,1,api-fm-shop-follow')
                ->name('shops.follow');
            Route::delete('/shops/{id}/follow', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'unfollow'])
                ->whereNumber('id')
                ->middleware('throttle:30,1,api-fm-shop-follow')
                ->name('shops.unfollow');
            Route::get('/me/followed-shops', [\App\Http\Controllers\Api\V1\FreshMarketShopApiController::class, 'followedShops'])->name('me.followed-shops');

            // (2026-09-26) ตะกร้าหลายรายการ (แยกตามร้าน) — ชำระผ่าน POST /orders {seller_id}
            Route::get('/cart', [\App\Http\Controllers\Api\V1\FreshMarketCartApiController::class, 'show'])->name('cart');
            Route::delete('/cart', [\App\Http\Controllers\Api\V1\FreshMarketCartApiController::class, 'clear'])->name('cart.clear');
            Route::get('/cart/quote', [\App\Http\Controllers\Api\V1\FreshMarketCartApiController::class, 'quote'])->middleware('throttle:30,1,api-fm-cart-quote')->name('cart.quote');
            Route::post('/cart/items', [\App\Http\Controllers\Api\V1\FreshMarketCartApiController::class, 'store'])->middleware('throttle:60,1,api-fm-cart-add')->name('cart.items.store');
            Route::put('/cart/items/{id}', [\App\Http\Controllers\Api\V1\FreshMarketCartApiController::class, 'update'])->whereNumber('id')->name('cart.items.update');
            Route::delete('/cart/items/{id}', [\App\Http\Controllers\Api\V1\FreshMarketCartApiController::class, 'destroy'])->whereNumber('id')->name('cart.items.destroy');

            // (2026-09-26) ร้านจัดการสินค้า + กลุ่มตัวเลือก/ตัวเลือก + รูป
            Route::get('/seller/listings', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'index'])->name('seller.listings');
            Route::get('/seller/listings/{id}', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'show'])->whereNumber('id')->name('seller.listings.show');
            Route::delete('/listings/{id}', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'destroyListing'])->whereNumber('id')->name('listings.destroy');
            Route::post('/listings/{id}/images', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'addImages'])->whereNumber('id')->name('listings.images.store');
            Route::delete('/listings/{id}/images', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'removeImage'])->whereNumber('id')->name('listings.images.destroy');
            Route::post('/listings/{id}/images/main', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'setMainImage'])->whereNumber('id')->name('listings.images.main');
            Route::get('/listings/{id}/option-groups', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'optionGroups'])->whereNumber('id')->name('listings.option-groups');
            Route::put('/listings/{id}/option-groups', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'syncOptionGroups'])->whereNumber('id')->name('listings.option-groups.sync');
            Route::post('/listings/{id}/option-groups/sync', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'syncOptionGroups'])->whereNumber('id')->name('listings.option-groups.sync-multipart');
            Route::post('/listings/{id}/option-groups', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'storeOptionGroup'])->whereNumber('id')->name('listings.option-groups.store');
            Route::put('/option-groups/{groupId}', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'updateOptionGroup'])->whereNumber('groupId')->name('option-groups.update');
            Route::delete('/option-groups/{groupId}', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'destroyOptionGroup'])->whereNumber('groupId')->name('option-groups.destroy');
            Route::post('/option-groups/{groupId}/options', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'storeOption'])->whereNumber('groupId')->name('option-groups.options.store');
            Route::put('/options/{optionId}', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'updateOption'])->whereNumber('optionId')->name('options.update');
            Route::post('/options/{optionId}/image', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'uploadOptionImage'])->whereNumber('optionId')->name('options.image');
            Route::delete('/options/{optionId}', [\App\Http\Controllers\Api\V1\FreshMarketSellerListingApiController::class, 'destroyOption'])->whereNumber('optionId')->name('options.destroy');

            // (2026-09-25) Rider GPS API เดิม /fresh-market/rider/gps/* ถูกรวมเข้า /api/v1/rider/* แล้ว:
            //    update → POST /rider/location · lost → POST /rider/jobs/{id}/gps-lost
            //    confirm-off → POST /rider/jobs/{id}/gps-off · resume = อัตโนมัติเมื่อส่งตำแหน่งใหม่
            //    customer/tracking → GET /rider/jobs/{id} (dropoff + customer_live_location)
        });
    });

    // ===== ตลาดสด ผู้ขาย + ไรเดอร์ตั้งค่า (แอป) =====
    // (2026-09-26) รายได้ร้าน (ตัวเลขชุดเดียวกับหน้าเว็บ) + ค่าที่ฟอร์มลงขาย/แก้สินค้าในแอปต้องใช้ — อ่านอย่างเดียว
    // ไรเดอร์ตั้งค่าใช้ PUT /rider/profile เดิม (GET /rider/status ส่งค่าความชอบงานกลับมาแล้ว)
    Route::prefix('fresh-market')->name('fresh-market.')->middleware('auth:sanctum')->group(function () {
        Route::get('/seller/earnings', [\App\Http\Controllers\Api\V1\FreshMarketSellerAppApiController::class, 'earnings'])
            ->middleware('throttle:30,1,api-fm-seller-earnings')
            ->name('seller.earnings');
        Route::get('/seller/listing-form', [\App\Http\Controllers\Api\V1\FreshMarketSellerAppApiController::class, 'listingForm'])->name('seller.listing-form');
    });

    // ===== 🛵 ไรเดอร์รอบ 2 (2026-10-04) — แยกไฟล์ต่อหัวข้อใน routes/api_v1/ =====
    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__.'/api_v1/rider_r2_routing.php';
        require __DIR__.'/api_v1/rider_r2_handover.php';
        require __DIR__.'/api_v1/rider_r2_social.php';
        require __DIR__.'/api_v1/rider_r2_profile_photo.php';
        // 🪪 AI eKYC (2026-10-04) — ยืนยันตัวตนด้วยบัตรประชาชน + ใบหน้า
        require __DIR__.'/api_v1/ekyc.php';
    });
});

// Public Crypto Wallet API (no auth required)
Route::post('/crypto/generate-nonce', [CryptoWalletApiController::class, 'generateNonce']);

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


// ============================================
// Google Maps API (V1)
// ============================================

// 🚦 (2026-09-25) CC-03: geocode ยิง Google Maps API (มีค่าใช้จ่าย) → หุ้ม throttle:api ด้วย
Route::prefix('v1/maps')->middleware(['auth:sanctum', 'throttle:api'])->name('api.maps.')->group(function () {
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

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
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
//
// 🔐 (2026-07-26) guard เปิดรับสองแบบ: Sanctum (แอป Flutter/สคริปต์เดิม) และ
//    Passport 'api-oauth' (ผู้ใช้ที่ล็อกอินเว็บจันทราผ่าน SSO ของเรา)
//    เดิมมีแต่ sanctum → token จาก SSO เป็น Passport JWT ยิงไม่ผ่านเลยสักเส้น
//    (ยืนยันบน prod 2026-07-26: credits = 401 แต่ /api/user = 200 ด้วย token เดียวกัน)
//    → เว็บจันทราถูกตัดขาดจากคลังความรู้+คีย์พูล แล้วตกไป Gemini ในเครื่องที่ไม่มีคีย์
//    middleware 'juntra.user' แปลงผู้ใช้จาก guard ไหนก็ตามให้เป็น App\Models\User
//    ก่อนถึงคอนโทรลเลอร์ (ทุกตัวเรียก $request->user() แล้วใช้ relation ของ User)
//    ⚠️ ไม่ใช้ `auth:sanctum,api-oauth` เพราะเวลา request ไม่มี token middleware
//       `auth` จะไล่ลองทุก guard รวม Passport ซึ่งถ้าสภาพแวดล้อมยังไม่มีคีย์จะโยน
//       exception → ได้ 500 แทน 401 (เจอบน CI จริง) → 'juntra.user' ยืนยันตัวตนเอง
//       แล้วคืน 401 JSON ให้ครบทุกกรณี
Route::prefix('v1/juntra')->middleware(['juntra.user'])->name('api.juntra.')->group(function () {

    Route::prefix('fortune')->name('fortune.')->group(function () {
        Route::get('/categories', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'categories'])->name('categories');
        Route::get('/spreads', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'spreads'])->name('spreads');
        Route::get('/credits', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'credits'])->name('credits');
        Route::get('/history', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'history'])->name('history');
        Route::post('/draw', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'draw'])->name('draw');
        Route::post('/read', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'read'])->name('read');
        Route::get('/readings/{id}', [\App\Http\Controllers\Api\Juntra\FortuneController::class, 'show'])->name('show');

        // คำทำนายไพ่ให้เว็บ จันทรา.online — juntraweb เปิดไพ่เองแล้วส่งไพ่มาให้
        // อ่านด้วย "คลังความรู้ไพ่ + คีย์พูล" ชุดเดียวกับบอท FB/LINE
        // (juntraweb หักเครดิตลูกค้าไปแล้ว ที่นี่ไม่คิดเงินซ้ำ)
        // throttle: อ่านไพ่หนึ่งครั้งกิน token เยอะ — กันยิงรัว
        Route::post('/tarot/interpret', \App\Http\Controllers\Api\Juntra\TarotInterpretController::class)
            ->middleware('throttle:20,1')->name('tarot.interpret');

        // 🎁 (2026-07-26) ดูดวงฟรี 1 ใบ สั้น — ปลายทางของปุ่ม "ดูดวงฟรี" จากบอท FB
        //    juntraweb คุมโควตาเอง (1 ครั้ง/คน + เพดานต่อวัน) ที่นี่แค่ทำนายให้
        //    throttle ต่ำกว่าตัวเต็มไม่ได้เพราะฟรี = โดนยิงถี่กว่า แต่กิน token น้อยกว่ามาก
        Route::post('/tarot/free', \App\Http\Controllers\Api\Juntra\FreeTarotController::class)
            ->middleware('throttle:30,1')->name('tarot.free');

        // ดูดวงเชิงลึก 39฿ ให้เว็บจันทรา — ตัวเดียวกับ READING_TYPE_DEEP ของบอท
        // (juntraweb หักเครดิตแล้วก่อนเรียก ที่นี่ไม่คิดเงินซ้ำ)
        Route::post('/deep', \App\Http\Controllers\Api\Juntra\DeepReadingController::class)
            ->middleware('throttle:20,1')->name('deep');
    });

    Route::prefix('chat/mae-mor')->name('chat.')->group(function () {
        Route::post('/start', [\App\Http\Controllers\Api\Juntra\ChatController::class, 'start'])->name('start');
        Route::post('/send', [\App\Http\Controllers\Api\Juntra\ChatController::class, 'send'])->name('send');
        Route::get('/sessions/{id}', [\App\Http\Controllers\Api\Juntra\ChatController::class, 'show'])->name('show');
    });

    Route::prefix('natal')->name('natal.')->group(function () {
        Route::post('/compute', [\App\Http\Controllers\Api\Juntra\NatalController::class, 'compute'])->name('compute');
        Route::get('/daily-transit', [\App\Http\Controllers\Api\Juntra\NatalController::class, 'dailyTransit'])->name('transit');
    });

    Route::prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'dashboard'])->name('dashboard');
        Route::get('/downline', [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'downline'])->name('downline');
        Route::get('/commissions', [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'commissions'])->name('commissions');
        Route::get('/link', [\App\Http\Controllers\Api\Juntra\AffiliateController::class, 'link'])->name('link');
    });

    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/methods', [\App\Http\Controllers\Api\Juntra\PaymentController::class, 'methods'])->name('methods');
        Route::post('/initiate', [\App\Http\Controllers\Api\Juntra\PaymentController::class, 'initiate'])->name('initiate');
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
        Route::get('/tree', [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'tree'])->name('tree');
        Route::get('/commissions', [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'commissions'])->name('commissions');
        Route::get('/stats', [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'stats'])->name('stats');
        Route::get('/users', [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'users'])->name('users');
        // จันทรา.online/r/{member_code} attribution — enroll the caller under
        // the inviter. Throttled: enrolls are one-shot; 6/min absorbs retries.
        Route::post('/claim-referral', [\App\Http\Controllers\Api\V1\JuntraMlmApiController::class, 'claimReferral'])
            ->middleware('throttle:6,1')->name('claim');
    });
});

// ─── Juntra (จันทรา.online) server-to-server — slips + cross-site unique amounts ──
// 🔐 (2026-09-15) เซิร์ฟเวอร์ของจันทรายิงเอง ไม่มีผู้ใช้ปลายทาง → ไม่อยู่ในกลุ่ม 'juntra.user'
//    'juntra.server' = Passport client_credentials ของ client จันทราเท่านั้น (token ผู้ใช้ = 403)
//    - slips/*   : สลิปใบเดียวใช้ได้ครั้งเดียวข้ามบอท + วอลเลตจันทรา (SlipOK ก้อนเดียวกับบอท)
//    - amounts/* : Thaiprompt จองยอดทศนิยมให้ทั้งสองเว็บ (บัญชี + มือถือ SMS เดียวกัน ห้ามชน)
//
// ⚠️ throttle ทุกตัวในกลุ่มนี้ต้องมี prefix (อาร์กิวเมนต์ที่ 3) — ทุกคำขอมาจาก IP เดียว (เซิร์ฟเวอร์เว็บ)
//    throttle ที่ไม่มี prefix ใช้ key เดียวกันหมด: ซ้อนกันสองชั้น = นับคำขอเดียวสองครั้ง และแชทของ
//    ลูกค้าทั้งเว็บจะไปแย่งถังเดียวกับการตรวจสลิป (เดิมคิดเป็นแชทได้ราว 45 ข้อความ/นาทีทั้งเว็บ)
//    เพดานต่อลูกค้าอยู่ฝั่งเว็บ (throttle:chat-send / throttle:reading ต่อ user) — ที่นี่คือด่านกันรั่วรวม
Route::prefix('v1/juntra/server')
    ->middleware(['juntra.server', 'throttle:600,1,jwsrv'])
    ->name('api.juntra.server.')
    ->group(function () {
        Route::middleware('throttle:120,1,jwpay')->group(function () {
            Route::post('/slips/verify', [\App\Http\Controllers\Api\Juntra\Server\SlipController::class, 'verify'])
                ->name('slips.verify');
            Route::post('/slips/check', [\App\Http\Controllers\Api\Juntra\Server\SlipController::class, 'check'])
                ->name('slips.check');
            Route::post('/slips/claim', [\App\Http\Controllers\Api\Juntra\Server\SlipController::class, 'claim'])
                ->name('slips.claim');

            Route::post('/amounts/reserve', [\App\Http\Controllers\Api\Juntra\Server\AmountController::class, 'reserve'])
                ->name('amounts.reserve');
            Route::post('/amounts/release', [\App\Http\Controllers\Api\Juntra\Server\AmountController::class, 'release'])
                ->name('amounts.release');
        });

        // - chat/*    : แชทแม่หมอให้ลูกค้าเว็บทุกคน (ไม่ต้องมี token Thaiprompt) คุยฟรี ทำนาย = ชวนเปิดไพ่
        Route::middleware('throttle:300,1,jwchat')->group(function () {
            Route::post('/chat/start', [\App\Http\Controllers\Api\Juntra\Server\ChatController::class, 'start'])
                ->name('chat.start');
            Route::post('/chat/send', [\App\Http\Controllers\Api\Juntra\Server\ChatController::class, 'send'])
                ->name('chat.send');
        });

        // - fortune/* : คำทำนายที่ลูกค้าเว็บจ่ายแล้ว — controller ตัวเดียวกับทาง token ของลูกค้า
        //   🔴 (2026-09-15) เดิมมีแต่ทาง token → ลูกค้าที่สมัครด้วยเบอร์/อีเมล (ส่วนใหญ่) จ่ายเปิดไพ่
        //      แล้วได้ข้อความประกอบจากความหมายไพ่แทนคำทำนายจริง ส่วนดูดวงเชิงลึกถูกคืนเงินทุกครั้ง
        //   juntraweb หักเครดิตก่อนเรียกแล้ว ที่นี่ไม่คิดเงินซ้ำ (เหมือนทางเดิม)
        Route::middleware('throttle:60,1,jwread')->prefix('fortune')->name('fortune.')->group(function () {
            Route::post('/tarot/interpret', \App\Http\Controllers\Api\Juntra\TarotInterpretController::class)
                ->name('tarot.interpret');
            Route::post('/tarot/free', \App\Http\Controllers\Api\Juntra\FreeTarotController::class)
                ->name('tarot.free');
            Route::post('/deep', \App\Http\Controllers\Api\Juntra\DeepReadingController::class)
                ->name('deep');
        });

        // - affiliate/* : 🌙 (2026-09-21) ผังแม่หมอของลูกค้าจันทราทุกคน — จันทราไม่คำนวณค่าแนะนำเอง
        //   บิลเว็บ+แอพจันทราส่งมาที่นี่ แจกค่าแนะนำ % ของยอดบิล · คืนเงินที่จันทรา = ดึงค่าแนะนำคืน
        Route::middleware('throttle:300,1,jwaff')->prefix('affiliate')->name('affiliate.')->group(function () {
            $affiliate = \App\Http\Controllers\Api\Juntra\Server\AffiliateController::class;

            Route::post('/accounts', [$affiliate, 'ensureAccount'])->name('accounts');
            Route::post('/bills', [$affiliate, 'recordBill'])->name('bills');
            Route::post('/bills/{billId}/void', [$affiliate, 'voidBill'])->whereNumber('billId')->name('bills.void');
            Route::get('/members/{userRef}/stats', [$affiliate, 'stats'])->whereNumber('userRef')->name('members.stats');
            Route::get('/members/{userRef}/tree', [$affiliate, 'tree'])->whereNumber('userRef')->name('members.tree');
            Route::get('/members/{userRef}/commissions', [$affiliate, 'commissions'])->whereNumber('userRef')->name('members.commissions');

            // หลังบ้านจันทรา — จัดการค่าแนะนำเฉพาะของบิลจันทรา (เว็บใครเว็บมัน) · อัตราดูได้อย่างเดียว
            //   จันทราตรวจสิทธิ์แอดมินเอง + ส่ง actor มาทุกคำสั่งที่เปลี่ยนข้อมูล
            $admin = \App\Http\Controllers\Api\Juntra\Server\AffiliateAdminController::class;
            Route::prefix('admin')->name('admin.')->group(function () use ($admin) {
                Route::get('/overview', [$admin, 'overview'])->name('overview');
                Route::get('/commissions', [$admin, 'commissions'])->name('commissions');
                Route::post('/commissions/approve', [$admin, 'approve'])->name('commissions.approve');
                Route::post('/commissions/pay', [$admin, 'pay'])->name('commissions.pay');
                Route::post('/commissions/manual', [$admin, 'createManual'])->name('commissions.manual');
                Route::post('/commissions/{commission}/reject', [$admin, 'reject'])->whereNumber('commission')->name('commissions.reject');
                Route::post('/commissions/{commission}/adjust', [$admin, 'adjust'])->whereNumber('commission')->name('commissions.adjust');
                Route::get('/settings', [$admin, 'settings'])->name('settings');
                Route::get('/users', [$admin, 'users'])->name('users');
                Route::get('/users/{userId}/stats', [$admin, 'userStats'])->whereNumber('userId')->name('users.stats');
                Route::get('/users/{userId}/tree', [$admin, 'userTree'])->whereNumber('userId')->name('users.tree');
                Route::get('/users/{userId}/commissions', [$admin, 'userCommissions'])->whereNumber('userId')->name('users.commissions');
                Route::get('/members/search', [$admin, 'searchMembers'])->name('members.search');
                Route::post('/members/{member}/move', [$admin, 'moveMember'])->whereNumber('member')->name('members.move');
            });
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
