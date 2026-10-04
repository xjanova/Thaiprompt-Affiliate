<?php

/*
 * 🛵 ไรเดอร์รอบ 2 (2026-10-04) — รูปโปรไฟล์ถ่ายสด + รูปพร้อมลายน้ำ (เจ้าของ: เลน photo)
 *
 * ถูก require จาก routes/api.php ภายในกลุ่ม prefix v1 + auth:sanctum
 * ชื่อ route ขึ้นต้นด้วย api.v1.
 *
 * รูปพร้อมลายน้ำ (signed URL, นอกกลุ่ม auth) อยู่ใน routes/api.php ข้างเส้นเอกสารไรเดอร์:
 *   GET /api/v1/media/profile-photo/{subject}/{viewer}/{version} → api.v1.media.profile-photo
 */

use App\Http\Controllers\Api\V1\ProfilePhotoApiController;
use Illuminate\Support\Facades\Route;

// สถานะรูปของตัวเอง { has_photo, taken_at, photo_url, required }
Route::get('/me/profile-photo', [ProfilePhotoApiController::class, 'show'])
    ->name('api.v1.me.profile-photo.show');

// ส่งรูปที่ถ่ายจากกล้อง (multipart `photo`) — จำกัดถี่: 6 ครั้ง/นาที และ 30 ครั้ง/ชั่วโมงต่อผู้ใช้
Route::post('/me/profile-photo', [ProfilePhotoApiController::class, 'store'])
    ->middleware(['throttle:6,1,api-profile-photo-upload', 'throttle:30,60,api-profile-photo-upload-hour'])
    ->name('api.v1.me.profile-photo.store');
