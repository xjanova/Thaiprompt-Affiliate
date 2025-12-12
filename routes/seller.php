<?php

use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\OrderManagementController;
use App\Http\Controllers\Seller\PackageController;
use App\Http\Controllers\Seller\StoreController;
use App\Http\Controllers\Seller\StoreLayoutController;
use App\Http\Controllers\Seller\SellerPosController;
use App\Http\Controllers\Seller\AnalyticsController;
use App\Http\Controllers\Seller\SystemMonitoringController;
use App\Http\Controllers\Seller\StaffController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Seller\UserGuideController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/

// Redirect root /seller to /seller/dashboard
Route::get('/', function () {
    return redirect()->route('seller.dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/marketing', [DashboardController::class, 'marketing'])->name('marketing');

// QR Scanner - redirect ไปหน้า QR Scanner สาธารณะ
Route::get('/qr-scanner', function () {
    return redirect()->route('qr-barcode.scanner');
})->name('qr-scanner');
Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
Route::get('/commissions', [DashboardController::class, 'commissions'])->name('commissions');
Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');

// Seller Guide (คู่มือการใช้งาน)
Route::get('/user-guide', [UserGuideController::class, 'index'])->name('user-guide.index');

// ========================================
// ANALYTICS
// ========================================
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
Route::prefix('analytics')->name('analytics.')->group(function () {
    // Basic Analytics - Note: root route is defined above as 'seller.analytics.index'
    Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
    Route::get('/settings', [AnalyticsController::class, 'settings'])->name('settings');
    Route::put('/settings', [AnalyticsController::class, 'updateSettings'])->name('settings.update');
    Route::post('/cleanup', [AnalyticsController::class, 'cleanup'])->name('cleanup');

    // AI-Powered Analytics
    Route::get('/ai-insights', [AnalyticsController::class, 'aiInsights'])->name('ai-insights');
    Route::get('/segmentation', [AnalyticsController::class, 'customerSegmentation'])->name('segmentation');
    Route::get('/cohort', [AnalyticsController::class, 'cohortAnalysis'])->name('cohort');
    Route::get('/products', [AnalyticsController::class, 'productPerformance'])->name('products');

    // API Endpoints
    Route::get('/api/prediction', [AnalyticsController::class, 'apiRevenuePrediction'])->name('api.prediction');
    Route::get('/api/insights', [AnalyticsController::class, 'apiInsights'])->name('api.insights');

    // System Monitoring (Real-time)
    Route::get('/system-monitoring', [SystemMonitoringController::class, 'index'])->name('system-monitoring');
    Route::get('/system-monitoring/api-metrics', [SystemMonitoringController::class, 'apiMetrics'])->name('system-monitoring.api-metrics');
    Route::get('/system-monitoring/api-info', [SystemMonitoringController::class, 'apiApplicationInfo'])->name('system-monitoring.api-info');
});

// ========================================
// WALLET MANAGEMENT
// ========================================
Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [DashboardController::class, 'walletIndex'])->name('index');
    Route::get('/withdraw', [DashboardController::class, 'walletWithdraw'])->name('withdraw');
    Route::post('/withdraw', [DashboardController::class, 'submitWithdrawal'])->name('withdraw.submit');
    Route::get('/withdrawals', [DashboardController::class, 'withdrawals'])->name('withdrawals');
    Route::delete('/withdrawal/{id}/cancel', [DashboardController::class, 'cancelWithdrawal'])->name('withdrawal.cancel');
});

// ========================================
// REPORTS
// ========================================
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/sales', [DashboardController::class, 'salesReport'])->name('sales');
});

// ========================================
// VENDOR PACKAGES & SUBSCRIPTIONS
// ========================================
Route::get('/packages', [PackageController::class, 'index'])->name('packages');
Route::prefix('packages')->name('packages.')->group(function () {
    Route::post('/{packageId}/subscribe', [PackageController::class, 'subscribe'])->name('subscribe');
    Route::get('/payment/{subscriptionId}', [PackageController::class, 'payment'])->name('payment');
    Route::post('/payment/{subscriptionId}/process', [PackageController::class, 'processPayment'])->name('process-payment');
    Route::post('/cancel', [PackageController::class, 'cancel'])->name('cancel');
});

