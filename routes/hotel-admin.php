<?php

use App\Http\Controllers\HotelAdmin\DashboardController;
use App\Http\Controllers\HotelAdmin\HotelManagementController;
use App\Http\Controllers\HotelAdmin\RoomManagementController;
use App\Http\Controllers\HotelAdmin\SpecialOfferManagementController;
use App\Http\Controllers\HotelAdmin\ReviewManagementController;
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

    // Hotel Management (Hotel Owner)
    Route::prefix('my-hotel')->name('my-hotel.')->group(function () {
        Route::get('/', [HotelManagementController::class, 'edit'])->name('edit');
        Route::put('/', [HotelManagementController::class, 'update'])->name('update');
        Route::delete('/gallery-image', [HotelManagementController::class, 'removeGalleryImage'])->name('remove-gallery-image');
        Route::get('/statistics', [HotelManagementController::class, 'statistics'])->name('statistics');
    });

    // Room Management (Hotel Owner)
    Route::prefix('my-rooms')->name('my-rooms.')->group(function () {
        Route::get('/', [RoomManagementController::class, 'index'])->name('index');
        Route::get('/{id}', [RoomManagementController::class, 'show'])->name('show');
        Route::post('/', [RoomManagementController::class, 'store'])->name('store');
        Route::put('/{id}', [RoomManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [RoomManagementController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/gallery-image', [RoomManagementController::class, 'removeGalleryImage'])->name('remove-gallery-image');

        // Availability management
        Route::get('/{id}/availability', [RoomManagementController::class, 'availability'])->name('availability');
        Route::put('/{id}/availability', [RoomManagementController::class, 'updateAvailability'])->name('update-availability');
        Route::put('/{id}/availability/bulk', [RoomManagementController::class, 'bulkUpdateAvailability'])->name('bulk-update-availability');
    });

    // Special Offers Management (Hotel Owner)
    Route::prefix('my-offers')->name('my-offers.')->group(function () {
        Route::get('/', [SpecialOfferManagementController::class, 'index'])->name('index');
        Route::get('/room-types', [SpecialOfferManagementController::class, 'getRoomTypes'])->name('room-types');
        Route::get('/{id}', [SpecialOfferManagementController::class, 'show'])->name('show');
        Route::post('/', [SpecialOfferManagementController::class, 'store'])->name('store');
        Route::put('/{id}', [SpecialOfferManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [SpecialOfferManagementController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle-active', [SpecialOfferManagementController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/{id}/statistics', [SpecialOfferManagementController::class, 'statistics'])->name('statistics');
    });

    // Review Management (Hotel Owner - can only respond, not delete)
    Route::prefix('my-reviews')->name('my-reviews.')->group(function () {
        Route::get('/', [ReviewManagementController::class, 'index'])->name('index');
        Route::get('/statistics', [ReviewManagementController::class, 'statistics'])->name('statistics');
        Route::get('/recent', [ReviewManagementController::class, 'recent'])->name('recent');
        Route::get('/needing-response', [ReviewManagementController::class, 'needingResponse'])->name('needing-response');
        Route::get('/{id}', [ReviewManagementController::class, 'show'])->name('show');
        Route::post('/{id}/respond', [ReviewManagementController::class, 'respond'])->name('respond');
        Route::put('/{id}/response', [ReviewManagementController::class, 'updateResponse'])->name('update-response');
        Route::delete('/{id}/response', [ReviewManagementController::class, 'deleteResponse'])->name('delete-response');
        Route::patch('/{id}/suggest-featured', [ReviewManagementController::class, 'suggestFeatured'])->name('suggest-featured');
    });
});
