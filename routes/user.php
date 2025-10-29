<?php

use App\Http\Controllers\User\DashboardController;
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
