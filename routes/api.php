<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\MlmController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\ThemeController;
use App\Http\Controllers\Api\Hotel\HotelController;
use App\Http\Controllers\Api\Hotel\BookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public API Routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register-with-referral', [AuthController::class, 'registerWithReferral']);

    // Products (Public)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/vendors', [VendorController::class, 'index']);

    // Hotels (Public)
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/{id}', [HotelController::class, 'show']);
    Route::get('/hotels/{id}/availability', [HotelController::class, 'availability']);

    // Themes (Public)
    Route::get('/themes', [ThemeController::class, 'index']);
    Route::get('/themes/featured', [ThemeController::class, 'featured']);
    Route::get('/themes/free', [ThemeController::class, 'free']);
    Route::get('/themes/premium', [ThemeController::class, 'premium']);
    Route::get('/themes/category/{category}', [ThemeController::class, 'byCategory']);
    Route::get('/themes/{id}', [ThemeController::class, 'show']);
    Route::get('/vendor/{vendorId}/theme', [ThemeController::class, 'publicTheme']);

    // Addons (Public)
    Route::get('/addons', [AddonController::class, 'index']);
    Route::get('/addons/{id}', [AddonController::class, 'show']);
});

// Protected API Routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/{item}', [CartController::class, 'update']);
    Route::delete('/cart/{item}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);

    // MLM
    Route::get('/mlm/stats', [MlmController::class, 'stats']);
    Route::get('/mlm/network', [MlmController::class, 'network']);
    Route::get('/mlm/commissions', [MlmController::class, 'commissions']);
    Route::post('/mlm/invite', [MlmController::class, 'sendInvitation']);

    // Hotel Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/calculate-price', [BookingController::class, 'calculatePrice']);
    Route::post('/bookings/{id}/payment', [BookingController::class, 'processPayment']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Addons
    Route::get('/my-addons', [AddonController::class, 'myAddons']);
    Route::get('/addons/available', [AddonController::class, 'available']);
    Route::post('/addons/purchase', [AddonController::class, 'purchase']);
    Route::post('/addons/{purchaseId}/deactivate', [AddonController::class, 'deactivate']);

    // Themes
    Route::get('/my-theme', [ThemeController::class, 'myTheme']);
    Route::post('/theme/apply', [ThemeController::class, 'apply']);
    Route::post('/theme/customize', [ThemeController::class, 'customize']);
    Route::post('/theme/reset', [ThemeController::class, 'reset']);

    // Vendor Routes
    Route::middleware('role:vendor')->prefix('vendor')->group(function () {
        Route::get('/dashboard', [VendorController::class, 'dashboard']);
        Route::get('/products', [VendorController::class, 'products']);
        Route::post('/products', [VendorController::class, 'storeProduct']);
        Route::put('/products/{product}', [VendorController::class, 'updateProduct']);
        Route::delete('/products/{product}', [VendorController::class, 'deleteProduct']);

        // POS
        Route::post('/pos/session/open', [VendorController::class, 'openPosSession']);
        Route::post('/pos/session/close', [VendorController::class, 'closePosSession']);
        Route::post('/pos/sale', [VendorController::class, 'createPosSale']);

        // Hotels
        Route::get('/hotels', [HotelController::class, 'vendorHotels']);
        Route::post('/hotels', [HotelController::class, 'store']);
        Route::put('/hotels/{id}', [HotelController::class, 'update']);
        Route::delete('/hotels/{id}', [HotelController::class, 'destroy']);
        Route::get('/hotels/{id}/stats', [HotelController::class, 'stats']);

        // Hotel Bookings
        Route::get('/bookings', [BookingController::class, 'vendorBookings']);
        Route::get('/bookings/stats', [BookingController::class, 'stats']);
        Route::post('/bookings/{id}/check-in', [BookingController::class, 'checkIn']);
        Route::post('/bookings/{id}/check-out', [BookingController::class, 'checkOut']);
    });

    // Admin Routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Addon Management
        Route::post('/addons', [AddonController::class, 'store']);
        Route::put('/addons/{id}', [AddonController::class, 'update']);
        Route::get('/addons/purchases', [AddonController::class, 'purchases']);

        // Theme Management
        Route::post('/themes', [ThemeController::class, 'store']);
        Route::put('/themes/{id}', [ThemeController::class, 'update']);
    });
});

// Webhook Routes (No Auth)
Route::post('/webhooks/stripe', [OrderController::class, 'stripeWebhook']);
Route::post('/webhooks/promptpay', [OrderController::class, 'promptpayWebhook']);
Route::post('/webhooks/line', [AuthController::class, 'lineWebhook']);