// ========================================
// VENDOR STORE SETTINGS
// ========================================
Route::prefix('store')->name('store.')->group(function () {
    Route::get('/settings', [StoreController::class, 'settings'])->name('settings');
    Route::put('/settings', [StoreController::class, 'update'])->name('update');

    // ========================================
    // STORE LAYOUT CUSTOMIZATION
    // ปรับแต่ง Layout หน้าร้านค้า
    // ========================================
    Route::prefix('layout')->name('layout.')->group(function () {
        // หน้า Editor หลัก
        Route::get('/', [StoreLayoutController::class, 'index'])->name('index');

        // บันทึกการตั้งค่าแต่ละส่วน (AJAX)
        Route::post('/save-general', [StoreLayoutController::class, 'saveGeneral'])->name('save-general');
        Route::post('/save-slider', [StoreLayoutController::class, 'saveSlider'])->name('save-slider');
        Route::post('/save-featured', [StoreLayoutController::class, 'saveFeatured'])->name('save-featured');
        Route::post('/save-categories', [StoreLayoutController::class, 'saveCategories'])->name('save-categories');
        Route::post('/save-promotion', [StoreLayoutController::class, 'savePromotion'])->name('save-promotion');
        Route::post('/save-social-links', [StoreLayoutController::class, 'saveSocialLinks'])->name('save-social-links');
        Route::post('/save-seo', [StoreLayoutController::class, 'saveSeo'])->name('save-seo');
        Route::post('/save-custom-code', [StoreLayoutController::class, 'saveCustomCode'])->name('save-custom-code');
        Route::post('/save-sections-order', [StoreLayoutController::class, 'saveSectionsOrder'])->name('save-sections-order');

        // อัพโหลดรูปภาพ
        Route::post('/upload-slider-image', [StoreLayoutController::class, 'uploadSliderImage'])->name('upload-slider-image');
        Route::delete('/delete-slider-image', [StoreLayoutController::class, 'deleteSliderImage'])->name('delete-slider-image');
        Route::post('/upload-header-image', [StoreLayoutController::class, 'uploadHeaderImage'])->name('upload-header-image');
        Route::post('/upload-promotion-image', [StoreLayoutController::class, 'uploadPromotionImage'])->name('upload-promotion-image');

        // เผยแพร่/ยกเลิก/รีเซ็ต
        Route::post('/publish', [StoreLayoutController::class, 'publish'])->name('publish');
        Route::post('/unpublish', [StoreLayoutController::class, 'unpublish'])->name('unpublish');
        Route::post('/reset', [StoreLayoutController::class, 'reset'])->name('reset');

        // Preview
        Route::get('/preview', [StoreLayoutController::class, 'preview'])->name('preview');
    });
});

// ========================================
// E-COMMERCE PRODUCT MANAGEMENT
// ========================================
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->name('toggle-status');
    Route::put('/{id}/stock', [ProductController::class, 'updateStock'])->name('update-stock');
    Route::delete('/{productId}/images/{imageId}', [ProductController::class, 'deleteImage'])->name('delete-image');
});

// ========================================
// E-COMMERCE ORDER MANAGEMENT
// ========================================
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderManagementController::class, 'index'])->name('index');

    // Shipping Status Filters (สำหรับเมนูจัดการการจัดส่ง)
    Route::get('/pending-shipping', [OrderManagementController::class, 'pendingShipping'])->name('pending-shipping');
    Route::get('/shipped', [OrderManagementController::class, 'shipped'])->name('shipped');
    Route::get('/delivered', [OrderManagementController::class, 'delivered'])->name('delivered');

    Route::get('/{id}', [OrderManagementController::class, 'show'])->name('show');
    Route::put('/{orderId}/items/{itemId}/status', [OrderManagementController::class, 'updateItemStatus'])->name('update-item-status');
    Route::post('/{orderId}/tracking', [OrderManagementController::class, 'addTracking'])->name('add-tracking');
    Route::get('/{id}/print', [OrderManagementController::class, 'print'])->name('print');
    // Order Tracking & Chat (V2)
    Route::get('/{orderId}/tracking-manage', [OrderManagementController::class, 'tracking'])->name('tracking');
    Route::post('/{orderId}/tracking/history', [OrderManagementController::class, 'addTrackingHistory'])->name('tracking.history');
    Route::get('/{orderId}/messages', [OrderManagementController::class, 'getMessages'])->name('messages');
    Route::post('/{orderId}/messages', [OrderManagementController::class, 'sendMessage'])->name('messages.send');
    Route::post('/{orderId}/messages/read', [OrderManagementController::class, 'markMessagesRead'])->name('messages.read');
});

// ========================================
// CUSTOMER MESSAGES (แชทกับลูกค้า)
// ========================================
Route::prefix('messages')->name('messages.')->group(function () {
    Route::get('/', [OrderManagementController::class, 'allMessages'])->name('index');
    Route::get('/unread', [OrderManagementController::class, 'unreadMessages'])->name('unread');
});

// Notifications
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
    Route::get('/immediate', [NotificationController::class, 'immediate'])->name('immediate');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::post('/{id}/archive', [NotificationController::class, 'archive'])->name('archive');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
    Route::post('/bulk-mark-as-read', [NotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-as-read');
    Route::delete('/delete-all-read', [NotificationController::class, 'deleteAllRead'])->name('delete-all-read');
});

