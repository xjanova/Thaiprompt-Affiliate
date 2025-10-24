<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Vendor\VendorDashboardController;
use App\Http\Controllers\Vendor\VendorProductController;
use App\Http\Controllers\Vendor\VendorEmployeeController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CloudflareConfigController;
use App\Http\Controllers\Admin\AdminEmployeeController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\MLM\MlmDashboardController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\Setup\SetupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\MlmTreeController;
use App\Http\Controllers\NfcPaymentController;
use App\Http\Controllers\Admin\NfcCardController;
use App\Http\Controllers\Admin\VersionUpdateController;
use App\Http\Controllers\Admin\ShopVerificationController;
use App\Http\Controllers\Vendor\VendorVerificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Setup Wizard Routes (No authentication required)
Route::middleware('setup.check')->prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('index');
    Route::get('/requirements', [SetupController::class, 'checkRequirements'])->name('requirements');
    Route::get('/database', [SetupController::class, 'database'])->name('database');
    Route::post('/database/test', [SetupController::class, 'testDatabase'])->name('database.test');
    Route::post('/database/save', [SetupController::class, 'saveDatabase'])->name('database.save');
    Route::post('/migrate', [SetupController::class, 'migrate'])->name('migrate');
    Route::post('/seed', [SetupController::class, 'seed'])->name('seed');
    Route::get('/admin', [SetupController::class, 'admin'])->name('admin');
    Route::post('/admin/create', [SetupController::class, 'createAdmin'])->name('admin.create');
    Route::post('/complete', [SetupController::class, 'complete'])->name('complete');
    Route::get('/progress', [SetupController::class, 'progress'])->name('progress');
});

