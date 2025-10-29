<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// User Management
Route::resource('users', UserController::class);
Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');
Route::get('users/{user}/dashboard', [UserController::class, 'viewDashboard'])->name('users.dashboard');

// Affiliate Management
Route::resource('affiliates', AffiliateController::class);
Route::get('affiliates/tree-view', [AffiliateController::class, 'treeView'])->name('affiliates.tree');
Route::get('affiliates/{affiliate}/tree', [AffiliateController::class, 'tree'])->name('affiliates.tree.single');
Route::post('affiliates/{affiliate}/move', [AffiliateController::class, 'move'])->name('affiliates.move');

// Commission Management
Route::resource('commissions', CommissionController::class);
Route::post('commissions/{commission}/approve', [CommissionController::class, 'approve'])->name('commissions.approve');
Route::post('commissions/{commission}/reject', [CommissionController::class, 'reject'])->name('commissions.reject');
Route::post('commissions/{commission}/pay', [CommissionController::class, 'pay'])->name('commissions.pay');

// Settings
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
Route::put('settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');

// Slider Management
Route::resource('sliders', SliderController::class);
Route::post('sliders/reorder', [SliderController::class, 'reorder'])->name('sliders.reorder');

// Pages Management (CMS)
Route::resource('pages', PageController::class);
Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');

// Home Sections Management
Route::resource('home-sections', HomeSectionController::class);
Route::post('home-sections/reorder', [HomeSectionController::class, 'reorder'])->name('home-sections.reorder');
Route::post('home-sections/{homeSection}/toggle', [HomeSectionController::class, 'toggle'])->name('home-sections.toggle');

// SEO Management
Route::resource('seo', SeoController::class);

// Wallet Management
Route::prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('index');
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
    Route::get('/logs', [WalletController::class, 'logs'])->name('logs');
    Route::get('/settings', [WalletController::class, 'settings'])->name('settings');
    Route::post('/set-pin', [WalletController::class, 'setPin'])->name('set-pin');
    Route::post('/deposit', [WalletController::class, 'deposit'])->name('deposit');
    Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    Route::post('/transfer', [WalletController::class, 'transfer'])->name('transfer');
    Route::post('/lock', [WalletController::class, 'lock'])->name('lock');
    Route::post('/unlock', [WalletController::class, 'unlock'])->name('unlock');
});