// ========================================
// POS MANAGEMENT
// ========================================
Route::prefix('pos')->name('pos.')->group(function () {
    // POS Terminal (Cashier Interface)
    Route::get('/terminal', [SellerPosController::class, 'terminal'])->name('terminal');
    Route::post('/transaction', [SellerPosController::class, 'createTransaction'])->name('create-transaction');
    Route::get('/transaction/{transaction}/receipt', [SellerPosController::class, 'receipt'])->name('receipt');
    Route::get('/search-products', [SellerPosController::class, 'searchProducts'])->name('search-products');

    // Dashboard
    Route::get('/', [SellerPosController::class, 'index'])->name('index');
    Route::get('/analytics', [SellerPosController::class, 'analytics'])->name('analytics');

    // Devices
    Route::get('/devices', [SellerPosController::class, 'devices'])->name('devices');
    Route::get('/devices/{device}', [SellerPosController::class, 'deviceShow'])->name('devices.show');

    // Transactions
    Route::get('/transactions', [SellerPosController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{transaction}', [SellerPosController::class, 'transactionShow'])->name('transactions.show');

    // Sessions
    Route::get('/sessions', [SellerPosController::class, 'sessions'])->name('sessions');
    Route::get('/sessions/{session}', [SellerPosController::class, 'sessionShow'])->name('sessions.show');

    // Settings
    Route::get('/settings', [SellerPosController::class, 'settings'])->name('settings');
    Route::put('/settings', [SellerPosController::class, 'updateSettings'])->name('settings.update');

    // Categories
    Route::get('/categories', [SellerPosController::class, 'categories'])->name('categories');
    Route::post('/categories', [SellerPosController::class, 'categoryStore'])->name('categories.store');
    Route::put('/categories/{category}', [SellerPosController::class, 'categoryUpdate'])->name('categories.update');
    Route::delete('/categories/{category}', [SellerPosController::class, 'categoryDestroy'])->name('categories.destroy');

    // Advertisements
    Route::get('/advertisements', [SellerPosController::class, 'advertisements'])->name('advertisements');
    Route::post('/advertisements', [SellerPosController::class, 'advertisementStore'])->name('advertisements.store');

    // POS Terminal Registration (สำหรับ Desktop App)
    Route::get('/terminals', [SellerPosController::class, 'terminals'])->name('terminals');
    Route::post('/api-keys', [SellerPosController::class, 'createApiKey'])->name('api-keys.store');
    Route::get('/api-keys/{apiKey}', [SellerPosController::class, 'showApiKey'])->name('api-keys.show');
    Route::post('/api-keys/{apiKey}/toggle-block', [SellerPosController::class, 'toggleApiKeyBlock'])->name('api-keys.toggle-block');
    Route::delete('/api-keys/{apiKey}', [SellerPosController::class, 'deleteApiKey'])->name('api-keys.destroy');
    Route::get('/terminals/{terminal}', [SellerPosController::class, 'showTerminal'])->name('terminals.show');
    Route::post('/terminals/{terminal}/toggle-status', [SellerPosController::class, 'toggleTerminalStatus'])->name('terminals.toggle-status');
    Route::delete('/terminals/{terminal}', [SellerPosController::class, 'deleteTerminal'])->name('terminals.destroy');
});

// ========================================
// STAFF MANAGEMENT (ERP ฟรี)
// ระบบจัดการพนักงานสำหรับเจ้าของร้าน
// ========================================
Route::prefix('staff')->name('staff.')->group(function () {
    // Employees
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::get('/create', [StaffController::class, 'create'])->name('create');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::get('/{employee}', [StaffController::class, 'show'])->name('show');
    Route::get('/{employee}/edit', [StaffController::class, 'edit'])->name('edit');
    Route::put('/{employee}', [StaffController::class, 'update'])->name('update');
    Route::delete('/{employee}', [StaffController::class, 'destroy'])->name('destroy');

    // Departments
    Route::get('/manage/departments', [StaffController::class, 'departments'])->name('departments');
    Route::post('/manage/departments', [StaffController::class, 'storeDepartment'])->name('departments.store');
    Route::delete('/manage/departments/{department}', [StaffController::class, 'destroyDepartment'])->name('departments.destroy');

    // Positions
    Route::get('/manage/positions', [StaffController::class, 'positions'])->name('positions');
    Route::post('/manage/positions', [StaffController::class, 'storePosition'])->name('positions.store');
    Route::delete('/manage/positions/{position}', [StaffController::class, 'destroyPosition'])->name('positions.destroy');

    // Work Shifts
    Route::get('/manage/shifts', [StaffController::class, 'shifts'])->name('shifts');
    Route::post('/manage/shifts', [StaffController::class, 'storeShift'])->name('shifts.store');
    Route::delete('/manage/shifts/{shift}', [StaffController::class, 'destroyShift'])->name('shifts.destroy');
});
