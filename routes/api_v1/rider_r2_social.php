<?php

/*
 * 🛵 ไรเดอร์รอบ 2 (2026-10-04) — หัวใจ / ไรเดอร์คนโปรด / ไรเดอร์ใกล้ฉัน / ล็อกเรียก (เจ้าของ: เลน social)
 *
 * ถูก require จาก routes/api.php ภายในกลุ่ม prefix v1 + auth:sanctum
 * ชื่อ route ขึ้นต้นด้วย api.v1.
 *
 * ล็อกเรียกไรเดอร์ตอนสั่ง = ช่อง preferred_rider_id ของ POST /cart/checkout และ POST /fresh-market/orders (ไม่มี route แยก)
 */

use App\Http\Controllers\Api\V1\RiderSocialApiController;
use Illuminate\Support\Facades\Route;

// ไรเดอร์ใกล้ฉัน — จำกัด 30 ครั้ง/นาที กันยิงถี่เพื่อไล่หาตำแหน่ง (ตำแหน่งที่ได้เป็นจุดเบลอเสมอ)
Route::get('/riders/nearby', [RiderSocialApiController::class, 'nearby'])
    ->middleware('throttle:30,1,api-riders-nearby')
    ->name('api.v1.riders.nearby');

Route::get('/riders/favorites', [RiderSocialApiController::class, 'favorites'])
    ->middleware('throttle:60,1,api-riders-favorites')
    ->name('api.v1.riders.favorites');

Route::get('/riders/{id}', [RiderSocialApiController::class, 'show'])
    ->whereNumber('id')
    ->middleware('throttle:60,1,api-riders-show')
    ->name('api.v1.riders.show');

// ให้หัวใจไรเดอร์ของออเดอร์ (เฉพาะผู้ซื้อ + งานส่งสำเร็จแล้ว · 1 ดวงต่องาน)
Route::post('/orders/{source}/{id}/heart', [RiderSocialApiController::class, 'heart'])
    ->where('source', 'shop|fresh-market')
    ->whereNumber('id')
    ->middleware('throttle:30,1,api-order-heart')
    ->name('api.v1.orders.heart');
