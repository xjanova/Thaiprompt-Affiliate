<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\GameController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LineLoginController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\TPIXWhitepaperController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Demo Routes
Route::get('/demo/loading', function () {
    return view('demo-loading');
})->name('demo.loading');

Route::get('/demo/3d-navigation', function () {
    return view('demo-3d-navigation');
})->name('demo.3d-navigation');

Route::get('/demo/space-shooter', function () {
    return view('demo-space-shooter');
})->name('demo.space-shooter');

// V3 UI Themes Demo Routes
Route::prefix('demo')->name('demo.')->group(function () {
    Route::get('/', function () {
        return view('demo.index');
    })->name('index');

    Route::get('/theme1', function () {
        return view('demo.theme1');
    })->name('theme1');

    Route::get('/theme2', function () {
        return view('demo.theme2');
    })->name('theme2');

    Route::get('/theme3', function () {
        return view('demo.theme3');
    })->name('theme3');

    // Dashboard Examples
    Route::get('/dashboard1', function () {
        return view('demo.dashboard1');
    })->name('dashboard1');

    Route::get('/dashboard2', function () {
        return view('demo.dashboard2');
    })->name('dashboard2');

    Route::get('/dashboard3', function () {
        return view('demo.dashboard3');
    })->name('dashboard3');

    Route::get('/dashboard4', function () {
        return view('demo.dashboard4');
    })->name('dashboard4');

    // Arrow X Components Showcase
    Route::get('/components', function () {
        return view('demo.components');
    })->name('components');
});

// Tournament Routes
// ⚠️ SEO: Tournament pages มี SEO value สูง
Route::prefix('tournaments')->name('tournaments.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\TournamentController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\TournamentController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/{slug}/leaderboard', [\App\Http\Controllers\TournamentController::class, 'leaderboard'])->name('leaderboard');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/{slug}/register', [\App\Http\Controllers\TournamentController::class, 'register'])->name('register');
        Route::post('/{slug}/submit-score', [\App\Http\Controllers\TournamentController::class, 'submitScore'])->name('submit-score');
    });
});

// Rewards & Missions Routes (Authenticated)
Route::middleware('auth')->prefix('rewards')->name('rewards.')->group(function () {
    Route::get('/daily', [\App\Http\Controllers\RewardController::class, 'daily'])->name('daily');
    Route::post('/daily/claim', [\App\Http\Controllers\RewardController::class, 'claimDaily'])->name('daily.claim');

    Route::get('/missions', [\App\Http\Controllers\RewardController::class, 'missions'])->name('missions');
    Route::post('/missions/{missionId}/claim', [\App\Http\Controllers\RewardController::class, 'claimMission'])->name('missions.claim');
    Route::get('/missions/progress', [\App\Http\Controllers\RewardController::class, 'getMissionsProgress'])->name('missions.progress');
});

