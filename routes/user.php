<?php

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\User\CryptoWalletController;
use App\Http\Controllers\User\CryptoExchangeController;
use App\Http\Controllers\User\RankController;
use App\Http\Controllers\User\MembershipRetentionController;
use App\Http\Controllers\User\KycController;
use App\Http\Controllers\User\ShopController;
use App\Http\Controllers\User\TwoFactorController;
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
Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
Route::post('/profile/update-password', [DashboardController::class, 'updatePassword'])
    ->middleware('turnstile:password_change')
    ->name('profile.update-password');
Route::get('/commissions', [DashboardController::class, 'commissions'])->name('commissions');
Route::get('/referrals', [DashboardController::class, 'referrals'])->name('referrals');
Route::get('/organization', [DashboardController::class, 'organizationChart'])->name('organization');
Route::get('/organization-binary', [DashboardController::class, 'binaryOrganizationChart'])->name('organization.binary');

// Organization Tree API (for web session)
Route::get('/organization/tree-data', [DashboardController::class, 'getOrganizationTreeData'])->name('organization.tree-data');

// KYC Verification
Route::prefix('kyc')->name('kyc.')->group(function () {
    Route::get('/', [KycController::class, 'index'])->name('index');
    Route::get('/create', [KycController::class, 'create'])->name('create');
    Route::post('/', [KycController::class, 'store'])->name('store');
    Route::get('/{kycVerification}', [KycController::class, 'show'])->name('show');
});

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

// Crypto Wallet Management (User)
Route::prefix('crypto-wallet')->name('crypto-wallet.')->group(function () {
    // Dashboard
    Route::get('/', [CryptoWalletController::class, 'index'])->name('index');

    // Wallet Management
    Route::get('/wallets', [CryptoWalletController::class, 'wallets'])->name('wallets');
    Route::post('/create-wallet', [CryptoWalletController::class, 'createWallet'])->name('create-wallet');
    Route::post('/connect-wallet', [CryptoWalletController::class, 'connectWallet'])->name('connect-wallet');
    Route::delete('/wallet/{id}', [CryptoWalletController::class, 'deleteWallet'])->name('wallet.delete');
    Route::post('/wallet/{id}/set-default', [CryptoWalletController::class, 'setDefaultWallet'])->name('wallet.set-default');

    // Deposit Routes
    Route::get('/deposit', [CryptoWalletController::class, 'deposit'])->name('deposit');
    Route::get('/deposit/{currency}', [CryptoWalletController::class, 'depositCurrency'])->name('deposit.currency');
    Route::get('/deposit-address/{currency}', [CryptoWalletController::class, 'getDepositAddress'])->name('deposit.address');

    // Withdrawal Routes
    Route::get('/withdraw', [CryptoWalletController::class, 'withdraw'])->name('withdraw');
    Route::post('/withdraw', [CryptoWalletController::class, 'submitWithdrawal'])
        ->middleware('turnstile:crypto_withdrawal')
        ->name('withdraw.submit');
    Route::get('/withdrawals', [CryptoWalletController::class, 'withdrawals'])->name('withdrawals');
    Route::delete('/withdrawal/{id}/cancel', [CryptoWalletController::class, 'cancelWithdrawal'])->name('withdrawal.cancel');

    // Exchange Routes (THB ↔ Crypto)
    Route::get('/exchange', [CryptoExchangeController::class, 'index'])->name('exchange');
    Route::post('/exchange/preview', [CryptoExchangeController::class, 'preview'])->name('exchange.preview');
    Route::post('/exchange/buy', [CryptoExchangeController::class, 'buyCrypto'])->name('exchange.buy');
    Route::post('/exchange/sell', [CryptoExchangeController::class, 'sellCrypto'])->name('exchange.sell');
    Route::get('/exchange/history', [CryptoExchangeController::class, 'history'])->name('exchange.history');

    // Transaction Routes
    Route::get('/transactions', [CryptoWalletController::class, 'transactions'])->name('transactions');
    Route::get('/transaction/{id}', [CryptoWalletController::class, 'transactionDetail'])->name('transaction.detail');

    // Price & Market Data
    Route::get('/prices', [CryptoWalletController::class, 'getPrices'])->name('prices');
    Route::get('/price/{currency}', [CryptoWalletController::class, 'getPrice'])->name('price');
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
    Route::get('/widget-data', [RankController::class, 'widgetData'])->name('widget-data');
    Route::post('/request-promotion', [RankController::class, 'requestPromotion'])->name('request-promotion');
});

