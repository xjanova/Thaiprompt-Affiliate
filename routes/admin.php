<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// User Management
Route::resource('users', UserController::class);

// Affiliate Management
Route::resource('affiliates', AffiliateController::class);
Route::get('affiliates/{affiliate}/tree', [AffiliateController::class, 'tree'])->name('affiliates.tree');

// Commission Management
Route::resource('commissions', CommissionController::class);
Route::post('commissions/{commission}/approve', [CommissionController::class, 'approve'])->name('commissions.approve');
Route::post('commissions/{commission}/reject', [CommissionController::class, 'reject'])->name('commissions.reject');

// Settings
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
