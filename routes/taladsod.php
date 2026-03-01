<?php

use App\Http\Controllers\FreshMarket\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fresh Market Routes (ตลาดสดไทยพร้อม)
|--------------------------------------------------------------------------
|
| เส้นทางทั้งหมดของระบบตลาดสดไทยพร้อม
| URL Prefix: /taladsod
|
*/

Route::prefix('taladsod')->name('taladsod.')->group(function () {

    // ===== Landing Pages — Onboarding ก่อนเพิ่มเพื่อน LINE =====
    Route::get('/start/buyer', [HomeController::class, 'landingBuyer'])->name('landing.buyer');
    Route::get('/start/seller', [HomeController::class, 'landingSeller'])->name('landing.seller');
    Route::get('/start/rider', [HomeController::class, 'landingRider'])->name('landing.rider');

    // ===== หน้าสาธารณะ (ไม่ต้อง login) =====
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/search', [HomeController::class, 'search'])->name('search');
    Route::get('/category/{slug}', [HomeController::class, 'category'])->name('category');
    Route::get('/listing/{slug}', [HomeController::class, 'listing'])->name('listing');
    Route::get('/seller/{id}', [HomeController::class, 'seller'])->name('seller');

    // ===== AJAX Endpoints (ไม่ต้อง login) =====
    Route::get('/api/nearby', [HomeController::class, 'nearby'])->name('api.nearby');
    Route::get('/api/listings', [HomeController::class, 'apiListings'])->name('api.listings');

    // ===== หน้าที่ต้อง login =====
    Route::middleware('auth')->group(function () {
        // ออเดอร์ของฉัน
        Route::get('/orders', [HomeController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [HomeController::class, 'orderDetail'])->name('orders.show');

        // สมัครเป็นผู้ขาย
        Route::get('/register-seller', [HomeController::class, 'registerSeller'])->name('register-seller');
        Route::post('/register-seller', [HomeController::class, 'storeSellerRegistration'])->name('register-seller.store');

        // แผงควบคุมผู้ขาย
        Route::get('/seller-dashboard', [HomeController::class, 'sellerDashboard'])->name('seller.dashboard');

        // ลงขายสินค้า
        Route::get('/create-listing', [HomeController::class, 'createListing'])->name('listing.create');
        Route::post('/listings', [HomeController::class, 'storeListing'])->name('listing.store');
        Route::get('/listings/{listing}/edit', [HomeController::class, 'editListing'])->name('listing.edit');
        Route::put('/listings/{listing}', [HomeController::class, 'updateListing'])->name('listing.update');
        Route::delete('/listings/{listing}', [HomeController::class, 'destroyListing'])->name('listing.destroy');
    });
});
