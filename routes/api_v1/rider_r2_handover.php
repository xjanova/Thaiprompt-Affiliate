<?php

/*
 * 🛵 ไรเดอร์รอบ 2 (2026-10-04) — ส่งมอบของด้วยการสแกน QR ใส่กัน + ทางสำรองรูป 2 รอบ + ร้องเรียน (เจ้าของ: เลน money)
 *
 * ถูก require จาก routes/api.php ภายในกลุ่ม prefix v1 + auth:sanctum
 * ชื่อ route ขึ้นต้นด้วย api.v1.
 */

use App\Http\Controllers\Api\V1\HandoverApiController;
use Illuminate\Support\Facades\Route;

// ===== ผู้ซื้อ: {source} = shop | fresh-market =====
Route::prefix('orders/{source}/{id}/handover')
    ->where(['source' => 'shop|fresh-market', 'id' => '[0-9]+'])
    ->name('api.v1.orders.handover.')
    ->controller(HandoverApiController::class)
    ->group(function () {
        Route::get('/', 'buyerShow')->name('show');
        Route::post('/scan', 'buyerScan')->middleware('throttle:30,1,api-handover-buyer-scan')->name('scan');
        Route::post('/dispute', 'buyerDispute')->middleware('throttle:10,1,api-handover-dispute')->name('dispute');
        // รอบแก้หลังรีวิว: ผู้ซื้อกด "ได้รับของแล้ว" ระหว่างทางสำรอง (รอผู้รับ/วางของแล้ว)
        Route::post('/confirm-received', 'buyerConfirmReceived')->middleware('throttle:10,1,api-handover-confirm')->name('confirm-received');
    });

// ===== ไรเดอร์ =====
Route::prefix('rider/jobs/{id}/handover')
    ->where(['id' => '[0-9]+'])
    ->name('api.v1.rider.jobs.handover.')
    ->controller(HandoverApiController::class)
    ->group(function () {
        Route::get('/', 'riderShow')->name('show');
        // รหัส 6 หลักมีตัวล็อก 5 ครั้ง/10 นาทีของตัวเองอยู่แล้ว — throttle นี้กันยิงถี่ทั้งระบบ
        Route::post('/scan', 'riderScan')->middleware('throttle:30,1,api-handover-rider-scan')->name('scan');
        Route::post('/arrival-photo', 'arrivalPhoto')->middleware('throttle:10,1,api-handover-arrival')->name('arrival-photo');
        Route::post('/waited-photo', 'waitedPhoto')->middleware('throttle:10,1,api-handover-waited')->name('waited-photo');
    });
