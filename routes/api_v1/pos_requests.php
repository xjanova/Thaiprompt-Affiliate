<?php

/*
 * 🛵 POS → ไรเดอร์ Thai Prompt (2026-10-04) — ลูกค้าสแกน QR จากเครื่อง POS แล้วจ่ายจากกระเป๋าเงิน
 *
 * ถูก require จาก routes/api.php ภายในกลุ่ม prefix v1 + auth:sanctum
 * ชื่อ route ขึ้นต้นด้วย api.v1.
 * {token} รับทั้งแบบมีและไม่มีคำนำหน้า "TPPOS1." (ตัวควบคุมตรวจรูปแบบอีกชั้น)
 */

use App\Http\Controllers\Api\V1\PosPaymentRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos-requests/{token}')
    ->where(['token' => '[A-Za-z0-9.]{1,64}'])
    ->name('api.v1.pos-requests.')
    ->controller(PosPaymentRequestController::class)
    ->group(function () {
        Route::get('/', 'show')->middleware('throttle:60,1,api-pos-request-show')->name('show');
        // profile.photo = ด่านรูปโปรไฟล์ถ่ายสดของไรเดอร์รอบ 2 (แบบเดียวกับ /cart/checkout) — ตรวจก่อน PIN จึงไม่กินจำนวนครั้ง PIN
        Route::post('/pay', 'pay')->middleware(['throttle:10,1,api-pos-request-pay', 'profile.photo'])->name('pay');
    });
