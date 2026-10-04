<?php

/*
 * 🪪 AI eKYC (2026-10-04) — ยืนยันตัวตนด้วยบัตรประชาชน + ใบหน้า
 *
 * ถูก require จาก routes/api.php ภายในกลุ่ม prefix v1 + auth:sanctum (ข้างเส้นไรเดอร์รอบ 2)
 * ชื่อ route ขึ้นต้นด้วย api.v1.ekyc.
 *
 * throttle ทุกเส้นมี prefix ของตัวเอง (ไม่ใช้ตัวนับร่วมกับเส้นอื่น)
 * ด่าน `ekyc.verified` (ต้องยืนยันก่อนสั่งซื้อ/รับงาน/สมัครร้าน) ติดที่เส้นเหล่านั้นใน routes/api.php
 */

use App\Http\Controllers\Api\V1\EkycApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('ekyc')->name('api.v1.ekyc.')->controller(EkycApiController::class)->group(function () {
    Route::get('/status', 'status')
        ->middleware('throttle:60,1,api-ekyc-status')
        ->name('status');

    // เริ่มรอบ (ยินยอม PDPA + รับคำสั่งสุ่ม) — จำกัดต่อนาที + เพดานรายวันใน EkycService
    Route::post('/sessions', 'start')
        ->middleware('throttle:6,1,api-ekyc-session')
        ->name('sessions.store');

    Route::post('/sessions/{id}/id-card', 'idCard')
        ->middleware('throttle:15,1,api-ekyc-card')
        ->name('sessions.id-card');

    Route::patch('/sessions/{id}/id-card', 'correctIdCard')
        ->middleware('throttle:20,1,api-ekyc-card-fix')
        ->name('sessions.id-card.update');

    Route::post('/sessions/{id}/face', 'face')
        ->middleware('throttle:6,1,api-ekyc-face')
        ->name('sessions.face');
});
