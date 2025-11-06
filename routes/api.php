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

// Webhooks (no CSRF, no auth)
Route::post('/webhook/line', [LineWebhookController::class, 'handle'])->name('api.line.webhook');

// Payment Gateway Webhooks
Route::prefix('webhook')->name('api.webhook.')->group(function () {
    Route::post('/paysolutions', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePaySolutions'])
        ->middleware('webhook.verify:paysolutions')
        ->name('paysolutions');

    Route::post('/promptpay', [\App\Http\Controllers\PaymentWebhookController::class, 'handlePromptPay'])
        ->middleware('webhook.verify:promptpay')
        ->name('promptpay');

    Route::post('/stripe', [\App\Http\Controllers\PaymentWebhookController::class, 'handleStripe'])
        ->middleware('webhook.verify:stripe')
        ->name('stripe');

    Route::post('/omise', [\App\Http\Controllers\PaymentWebhookController::class, 'handleOmise'])
        ->middleware('webhook.verify:omise')
        ->name('omise');
});

// API v1
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // App settings (public)
    Route::get('/settings', [DashboardController::class, 'settings']);

    // Ranks (public - for marketing tools)
    Route::get('/ranks', [RankController::class, 'index']);

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

            // Binary Tree
            Route::get('/binary', [TreeController::class, 'getUserBinaryTree']);
            Route::get('/binary/admin/{affiliateId?}', [TreeController::class, 'getAdminBinaryTree']);
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
