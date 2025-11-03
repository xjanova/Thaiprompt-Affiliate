<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\LineWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// LINE Webhook (no CSRF, no auth)
Route::post('/webhook/line', [LineWebhookController::class, 'handle'])->name('api.line.webhook');

// API v1
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // App settings (public)
    Route::get('/settings', [DashboardController::class, 'settings']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);
        Route::get('/dashboard/commissions', [DashboardController::class, 'commissions']);
        Route::get('/dashboard/referrals', [DashboardController::class, 'referrals']);

        // Tree (Organization Chart)
        Route::prefix('tree')->group(function () {
            Route::get('/user', [TreeController::class, 'getUserTree']);
            Route::get('/admin/{affiliateId?}', [TreeController::class, 'getAdminTree']);
        });

        // Ranks
        Route::prefix('ranks')->group(function () {
            Route::get('/', [RankController::class, 'index']);
            Route::get('/{rank}', [RankController::class, 'show']);
            Route::get('/user/progress', [RankController::class, 'userProgress']);
            Route::get('/leaderboard', [RankController::class, 'leaderboard']);
            Route::get('/user/eligibility', [RankController::class, 'checkEligibility']);
            Route::post('/promotions/request', [RankController::class, 'requestPromotion']);
        });
    });
});
