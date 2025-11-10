<?php

use App\Http\Controllers\HotelAdmin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hotel Admin Routes
|--------------------------------------------------------------------------
|
| Routes for hotel administrators to manage their hotels
| Super admins have access to all hotels
|
*/

Route::middleware(['auth', 'hotel-admin'])->prefix('hotel-admin')->name('hotel-admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/map-data', [DashboardController::class, 'getMapData'])->name('map-data');

    // Bookings
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', function () {
            return view('hotel-admin.bookings.index');
        })->name('index');
        Route::get('/create', function () {
            return view('hotel-admin.bookings.create');
        })->name('create');
        Route::get('/{id}', function () {
            return view('hotel-admin.bookings.show');
        })->name('show');
    });

    // Hotels (Super Admin only)
    Route::middleware('admin')->prefix('hotels')->name('hotels.')->group(function () {
        Route::get('/', function () {
            return view('hotel-admin.hotels.index');
        })->name('index');
        Route::get('/create', function () {
            return view('hotel-admin.hotels.create');
        })->name('create');
        Route::get('/{id}', function () {
            return view('hotel-admin.hotels.show');
        })->name('show');
    });

    // Hotel Settings (Hotel Admin only)
    Route::middleware('hotel-admin')->prefix('hotel')->name('hotel.')->group(function () {
        Route::get('/settings', function () {
            return view('hotel-admin.hotel.settings');
        })->name('settings');
    });

    // Rooms
    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', function () {
            return view('hotel-admin.rooms.index');
        })->name('index');
        Route::get('/create', function () {
            return view('hotel-admin.rooms.create');
        })->name('create');
    });

    // Reviews
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', function () {
            return view('hotel-admin.reviews.index');
        })->name('index');
        Route::post('/{id}/approve', function () {
            return redirect()->back();
        })->name('approve');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/revenue', function () {
            return view('hotel-admin.reports.revenue');
        })->name('revenue');
        Route::get('/occupancy', function () {
            return view('hotel-admin.reports.occupancy');
        })->name('occupancy');
    });
});
