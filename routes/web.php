<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SetupController;
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

// Setup Wizard (First time installation)
Route::prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('index');
    Route::get('/check-requirements', [SetupController::class, 'checkRequirements'])->name('check-requirements');
    Route::post('/verify-license', [SetupController::class, 'verifyLicense'])->name('verify-license');
    Route::post('/create-admin', [SetupController::class, 'createAdmin'])->name('create-admin');
    Route::post('/seed-data', [SetupController::class, 'seedData'])->name('seed-data');
    Route::post('/finalize', [SetupController::class, 'finalize'])->name('finalize');
    Route::get('/info', [SetupController::class, 'info'])->name('info');
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
});

// LINE Signup via Invitation Link (Public Routes)
Route::prefix('line/signup')->name('line.signup.')->group(function () {
    Route::get('/invitation/{token}', [\App\Http\Controllers\LineSignupController::class, 'handleInvitation'])->name('invitation');
    Route::get('/callback', [\App\Http\Controllers\LineSignupController::class, 'handleCallback'])->name('callback');
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
Route::get('/platform-wiki', [HomeController::class, 'platformWiki'])->name('platform.wiki');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

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
    Route::get('/success/{orderId}', [\App\Http\Controllers\CheckoutController::class, 'success'])->name('success');
});

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