// Games Routes
// ⚠️ SEO Critical: Game pages สำคัญสำหรับ organic traffic
Route::prefix('games')->name('games.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\GameController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\GameController::class, 'show'])->name('show');

    // API Routes for game progress
    Route::middleware('auth')->group(function () {
        Route::post('/{slug}/save-progress', [\App\Http\Controllers\GameController::class, 'saveProgress'])->name('save-progress');
        Route::post('/{slug}/change-loadout', [\App\Http\Controllers\GameController::class, 'changeLoadout'])->name('change-loadout');

        // Game Shop Routes
        Route::prefix('{slug}/shop')->name('shop.')->group(function () {
            Route::get('/', [\App\Http\Controllers\GameShopController::class, 'index'])->name('index');
            Route::post('/purchase/{skinId}', [\App\Http\Controllers\GameShopController::class, 'purchaseSkin'])->name('purchase');
            Route::get('/owned', [\App\Http\Controllers\GameShopController::class, 'getOwnedSkins'])->name('owned');
        });

        // Game Achievements Routes
        Route::get('/{slug}/achievements', [\App\Http\Controllers\GameController::class, 'achievements'])->name('achievements');

        // Multiplayer API Routes
        Route::prefix('{slug}/multiplayer')->name('multiplayer.')->group(function () {
            Route::post('/create-room', [\App\Http\Controllers\MultiplayerController::class, 'createRoom'])->name('create-room');
            Route::get('/rooms', [\App\Http\Controllers\MultiplayerController::class, 'listRooms'])->name('rooms');
            Route::post('/join/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'joinRoom'])->name('join');
            Route::post('/leave/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'leaveRoom'])->name('leave');
            Route::post('/update/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'updatePosition'])->name('update');
            Route::get('/state/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'getRoomState'])->name('state');
            Route::post('/death/{roomCode}', [\App\Http\Controllers\MultiplayerController::class, 'reportDeath'])->name('death');
        });
    });

    Route::match(['GET', 'HEAD'], '/{slug}/leaderboard', [\App\Http\Controllers\GameController::class, 'leaderboard'])->name('leaderboard');
});

Route::get('/demo/game-selector', [GameController::class, 'index'])->name('demo.game-selector');
Route::get('/api/games', [GameController::class, 'getGames'])->name('api.games');

Route::get('/demo/audio-spectrum', function () {
    return view('demo-audio-spectrum');
})->name('demo.audio-spectrum');

Route::get('/demo/snooker', function () {
    return view('demo-snooker');
})->name('demo.snooker');

// Prompt to Web Routes
// ⚠️ Public Tool: Prompt-to-web generator ต้องถูก index โดย search engines
Route::prefix('prompt-to-web')->name('prompt-to-web.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\PromptToWebController::class, 'index'])->name('index');
    Route::post('/generate', [\App\Http\Controllers\PromptToWebController::class, 'generate'])->name('generate');
    Route::post('/{id}/improve', [\App\Http\Controllers\PromptToWebController::class, 'improve'])->name('improve');
    Route::match(['GET', 'HEAD'], '/preview/{slug}', [\App\Http\Controllers\PromptToWebController::class, 'preview'])->name('preview');
    Route::match(['GET', 'HEAD'], '/show/{slug}', [\App\Http\Controllers\PromptToWebController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/list', [\App\Http\Controllers\PromptToWebController::class, 'list'])->name('list');
    Route::delete('/{id}', [\App\Http\Controllers\PromptToWebController::class, 'delete'])->name('delete');
});

Route::get('/demo/tetris', function () {
    return view('demo-tetris');
})->name('demo.tetris');

// Sitemap
// ⚠️ SEO Critical: Search engines ใช้ HEAD request ตรวจสอบ sitemap
Route::match(['GET', 'HEAD'], '/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Language Switching
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// Translation API Routes (with rate limiting: 60 requests per minute)
Route::prefix('api/translate')->name('api.translate.')->middleware('throttle:60,1')->group(function () {
    Route::post('/', [\App\Http\Controllers\TranslationController::class, 'translate'])->name('text');
    Route::post('/batch', [\App\Http\Controllers\TranslationController::class, 'translateBatch'])->name('batch');
    Route::get('/languages', [\App\Http\Controllers\TranslationController::class, 'languages'])->name('languages');
    Route::post('/detect', [\App\Http\Controllers\TranslationController::class, 'detect'])->name('detect');
    Route::get('/status', [\App\Http\Controllers\TranslationController::class, 'status'])->name('status');
});

// Public Certificate Verification
// ⚠️ Public Service: Certificate verification ต้องเข้าถึงได้สาธารณะ
Route::prefix('certificate')->name('certificate.')->group(function () {
    Route::match(['GET', 'HEAD'], '/verify/{verificationCode}', [\App\Http\Controllers\Admin\CertificateController::class, 'verify'])->name('verify');
    Route::match(['GET', 'HEAD'], '/share/{id}', [\App\Http\Controllers\Admin\CertificateController::class, 'share'])->name('share');
});

// Setup Routes
// ⚠️ Setup: ระบบติดตั้งครั้งแรก - สร้าง Super Admin
Route::prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('index');
    Route::post('/', [SetupController::class, 'store'])->name('store');
});

// Authentication Routes
// ⚠️ Authentication: หน้า login/register ต้องรองรับ HEAD method
Route::middleware('guest')->group(function () {
    Route::match(['GET', 'HEAD'], '/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware(['turnstile:login', 'throttle.login']);

    Route::match(['GET', 'HEAD'], '/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('turnstile:register');

    // LINE Login Routes
    Route::match(['GET', 'HEAD'], '/auth/line', [LineLoginController::class, 'redirect'])->name('line.login');
    Route::match(['GET', 'HEAD'], '/auth/line/callback', [LineLoginController::class, 'callback'])->name('line.callback');
    Route::match(['GET', 'HEAD'], '/auth/line/register-guide', function () {
        return view('auth.line-register-guide');
    })->name('line.register.guide');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Public Recruit Page Routes
// ⚠️ Affiliate Critical: Recruit links สำคัญสำหรับระบบ MLM/Affiliate
Route::prefix('recruit')->name('recruit.')->group(function () {
    Route::match(['GET', 'HEAD'], '/{member_code}', [\App\Http\Controllers\RecruitController::class, 'show'])->name('show');
    Route::post('/track-behavior', [\App\Http\Controllers\RecruitController::class, 'trackBehavior'])->name('track-behavior');
});

// LINE Account Linking (for authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/auth/line/link', [LineLoginController::class, 'link'])->name('line.link');
    Route::get('/auth/line/link/callback', [LineLoginController::class, 'linkCallback'])->name('line.link.callback');
    Route::post('/auth/line/unlink', [LineLoginController::class, 'unlink'])->name('line.unlink');
});

// Language Switcher (Public - no auth required)
Route::prefix('language')->name('language.')->group(function () {
    Route::get('/switch/{lang}', [\App\Http\Controllers\LanguageSwitcherController::class, 'switch'])->name('switch');
    Route::get('/current', [\App\Http\Controllers\LanguageSwitcherController::class, 'current'])->name('current');
});

// LINE Signup via Invitation Link (Public Routes with Rate Limiting)
// ⚠️ Affiliate: LINE signup links สำคัญสำหรับระบบ affiliate
Route::prefix('line/signup')->name('line.signup.')->middleware(['line.signup.throttle'])->group(function () {
    Route::match(['GET', 'HEAD'], '/invitation/{token}', [\App\Http\Controllers\LineSignupController::class, 'handleInvitation'])->name('invitation');
    Route::match(['GET', 'HEAD'], '/callback', [\App\Http\Controllers\LineSignupController::class, 'handleCallback'])->name('callback');
});

// LINE Membership Signup System (New AI-Powered Signup)
// ⚠️ Affiliate: LINE membership signup สำคัญสำหรับระบบ affiliate
Route::prefix('line/membership')->name('line.membership.')->group(function () {
    // Public invitation routes
    Route::match(['GET', 'HEAD'], '/invitation/{token}', [\App\Http\Controllers\LineMembershipSignupController::class, 'showInvitation'])->name('invitation');
    Route::post('/invitation/{token}', [\App\Http\Controllers\LineMembershipSignupController::class, 'processInvitation'])->name('invitation.process');

    // Progress tracking (for debugging)
    Route::match(['GET', 'HEAD'], '/progress/{sessionToken}', [\App\Http\Controllers\LineMembershipSignupController::class, 'showProgress'])->name('progress');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        // Create invitation link
        Route::post('/invitations/create', [\App\Http\Controllers\LineMembershipSignupController::class, 'createInvitation'])->name('invitations.create');

        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\LineMembershipSignupController::class, 'getAnalytics'])->name('analytics');
    });
});

// OTP Routes
Route::prefix('otp')->name('otp.')->group(function () {
    Route::post('/send', [OtpController::class, 'send'])->name('send');
    Route::post('/verify', [OtpController::class, 'verify'])->name('verify');
    Route::post('/resend', [OtpController::class, 'resend'])->name('resend');
    Route::get('/status', [OtpController::class, 'status'])->name('status');
});

// Frontend Routes
// Landing Page - หน้าแรก (3 ประตู Storytelling)
// ⚠️ CRITICAL: ต้องระบุ methods อย่างชัดเจนเพื่อป้องกันปัญหา route caching ใน production
Route::match(['GET', 'HEAD'], '/', [HomeController::class, 'index'])->name('home');

// Original Home Page
// ⚠️ SEO Critical: Landing pages ต้องรองรับ HEAD method
Route::match(['GET', 'HEAD'], '/home', [HomeController::class, 'index'])->name('home.original');
Route::match(['GET', 'HEAD'], '/about', [HomeController::class, 'about'])->name('about');
Route::match(['GET', 'HEAD'], '/about-us', [HomeController::class, 'aboutProfessional'])->name('about.professional');
Route::match(['GET', 'HEAD'], '/contact', [HomeController::class, 'contact'])->name('contact');

// 3 Doors Storytelling Pages - หน้าละเอียดของแต่ละประตู
// ⚠️ SEO Critical: Landing pages สำหรับแต่ละกลุ่มเป้าหมาย
Route::match(['GET', 'HEAD'], '/investors', [HomeController::class, 'investors'])->name('frontend.investors');
Route::match(['GET', 'HEAD'], '/developers', [HomeController::class, 'developers'])->name('frontend.developers');
Route::match(['GET', 'HEAD'], '/community', [HomeController::class, 'community'])->name('frontend.community');

// 3D Interactive Presentation
Route::match(['GET', 'HEAD'], '/presentation', [HomeController::class, 'presentation'])->name('presentation');

// Wiki Routes (New modular system)
// ⚠️ SEO High: Wiki pages มี content value สูง
Route::prefix('wiki')->name('wiki.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Frontend\WikiController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/content/{category}/{section?}', [\App\Http\Controllers\Frontend\WikiController::class, 'getContent'])->name('content');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\Frontend\WikiController::class, 'search'])->name('search');
});

