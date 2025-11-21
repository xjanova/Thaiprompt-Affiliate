<?php

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\SettingsController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\User\CryptoWalletController;
use App\Http\Controllers\User\CryptoExchangeController;
use App\Http\Controllers\User\RankController;
use App\Http\Controllers\User\MembershipRetentionController;
use App\Http\Controllers\User\KycController;
use App\Http\Controllers\User\TwoFactorController;
use App\Http\Controllers\User\TicketController;
use App\Http\Controllers\User\InvestmentController;
use App\Http\Controllers\User\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\EmailPreferenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

// Redirect root /user to /user/dashboard
Route::get('/', function () {
    return redirect()->route('user.dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
Route::put('/profile', [DashboardController::class, 'updateProfile'])
    ->middleware('two-factor:profile_change')
    ->name('profile.update');
Route::post('/profile/update-password', [DashboardController::class, 'updatePassword'])
    ->middleware(['turnstile:password_change', 'two-factor:password_change'])
    ->name('profile.update-password');
Route::get('/commissions', [DashboardController::class, 'commissions'])->name('commissions');

// User Settings
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::delete('/settings/sessions/{sessionId}', [SettingsController::class, 'deleteSession'])->name('settings.sessions.delete');
Route::delete('/settings/sessions', [SettingsController::class, 'deleteOtherSessions'])->name('settings.sessions.delete-others');

// MLM Prospects (ผู้มุ่งหวัง)
Route::prefix('prospects')->name('prospects.')->group(function () {
    Route::get('/', [\App\Http\Controllers\User\MlmProspectController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\User\MlmProspectController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\User\MlmProspectController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\User\MlmProspectController::class, 'show'])->name('show');
});

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

    // Topup Routes (Wallet Topup Packages)
    Route::get('/topup', [WalletController::class, 'topup'])->name('topup');
    Route::post('/topup/process', [WalletController::class, 'processTopup'])->name('topup.process');

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
        ->middleware(['turnstile:withdrawal_request', 'two-factor:withdrawal'])
        ->name('withdraw.submit');
    Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
    Route::delete('/withdrawal/{id}/cancel', [WalletController::class, 'cancelWithdrawal'])->name('withdrawal.cancel');

    // Transfer Routes
    Route::get('/transfer', [WalletController::class, 'transfer'])->name('transfer');
    Route::post('/transfer', [WalletController::class, 'submitTransfer'])
        ->middleware('two-factor:transfer')
        ->name('transfer.submit');

    // Transaction Routes
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');

    // Payment Methods Routes
    Route::get('/payment-methods', [WalletController::class, 'paymentMethods'])->name('payment-methods');
    Route::post('/payment-method', [WalletController::class, 'storePaymentMethod'])
        ->middleware('two-factor:payment_method_change')
        ->name('payment-method.store');
    Route::post('/payment-method/{id}/set-default', [WalletController::class, 'setDefaultPaymentMethod'])
        ->middleware('two-factor:payment_method_change')
        ->name('payment-method.set-default');
    Route::delete('/payment-method/{id}', [WalletController::class, 'deletePaymentMethod'])
        ->middleware('two-factor:payment_method_change')
        ->name('payment-method.delete');
});

// Crypto Wallet Management (User)
Route::prefix('crypto-wallet')->name('crypto-wallet.')->group(function () {
    // Dashboard (no wallet required - shows create wallet option)
    Route::get('/', [CryptoWalletController::class, 'index'])->name('index');

    // Wallet Management (no wallet required - for creating first wallet)
    Route::get('/wallets', [CryptoWalletController::class, 'wallets'])->name('wallets');
    Route::get('/wallet-management', [CryptoWalletController::class, 'walletManagement'])->name('wallet-management');
    Route::post('/create-wallet', [CryptoWalletController::class, 'createWallet'])->name('create-wallet');
    Route::post('/connect-wallet', [CryptoWalletController::class, 'connectWallet'])->name('connect-wallet');

    // Routes requiring crypto wallet to exist
    Route::middleware(['crypto.wallet.exists'])->group(function () {
        Route::delete('/wallet/{id}', [CryptoWalletController::class, 'deleteWallet'])->name('wallet.delete');
        Route::post('/wallet/{id}/set-default', [CryptoWalletController::class, 'setDefaultWallet'])->name('wallet.set-default');

        // Premium Trading & Portfolio (wallet must exist)
        Route::get('/trading', [CryptoWalletController::class, 'tradingDashboard'])->name('trading');
        Route::get('/portfolio', [CryptoWalletController::class, 'portfolio'])->name('portfolio');

        // Deposit Routes (wallet must exist)
        Route::get('/deposit', [CryptoWalletController::class, 'deposit'])->name('deposit');
        Route::get('/deposit/{currency}', [CryptoWalletController::class, 'depositCurrency'])->name('deposit.currency');
        Route::get('/deposit-address/{currency}', [CryptoWalletController::class, 'getDepositAddress'])->name('deposit.address');

        // Transaction Routes (wallet must exist)
        Route::get('/transactions', [CryptoWalletController::class, 'transactions'])->name('transactions');
        Route::get('/transaction/{id}', [CryptoWalletController::class, 'transactionDetail'])->name('transaction.detail');

        // Routes requiring active wallet status
        Route::middleware(['crypto.wallet.active'])->group(function () {
            // Withdrawal Routes (wallet must be active)
            Route::get('/withdraw', [CryptoWalletController::class, 'withdraw'])->name('withdraw');
            Route::post('/withdraw', [CryptoWalletController::class, 'submitWithdrawal'])
                ->middleware(['turnstile:crypto_withdrawal', 'two-factor:withdrawal'])
                ->name('withdraw.submit');
            Route::get('/withdrawals', [CryptoWalletController::class, 'withdrawals'])->name('withdrawals');
            Route::delete('/withdrawal/{id}/cancel', [CryptoWalletController::class, 'cancelWithdrawal'])->name('withdrawal.cancel');

            // Exchange Routes (wallet must be active)
            Route::get('/exchange', [CryptoExchangeController::class, 'index'])->name('exchange');
            Route::post('/exchange/preview', [CryptoExchangeController::class, 'preview'])->name('exchange.preview');
            Route::post('/exchange/buy', [CryptoExchangeController::class, 'buyCrypto'])->name('exchange.buy');
            Route::post('/exchange/sell', [CryptoExchangeController::class, 'sellCrypto'])->name('exchange.sell');
            Route::get('/exchange/history', [CryptoExchangeController::class, 'history'])->name('exchange.history');
        });
    });

    // Price & Market Data (no wallet required - public data)
    Route::get('/prices', [CryptoWalletController::class, 'getPrices'])->name('prices');
    Route::get('/price/{currency}', [CryptoWalletController::class, 'getPrice'])->name('price');
});

// TPIX Native Blockchain Wallet (User)
Route::prefix('tpix')->name('tpix.')->group(function () {
    // Wallet Dashboard
    Route::get('/wallet', [\App\Http\Controllers\TPIXWalletController::class, 'index'])->name('wallet');

    // Deposit
    Route::get('/deposit', [\App\Http\Controllers\TPIXWalletController::class, 'deposit'])->name('deposit');

    // Withdrawal
    Route::get('/withdrawal', [\App\Http\Controllers\TPIXWalletController::class, 'withdrawal'])->name('withdrawal');
    Route::post('/withdrawal', [\App\Http\Controllers\TPIXWalletController::class, 'processWithdrawal'])
        ->middleware(['turnstile:tpix_withdrawal', 'two-factor:withdrawal'])
        ->name('withdrawal.process');

    // Transactions
    Route::get('/transactions', [\App\Http\Controllers\TPIXWalletController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{id}', [\App\Http\Controllers\TPIXWalletController::class, 'transactionDetails'])->name('transactions.details');

    // Send TPIX (P2P transfer)
    Route::get('/send', [\App\Http\Controllers\TPIXWalletController::class, 'send'])->name('send');
    Route::post('/send', [\App\Http\Controllers\TPIXWalletController::class, 'processSend'])
        ->middleware('two-factor:transfer')
        ->name('send.process');
});

// TPIX Token Marketplace & Management (User)
Route::prefix('tokens')->name('tokens.')->group(function () {
    // Token Marketplace
    Route::get('/', [\App\Http\Controllers\User\TokenController::class, 'index'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\User\TokenController::class, 'show'])->name('show');

    // My Tokens (Creator View)
    Route::get('/my/tokens', [\App\Http\Controllers\User\TokenController::class, 'myTokens'])->name('my-tokens');
    Route::get('/my/balances', [\App\Http\Controllers\User\TokenController::class, 'myBalances'])->name('my-balances');
    Route::get('/my/portfolio', [\App\Http\Controllers\User\TokenController::class, 'portfolio'])->name('portfolio');

    // Create Token
    Route::get('/create', [\App\Http\Controllers\User\TokenController::class, 'create'])->name('create');
    Route::post('/create', [\App\Http\Controllers\User\TokenController::class, 'store'])->name('store');

    // Deploy Token
    Route::post('/{id}/deploy', [\App\Http\Controllers\User\TokenController::class, 'deploy'])->name('deploy');

    // Token Transfer
    Route::get('/{id}/transfer', [\App\Http\Controllers\User\TokenController::class, 'showTransfer'])->name('show-transfer');
    Route::post('/{id}/transfer', [\App\Http\Controllers\User\TokenController::class, 'transfer'])
        ->middleware('two-factor:transfer')
        ->name('transfer');

    // Buy/Sell Tokens
    Route::post('/{id}/buy', [\App\Http\Controllers\User\TokenController::class, 'buy'])->name('buy');
    Route::post('/{id}/sell', [\App\Http\Controllers\User\TokenController::class, 'sell'])->name('sell');

    // Referrals
    Route::get('/referrals', [\App\Http\Controllers\User\TokenController::class, 'referrals'])->name('referrals');
    Route::post('/referrals/use', [\App\Http\Controllers\User\TokenController::class, 'useReferralCode'])->name('referrals.use');
    Route::post('/referrals/verify', [\App\Http\Controllers\User\TokenController::class, 'verifyReferralCode'])->name('referrals.verify');
    Route::get('/referrals/my-codes', [\App\Http\Controllers\User\TokenController::class, 'myReferralCodes'])->name('referrals.my-codes');

    // Token Transactions History
    Route::get('/{id}/transactions', [\App\Http\Controllers\User\TokenController::class, 'transactions'])->name('transactions');
});

// TPIX DEX (Decentralized Exchange)
Route::prefix('dex')->name('dex.')->group(function () {
    // Swap
    Route::get('/swap', [\App\Http\Controllers\User\DEXController::class, 'swap'])->name('swap');

    // Liquidity Pools
    Route::get('/pools', [\App\Http\Controllers\User\DEXController::class, 'pools'])->name('pools');
    Route::get('/add-liquidity', [\App\Http\Controllers\User\DEXController::class, 'addLiquidity'])->name('add-liquidity');
    Route::get('/remove-liquidity/{positionId}', [\App\Http\Controllers\User\DEXController::class, 'removeLiquidity'])->name('remove-liquidity');

    // My DEX Activity
    Route::get('/my-positions', [\App\Http\Controllers\User\DEXController::class, 'myPositions'])->name('my-positions');
    Route::get('/swap-history', [\App\Http\Controllers\User\DEXController::class, 'swapHistory'])->name('swap-history');
});

// TPIX Staking
Route::prefix('staking')->name('staking.')->group(function () {
    // Staking Pools
    Route::get('/', [\App\Http\Controllers\User\StakingController::class, 'index'])->name('index');
    Route::get('/pools/{poolId}', [\App\Http\Controllers\User\StakingController::class, 'show'])->name('show');

    // Stake Actions
    Route::get('/stake/{poolId}', [\App\Http\Controllers\User\StakingController::class, 'stakeForm'])->name('stake-form');

    // My Staking
    Route::get('/history', [\App\Http\Controllers\User\StakingController::class, 'history'])->name('history');
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

// Wealth Guide E-book - Complete guide from beginner to expert
Route::get('/wealth-guide', function () {
    return view('user.wealth-guide');
})->name('wealth-guide');

// Wealth Guide Pro - Enhanced version with 3D visualizations
Route::get('/wealth-guide-pro', function () {
    return view('user.wealth-guide-pro');
})->name('wealth-guide-pro');

// Marketplace Affiliate System (User)
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/products', [MarketplaceController::class, 'products'])->name('products');
});

// Investment & Staking System (User)
Route::prefix('investments')->name('investments.')->group(function () {
    Route::get('/', [InvestmentController::class, 'index'])->name('index');
    Route::get('/plans', [InvestmentController::class, 'plans'])->name('plans');
    Route::get('/plans/{plan}', [InvestmentController::class, 'showPlan'])->name('plans.show');
    Route::get('/history', [InvestmentController::class, 'history'])->name('history');
    Route::post('/invest', [InvestmentController::class, 'store'])->name('store');
    Route::post('/calculate-roi', [InvestmentController::class, 'calculateROI'])->name('calculate-roi');

    Route::get('/{position}', [InvestmentController::class, 'show'])->name('show');
    Route::post('/{position}/withdraw', [InvestmentController::class, 'withdraw'])->name('withdraw');
    Route::get('/{position}/distributions', [InvestmentController::class, 'distributions'])->name('distributions');
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

// Redirect old user shop to main frontend shop
Route::get('shop', function () {
    return redirect('/shop');
});
Route::get('shop/{slug}', function ($slug) {
    return redirect('/shop/' . $slug);
});

// Theme Management (User) - DISABLED: ใช้ Arrow X เป็นค่าเริ่มต้นทุกคนแล้ว
// Route::prefix('themes')->name('themes.')->group(function () {
//     Route::get('/', [\App\Http\Controllers\User\ThemeController::class, 'index'])->name('index');
// });

// Ticket Support System (User)
Route::prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/create', [TicketController::class, 'create'])->name('create');
    Route::post('/', [TicketController::class, 'store'])->name('store');
    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
    Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply');
    Route::post('/{ticket}/close', [TicketController::class, 'close'])->name('close');
});

// AI Gen System (User)
Route::prefix('ai-gen')->name('ai-gen.')->group(function () {
    Route::get('/', [\App\Http\Controllers\User\AiGenController::class, 'index'])->name('index');
    Route::get('/packages', [\App\Http\Controllers\User\AiGenController::class, 'packages'])->name('packages');
    Route::get('/my-creations', [\App\Http\Controllers\User\AiGenController::class, 'myCreations'])->name('my-creations');
    Route::get('/explore', [\App\Http\Controllers\User\AiGenController::class, 'explore'])->name('explore');
});

// Recruit Page Management (User - เครื่องมือการตลาด)
Route::prefix('marketing')->name('marketing.')->group(function () {
    Route::prefix('recruit')->name('recruit.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\RecruitPageController::class, 'index'])->name('index');
        Route::put('/update', [\App\Http\Controllers\User\RecruitPageController::class, 'update'])->name('update');
        Route::get('/leads', [\App\Http\Controllers\User\RecruitPageController::class, 'leads'])->name('leads');
        Route::get('/analytics', [\App\Http\Controllers\User\RecruitPageController::class, 'analytics'])->name('analytics');
    });
});
