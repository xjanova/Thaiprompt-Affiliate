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
    // ร้านที่เปิดอยู่ใกล้คุณ (หน้าแรก — รวมรถเข็น/ตลาดนัดที่เปิดอยู่ตอนนี้) (2026-09-26)
    Route::get('/api/nearby-shops', [HomeController::class, 'nearbyShops'])
        ->middleware('throttle:60,1,web-fm-nearby-shops')
        ->name('api.nearby-shops');
    // ตำแหน่งร้านตอนนี้ (poll บนหน้าร้าน — ร้านเคลื่อนที่เปิดเผยตำแหน่งเฉพาะตอนเปิดร้าน) (2026-09-26)
    Route::get('/shop/{id}/location', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'shopLocation'])
        ->whereNumber('id')
        ->middleware('throttle:120,1,web-fm-shop-location')
        ->name('shop.location');

    // ===== หน้าที่ต้อง login =====
    Route::middleware('auth')->group(function () {
        // ผู้ใช้ที่ยังไม่ login กดสั่งซื้อ/ติดตามร้าน → ผ่านหน้า login แล้วกลับหน้าเดิม (รับเฉพาะ path /taladsod/...)
        Route::get('/login-continue', [HomeController::class, 'loginContinue'])->name('login-continue');

        // ออเดอร์ของฉัน
        Route::get('/orders', [HomeController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [HomeController::class, 'orderDetail'])->name('orders.show');
        // สถานะล่าสุด (JSON) — หน้ารายละเอียดออเดอร์ poll เพื่อรีเฟรชเมื่อร้าน/ไรเดอร์อัปเดต
        Route::get('/orders/{order}/status', [HomeController::class, 'orderStatus'])
            ->middleware('throttle:60,1,web-fm-order-status')
            ->name('orders.status-json');
        Route::post('/orders', [HomeController::class, 'storeOrder'])->name('order.store');
        Route::put('/orders/{order}/confirm', [HomeController::class, 'confirmOrder'])->name('orders.confirm');
        Route::put('/orders/{order}/cancel', [HomeController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/orders/{order}/review', [HomeController::class, 'storeReview'])->name('orders.review');

        // ตะกร้าหลายรายการ (แยกตามร้าน) + ชำระเงินทีละร้าน (2026-09-26)
        Route::get('/cart', [\App\Http\Controllers\FreshMarket\CartController::class, 'index'])->name('cart');
        Route::get('/cart/data', [\App\Http\Controllers\FreshMarket\CartController::class, 'data'])->name('cart.data');
        Route::get('/cart/quote', [\App\Http\Controllers\FreshMarket\CartController::class, 'quote'])
            ->middleware('throttle:30,1,web-fm-cart-quote')
            ->name('cart.quote');
        Route::post('/cart/items', [\App\Http\Controllers\FreshMarket\CartController::class, 'store'])
            ->middleware('throttle:60,1,web-fm-cart-add')
            ->name('cart.items.store');
        Route::put('/cart/items/{item}', [\App\Http\Controllers\FreshMarket\CartController::class, 'update'])->whereNumber('item')->name('cart.items.update');
        Route::delete('/cart/items/{item}', [\App\Http\Controllers\FreshMarket\CartController::class, 'destroy'])->whereNumber('item')->name('cart.items.destroy');
        Route::delete('/cart', [\App\Http\Controllers\FreshMarket\CartController::class, 'clear'])->name('cart.clear');
        Route::get('/checkout/{seller}', [\App\Http\Controllers\FreshMarket\CartController::class, 'checkout'])->whereNumber('seller')->name('checkout');
        Route::post('/checkout', [\App\Http\Controllers\FreshMarket\CartController::class, 'placeOrder'])
            ->middleware('throttle:10,1,web-fm-checkout')
            ->name('checkout.store');

        // สมัครเป็นผู้ขาย
        Route::get('/register-seller', [HomeController::class, 'registerSeller'])->name('register-seller');
        Route::post('/register-seller', [HomeController::class, 'storeSellerRegistration'])->name('register-seller.store');

        // แผงควบคุมผู้ขาย
        Route::get('/seller-dashboard', [HomeController::class, 'sellerDashboard'])->name('seller.dashboard');

        // จัดการออเดอร์ร้าน (รับ / เตรียม / พร้อมส่ง / ส่งมอบ / ยกเลิก)
        Route::get('/seller/orders', [HomeController::class, 'sellerOrders'])->name('seller.orders');
        // ตัวเลขออเดอร์ล่าสุด (JSON) — หน้าออเดอร์/แดชบอร์ดร้าน poll เพื่อรีเฟรชเมื่อมีออเดอร์ใหม่
        Route::get('/seller/orders-poll', [HomeController::class, 'sellerOrdersPoll'])
            ->middleware('throttle:60,1,web-fm-seller-orders-poll')
            ->name('seller.orders.poll');
        // สินค้าทั้งหมดของร้าน + รายได้ร้าน (2026-09-26)
        Route::get('/seller/listings', [HomeController::class, 'sellerListings'])->name('seller.listings');
        Route::get('/seller/earnings', [HomeController::class, 'sellerEarnings'])->name('seller.earnings');
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

        // ===== ร้านรถเข็น/ตลาดนัด: เปิดร้านที่นี่วันนี้ / ตำแหน่งสด / ปิดร้าน (2026-09-26) =====
        Route::get('/seller/presence', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'sellerPresence'])->name('seller.presence');
        Route::post('/seller/open', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'sellerOpen'])
            ->middleware('throttle:10,1,web-fm-seller-open')
            ->name('seller.open');
        Route::post('/seller/location', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'sellerLocation'])
            ->middleware('throttle:6,1,web-fm-seller-location')
            ->name('seller.location');
        Route::post('/seller/close', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'sellerClose'])
            ->middleware('throttle:10,1,web-fm-seller-close')
            ->name('seller.close');
        Route::post('/seller/mobile-mode', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'sellerMobileMode'])
            ->middleware('throttle:10,1,web-fm-seller-mobile-mode')
            ->name('seller.mobile-mode');

        // ติดตามร้าน (แจ้งเตือนเมื่อร้านเปิด)
        Route::post('/shop/{id}/follow', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'follow'])
            ->whereNumber('id')
            ->middleware('throttle:30,1,web-fm-shop-follow')
            ->name('shop.follow');
        Route::delete('/shop/{id}/follow', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'unfollow'])
            ->whereNumber('id')
            ->middleware('throttle:30,1,web-fm-shop-follow')
            ->name('shop.unfollow');
        Route::get('/me/followed-shops', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'followedShops'])->name('followed-shops');

        // ผู้ซื้อติดตามไรเดอร์ + แชร์ตำแหน่งตัวเองให้ไรเดอร์ (ออเดอร์ของตัวเองเท่านั้น) — source = shop | fresh-market
        Route::get('/delivery/{source}/{id}/rider-location', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'deliveryRiderLocation'])
            ->where('source', 'shop|fresh-market')
            ->whereNumber('id')
            ->middleware('throttle:60,1,web-delivery-rider-location')
            ->name('delivery.rider-location');
        Route::post('/delivery/{source}/{id}/share-location', [\App\Http\Controllers\FreshMarket\ShopPresenceController::class, 'deliveryShareLocation'])
            ->where('source', 'shop|fresh-market')
            ->whereNumber('id')
            ->middleware('throttle:20,1,web-delivery-share-location')
            ->name('delivery.share-location');

        // ===== หน้าไรเดอร์ (ต้อง login) =====
        Route::get('/rider/active-job/{job}', [\App\Http\Controllers\FreshMarket\RiderTrackingController::class, 'riderActiveJob'])->name('rider.active-job');
    });
});
