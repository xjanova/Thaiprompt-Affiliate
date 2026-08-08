<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_comment_link_blocks
     *
     * บันทึกทุกครั้งที่บอทเจอ "ลิงก์ภายนอกในคอมเมนต์" แล้วบล็อกคนโพสต์
     *
     * ทำไมต้องมีตารางนี้:
     * - Page token ยังไม่มีสิทธิ์ pages_manage_engagement (ติด App Review)
     *   → บอท "ซ่อนคอมเมนต์" เองไม่ได้ ทำได้แค่ "บล็อกคนโพสต์"
     * - เลยต้องเก็บลิงก์ตำแหน่งคอมเมนต์ไว้ให้แอดมินกดเข้าไปลบมือ
     * - และต้องปลดบล็อกย้อนหลังได้ เพราะนโยบายคือบล็อกทันทีไม่เว้นใคร (รวมคนที่จ่ายเงิน)
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (CREATE TABLE ใช้รูปแบบนี้ได้)
        if (Schema::hasTable('fortune_comment_link_blocks')) {
            return;
        }

        Schema::create('fortune_comment_link_blocks', function (Blueprint $table) {
            $table->id();

            // ตัวตนผู้โพสต์
            $table->string('platform', 20)->default('facebook')->comment('ช่องทาง — facebook');
            $table->string('platform_user_id', 100)->comment('PSID ของคนที่โพสต์คอมเมนต์');
            $table->string('display_name', 200)->nullable()->comment('ชื่อที่แสดงบน Facebook');

            // ตำแหน่งคอมเมนต์ — หัวใจของฟีเจอร์นี้ (แอดมินต้องกดไปลบเอง)
            $table->string('comment_id', 120)->comment('comment_id จาก webhook');
            $table->string('post_id', 120)->nullable()->comment('post_id ของโพสต์/Reel ที่ถูกคอมเมนต์');
            $table->string('permalink', 500)->nullable()->comment('ลิงก์เด้งไปที่คอมเมนต์นั้นตรงๆ');

            // หลักฐาน
            $table->text('message')->nullable()->comment('ข้อความคอมเมนต์ที่ตรวจเจอ');
            $table->string('matched_domain', 255)->nullable()->comment('โดเมนที่ทำให้ถูกตัด');
            $table->string('detected_from', 20)->default('text')->comment('text = เจอในข้อความ / attachment = คอมเมนต์ไม่มีข้อความ');

            // ผลการดำเนินการ
            $table->boolean('page_blocked')->default(false)->comment('บล็อกบนเพจ FB สำเร็จหรือไม่');
            $table->string('block_error', 500)->nullable()->comment('เหตุผลที่บล็อกไม่สำเร็จ');
            $table->boolean('bot_banned')->default(false)->comment('แบนระดับบอทสำเร็จหรือไม่');
            $table->boolean('hide_succeeded')->default(false)->comment('ซ่อนคอมเมนต์สำเร็จ (ได้เมื่อ App Review ผ่าน)');

            // สถานะสำหรับหน้าแอดมิน
            $table->string('status', 20)->default('blocked')->comment('blocked | unblocked | detect_only');
            $table->boolean('is_read')->default(false)->comment('แอดมินอ่าน/จัดการแล้วหรือยัง');
            $table->boolean('comment_deleted')->default(false)->comment('แอดมินกดยืนยันว่าลบคอมเมนต์แล้ว');

            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('unblocked_at')->nullable();

            // ⚠️ ระบุชื่อตารางชัดเจนเสมอ
            $table->foreignId('unblocked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('แอดมินที่กดปลดบล็อก');

            $table->timestamps();

            // 1 คอมเมนต์ = 1 แถว (webhook ส่งซ้ำได้ ต้อง idempotent)
            $table->unique('comment_id', 'fclb_comment_unique');
            $table->index(['platform', 'platform_user_id'], 'fclb_user_idx');
            $table->index(['status', 'is_read'], 'fclb_status_read_idx');
            $table->index('created_at', 'fclb_created_idx');
        });
    }

    /**
     * ลบตาราง fortune_comment_link_blocks
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_comment_link_blocks');
    }
};