// Home & Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/category/{slug}', [ProductController::class, 'category'])->name('category.show');
Route::get('/vendor/{slug}', [ProductController::class, 'vendor'])->name('vendor.show');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/register/{code}', [RegisterController::class, 'showReferralForm'])->name('register.referral');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Cart & Checkout
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::put('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Customer Dashboard
Route::middleware(['auth'])->prefix('dashboard')->name('customer.')->group(function () {
    Route::get('/', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [CustomerDashboardController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [CustomerDashboardController::class, 'orderDetail'])->name('orders.show');
    Route::get('/profile', [CustomerDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');
});

// MLM Dashboard
Route::middleware(['auth'])->prefix('mlm')->name('mlm.')->group(function () {
    Route::get('/', [MlmDashboardController::class, 'index'])->name('dashboard');
    Route::get('/network', [MlmDashboardController::class, 'network'])->name('network');
    Route::get('/commissions', [MlmDashboardController::class, 'commissions'])->name('commissions');
    Route::get('/referrals', [MlmDashboardController::class, 'referrals'])->name('referrals');
    Route::post('/invite', [MlmDashboardController::class, 'sendInvitation'])->name('invite');

    // MLM Tree Visualization
    Route::get('/tree', [MlmTreeController::class, 'index'])->name('tree.index');
    Route::get('/tree/{user}', [MlmTreeController::class, 'show'])->name('tree.show');
    Route::get('/tree-data/{userId?}', [MlmTreeController::class, 'getTreeData'])->name('tree.data');
    Route::get('/tree/node/{user}', [MlmTreeController::class, 'getNodeDetails'])->name('tree.node');
    Route::post('/tree/add-member', [MlmTreeController::class, 'addMember'])->name('tree.add-member');
});

// Wallet Routes
Route::middleware(['auth'])->prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('index');
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
    Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    Route::get('/withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
});

// Vendor Routes
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/sales', [VendorDashboardController::class, 'sales'])->name('sales');
    Route::get('/earnings', [VendorDashboardController::class, 'earnings'])->name('earnings');

    // Products
    Route::resource('products', VendorProductController::class);

    // POS
    Route::get('/pos', [VendorDashboardController::class, 'pos'])->name('pos');

    // Shop Verification
    Route::get('/verification', [VendorVerificationController::class, 'index'])->name('verification.index');
    Route::get('/verification/create', [VendorVerificationController::class, 'create'])->name('verification.create');
    Route::post('/verification', [VendorVerificationController::class, 'store'])->name('verification.store');
    Route::get('/verification/status', [VendorVerificationController::class, 'show'])->name('verification.show');

    // Employee Management
    Route::resource('employees', VendorEmployeeController::class);
});

// NFC Payment Routes (Public - for POS terminals)
Route::prefix('nfc')->name('nfc.')->group(function () {
    Route::get('/payment', [NfcPaymentController::class, 'index'])->name('payment');
    Route::post('/process', [NfcPaymentController::class, 'processPayment'])->name('process');
    Route::post('/check-balance', [NfcPaymentController::class, 'checkBalance'])->name('check-balance');
    Route::post('/verify', [NfcPaymentController::class, 'verifyCard'])->name('verify');
    Route::post('/card-info', [NfcPaymentController::class, 'getCardInfo'])->name('card-info');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/charts', [DashboardController::class, 'getChartDataApi'])->name('dashboard.charts');

    // Original Admin Routes
    Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
    Route::get('/vendors', [AdminDashboardController::class, 'vendors'])->name('vendors');
    Route::get('/orders', [AdminDashboardController::class, 'orders'])->name('orders');
    Route::get('/products', [AdminDashboardController::class, 'products'])->name('products');
    Route::get('/commissions', [AdminDashboardController::class, 'commissions'])->name('commissions');
    Route::get('/withdrawals', [AdminDashboardController::class, 'withdrawals'])->name('withdrawals');

    // Vendor Management
    Route::post('/vendors/{vendor}/approve', [AdminDashboardController::class, 'approveVendor'])->name('vendors.approve');
    Route::post('/vendors/{vendor}/reject', [AdminDashboardController::class, 'rejectVendor'])->name('vendors.reject');

    // Withdrawal Management
    Route::post('/withdrawals/{withdrawal}/approve', [AdminDashboardController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [AdminDashboardController::class, 'rejectWithdrawal'])->name('withdrawals.reject');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');
    Route::post('/settings/favicon', [SettingsController::class, 'uploadFavicon'])->name('settings.favicon');
    Route::get('/settings/public', [SettingsController::class, 'getPublicSettings'])->name('settings.public');

    // Theme Customization
    Route::prefix('theme')->name('theme.')->group(function () {
        Route::get('/', [ThemeController::class, 'index'])->name('index');
        Route::post('/', [ThemeController::class, 'update'])->name('update');
        Route::post('/logo', [ThemeController::class, 'uploadLogo'])->name('logo');
        Route::post('/favicon', [ThemeController::class, 'uploadFavicon'])->name('favicon');
        Route::post('/subscribe', [ThemeController::class, 'subscribe'])->name('subscribe');
        Route::post('/preview', [ThemeController::class, 'preview'])->name('preview');
    });

    // Backups
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/', [BackupController::class, 'create'])->name('create');
        Route::get('/{backup}/download', [BackupController::class, 'download'])->name('download');
        Route::post('/{backup}/restore', [BackupController::class, 'restore'])->name('restore');
        Route::delete('/{backup}', [BackupController::class, 'destroy'])->name('destroy');
        Route::post('/clean', [BackupController::class, 'clean'])->name('clean');
    });

    // NFC Card Management
    Route::prefix('nfc')->name('nfc.')->group(function () {
        Route::get('/', [NfcCardController::class, 'index'])->name('index');
        Route::get('/create', [NfcCardController::class, 'create'])->name('create');
        Route::post('/', [NfcCardController::class, 'store'])->name('store');
        Route::get('/scan', [NfcCardController::class, 'scan'])->name('scan');
        Route::get('/{nfcCard}', [NfcCardController::class, 'show'])->name('show');
        Route::get('/{nfcCard}/link', [NfcCardController::class, 'linkForm'])->name('link-form');
        Route::post('/{nfcCard}/link', [NfcCardController::class, 'link'])->name('link');
        Route::post('/{nfcCard}/unlink', [NfcCardController::class, 'unlink'])->name('unlink');
        Route::post('/{nfcCard}/activate', [NfcCardController::class, 'activate'])->name('activate');
        Route::post('/{nfcCard}/deactivate', [NfcCardController::class, 'deactivate'])->name('deactivate');
        Route::get('/{nfcCard}/transactions', [NfcCardController::class, 'transactions'])->name('transactions');
        Route::delete('/{nfcCard}', [NfcCardController::class, 'destroy'])->name('destroy');
    });

    // Version Update Management
    Route::prefix('version')->name('version.')->group(function () {
        Route::get('/', [VersionUpdateController::class, 'index'])->name('index');
        Route::post('/check', [VersionUpdateController::class, 'checkUpdates'])->name('check');
        Route::get('/update', [VersionUpdateController::class, 'showUpdate'])->name('update');
        Route::post('/update/start', [VersionUpdateController::class, 'startUpdate'])->name('start');
        Route::post('/update/complete', [VersionUpdateController::class, 'completeUpdate'])->name('complete');
        Route::post('/update/fail', [VersionUpdateController::class, 'failUpdate'])->name('fail');
        Route::get('/history', [VersionUpdateController::class, 'history'])->name('history');
        Route::get('/releases', [VersionUpdateController::class, 'releases'])->name('releases');
    });

    // Shop Verification Management
    Route::prefix('verification')->name('verification.')->group(function () {
        Route::get('/', [ShopVerificationController::class, 'index'])->name('index');
        Route::get('/pending', [ShopVerificationController::class, 'pending'])->name('pending');
        Route::get('/statistics', [ShopVerificationController::class, 'statistics'])->name('statistics');
        Route::get('/{verification}', [ShopVerificationController::class, 'show'])->name('show');
        Route::post('/{verification}/approve', [ShopVerificationController::class, 'approve'])->name('approve');
        Route::post('/{verification}/reject', [ShopVerificationController::class, 'reject'])->name('reject');
    });

    // Cloudflare Configuration (Super Admin Only)
    Route::prefix('cloudflare')->name('cloudflare.')->group(function () {
        Route::get('/', [CloudflareConfigController::class, 'index'])->name('index');
        Route::post('/update', [CloudflareConfigController::class, 'update'])->name('update');
        Route::post('/test', [CloudflareConfigController::class, 'testConnection'])->name('test');
        Route::get('/statistics', [CloudflareConfigController::class, 'statistics'])->name('statistics');
        Route::get('/logs', [CloudflareConfigController::class, 'logs'])->name('logs');
    });

    // Admin Employee Management (Super Admin Only)
    Route::resource('employees', AdminEmployeeController::class);
});
