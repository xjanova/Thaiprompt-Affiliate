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
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\MLM\MlmDashboardController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\Setup\SetupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\BackupController;

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
});
