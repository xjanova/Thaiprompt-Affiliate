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
use App\Http\Middleware\VerifySmsGatewayAccess;
use Illuminate\Support\Facades\Route;

// หมายเหตุ: เมื่อ load จาก api.php จะมี prefix /api อยู่แล้ว
// URL เต็มจะเป็น: /api/v1/sms-payment/*
Route::prefix('v1/sms-payment')->group(function () {

    // เส้นทางที่ต้อง authenticate ด้วย API Key ของอุปกรณ์
    Route::middleware([VerifySmsCheckerDevice::class])->group(function () {
        // รับ SMS notification จาก Android App (ไม่ต้องเช็ค subscription — รับ SMS ได้เสมอ)
        Route::post('/notify', [SmsPaymentController::class, 'notify']);

        // รับ encrypted action (approve/reject) จาก Android App (critical path — ไม่ต้องเช็ค subscription)
        Route::post('/notify-action', [SmsPaymentController::class, 'notifyAction']);

        // ตรวจสอบสถานะอุปกรณ์
        Route::get('/status', [SmsPaymentController::class, 'status']);

        // ลงทะเบียน/อัพเดทข้อมูลอุปกรณ์
        Route::post('/register-device', [SmsPaymentController::class, 'registerDevice']);

        // FCM Token registration (สำหรับ push notifications)
        Route::post('/register-fcm-token', [SmsPaymentController::class, 'registerFcmToken']);

        // เส้นทางที่ต้องมี SMS Gateway access (subscription/premium/official)
        Route::middleware([VerifySmsGatewayAccess::class])->group(function () {
            // จัดการ Orders (สำหรับ Android App)
            Route::get('/orders', [SmsPaymentController::class, 'orders']);
            Route::get('/orders/match', [SmsPaymentController::class, 'matchOrderByAmount']); // ค้นหา order ตามยอดเงิน
            Route::post('/orders/{id}/approve', [SmsPaymentController::class, 'approveOrder']);
            Route::post('/orders/{id}/reject', [SmsPaymentController::class, 'rejectOrder']);
            Route::post('/orders/bulk-approve', [SmsPaymentController::class, 'bulkApproveOrders']);
            Route::get('/orders/sync', [SmsPaymentController::class, 'syncOrders']);

            // Legacy sync (Android app เวอร์ชันเก่า)
            Route::get('/sync', [SmsPaymentController::class, 'sync']);

            // Sync version — แอพเช็คก่อนว่า data มีการเปลี่ยนแปลงหรือไม่
            Route::get('/sync-version', [SmsPaymentController::class, 'getSyncVersion']);

            // สถิติ dashboard (สำหรับ Android App)
            Route::get('/dashboard-stats', [SmsPaymentController::class, 'dashboardStats']);
        });

        // ตั้งค่าอุปกรณ์ (สำหรับ Android App — ไม่ต้องเช็ค subscription)
        Route::get('/device-settings', [SmsPaymentController::class, 'getDeviceSettings']);
        Route::put('/device-settings', [SmsPaymentController::class, 'updateDeviceSettings']);
    });

    // เส้นทางที่ต้อง authenticate ด้วย Laravel Sanctum (web/admin)
    Route::middleware(['auth:sanctum'])->group(function () {
        // สร้าง unique payment amount (เรียกจาก checkout)
        Route::post('/generate-amount', [SmsPaymentController::class, 'generateAmount']);

        // ดู notification history (admin)
        Route::get('/notifications', [SmsPaymentController::class, 'notifications']);
    });
});
