<?php

use App\Http\Controllers\Pos\PosCashierController;
use App\Http\Controllers\Pos\PosApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS Routes
|--------------------------------------------------------------------------
| Routes for POS Cashier Interface, Customer Display, and PWA API
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

/*
|--------------------------------------------------------------------------
| POS PWA API Routes (สำหรับ Offline-First PWA)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {

    // ============================================
    // Products API
    // ============================================
    Route::prefix('products')->name('products.')->group(function () {
        // ดึงสินค้าทั้งหมดสำหรับ offline cache
        Route::get('/all', [PosApiController::class, 'getAllProducts'])->name('all');

        // ดึงสินค้าที่อัพเดทตั้งแต่ timestamp ที่กำหนด
        Route::get('/updated', [PosApiController::class, 'getUpdatedProducts'])->name('updated');

        // ค้นหาสินค้า
        Route::get('/search', [PosApiController::class, 'searchProducts'])->name('search');

        // ดึงสินค้าเดี่ยว
        Route::get('/{id}', [PosApiController::class, 'getProduct'])->name('show');
    });

    // ============================================
    // Categories API
    // ============================================
    Route::prefix('categories')->name('categories.')->group(function () {
        // ดึงหมวดหมู่ทั้งหมด
        Route::get('/all', [PosApiController::class, 'getAllCategories'])->name('all');

        // ดึงหมวดหมู่ที่อัพเดท
        Route::get('/updated', [PosApiController::class, 'getUpdatedCategories'])->name('updated');
    });

    // ============================================
    // Customers API
    // ============================================
    Route::prefix('customers')->name('customers.')->group(function () {
        // ดึงลูกค้าทั้งหมด
        Route::get('/all', [PosApiController::class, 'getAllCustomers'])->name('all');

        // ดึงลูกค้าที่อัพเดท
        Route::get('/updated', [PosApiController::class, 'getUpdatedCustomers'])->name('updated');

        // ค้นหาลูกค้า
        Route::get('/search', [PosApiController::class, 'searchCustomers'])->name('search');

        // สร้างลูกค้าใหม่
        Route::post('/', [PosApiController::class, 'createCustomer'])->name('store');
    });

    // ============================================
    // Settings API
    // ============================================
    Route::get('/settings', [PosApiController::class, 'getSettings'])->name('settings');

    // ============================================
    // Transactions Sync API
    // ============================================
    Route::prefix('transactions')->name('transactions.')->group(function () {
        // Sync รายการขายเดี่ยว
        Route::post('/sync', [PosApiController::class, 'syncTransaction'])->name('sync');

        // Sync หลายรายการขาย
        Route::post('/bulk-sync', [PosApiController::class, 'bulkSyncTransactions'])->name('bulk-sync');

        // ดึงรายการขายล่าสุด
        Route::get('/recent', [PosApiController::class, 'getRecentTransactions'])->name('recent');
    });

    // ============================================
    // Stock Sync API
    // ============================================
    Route::prefix('stock')->name('stock.')->group(function () {
        // Sync การเคลื่อนไหวสต็อกหลายรายการ
        Route::post('/sync', [PosApiController::class, 'syncStockMovements'])->name('sync');

        // บันทึกการเคลื่อนไหวสต็อกเดี่ยว
        Route::post('/movement', [PosApiController::class, 'recordStockMovement'])->name('movement');
    });

    // ============================================
    // Device API
    // ============================================
    Route::prefix('device')->name('device.')->group(function () {
        // ลงทะเบียน/อัพเดท Device
        Route::post('/register', [PosApiController::class, 'registerDevice'])->name('register');

        // Heartbeat
        Route::post('/heartbeat', [PosApiController::class, 'heartbeat'])->name('heartbeat');
    });

    // ============================================
    // Reports API
    // ============================================
    Route::prefix('reports')->name('reports.')->group(function () {
        // สรุปยอดขายวันนี้
        Route::get('/today', [PosApiController::class, 'getTodaySummary'])->name('today');
    });
});

/*
|--------------------------------------------------------------------------
| POS PWA Pages (Additional Routes)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // PWA Main App
    Route::get('/app', function () {
        return view('pos.pwa.index');
    })->name('app');

    // Offline page
    Route::get('/offline', function () {
        return view('pos.offline');
    })->name('offline');

    // รายงาน
    Route::get('/reports', function () {
        return view('pos.pwa.reports');
    })->name('reports');

    // จัดการสินค้า/สต็อก
    Route::get('/inventory', function () {
        return view('pos.pwa.inventory');
    })->name('inventory');
});
