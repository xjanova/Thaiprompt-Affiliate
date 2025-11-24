<?php

use App\Http\Controllers\Provider\ServiceBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provider Routes
|--------------------------------------------------------------------------
|
| Routes สำหรับผู้ให้บริการ (Service Providers)
| จัดการงาน, รับ/ปฏิเสธงาน, อัพเดทสถานะ, tracking
|
*/

Route::middleware(['auth', 'verified'])->prefix('provider')->name('provider.')->group(function () {

    // Redirect root /provider to /provider/bookings
    Route::get('/', function () {
        return redirect()->route('provider.bookings.index');
    });

    // Provider Dashboard & Bookings
    Route::prefix('bookings')->name('bookings.')->group(function () {
        // รายการงานทั้งหมด
        Route::get('/', [ServiceBookingController::class, 'index'])->name('index');

        // งานใหม่ที่รอการตอบกลับ
        Route::get('/pending', [ServiceBookingController::class, 'pending'])->name('pending');

        // รายละเอียดงาน
        Route::get('/{booking}', [ServiceBookingController::class, 'show'])->name('show');

        // รับงาน
        Route::post('/{booking}/accept', [ServiceBookingController::class, 'accept'])->name('accept');

        // ปฏิเสธงาน
        Route::post('/{booking}/reject', [ServiceBookingController::class, 'reject'])->name('reject');

        // เริ่มเดินทาง
        Route::post('/{booking}/start-journey', [ServiceBookingController::class, 'startJourney'])->name('start-journey');

        // เริ่มให้บริการ
        Route::post('/{booking}/start-service', [ServiceBookingController::class, 'startService'])->name('start-service');

        // เสร็จสิ้นบริการ
        Route::post('/{booking}/complete', [ServiceBookingController::class, 'complete'])->name('complete');

        // อัพเดทตำแหน่ง GPS
        Route::post('/{booking}/update-location', [ServiceBookingController::class, 'updateLocation'])->name('update-location');
    });

    // Provider Status Management
    Route::post('/toggle-availability', [ServiceBookingController::class, 'toggleAvailability'])->name('toggle-availability');
});
