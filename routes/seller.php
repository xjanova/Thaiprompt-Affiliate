<?php

use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\OrderManagementController;
use App\Http\Controllers\Seller\PackageController;
use App\Http\Controllers\Seller\StoreController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
Route::get('/marketing', [DashboardController::class, 'marketing'])->name('marketing');
Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
Route::get('/products', [DashboardController::class, 'products'])->name('products');
Route::get('/sales', [DashboardController::class, 'sales'])->name('sales');

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
    Route::get('/{id}', [OrderManagementController::class, 'show'])->name('show');
    Route::put('/{orderId}/items/{itemId}/status', [OrderManagementController::class, 'updateItemStatus'])->name('update-item-status');
    Route::post('/{orderId}/tracking', [OrderManagementController::class, 'addTracking'])->name('add-tracking');
    Route::get('/{id}/print', [OrderManagementController::class, 'print'])->name('print');
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
