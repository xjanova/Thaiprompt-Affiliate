<?php

/**
 * SMS Payment Gateway API Routes
 *
 * เส้นทาง API สำหรับระบบชำระเงินผ่าน SMS Checker
 * URL เต็ม: /api/v1/sms-payment/*
 *
 * Rate Limiting (ต่อนาที):
 * - Critical endpoints: 300/นาที (notify, register, FCM, approve/reject)
 * - Standard endpoints: 120/นาที (orders, sync, status)
 * - Web auth endpoints: 30/นาที (generate-amount, notifications)
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

    // ═══════════════════════════════════════════════════
    // Critical endpoints — 300/นาที (ต้องผ่านเสมอ)
    // SMS notification, device registration, FCM token
    // ═══════════════════════════════════════════════════
    Route::middleware([VerifySmsCheckerDevice::class, 'throttle:300,1'])->group(function () {
        // รับ SMS notification จาก Android App
        Route::post('/notify', [SmsPaymentController::class, 'notify']);

        // รับ encrypted action (approve/reject) จาก Android App
        Route::post('/notify-action', [SmsPaymentController::class, 'notifyAction']);

        // ลงทะเบียน/อัพเดทข้อมูลอุปกรณ์
        Route::post('/register-device', [SmsPaymentController::class, 'registerDevice']);

        // FCM Token registration (สำหรับ push notifications)
        Route::post('/register-fcm-token', [SmsPaymentController::class, 'registerFcmToken']);
    });

    // ═══════════════════════════════════════════════════
    // Standard endpoints — 120/นาที
    // Status, settings, orders, sync
    // ═══════════════════════════════════════════════════
    Route::middleware([VerifySmsCheckerDevice::class, 'throttle:120,1'])->group(function () {
        // ตรวจสอบสถานะอุปกรณ์
        Route::get('/status', [SmsPaymentController::class, 'status']);

        // ตั้งค่าอุปกรณ์ (สำหรับ Android App)
        Route::get('/device-settings', [SmsPaymentController::class, 'getDeviceSettings']);
        Route::put('/device-settings', [SmsPaymentController::class, 'updateDeviceSettings']);

        // เส้นทางที่ต้องมี SMS Gateway access (subscription/premium/official)
        Route::middleware([VerifySmsGatewayAccess::class])->group(function () {
            // จัดการ Orders (สำหรับ Android App)
            Route::get('/orders', [SmsPaymentController::class, 'orders']);
            Route::get('/orders/match', [SmsPaymentController::class, 'matchOrderByAmount']);
            Route::post('/orders/{id}/approve', [SmsPaymentController::class, 'approveOrder']);
            Route::post('/orders/{id}/reject', [SmsPaymentController::class, 'rejectOrder']);
            Route::post('/orders/bulk-approve', [SmsPaymentController::class, 'bulkApproveOrders']);
            Route::get('/orders/sync', [SmsPaymentController::class, 'syncOrders']);

            // 🔍 (2026-05-21) Orphan SMS → bill fuzzy match (admin-side)
            //   ใช้กรณี SMS เลขกลม (39, 100) ไม่จับคู่อัตโนมัติ — admin หาบิลด้วยมือ
            Route::post('/orphans/find-bill-candidates', [SmsPaymentController::class, 'findBillCandidatesForOrphan']);
            Route::post('/orphans/confirm-match', [SmsPaymentController::class, 'confirmOrphanMatch']);

            // Legacy sync (Android app เวอร์ชันเก่า)
            Route::get('/sync', [SmsPaymentController::class, 'sync']);

            // Sync version — แอพเช็คก่อนว่า data มีการเปลี่ยนแปลงหรือไม่
            Route::get('/sync-version', [SmsPaymentController::class, 'getSyncVersion']);

            // สถิติ dashboard (สำหรับ Android App)
            Route::get('/dashboard-stats', [SmsPaymentController::class, 'dashboardStats']);
        });
    });

    // ═══════════════════════════════════════════════════
    // Web auth endpoints — 30/นาที
    // เรียกจาก checkout/admin ไม่ใช่จากแอพ
    // ═══════════════════════════════════════════════════
    Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
        // สร้าง unique payment amount (เรียกจาก checkout)
        Route::post('/generate-amount', [SmsPaymentController::class, 'generateAmount']);

        // ดู notification history (admin)
        Route::get('/notifications', [SmsPaymentController::class, 'notifications']);
    });
});