// Legacy wiki route (redirect to new system)
Route::get('/platform-wiki', function () {
    return redirect()->route('wiki.index');
})->name('platform.wiki');

// Dynamic Page Routes (Privacy Policy, Terms, etc.)
// ⚠️ Legal Critical: Privacy Policy, Terms of Service ต้องเข้าถึงได้ตลอดเวลา
Route::match(['GET', 'HEAD'], '/page/{slug}', [PageController::class, 'show'])->name('page.show');

// Marketplace Routes (Public browsing, Auth for renting)
// ⚠️ E-commerce Critical: Marketplace pages ต้องถูก index โดย search engines
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\MarketplaceController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{id}', [\App\Http\Controllers\MarketplaceController::class, 'show'])->name('show');

    // Bot marketplace routes (alias to chatbot.marketplace)
    Route::prefix('bots')->name('bots.')->group(function () {
        Route::match(['GET', 'HEAD'], '/{id}', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'show'])->name('show');
    });

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/{id}/rent', [\App\Http\Controllers\MarketplaceController::class, 'rent'])->name('rent');
        Route::get('/rent-success/{rentalId}', [\App\Http\Controllers\MarketplaceController::class, 'rentSuccess'])->name('rent-success');
    });
});

// My Rentals Routes (Authenticated users)
Route::middleware('auth')->prefix('my-rentals')->name('my-rentals.')->group(function () {
    Route::get('/', [\App\Http\Controllers\RentalManagementController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\RentalManagementController::class, 'show'])->name('show');
    Route::post('/{id}/cancel', [\App\Http\Controllers\RentalManagementController::class, 'cancel'])->name('cancel');
    Route::post('/{id}/toggle-auto-renew', [\App\Http\Controllers\RentalManagementController::class, 'toggleAutoRenew'])->name('toggle-auto-renew');
    Route::get('/transactions/all', [\App\Http\Controllers\RentalManagementController::class, 'transactions'])->name('transactions');
});

