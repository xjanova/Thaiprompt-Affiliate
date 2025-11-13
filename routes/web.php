<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\GameController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LineLoginController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\OtpController;
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

Route::get('/demo/game-selector', [GameController::class, 'index'])->name('demo.game-selector');
Route::get('/api/games', [GameController::class, 'getGames'])->name('api.games');

Route::get('/demo/audio-spectrum', function () {
    return view('demo-audio-spectrum');
})->name('demo.audio-spectrum');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

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
Route::prefix('certificate')->name('certificate.')->group(function () {
    Route::get('/verify/{verificationCode}', [\App\Http\Controllers\Admin\CertificateController::class, 'verify'])->name('verify');
    Route::get('/share/{id}', [\App\Http\Controllers\Admin\CertificateController::class, 'share'])->name('share');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware(['turnstile:login', 'throttle.login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('turnstile:register');

    // LINE Login Routes
    Route::get('/auth/line', [LineLoginController::class, 'redirect'])->name('line.login');
    Route::get('/auth/line/callback', [LineLoginController::class, 'callback'])->name('line.callback');
    Route::get('/auth/line/register-guide', function () {
        return view('auth.line-register-guide');
    })->name('line.register.guide');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// LINE Account Linking (for authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/auth/line/link', [LineLoginController::class, 'link'])->name('line.link');
    Route::get('/auth/line/link/callback', [LineLoginController::class, 'linkCallback'])->name('line.link.callback');
    Route::post('/auth/line/unlink', [LineLoginController::class, 'unlink'])->name('line.unlink');

    // Theme Switching (accessible by all authenticated users)
    Route::post('/user/theme/update', [\App\Http\Controllers\User\ThemeController::class, 'updateTheme'])->name('user.theme.update');
    Route::get('/user/theme/current', [\App\Http\Controllers\User\ThemeController::class, 'getCurrentTheme'])->name('user.theme.current');
});

// LINE Signup via Invitation Link (Public Routes with Rate Limiting)
Route::prefix('line/signup')->name('line.signup.')->middleware(['line.signup.throttle'])->group(function () {
    Route::get('/invitation/{token}', [\App\Http\Controllers\LineSignupController::class, 'handleInvitation'])->name('invitation');
    Route::get('/callback', [\App\Http\Controllers\LineSignupController::class, 'handleCallback'])->name('callback');
});

// LINE Membership Signup System (New AI-Powered Signup)
Route::prefix('line/membership')->name('line.membership.')->group(function () {
    // Public invitation routes
    Route::get('/invitation/{token}', [\App\Http\Controllers\LineMembershipSignupController::class, 'showInvitation'])->name('invitation');
    Route::post('/invitation/{token}', [\App\Http\Controllers\LineMembershipSignupController::class, 'processInvitation'])->name('invitation.process');

    // Progress tracking (for debugging)
    Route::get('/progress/{sessionToken}', [\App\Http\Controllers\LineMembershipSignupController::class, 'showProgress'])->name('progress');

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
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/about-us', [HomeController::class, 'aboutProfessional'])->name('about.professional');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

// Wiki Routes (New modular system)
Route::prefix('wiki')->name('wiki.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Frontend\WikiController::class, 'index'])->name('index');
    Route::get('/content/{category}/{section?}', [\App\Http\Controllers\Frontend\WikiController::class, 'getContent'])->name('content');
    Route::get('/search', [\App\Http\Controllers\Frontend\WikiController::class, 'search'])->name('search');
});

// Legacy wiki route (redirect to new system)
Route::get('/platform-wiki', function () {
    return redirect()->route('wiki.index');
})->name('platform.wiki');

// Dynamic Page Routes (Privacy Policy, Terms, etc.)
Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');

// Marketplace Routes (Public browsing, Auth for renting)
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/', [\App\Http\Controllers\MarketplaceController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\MarketplaceController::class, 'show'])->name('show');

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
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ShopController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [\App\Http\Controllers\ShopController::class, 'category'])->name('category');
    Route::get('/search', [\App\Http\Controllers\ShopController::class, 'quickSearch'])->name('search');
    Route::get('/{slug}', [\App\Http\Controllers\ShopController::class, 'show'])->name('show');
});

// Vendor Store Routes (Public browsing of individual vendor stores)
Route::prefix('store')->name('vendor.store.')->group(function () {
    Route::get('/{slug}', [\App\Http\Controllers\VendorStoreController::class, 'show'])->name('show');
});

