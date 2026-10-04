<?php

namespace App\Services\Media;

use App\Models\User;

/**
 * รูปโปรไฟล์ถ่ายสด + ลายน้ำ (ไรเดอร์รอบ 2, 2026-10-04)
 *
 * ไฟล์ต้นฉบับเก็บใน private disk — คนอื่นเห็นได้เฉพาะรูปที่ฝังลายน้ำรหัสผู้ดู
 * ทุกที่ที่ส่งรูปคนให้แอป (ไรเดอร์ ผู้ซื้อ เจ้าของร้าน) ต้องเรียก urlFor() เท่านั้น
 */
class ProfilePhotoService
{
    /**
     * URL รูปพร้อมลายน้ำของ $subject ที่ $viewer เปิดดู (signed URL อายุสั้น)
     *
     * @return string|null null = ยังไม่มีรูป
     */
    public function urlFor(User $subject, ?User $viewer): ?string
    {
        // เลน photo เติมการทำงานจริง
        return null;
    }

    /**
     * มีรูปถ่ายสดแล้วหรือยัง
     */
    public function hasPhoto(User $user): bool
    {
        return ! empty($user->profile_photo_private_path);
    }
}