// Owner Dashboard Routes (Bot owners)
Route::middleware('auth')->prefix('owner-dashboard')->name('owner-dashboard.')->group(function () {
    Route::get('/', [\App\Http\Controllers\OwnerDashboardController::class, 'index'])->name('index');
    Route::get('/earnings', [\App\Http\Controllers\OwnerDashboardController::class, 'earnings'])->name('earnings');
    Route::get('/rentals', [\App\Http\Controllers\OwnerDashboardController::class, 'rentals'])->name('rentals');
    Route::get('/payouts', [\App\Http\Controllers\OwnerDashboardController::class, 'payouts'])->name('payouts');
    Route::post('/request-payout', [\App\Http\Controllers\OwnerDashboardController::class, 'requestPayout'])->name('request-payout');
});

// ========================================
// E-COMMERCE ROUTES (Shop System)
// ========================================

// Shop Routes (Public browsing)
// ⚠️ E-commerce Critical: Shop system ต้องถูก index โดย search engines
Route::prefix('shop')->name('shop.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\ShopController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/category/{slug}', [\App\Http\Controllers\ShopController::class, 'category'])->name('category');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\ShopController::class, 'quickSearch'])->name('search');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\ShopController::class, 'show'])->name('show');
});

// Vendor Store Routes (Public browsing of individual vendor stores)
// ⚠️ E-commerce: Vendor stores ต้องถูก index
Route::prefix('stores')->name('vendor.stores.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\VendorStoreController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\VendorStoreController::class, 'show'])->name('show');
});

// Official Shop Routes (ร้านของระบบ - แยกจากร้านผู้เช่า)
// ⚠️ E-commerce Critical: ร้านของระบบต้องถูก index โดย search engines
// โทนสีและ UI แตกต่างจากร้านทั่วไป (3D, Premium, Glassmorphism)
Route::prefix('official-shop')->name('official-shop.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\OfficialShopController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/featured', [\App\Http\Controllers\OfficialShopController::class, 'featured'])->name('featured');
    Route::match(['GET', 'HEAD'], '/category/{slug}', [\App\Http\Controllers\OfficialShopController::class, 'category'])->name('category');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\OfficialShopController::class, 'quickSearch'])->name('search');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\OfficialShopController::class, 'show'])->name('show');
});

// Admin Store Routes (Legacy - เก็บไว้เพื่อ backward compatibility)
// ⚠️ E-commerce: Admin store ต้องถูก index
Route::prefix('admin-store')->name('admin-store.')->group(function () {
    // Redirect to official shop
    Route::match(['GET', 'HEAD'], '/', function () {
        return redirect()->route('official-shop.index');
    })->name('index');
    Route::match(['GET', 'HEAD'], '/{slug}', function ($slug) {
        return redirect()->route('official-shop.show', $slug);
    })->name('show');
});

// Cart Routes (Authenticated)
Route::middleware('auth')->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CartController::class, 'index'])->name('index');
    Route::post('/add', [\App\Http\Controllers\CartController::class, 'add'])->name('add');
    Route::put('/{id}', [\App\Http\Controllers\CartController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\CartController::class, 'remove'])->name('remove');
    Route::delete('/', [\App\Http\Controllers\CartController::class, 'clear'])->name('clear');
    Route::get('/count', [\App\Http\Controllers\CartController::class, 'count'])->name('count');
});

// Checkout Routes (Authenticated)
Route::middleware('auth')->prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('index');
    Route::post('/process', [\App\Http\Controllers\CheckoutController::class, 'process'])->name('process');
    Route::get('/payment/{orderId}', [\App\Http\Controllers\CheckoutController::class, 'payment'])->name('payment');
    Route::post('/payment/{orderId}/process', [\App\Http\Controllers\CheckoutController::class, 'processPayment'])->name('payment.process');
    Route::get('/success/{orderId}', [\App\Http\Controllers\CheckoutController::class, 'success'])->name('success');
});

// Payment Callback Routes (for webhooks - no auth required)
Route::post('/payment/callback/{transactionId}', [\App\Http\Controllers\CheckoutController::class, 'paymentCallback'])->name('payment.callback');

