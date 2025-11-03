<?php

use App\Http\Controllers\Frontend\HomeController;
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
Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

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
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

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
