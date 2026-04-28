<?php

namespace Database\Seeders;

use App\Models\FortuneBanner;
use Illuminate\Database\Seeder;

/**
 * Seed banner เริ่มต้น 4 รูปจาก banner1.zip
 *
 * รูปวางอยู่ที่ public/images/fortune/banners/banner-01.jpg ... banner-04.jpg
 * (ย่อแล้วจาก 1344x752 PNG → 1280x716 JPEG quality 85)
 */
class FortuneBannerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 กำลัง seed fortune banners...');

        $banners = [
            ['name' => 'แบนเนอร์ #1', 'file' => 'banner-01.jpg'],
            ['name' => 'แบนเนอร์ #2', 'file' => 'banner-02.jpg'],
            ['name' => 'แบนเนอร์ #3', 'file' => 'banner-03.jpg'],
            ['name' => 'แบนเนอร์ #4', 'file' => 'banner-04.jpg'],
        ];

        foreach ($banners as $index => $data) {
            $imagePath = "images/fortune/banners/{$data['file']}";
            $fullPath = public_path($imagePath);

            if (! file_exists($fullPath)) {
                $this->command->warn("  ⚠️ ไม่พบไฟล์ {$fullPath} — ข้าม");
                continue;
            }

            // idempotent — ถ้ามี name นี้แล้ว ข้าม
            if (FortuneBanner::where('name', $data['name'])->exists()) {
                $this->command->info("  ✓ {$data['name']} มีอยู่แล้ว — ข้าม");
                continue;
            }

            $imageInfo = @getimagesize($fullPath);
            $fileSize = @filesize($fullPath);

            FortuneBanner::create([
                'name' => $data['name'],
                'description' => 'แบนเนอร์เริ่มต้นจากชุด banner1.zip (ย่อเป็น 1280x716 JPEG)',
                'image_path' => $imagePath,
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
                'file_size' => $fileSize ?: null,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            $this->command->info("  ✅ สร้าง {$data['name']} สำเร็จ");
        }

        $this->command->info('✅ Seed fortune banners สำเร็จ');
    }
}
