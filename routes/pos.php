<?php

use App\Http\Controllers\Pos\PosCashierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS Routes
|--------------------------------------------------------------------------
| Routes for POS Cashier Interface and Customer Display
*/

// POS Cashier Interface (requires authentication)
Route::middleware(['auth'])->group(function () {
    // Main cashier interface
    Route::get('/cashier', [PosCashierController::class, 'index'])->name('cashier');

    // Session Management
    Route::get('/session/open', [PosCashierController::class, 'openSession'])->name('session.open');
    Route::post('/session/open', [PosCashierController::class, 'openSession'])->name('session.open.post');
    Route::get('/session/{session}/close', [PosCashierController::class, 'closeSession'])->name('session.close');
    Route::post('/session/{session}/close', [PosCashierController::class, 'closeSession'])->name('session.close.post');
    Route::get('/session/{session}/report', [PosCashierController::class, 'sessionReport'])->name('session.report');

    // Product Search & Management
    Route::get('/products/search', [PosCashierController::class, 'searchProducts'])->name('products.search');
    Route::get('/products/{product}', [PosCashierController::class, 'getProduct'])->name('products.get');

    // Checkout
    Route::post('/checkout', [PosCashierController::class, 'checkout'])->name('checkout');

    // Receipt
    Route::get('/receipt/{transaction}', [PosCashierController::class, 'receipt'])->name('receipt');
});

// Customer Display (public, no auth required)
Route::get('/customer-display', [PosCashierController::class, 'customerDisplay'])->name('customer-display');
