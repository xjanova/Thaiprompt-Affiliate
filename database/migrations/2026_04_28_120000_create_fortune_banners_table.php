<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง fortune_banners
 *
 * เก็บรูปแบนเนอร์ที่ส่งให้ลูกค้าเมื่อ:
 *   - กด reaction บนโพสต์
 *   - คอมเมนต์ใต้โพสต์
 *   - ทักเพจครั้งแรก (welcome)
 *
 * แอดมินอัพโหลดได้, จัดเรียงได้, เปิด/ปิดเป็นรายตัว, สุ่มหรือเรียงตามลำดับได้
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('fortune_banners')) {
            Schema::create('fortune_banners', function (Blueprint $table) {
                $table->id();

                // ชื่อ + คำอธิบาย
                $table->string('name', 100); // ชื่อภายใน เช่น "Banner #1 - ขอบคุณกดไลก์"
                $table->string('description', 500)->nullable();

                // ไฟล์ภาพ
                $table->string('image_path', 500); // เช่น images/fortune/banners/banner-01.jpg
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->unsignedInteger('file_size')->nullable(); // bytes

                // จัดเรียง + เปิดปิด
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);

                // สถิติการใช้งาน
                $table->unsignedInteger('send_count')->default(0);
                $table->timestamp('last_sent_at')->nullable();

                // Tracking
                $table->unsignedBigInteger('uploaded_by')->nullable(); // user_id ของ admin ที่ upload

                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'sort_order'], 'fb_active_sort_idx');
            });
        }

        // 🌱 Auto-seed 4 banner หลักจาก banner1.zip (idempotent — skip ถ้ามีอยู่แล้ว)
        // ทำใน migration เพื่อให้ deploy เพียง php artisan migrate --force ได้ครบ
        $this->seedDefaultBanners();
    }

    /**
     * Seed 4 banner เริ่มต้น (idempotent — รันซ้ำได้)
     *
     * รูปต้องอยู่ที่ public/images/fortune/banners/ ตามชื่อด้านล่าง
     * ถ้าไม่มีไฟล์จริง → skip + log
     */
    protected function seedDefaultBanners(): void
    {
        $banners = [
            ['name' => 'แบนเนอร์ #1', 'file' => 'banner-01.jpg'],
            ['name' => 'แบนเนอร์ #2', 'file' => 'banner-02.jpg'],
            ['name' => 'แบนเนอร์ #3', 'file' => 'banner-03.jpg'],
            ['name' => 'แบนเนอร์ #4', 'file' => 'banner-04.jpg'],
        ];

        foreach ($banners as $index => $data) {
            $imagePath = "images/fortune/banners/{$data['file']}";
            $fullPath = public_path($imagePath);

            // Idempotent: skip ถ้ามี banner ชื่อเดียวกันแล้ว
            $exists = \Illuminate\Support\Facades\DB::table('fortune_banners')
                ->where('name', $data['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            $width = null;
            $height = null;
            $size = null;

            if (file_exists($fullPath)) {
                $info = @getimagesize($fullPath);
                $width = $info[0] ?? null;
                $height = $info[1] ?? null;
                $size = @filesize($fullPath) ?: null;
            }

            \Illuminate\Support\Facades\DB::table('fortune_banners')->insert([
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
        Schema::dropIfExists('fortune_banners');
    }
};