// Email Preferences
Route::prefix('email')->name('email.')->group(function () {
    Route::get('/preferences', [EmailPreferenceController::class, 'index'])->name('preferences');
    Route::put('/preferences', [EmailPreferenceController::class, 'update'])->name('preferences.update');
    Route::post('/preferences/disable-all', [EmailPreferenceController::class, 'disableAll'])->name('preferences.disable-all');
    Route::post('/preferences/enable-all', [EmailPreferenceController::class, 'enableAll'])->name('preferences.enable-all');
});

// Membership Retention
Route::prefix('retention')->name('retention.')->group(function () {
    Route::get('/', [MembershipRetentionController::class, 'index'])->name('index');
    Route::get('/status', [MembershipRetentionController::class, 'getStatus'])->name('status');
    Route::get('/history', [MembershipRetentionController::class, 'history'])->name('history');
    Route::get('/widget-data', [MembershipRetentionController::class, 'getWidgetData'])->name('widget-data');
    Route::get('/how-it-works', [MembershipRetentionController::class, 'howItWorks'])->name('how-it-works');

    // Repair Routes
    Route::get('/repair', [MembershipRetentionController::class, 'showRepair'])->name('repair');
    Route::post('/repair', [MembershipRetentionController::class, 'processRepair'])->name('repair.process');

    // Advance Renewal Routes
    Route::get('/advance-renewal', [MembershipRetentionController::class, 'showAdvanceRenewal'])->name('advance-renewal');
    Route::post('/advance-renewal', [MembershipRetentionController::class, 'processAdvanceRenewal'])->name('advance-renewal.process');
});

// MLM System (User)
Route::prefix('mlm')->name('mlm.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\User\MlmDashboardController::class, 'index'])->name('dashboard');
    Route::get('/genealogy', [\App\Http\Controllers\User\MlmDashboardController::class, 'genealogy'])->name('genealogy');
    Route::get('/commissions', [\App\Http\Controllers\User\MlmDashboardController::class, 'commissions'])->name('commissions');
    Route::get('/referral', [\App\Http\Controllers\User\MlmDashboardController::class, 'referral'])->name('referral');
    Route::get('/team', [\App\Http\Controllers\User\MlmDashboardController::class, 'team'])->name('team');

    // Income Simulator - Marketing Tool
    Route::get('/income-simulator', function () {
        return view('user.mlm.income-simulator');
    })->name('income-simulator');

    // Scenario Simulator - Marketing Tool
    Route::get('/scenario-simulator', function () {
        return view('user.mlm.scenario-simulator');
    })->name('scenario-simulator');

    // Income Comparison - Marketing Tool
    Route::get('/income-comparison', function () {
        return view('user.mlm.income-comparison');
    })->name('income-comparison');

    // Dividend Simulator - Marketing Tool
    Route::get('/dividend-simulator', function () {
        return view('user.mlm.dividend-simulator');
    })->name('dividend-simulator');
});

// Two-Factor Authentication Management
Route::prefix('two-factor')->name('two-factor.')->group(function () {
    Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
    Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable');
    Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable');
    Route::get('/verify', [TwoFactorController::class, 'verify'])->name('verify');
    Route::post('/send-code', [TwoFactorController::class, 'sendCode'])->name('send-code');
    Route::post('/verify-code', [TwoFactorController::class, 'verifyCode'])->name('verify-code');
    Route::post('/verify-recovery-code', [TwoFactorController::class, 'verifyRecoveryCode'])->name('verify-recovery-code');
    Route::get('/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])->name('recovery-codes');
    Route::post('/regenerate-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('regenerate-recovery-codes');
    Route::delete('/trusted-devices/{fingerprint}', [TwoFactorController::class, 'removeTrustedDevice'])->name('remove-trusted-device');
    Route::delete('/trusted-devices', [TwoFactorController::class, 'removeAllTrustedDevices'])->name('remove-all-trusted-devices');
});

// Shop (Main Store - System Products)
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/{slug}', [ShopController::class, 'show'])->name('show');
});

// Theme Management (User)
Route::prefix('themes')->name('themes.')->group(function () {
    Route::get('/', [\App\Http\Controllers\User\ThemeController::class, 'index'])->name('index');
    Route::post('/set', [\App\Http\Controllers\User\ThemeController::class, 'setTheme'])->name('set');
    Route::get('/css', [\App\Http\Controllers\User\ThemeController::class, 'getCss'])->name('css');
});