// Shipping Addresses Routes (Authenticated)
Route::middleware('auth')->prefix('shipping-addresses')->name('shipping-addresses.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ShippingAddressController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\ShippingAddressController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\ShippingAddressController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\ShippingAddressController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\ShippingAddressController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\ShippingAddressController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/set-default', [\App\Http\Controllers\ShippingAddressController::class, 'setDefault'])->name('set-default');
});

// Orders Routes (Customer orders - Authenticated)
Route::middleware('auth')->prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [\App\Http\Controllers\OrderController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\OrderController::class, 'show'])->name('show');
    Route::post('/{id}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel'])->name('cancel');
    Route::post('/{id}/confirm-received', [\App\Http\Controllers\OrderController::class, 'confirmReceived'])->name('confirm-received');

    // Reviews
    Route::get('/{orderId}/items/{itemId}/review', [\App\Http\Controllers\OrderController::class, 'showReviewForm'])->name('review.form');
    Route::post('/{orderId}/items/{itemId}/review', [\App\Http\Controllers\OrderController::class, 'submitReview'])->name('review.submit');
});

// ========================================
// HOTEL BOOKING ROUTES
// ========================================

// Public Hotel Browsing Routes
// ⚠️ E-commerce Critical: Hotel booking system ต้องถูก index โดย search engines
Route::prefix('hotels')->name('hotels.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\HotelController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/featured', [\App\Http\Controllers\HotelController::class, 'featured'])->name('featured');
    Route::match(['GET', 'HEAD'], '/city/{city}', [\App\Http\Controllers\HotelController::class, 'byCity'])->name('by-city');
    Route::match(['GET', 'HEAD'], '/search', [\App\Http\Controllers\HotelController::class, 'search'])->name('search');
    Route::match(['GET', 'HEAD'], '/autocomplete', [\App\Http\Controllers\HotelController::class, 'autocomplete'])->name('autocomplete');
    Route::post('/check-availability', [\App\Http\Controllers\HotelController::class, 'checkAvailability'])->name('check-availability');
    Route::match(['GET', 'HEAD'], '/{slug}', [\App\Http\Controllers\HotelController::class, 'show'])->name('show');

    // Hotel Reviews (Public viewing)
    Route::match(['GET', 'HEAD'], '/{slug}/reviews', [\App\Http\Controllers\HotelReviewController::class, 'index'])->name('reviews.index');
});

// Hotel Booking Routes (Authenticated)
Route::middleware('auth')->prefix('hotels')->name('hotels.')->group(function () {
    // Booking Management
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\HotelBookingController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\HotelBookingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\HotelBookingController::class, 'store'])->name('store');
        Route::get('/{bookingNumber}', [\App\Http\Controllers\HotelBookingController::class, 'show'])->name('show');
        Route::get('/{bookingNumber}/edit', [\App\Http\Controllers\HotelBookingController::class, 'edit'])->name('edit');
        Route::put('/{bookingNumber}', [\App\Http\Controllers\HotelBookingController::class, 'update'])->name('update');
        Route::post('/{bookingNumber}/cancel', [\App\Http\Controllers\HotelBookingController::class, 'cancel'])->name('cancel');
        Route::get('/{bookingNumber}/payment', [\App\Http\Controllers\HotelBookingController::class, 'payment'])->name('payment');
        Route::post('/{bookingNumber}/payment', [\App\Http\Controllers\HotelBookingController::class, 'processPayment'])->name('process-payment');
        Route::post('/calculate-price', [\App\Http\Controllers\HotelBookingController::class, 'calculatePrice'])->name('calculate-price');
    });

    // Review Management
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/booking/{bookingNumber}', [\App\Http\Controllers\HotelReviewController::class, 'create'])->name('create');
        Route::post('/booking/{bookingNumber}', [\App\Http\Controllers\HotelReviewController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\HotelReviewController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\HotelReviewController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\HotelReviewController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\HotelReviewController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/helpful', [\App\Http\Controllers\HotelReviewController::class, 'markHelpful'])->name('helpful');
        Route::post('/{id}/not-helpful', [\App\Http\Controllers\HotelReviewController::class, 'markNotHelpful'])->name('not-helpful');
    });
});

// ========================================
// SMART SLIDER FRONTEND ROUTES
// ========================================
use App\Http\Controllers\Frontend\SliderController;

// ⚠️ Public Component: Slider component ต้องรองรับ HEAD method
Route::prefix('sliders')->name('sliders.')->group(function () {
    Route::match(['GET', 'HEAD'], '/{idOrAlias}', [SliderController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/{idOrAlias}/data', [SliderController::class, 'getData'])->name('data');
    Route::post('/{slider}/track-click', [SliderController::class, 'trackClick'])->name('track-click');
    Route::post('/{slider}/track-slide-change', [SliderController::class, 'trackSlideChange'])->name('track-slide-change');
});

// User Routes (Protected by auth middleware and role check)
Route::middleware(['auth', 'role:user'])->prefix('user')->name('user.')->group(function () {
    require __DIR__.'/user.php';
});

// Admin Routes (Protected by auth middleware and role check)
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/admin.php';
});