// Admin Store Routes (Public browsing of admin's official store)
Route::prefix('admin-store')->name('admin-store.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AdminStoreController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\AdminStoreController::class, 'show'])->name('show');
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
Route::prefix('hotels')->name('hotels.')->group(function () {
    Route::get('/', [\App\Http\Controllers\HotelController::class, 'index'])->name('index');
    Route::get('/featured', [\App\Http\Controllers\HotelController::class, 'featured'])->name('featured');
    Route::get('/city/{city}', [\App\Http\Controllers\HotelController::class, 'byCity'])->name('by-city');
    Route::get('/search', [\App\Http\Controllers\HotelController::class, 'search'])->name('search');
    Route::get('/autocomplete', [\App\Http\Controllers\HotelController::class, 'autocomplete'])->name('autocomplete');
    Route::post('/check-availability', [\App\Http\Controllers\HotelController::class, 'checkAvailability'])->name('check-availability');
    Route::get('/{slug}', [\App\Http\Controllers\HotelController::class, 'show'])->name('show');

    // Hotel Reviews (Public viewing)
    Route::get('/{slug}/reviews', [\App\Http\Controllers\HotelReviewController::class, 'index'])->name('reviews.index');
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

Route::prefix('sliders')->name('sliders.')->group(function () {
    Route::get('/{idOrAlias}', [SliderController::class, 'show'])->name('show');
    Route::get('/{idOrAlias}/data', [SliderController::class, 'getData'])->name('data');
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
Route::prefix('tarot')->name('tarot.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TarotReadingController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [\App\Http\Controllers\TarotReadingController::class, 'showCategory'])->name('category');
    Route::post('/start', [\App\Http\Controllers\TarotReadingController::class, 'startReading'])->name('start');
    Route::get('/select-cards/{readingId}', [\App\Http\Controllers\TarotReadingController::class, 'showCardSelection'])->name('select-cards');
    Route::post('/save-selection', [\App\Http\Controllers\TarotReadingController::class, 'saveCardSelection'])->name('save-selection');
    Route::get('/reading/{id}', [\App\Http\Controllers\TarotReadingController::class, 'showReading'])->name('reading.show');
    Route::get('/card-backs', [\App\Http\Controllers\TarotReadingController::class, 'getCardBackImages'])->name('card-backs');

    // Cart routes
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TarotCartController::class, 'index'])->name('index');
        Route::post('/add', [\App\Http\Controllers\TarotCartController::class, 'addToCart'])->name('add');
        Route::delete('/remove/{id}', [\App\Http\Controllers\TarotCartController::class, 'removeItem'])->name('remove');
        Route::post('/clear', [\App\Http\Controllers\TarotCartController::class, 'clearCart'])->name('clear');
        Route::get('/checkout', [\App\Http\Controllers\TarotCartController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/process', [\App\Http\Controllers\TarotCartController::class, 'processCheckout'])->name('processCheckout');
    });

    // Payment routes
    Route::get('/payment', [\App\Http\Controllers\TarotReadingController::class, 'payment'])->name('payment');
    Route::post('/payment/process', [\App\Http\Controllers\TarotReadingController::class, 'processPayment'])->name('payment.process');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/reading/{id}/save', [\App\Http\Controllers\TarotReadingController::class, 'saveReading'])->name('reading.save');
        Route::get('/history', [\App\Http\Controllers\TarotReadingController::class, 'history'])->name('history');
        Route::get('/saved', [\App\Http\Controllers\TarotReadingController::class, 'savedReadings'])->name('saved');
    });
});

// QR Code & Barcode Generator Routes (Public)
Route::prefix('qr-barcode')->name('qr-barcode.')->group(function () {
    // Public routes
    Route::get('/', [\App\Http\Controllers\QrBarcodeController::class, 'index'])->name('index');
    Route::get('/scanner', [\App\Http\Controllers\QrBarcodeController::class, 'scanner'])->name('scanner');
    Route::post('/decode', [\App\Http\Controllers\QrBarcodeController::class, 'decode'])->name('decode');
    Route::get('/templates', [\App\Http\Controllers\QrBarcodeController::class, 'templates'])->name('templates');
    Route::get('/gallery', [\App\Http\Controllers\QrBarcodeController::class, 'gallery'])->name('gallery');
    Route::get('/show/{id}', [\App\Http\Controllers\QrBarcodeController::class, 'show'])->name('show');
    Route::get('/r/{shortUrl}', [\App\Http\Controllers\QrBarcodeController::class, 'redirect'])->name('redirect');

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
Route::get('/cookie-policy', function () {
    return view('cookie-policy');
})->name('cookie-policy');

// Cryptocurrency Price Charts
Route::prefix('crypto')->name('crypto.')->group(function () {
    Route::get('/charts', [\App\Http\Controllers\CryptoPriceChartController::class, 'index'])->name('charts');
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
Route::prefix('chatbot/marketplace')->name('chatbot.marketplace.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\Chatbot\MarketplaceWebController::class, 'show'])->name('show');

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
Route::prefix('trading-bot')->name('trading-bot.')->group(function () {
    Route::get('/marketplace', [TradingBotController::class, 'marketplace'])->name('marketplace');
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
