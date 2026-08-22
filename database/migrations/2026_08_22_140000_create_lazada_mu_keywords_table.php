<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง lazada_mu_keywords — คีย์เวิร์ดที่ระบบจะไล่นำเข้าสินค้าจาก Lazada
     *
     * ทำไมต้องมีตารางนี้ (ไม่ใช่ hardcode ในไฟล์):
     *   เดิมรายการสินค้าสายมูอยู่ใน `database/data/lazada-mu-products.json` (105 id ตายตัว)
     *   เติมของใหม่ = ต้องแก้ไฟล์แล้ว deploy ⇒ owner เติมเองไม่ได้
     *   ตารางนี้ทำให้ตั้งค่าคีย์เวิร์ดจากหลังบ้านได้ แล้ว cron ไล่เก็บให้เอง
     *
     * 🚨 คีย์เวิร์ดมาได้ 2 ทาง:
     *   - `admin`    = owner พิมพ์เอง (is_active = 1 ทันที)
     *   - `customer` = ลูกค้าถามหาของในแชทแล้วระบบยังไม่มี ⇒ บันทึกอัตโนมัติ **is_active = 0**
     *     รอ owner กดเปิด — ป้องกันคนป่วนพิมพ์คำมั่วแล้วระบบไล่ดูดของเข้าร้าน
     *     ตัวนับ `ask_count` ทำให้คำที่ลูกค้าถามบ่อยลอยขึ้นบนสุดในหลังบ้านเอง
     */
    public function up(): void
    {
        if (Schema::hasTable('lazada_mu_keywords')) {
            return;
        }

        Schema::create('lazada_mu_keywords', function (Blueprint $table) {
            $table->id();

            $table->string('keyword', 191)->comment('คำค้นที่ยิงเข้าพอร์ทัล Lazada เช่น "บ่วงนาคบาศ"');
            $table->string('mu_group', 32)->nullable()->comment('pixiu | pyramid | pichong | zodiac | charm | general');

            // หมวดบนเว็บเราที่ของจากคำนี้ควรไปลง (null = ให้ตัวจัดหมวดอัตโนมัติเดาเอง)
            $table->unsignedBigInteger('product_category_id')->nullable()
                ->comment('หมวดปลายทางบนหน้าร้าน (product_categories.id)');

            // เกณฑ์คัด — null = ใช้ค่ากลางจาก MarketplaceSetting
            $table->decimal('min_commission_rate', 5, 2)->nullable()->comment('ค่าคอมขั้นต่ำ %% (null = ใช้ค่ากลาง)');
            $table->decimal('min_price', 12, 2)->nullable()->comment('ราคาต่ำสุดที่รับ (กันของแถม 1 บาท)');
            $table->decimal('max_price', 12, 2)->nullable()->comment('ราคาสูงสุดที่รับ');

            $table->unsignedInteger('target_count')->default(20)->comment('อยากได้กี่ชิ้นจากคำนี้');
            $table->unsignedInteger('imported_count')->default(0)->comment('เก็บเข้าระบบได้แล้วกี่ชิ้น');
            $table->unsignedInteger('ask_count')->default(0)->comment('ลูกค้าถามหาคำนี้กี่ครั้ง');

            $table->string('source', 16)->default('admin')->comment('admin | customer | seed');
            $table->boolean('is_active')->default(true)->comment('เปิดให้ cron ไล่เก็บหรือยัง');

            $table->timestamp('last_scanned_at')->nullable()->comment('ยิงพอร์ทัลรอบล่าสุดเมื่อไหร่');
            $table->unsignedInteger('last_found_count')->default(0)->comment('รอบล่าสุดเจอกี่ชิ้นที่ผ่านเกณฑ์');
            $table->text('last_error')->nullable()->comment('ข้อผิดพลาดรอบล่าสุด (คุกกี้หมดอายุ ฯลฯ)');

            $table->timestamps();

            $table->unique('keyword', 'lazada_mu_keyword_unique');
            $table->index(['is_active', 'last_scanned_at'], 'lazada_mu_kw_scan_idx');
            $table->index(['source', 'ask_count'], 'lazada_mu_kw_ask_idx');
        });
    }

    /**
     * ลบตาราง lazada_mu_keywords
     */
    public function down(): void
    {
        Schema::dropIfExists('lazada_mu_keywords');
    }
};
