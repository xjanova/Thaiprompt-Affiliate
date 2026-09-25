<?php

use App\Http\Controllers\FreshMarket\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fresh Market Routes (ตลาดสดไทยพร๊อม)
|--------------------------------------------------------------------------
|
| เส้นทางทั้งหมดของระบบตลาดสดไทยพร๊อม
| URL Prefix: /taladsod
|
*/

Route::prefix('taladsod')->name('taladsod.')->group(function () {

    // ===== ติดตามไรเดอร์ (ไม่ต้อง login — ใช้ token) =====
    Route::get('/track/{token}', [\App\Http\Controllers\FreshMarket\RiderTrackingController::class, 'show'])->name('track.show');
    Route::get('/track/{token}/location', [\App\Http\Controllers\FreshMarket\RiderTrackingController::class, 'getLocation'])->name('track.location');
    Route::get('/track/{token}/route', [\App\Http\Controllers\FreshMarket\RiderTrackingController::class, 'getRoute'])->name('track.route');
    // รูปไรเดอร์ (private disk) — เปิดได้เฉพาะคนถือ token ที่ยังไม่หมดอายุ
    Route::get('/track/{token}/rider-photo', [\App\Http\Controllers\FreshMarket\RiderTrackingController::class, 'riderPhoto'])
        ->middleware('throttle:60,1,web-track-rider-photo')
        ->name('track.rider-photo');

    // ===== Landing Pages — Onboarding ก่อนเพิ่มเพื่อน LINE =====
    Route::get('/start/buyer', [HomeController::class, 'landingBuyer'])->name('landing.buyer');
    Route::get('/start/seller', [HomeController::class, 'landingSeller'])->name('landing.seller');
    Route::get('/start/rider', [HomeController::class, 'landingRider'])->name('landing.rider');

    // ===== หน้าสาธารณะ (ไม่ต้อง login) =====
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/search', [HomeController::class, 'search'])->name('search');
    Route::get('/category/{slug}', [HomeController::class, 'category'])->name('category');
    Route::get('/listing/{slug}', [HomeController::class, 'listing'])->name('listing');
    // whereNumber: กัน /seller/orders, /seller/profile ถูกจับเป็นโปรไฟล์ร้าน
    Route::get('/seller/{id}', [HomeController::class, 'seller'])->whereNumber('id')->name('seller');

    // ===== AJAX Endpoints (ไม่ต้อง login) =====
    Route::get('/api/nearby', [HomeController::class, 'nearby'])->name('api.nearby');
    Route::get('/api/listings', [HomeController::class, 'apiListings'])->name('api.listings');
    Route::get('/api/delivery-quote', [HomeController::class, 'deliveryQuote'])
        ->middleware('throttle:30,1,web-fm-delivery-quote')
        ->name('api.delivery-quote');

    // ===== หน้าที่ต้อง login =====
    Route::middleware('auth')->group(function () {
        // ออเดอร์ของฉัน
        Route::get('/orders', [HomeController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [HomeController::class, 'orderDetail'])->name('orders.show');
        Route::post('/orders', [HomeController::class, 'storeOrder'])->name('order.store');
        Route::put('/orders/{order}/confirm', [HomeController::class, 'confirmOrder'])->name('orders.confirm');
        Route::put('/orders/{order}/cancel', [HomeController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/orders/{order}/review', [HomeController::class, 'storeReview'])->name('orders.review');

        // สมัครเป็นผู้ขาย
        Route::get('/register-seller', [HomeController::class, 'registerSeller'])->name('register-seller');
        Route::post('/register-seller', [HomeController::class, 'storeSellerRegistration'])->name('register-seller.store');

        // แผงควบคุมผู้ขาย
        Route::get('/seller-dashboard', [HomeController::class, 'sellerDashboard'])->name('seller.dashboard');

        // จัดการออเดอร์ร้าน (รับ / เตรียม / พร้อมส่ง / ส่งมอบ / ยกเลิก)
        Route::get('/seller/orders', [HomeController::class, 'sellerOrders'])->name('seller.orders');
        Route::get('/seller/orders/{order}', [HomeController::class, 'sellerOrderShow'])->name('seller.orders.show');
        Route::put('/seller/orders/{order}/status', [HomeController::class, 'sellerOrderAction'])->name('seller.orders.status');

        // ตั้งค่าร้าน + สมาชิกรายเดือน
        Route::get('/seller/profile', [HomeController::class, 'sellerProfile'])->name('seller.profile');
        Route::put('/seller/profile', [HomeController::class, 'updateSellerProfile'])->name('seller.profile.update');
        Route::post('/seller/subscribe', [HomeController::class, 'subscribe'])->name('seller.subscribe');

        // ลงขายสินค้า
        Route::get('/create-listing', [HomeController::class, 'createListing'])->name('listing.create');
        Route::post('/listings', [HomeController::class, 'storeListing'])->name('listing.store');
        Route::get('/listings/{listing}/edit', [HomeController::class, 'editListing'])->name('listing.edit');
        Route::put('/listings/{listing}', [HomeController::class, 'updateListing'])->name('listing.update');
        Route::delete('/listings/{listing}', [HomeController::class, 'destroyListing'])->name('listing.destroy');

        // ===== หน้าไรเดอร์ (ต้อง login) =====
        Route::get('/rider/active-job/{job}', [\App\Http\Controllers\FreshMarket\RiderTrackingController::class, 'riderActiveJob'])->name('rider.active-job');
    });
});