// Seller Routes (Protected by auth middleware and role check)
Route::middleware(['auth', 'role:seller,super_admin'])->prefix('seller')->name('seller.')->group(function () {
    require __DIR__.'/seller.php';
});

// POS Routes (Protected by auth middleware)
Route::middleware('auth')->prefix('pos')->name('pos.')->group(function () {
    require __DIR__.'/pos.php';
});

// Tarot Reading Routes (Public)
// ⚠️ Public Service: Tarot reading service ต้องถูก index โดย search engines
Route::prefix('tarot')->name('tarot.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\TarotReadingController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/category/{slug}', [\App\Http\Controllers\TarotReadingController::class, 'showCategory'])->name('category');
    Route::post('/start', [\App\Http\Controllers\TarotReadingController::class, 'startReading'])->name('start');
    Route::match(['GET', 'HEAD'], '/select-cards/{readingId}', [\App\Http\Controllers\TarotReadingController::class, 'showCardSelection'])->name('select-cards');
    Route::post('/save-selection', [\App\Http\Controllers\TarotReadingController::class, 'saveCardSelection'])->name('save-selection');
    Route::match(['GET', 'HEAD'], '/reading/{id}', [\App\Http\Controllers\TarotReadingController::class, 'showReading'])->name('reading.show');
    Route::match(['GET', 'HEAD'], '/card-backs', [\App\Http\Controllers\TarotReadingController::class, 'getCardBackImages'])->name('card-backs');

    // Cart routes
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\TarotCartController::class, 'index'])->name('index');
        Route::post('/add', [\App\Http\Controllers\TarotCartController::class, 'addToCart'])->name('add');
        Route::delete('/remove/{id}', [\App\Http\Controllers\TarotCartController::class, 'removeItem'])->name('remove');
        Route::post('/clear', [\App\Http\Controllers\TarotCartController::class, 'clearCart'])->name('clear');
        Route::match(['GET', 'HEAD'], '/checkout', [\App\Http\Controllers\TarotCartController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/process', [\App\Http\Controllers\TarotCartController::class, 'processCheckout'])->name('processCheckout');
    });

    // Payment routes
    Route::match(['GET', 'HEAD'], '/payment', [\App\Http\Controllers\TarotReadingController::class, 'payment'])->name('payment');
    Route::post('/payment/process', [\App\Http\Controllers\TarotReadingController::class, 'processPayment'])->name('payment.process');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/reading/{id}/save', [\App\Http\Controllers\TarotReadingController::class, 'saveReading'])->name('reading.save');
        Route::get('/history', [\App\Http\Controllers\TarotReadingController::class, 'history'])->name('history');
        Route::get('/saved', [\App\Http\Controllers\TarotReadingController::class, 'savedReadings'])->name('saved');
    });
});

// QR Code & Barcode Generator Routes (Public)
// ⚠️ Public Tool: QR/Barcode generator เป็น SEO-friendly tool
Route::prefix('qr-barcode')->name('qr-barcode.')->group(function () {
    // Public routes
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\QrBarcodeController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/scanner', [\App\Http\Controllers\QrBarcodeController::class, 'scanner'])->name('scanner');
    Route::post('/decode', [\App\Http\Controllers\QrBarcodeController::class, 'decode'])->name('decode');
    Route::match(['GET', 'HEAD'], '/templates', [\App\Http\Controllers\QrBarcodeController::class, 'templates'])->name('templates');
    Route::match(['GET', 'HEAD'], '/gallery', [\App\Http\Controllers\QrBarcodeController::class, 'gallery'])->name('gallery');
    Route::match(['GET', 'HEAD'], '/show/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'show'])->name('show');
    Route::match(['GET', 'HEAD'], '/r/{shortUrl}', [\App\Http\Controllers\QrBarcodeController::class, 'redirect'])->name('redirect');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/store', [\App\Http\Controllers\QrBarcodeController::class, 'store'])->name('store');
        Route::get('/history', [\App\Http\Controllers\QrBarcodeController::class, 'history'])->name('history');
        Route::get('/analytics', [\App\Http\Controllers\QrBarcodeController::class, 'analytics'])->name('analytics');
        Route::put('/update/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'destroy'])->name('destroy');
        Route::post('/favorite/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'toggleFavorite'])->name('favorite');
        Route::post('/batch-generate', [\App\Http\Controllers\QrBarcodeController::class, 'batchGenerate'])->name('batch-generate');
    });
});

// ========================================
// SOFTWARE SALES SYSTEM ROUTES
// ========================================
require __DIR__.'/software_sales.php';

// Cookie Policy Page
// ⚠️ Legal Compliance: Cookie policy ต้องเข้าถึงได้ตลอดเวลา
Route::match(['GET', 'HEAD'], '/cookie-policy', function () {
    return view('cookie-policy');
})->name('cookie-policy');

// Cryptocurrency Price Charts
// ⚠️ Public Tool: Crypto charts ต้องถูก index โดย search engines
Route::prefix('crypto')->name('crypto.')->group(function () {
    Route::match(['GET', 'HEAD'], '/charts', [\App\Http\Controllers\CryptoPriceChartController::class, 'index'])->name('charts');
});

// Cryptocurrency API Routes
Route::prefix('api/crypto')->name('api.crypto.')->group(function () {
    Route::get('/chart/{currency}', [\App\Http\Controllers\CryptoPriceChartController::class, 'getChartData'])->name('chart');
    Route::get('/compare', [\App\Http\Controllers\CryptoPriceChartController::class, 'getComparisonData'])->name('compare');
    Route::get('/market-overview', [\App\Http\Controllers\CryptoPriceChartController::class, 'getMarketOverview'])->name('market-overview');
    Route::get('/realtime-prices', [\App\Http\Controllers\CryptoPriceChartController::class, 'getRealTimePrices'])->name('realtime-prices');
});

// ========================================
// CHATBOT RENTAL SYSTEM ROUTES
// ========================================
Route::prefix('chatbot')->name('chatbot.')->middleware('auth')->group(function () {
    // Bot Management
    Route::get('/', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'create'])->name('create');
    Route::get('/{id}', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'edit'])->name('edit');
    Route::get('/{id}/keywords', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'keywords'])->name('keywords');
    Route::get('/{id}/integrations', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'integrations'])->name('integrations');
    Route::get('/{id}/auto-content', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'autoContent'])->name('auto-content');
    Route::get('/{id}/analytics', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'analytics'])->name('analytics');
    Route::get('/{id}/playground', [\App\Http\Controllers\Chatbot\ChatbotController::class, 'playground'])->name('playground');
});

