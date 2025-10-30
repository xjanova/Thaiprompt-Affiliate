<?php

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
Route::get('/commissions', [DashboardController::class, 'commissions'])->name('commissions');
Route::get('/referrals', [DashboardController::class, 'referrals'])->name('referrals');

// Wallet Management (User)
Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('index');
    Route::get('/deposit', [WalletController::class, 'deposit'])->name('deposit');
    Route::get('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    Route::post('/withdraw', [WalletController::class, 'submitWithdrawal'])->name('withdraw.submit');
    Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals/{id}/cancel', [WalletController::class, 'cancelWithdrawal'])->name('withdrawals.cancel');
    Route::get('/transfer', [WalletController::class, 'transfer'])->name('transfer');
    Route::post('/transfer', [WalletController::class, 'submitTransfer'])->name('transfer.submit');
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
    Route::get('/payment-methods', [WalletController::class, 'paymentMethods'])->name('payment-methods');
    Route::post('/payment-methods', [WalletController::class, 'storePaymentMethod'])->name('payment-methods.store');
    Route::delete('/payment-methods/{id}', [WalletController::class, 'deletePaymentMethod'])->name('payment-methods.delete');
});

// Notifications
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::post('/{id}/archive', [NotificationController::class, 'archive'])->name('archive');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
});
