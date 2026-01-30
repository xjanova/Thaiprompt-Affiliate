<?php

/**
 * SMS Payment Gateway API Routes
 *
 * เส้นทาง API สำหรับระบบชำระเงินผ่าน SMS Checker
 * URL เต็ม: /api/v1/sms-payment/*
 *
 * รวมอยู่ใน routes/api.php:
 * require __DIR__ . '/sms_payment_api.php';
 */

use App\Http\Controllers\Api\V1\SmsPaymentController;
use App\Http\Middleware\VerifySmsCheckerDevice;
use Illuminate\Support\Facades\Route;

// หมายเหตุ: เมื่อ load จาก api.php จะมี prefix /api อยู่แล้ว
// URL เต็มจะเป็น: /api/v1/sms-payment/*
Route::prefix('v1/sms-payment')->group(function () {

    // เส้นทางที่ต้อง authenticate ด้วย API Key ของอุปกรณ์
    Route::middleware([VerifySmsCheckerDevice::class])->group(function () {
        // รับ SMS notification จาก Android App
        Route::post('/notify', [SmsPaymentController::class, 'notify']);

        // ตรวจสอบสถานะอุปกรณ์
        Route::get('/status', [SmsPaymentController::class, 'status']);

        // ลงทะเบียน/อัพเดทข้อมูลอุปกรณ์
        Route::post('/register-device', [SmsPaymentController::class, 'registerDevice']);
    });

    // เส้นทางที่ต้อง authenticate ด้วย Laravel Sanctum (web/admin)
    Route::middleware(['auth:sanctum'])->group(function () {
        // สร้าง unique payment amount (เรียกจาก checkout)
        Route::post('/generate-amount', [SmsPaymentController::class, 'generateAmount']);

        // ดู notification history (admin)
        Route::get('/notifications', [SmsPaymentController::class, 'notifications']);
    });
});