// Chatbot Marketplace
// ⚠️ Marketplace: Chatbot marketplace ต้องถูก index โดย search engines
Route::prefix('chatbot/marketplace')->name('chatbot.marketplace.')->group(function () {
    Route::match(['GET', 'HEAD'], '/', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'index'])->name('index');
    Route::match(['GET', 'HEAD'], '/{id}', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'show'])->name('show');

    Route::middleware('auth')->group(function () {
        Route::get('/my-rentals', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'myRentals'])->name('my-rentals');
        Route::get('/my-earnings', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'myEarnings'])->name('my-earnings');
    });
});

// ========================================
// TRADING BOT SYSTEM ROUTES
// ========================================

use App\Http\Controllers\TradingBotController;
use App\Http\Controllers\Admin\TradingBotAdminController;

// Public Trading Bot Marketplace
// ⚠️ Marketplace: Trading bot marketplace ต้องถูก index โดย search engines
Route::prefix('trading-bot')->name('trading-bot.')->group(function () {
    Route::match(['GET', 'HEAD'], '/marketplace', [TradingBotController::class, 'marketplace'])->name('marketplace');
});

// Authenticated Trading Bot Routes
Route::middleware('auth')->prefix('trading-bot')->name('trading-bot.')->group(function () {
    // Dashboard
    Route::get('/', [TradingBotController::class, 'index'])->name('index');

    // Subscription
    Route::post('/subscribe/{package}', [TradingBotController::class, 'subscribe'])->name('subscribe');

    // Bot Management
    Route::get('/bots/create', [TradingBotController::class, 'create'])->name('create');
    Route::post('/bots', [TradingBotController::class, 'store'])->name('store');
    Route::get('/bots/{bot}', [TradingBotController::class, 'show'])->name('show');
    Route::get('/bots/{bot}/edit', [TradingBotController::class, 'edit'])->name('edit');
    Route::put('/bots/{bot}', [TradingBotController::class, 'update'])->name('update');
    Route::delete('/bots/{bot}', [TradingBotController::class, 'destroy'])->name('destroy');
    Route::post('/bots/{bot}/start', [TradingBotController::class, 'start'])->name('start');
    Route::post('/bots/{bot}/stop', [TradingBotController::class, 'stop'])->name('stop');
    Route::get('/bots/{bot}/analytics', [TradingBotController::class, 'analytics'])->name('analytics');
    Route::get('/bots/{bot}/advanced-config', [TradingBotController::class, 'advancedConfig'])->name('advanced-config');
    Route::get('/bots/{bot}/pro-analytics', [TradingBotController::class, 'proAnalytics'])->name('pro-analytics');

    // Multi-Exchange & Risk Management
    Route::get('/multi-exchange', [TradingBotController::class, 'multiExchange'])->name('multi-exchange');
    Route::get('/risk-management', [TradingBotController::class, 'riskManagement'])->name('risk-management');

    // Trading Accounts
    Route::get('/accounts', [TradingBotController::class, 'accounts'])->name('accounts');
    Route::post('/accounts', [TradingBotController::class, 'storeAccount'])->name('accounts.store');

    // Strategies
    Route::get('/strategies', [TradingBotController::class, 'strategies'])->name('strategies');
    Route::get('/strategies/create', [TradingBotController::class, 'createStrategy'])->name('strategies.create');
    Route::post('/strategies', [TradingBotController::class, 'storeStrategy'])->name('strategies.store');
});

