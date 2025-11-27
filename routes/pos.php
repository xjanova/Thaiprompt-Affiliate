<?php

use App\Http\Controllers\Pos\PosCashierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS Routes
|--------------------------------------------------------------------------
| Routes for POS Cashier Interface and Customer Display
*/

// POS Cashier Interface (requires authentication)
Route::middleware(['auth'])->group(function () {
    // Main cashier interface
    Route::get('/cashier', [PosCashierController::class, 'index'])->name('cashier');

    // Session Management
    Route::get('/session/open', [PosCashierController::class, 'openSession'])->name('session.open');
    Route::post('/session/open', [PosCashierController::class, 'openSession'])->name('session.open.post');
    Route::get('/session/{session}/close', [PosCashierController::class, 'closeSession'])->name('session.close');
    Route::post('/session/{session}/close', [PosCashierController::class, 'closeSession'])->name('session.close.post');
    Route::get('/session/{session}/report', [PosCashierController::class, 'sessionReport'])->name('session.report');

    // Product Search & Management
    Route::get('/products/search', [PosCashierController::class, 'searchProducts'])->name('products.search');
    Route::get('/products/{product}', [PosCashierController::class, 'getProduct'])->name('products.get');

    // Checkout
    Route::post('/checkout', [PosCashierController::class, 'checkout'])->name('checkout');

    // Receipt
    Route::get('/receipt/{transaction}', [PosCashierController::class, 'receipt'])->name('receipt');
});

// Customer Display (public, no auth required)
Route::get('/customer-display', [PosCashierController::class, 'customerDisplay'])->name('customer-display');

// Customer Display API endpoints (public, no auth required)
Route::prefix('api/display')->name('api.display.')->group(function () {
    // ดึงรายการโฆษณาสำหรับ device
    Route::get('/advertisements', [PosCashierController::class, 'getAdvertisements'])->name('advertisements');

    // อัพเดทสถิติการแสดงโฆษณา
    Route::post('/advertisements/{advertisement}/view', [PosCashierController::class, 'recordAdView'])->name('advertisements.view');

    // ดึงการตั้งค่าสำหรับ device
    Route::get('/settings', [PosCashierController::class, 'getDisplaySettings'])->name('settings');

    // Heartbeat - อัพเดทสถานะออนไลน์ของ device
    Route::post('/heartbeat', [PosCashierController::class, 'displayHeartbeat'])->name('heartbeat');
});

// Cashier to Display sync endpoints (requires authentication)
Route::middleware(['auth'])->prefix('api/display')->name('api.display.')->group(function () {
    // อัพเดทตะกร้าสินค้าไปยัง Customer Display
    Route::post('/cart/update', [PosCashierController::class, 'updateCart'])->name('cart.update');

    // ล้างหน้าจอ Customer Display
    Route::post('/clear', [PosCashierController::class, 'clearDisplay'])->name('clear');
});
