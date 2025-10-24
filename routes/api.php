<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\MlmController;
use App\Http\Controllers\Api\VendorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public API Routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register-with-referral', [AuthController::class, 'registerWithReferral']);

    // Products (Public)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/vendors', [VendorController::class, 'index']);
});

// Protected API Routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/{item}', [CartController::class, 'update']);
    Route::delete('/cart/{item}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);

    // MLM
    Route::get('/mlm/stats', [MlmController::class, 'stats']);
    Route::get('/mlm/network', [MlmController::class, 'network']);
    Route::get('/mlm/commissions', [MlmController::class, 'commissions']);
    Route::post('/mlm/invite', [MlmController::class, 'sendInvitation']);

    // Vendor Routes
    Route::middleware('role:vendor')->prefix('vendor')->group(function () {
        Route::get('/dashboard', [VendorController::class, 'dashboard']);
        Route::get('/products', [VendorController::class, 'products']);
        Route::post('/products', [VendorController::class, 'storeProduct']);
        Route::put('/products/{product}', [VendorController::class, 'updateProduct']);
        Route::delete('/products/{product}', [VendorController::class, 'deleteProduct']);

        // POS
        Route::post('/pos/session/open', [VendorController::class, 'openPosSession']);
        Route::post('/pos/session/close', [VendorController::class, 'closePosSession']);
        Route::post('/pos/sale', [VendorController::class, 'createPosSale']);
    });
});

// NFC Payment API Routes (No Auth - for POS terminals)
Route::prefix('v1/nfc')->group(function () {
    Route::post('/process', [\App\Http\Controllers\NfcPaymentController::class, 'processPayment']);
    Route::post('/check-balance', [\App\Http\Controllers\NfcPaymentController::class, 'checkBalance']);
    Route::post('/verify', [\App\Http\Controllers\NfcPaymentController::class, 'verifyCard']);
    Route::post('/card-info', [\App\Http\Controllers\NfcPaymentController::class, 'getCardInfo']);
});

// Version Check API (Public)
Route::get('/v1/version', [\App\Http\Controllers\Admin\VersionUpdateController::class, 'apiVersion']);
Route::get('/v1/version/check', [\App\Http\Controllers\Admin\VersionUpdateController::class, 'apiCheckUpdates']);

// MLM Tree API (Protected)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/mlm/tree-data/{userId?}', [\App\Http\Controllers\MlmTreeController::class, 'getTreeData']);
    Route::get('/mlm/tree/node/{user}', [\App\Http\Controllers\MlmTreeController::class, 'getNodeDetails']);
    Route::post('/mlm/tree/add-member', [\App\Http\Controllers\MlmTreeController::class, 'addMember']);
});

// Webhook Routes (No Auth)
Route::post('/webhooks/stripe', [OrderController::class, 'stripeWebhook']);
Route::post('/webhooks/promptpay', [OrderController::class, 'promptpayWebhook']);
Route::post('/webhooks/line', [AuthController::class, 'lineWebhook']);