// Admin Trading Bot Routes
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin/trading-bot')->name('admin.trading-bot.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [TradingBotAdminController::class, 'dashboard'])->name('dashboard');

    // Package Management
    Route::get('/packages', [TradingBotAdminController::class, 'packages'])->name('packages.index');
    Route::get('/packages/create', [TradingBotAdminController::class, 'createPackage'])->name('packages.create');
    Route::post('/packages', [TradingBotAdminController::class, 'storePackage'])->name('packages.store');
    Route::get('/packages/{package}/edit', [TradingBotAdminController::class, 'editPackage'])->name('packages.edit');
    Route::put('/packages/{package}', [TradingBotAdminController::class, 'updatePackage'])->name('packages.update');
    Route::delete('/packages/{package}', [TradingBotAdminController::class, 'destroyPackage'])->name('packages.destroy');

    // Subscription Management
    Route::get('/subscriptions', [TradingBotAdminController::class, 'subscriptions'])->name('subscriptions.index');
    Route::get('/subscriptions/{subscription}', [TradingBotAdminController::class, 'showSubscription'])->name('subscriptions.show');
    Route::post('/subscriptions/{subscription}/cancel', [TradingBotAdminController::class, 'cancelSubscription'])->name('subscriptions.cancel');

    // Bot Management
    Route::get('/bots', [TradingBotAdminController::class, 'bots'])->name('bots.index');
    Route::get('/bots/{bot}', [TradingBotAdminController::class, 'showBot'])->name('bots.show');
    Route::post('/bots/{bot}/stop', [TradingBotAdminController::class, 'stopBot'])->name('bots.stop');
    Route::post('/bots/{bot}/start', [TradingBotAdminController::class, 'startBot'])->name('bots.start');
    Route::delete('/bots/{bot}', [TradingBotAdminController::class, 'destroyBot'])->name('bots.destroy');

    // Exchange Management
    Route::get('/exchanges', [TradingBotAdminController::class, 'exchanges'])->name('exchanges.index');
    Route::post('/exchanges', [TradingBotAdminController::class, 'storeExchange'])->name('exchanges.store');
    Route::put('/exchanges/{exchange}', [TradingBotAdminController::class, 'updateExchange'])->name('exchanges.update');

    // Analytics
    Route::get('/analytics', [TradingBotAdminController::class, 'analytics'])->name('analytics');

    // Arbitrage Monitor
    Route::get('/arbitrage-monitor', [TradingBotAdminController::class, 'arbitrageMonitor'])->name('arbitrage-monitor');

    // Settings
    Route::get('/settings', [TradingBotAdminController::class, 'settings'])->name('settings');
    Route::put('/settings', [TradingBotAdminController::class, 'updateSettings'])->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| TPIX Whitepaper Routes
|--------------------------------------------------------------------------
|
| Routes สำหรับแสดงและ export ไวท์เปเปอร์ของ TPIX
|
*/
// ⚠️ Marketing Critical: TPIX Whitepaper ต้องถูก index โดย search engines
Route::prefix('tpix')->name('tpix.')->group(function () {
    // แสดงหน้าไวท์เปเปอร์
    Route::match(['GET', 'HEAD'], '/whitepaper', [TPIXWhitepaperController::class, 'index'])
        ->name('whitepaper.index');

    // Export PDF
    Route::match(['GET', 'HEAD'], '/whitepaper/pdf', [TPIXWhitepaperController::class, 'exportPdf'])
        ->name('whitepaper.pdf');
});

/*
|--------------------------------------------------------------------------
| NFC Card Verification Routes (Public)
|--------------------------------------------------------------------------
|
| Routes สำหรับตรวจสอบความถูกต้องของบัตร NFC แบบ Public
| ผู้ใช้ทั่วไปสามารถเข้าถึงได้โดยไม่ต้อง login
|
*/
use App\Http\Controllers\NFCVerificationController;

// ⚠️ Security: Public NFC verification - ไม่ต้อง login
Route::prefix('nfc')->name('nfc.')->group(function () {
    // หน้าตรวจสอบบัตร NFC
    Route::match(['GET', 'HEAD'], '/verify/{cardNumber?}', [NFCVerificationController::class, 'show'])
        ->name('verify');

    // API สำหรับตรวจสอบบัตร (Full Verification)
    Route::post('/verify', [NFCVerificationController::class, 'verify'])
        ->name('verify.api');

    // API สำหรับตรวจสอบด่วน (Quick Verification)
    Route::post('/verify/quick', [NFCVerificationController::class, 'quickVerify'])
        ->name('verify.quick');

    // สถิติการตรวจสอบ (สำหรับ Admin หรือ Card Owner)
    Route::middleware('auth')->get('/statistics/{cardNumber}', [NFCVerificationController::class, 'statistics'])
        ->name('statistics');
});
