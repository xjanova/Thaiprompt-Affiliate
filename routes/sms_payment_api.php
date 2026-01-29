<?php

/**
 * SMS Payment Gateway API Routes
 *
 * Add these routes to your main routes/api.php file:
 *
 * require __DIR__ . '/sms_payment_api.php';
 *
 * Or copy the contents into your existing api.php
 */

use App\Http\Controllers\Api\V1\SmsPaymentController;
use App\Http\Middleware\VerifySmsCheckerDevice;
use Illuminate\Support\Facades\Route;

// Note: When loaded from api.php, the /api prefix is already applied by Laravel.
// So full URL will be: /api/v1/sms-payment/*
Route::prefix('v1/sms-payment')->group(function () {

    // Protected by device API key
    Route::middleware([VerifySmsCheckerDevice::class])->group(function () {
        // Receive SMS notification from Android app
        Route::post('/notify', [SmsPaymentController::class, 'notify']);

        // Check device status
        Route::get('/status', [SmsPaymentController::class, 'status']);

        // Register/update device info
        Route::post('/register-device', [SmsPaymentController::class, 'registerDevice']);
    });

    // Protected by Laravel auth (web/admin)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Generate unique payment amount (called during checkout)
        Route::post('/generate-amount', [SmsPaymentController::class, 'generateAmount']);

        // View notification history (admin)
        Route::get('/notifications', [SmsPaymentController::class, 'notifications']);
    });
});
