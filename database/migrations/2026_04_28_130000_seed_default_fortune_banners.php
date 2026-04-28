<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed 4 default banners (idempotent)
 *
 * เหตุผล: migration `create_fortune_banners_table` ถูกรันบน prod
 * ก่อนที่ผมจะเพิ่ม auto-seed ลงไป → Laravel จำว่ารันแล้ว ไม่รันซ้ำ
 * → ตาราง fortune_banners ว่างเปล่า → /admin/fortune/banners ไม่เห็นรูป
 *
 * Migration นี้แก้โดย: seed banners แยกใน migration ใหม่
 * - idempotent (เช็คก่อน insert ถ้ามีอยู่แล้ว skip)
 * - ปลอดภัยกับการรันซ้ำ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_banners')) {
            // ตารางต้องมาก่อน — migration นี้ depends on create_fortune_banners
            return;
        }

        $banners = [
            ['name' => 'แบนเนอร์ #1', 'file' => 'banner-01.jpg'],
            ['name' => 'แบนเนอร์ #2', 'file' => 'banner-02.jpg'],
            ['name' => 'แบนเนอร์ #3', 'file' => 'banner-03.jpg'],
            ['name' => 'แบนเนอร์ #4', 'file' => 'banner-04.jpg'],
        ];

        foreach ($banners as $index => $data) {
            // Idempotent: skip ถ้ามี banner ชื่อเดียวกันแล้ว
            $exists = DB::table('fortune_banners')
                ->where('name', $data['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            $imagePath = "images/fortune/banners/{$data['file']}";
            $fullPath = public_path($imagePath);

            $width = null;
            $height = null;
            $size = null;

            if (file_exists($fullPath)) {
                $info = @getimagesize($fullPath);
                $width = $info[0] ?? null;
                $height = $info[1] ?? null;
                $size = @filesize($fullPath) ?: null;
            }

            DB::table('fortune_banners')->insert([
                'name' => $data['name'],
                'description' => 'แบนเนอร์เริ่มต้นจากชุด banner1.zip (1280x716 JPEG)',
                'image_path' => $imagePath,
                'width' => $width,
                'height' => $height,
                'file_size' => $size,
                'is_active' => true,
                'sort_order' => $index + 1,
                'send_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_banners')) {
            return;
        }

        DB::table('fortune_banners')
            ->whereIn('name', ['แบนเนอร์ #1', 'แบนเนอร์ #2', 'แบนเนอร์ #3', 'แบนเนอร์ #4'])
            ->delete();
    }
};
