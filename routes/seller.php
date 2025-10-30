<?php

use App\Http\Controllers\Seller\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/products', [DashboardController::class, 'products'])->name('products');
Route::get('/sales', [DashboardController::class, 'sales'])->name('sales');
Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
