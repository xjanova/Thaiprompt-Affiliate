<?php

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\User\RankController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\EmailPreferenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
Route::post('/profile/update-password', [DashboardController::class, 'updatePassword'])
    ->middleware('turnstile:password_change')
    ->name('profile.update-password');
Route::get('/commissions', [DashboardController::class, 'commissions'])->name('commissions');
Route::get('/referrals', [DashboardController::class, 'referrals'])->name('referrals');
Route::get('/organization', [DashboardController::class, 'organizationChart'])->name('organization');

// Wallet Management (User)
Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('index');

    // Deposit Routes
    Route::get('/deposit', [WalletController::class, 'deposit'])->name('deposit');
    Route::post('/deposit/promptpay', [WalletController::class, 'depositPromptPay'])->name('deposit.promptpay');
    Route::post('/deposit/bank-transfer', [WalletController::class, 'depositBankTransfer'])->name('deposit.bank-transfer');
    Route::post('/deposit/stripe', [WalletController::class, 'depositStripe'])->name('deposit.stripe');
    Route::post('/deposit/paypal', [WalletController::class, 'depositPayPal'])->name('deposit.paypal');
    Route::get('/deposit/verify/{reference}', [WalletController::class, 'verifyDeposit'])->name('deposit.verify');

    // Withdrawal Routes
    Route::get('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    Route::post('/withdraw', [WalletController::class, 'submitWithdrawal'])
        ->middleware('turnstile:withdrawal_request')
        ->name('withdraw.submit');
    Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
    Route::delete('/withdrawal/{id}/cancel', [WalletController::class, 'cancelWithdrawal'])->name('withdrawal.cancel');

    // Transfer Routes
    Route::get('/transfer', [WalletController::class, 'transfer'])->name('transfer');
    Route::post('/transfer', [WalletController::class, 'submitTransfer'])->name('transfer.submit');

    // Transaction Routes
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');

    // Payment Methods Routes
    Route::get('/payment-methods', [WalletController::class, 'paymentMethods'])->name('payment-methods');
    Route::post('/payment-method', [WalletController::class, 'storePaymentMethod'])->name('payment-method.store');
    Route::post('/payment-method/{id}/set-default', [WalletController::class, 'setDefaultPaymentMethod'])->name('payment-method.set-default');
    Route::delete('/payment-method/{id}', [WalletController::class, 'deletePaymentMethod'])->name('payment-method.delete');
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

    // Bulk operations
    Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
    Route::post('/bulk-mark-as-read', [NotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-as-read');
    Route::delete('/delete-all-read', [NotificationController::class, 'deleteAllRead'])->name('delete-all-read');
});

// Rank & Leaderboard
Route::prefix('ranks')->name('ranks.')->group(function () {
    Route::get('/dashboard', [RankController::class, 'dashboard'])->name('dashboard');
    Route::get('/progress', [RankController::class, 'progress'])->name('progress');
    Route::get('/leaderboard', [RankController::class, 'leaderboard'])->name('leaderboard');
    Route::post('/request-promotion', [RankController::class, 'requestPromotion'])->name('request-promotion');
});

// Email Preferences
Route::prefix('email')->name('email.')->group(function () {
    Route::get('/preferences', [EmailPreferenceController::class, 'index'])->name('preferences');
    Route::put('/preferences', [EmailPreferenceController::class, 'update'])->name('preferences.update');
    Route::post('/preferences/disable-all', [EmailPreferenceController::class, 'disableAll'])->name('preferences.disable-all');
    Route::post('/preferences/enable-all', [EmailPreferenceController::class, 'enableAll'])->name('preferences.enable-all');
});
