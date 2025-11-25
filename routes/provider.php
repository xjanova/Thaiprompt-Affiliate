<?php

use App\Http\Controllers\Provider\RegistrationController;
use App\Http\Controllers\Provider\ServiceBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provider Routes
|--------------------------------------------------------------------------
|
| Routes สำหรับผู้ให้บริการ (Service Providers)
| ลงทะเบียน, จัดการงาน, รับ/ปฏิเสธงาน, อัพเดทสถานะ, tracking
|
*/

Route::middleware(['auth', 'verified'])->prefix('provider')->name('provider.')->group(function () {

    // ===== Provider Registration =====
    // ลงทะเบียนเป็นผู้ให้บริการ
    Route::get('/register', [RegistrationController::class, 'showForm'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
    Route::get('/register/status', [RegistrationController::class, 'status'])->name('register.status');

    // Dashboard
    Route::get('/dashboard', [RegistrationController::class, 'dashboard'])->name('dashboard');

    // Profile Management
    Route::get('/profile/edit', [RegistrationController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [RegistrationController::class, 'updateProfile'])->name('profile.update');
    Route::put('/bank-info', [RegistrationController::class, 'updateBankInfo'])->name('bank-info.update');

    // Redirect root /provider to /provider/bookings or register
    Route::get('/', function () {
        $provider = \App\Models\ServiceProvider::where('user_id', auth()->id())->first();
        if ($provider && $provider->verification_status === 'approved') {
            return redirect()->route('provider.bookings.index');
        }
        return redirect()->route('provider.register');
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

    // Provider Earnings
    Route::prefix('earnings')->name('earnings.')->group(function () {
        Route::get('/', [ServiceBookingController::class, 'earnings'])->name('index');
        Route::get('/export', [ServiceBookingController::class, 'exportEarnings'])->name('export');
    });

    // Provider Statistics & Reviews
    Route::prefix('stats')->name('stats.')->group(function () {
        Route::get('/', [ServiceBookingController::class, 'stats'])->name('index');
    });

    // Provider Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [RegistrationController::class, 'settings'])->name('index');
        Route::put('/', [RegistrationController::class, 'updateSettings'])->name('update');
    });
});
