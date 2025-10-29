<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes for future use
Route::prefix('v1')->group(function () {
    // Stats API
    Route::get('/stats/overview', function () {
        return response()->json([
            'total_users' => 0,
            'total_affiliates' => 0,
            'total_commissions' => 0,
            'monthly_revenue' => 0,
        ]);
    });
});
