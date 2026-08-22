<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_comment_replies — คลังคำตอบคอมเมนต์สำเร็จรูป
     *
     * ทำไมต้องมี:
     * เดิม canned reply ฝังตายใน ProcessCommentEngagement::pickCannedReply() แค่ 14 ชุด
     * → ตอบซ้ำกันเองบ่อยมาก (สัญญาณสแปมของ FB) และแอดมินแก้ข้อความเองไม่ได้
     *
     * ย้ายมาเป็นตารางเพื่อให้:
     *  - แอดมินเพิ่ม/ปิด/แก้ข้อความได้โดยไม่ต้อง deploy
     *  - มีหลายหมวดให้เลือกตามสถานะลูกค้า (ยังไม่เคยคุย → ชวนติดตาม / คุยแล้ว → อวยพรเฉยๆ)
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CREATE TABLE — ใช้ hasTable() + return ได้
        if (Schema::hasTable('fortune_comment_replies')) {
            return;
        }

        Schema::create('fortune_comment_replies', function (Blueprint $table) {
            $table->id();

            // ข้อความตอบ — รองรับ {name} เป็น placeholder ชื่อลูกค้า
            $table->text('message');

            /**
             * หมวดคำตอบ — ตัวตัดสินว่าจะหยิบชุดไหนมาตอบ
             *
             * invite   = มีคำชวนกดไลก์/ติดตาม เพื่อรับดวงฟรีรายวัน
             *            ⚠️ ใช้เฉพาะคนที่ "ยังไม่เคยโต้ตอบกับเราใน Messenger"
             * blessing = อวยพรอย่างเดียว ไม่ชวนอะไร (คนที่คุยกับเราแล้ว — ชวนซ้ำ = รำคาญ)
             * thanks   = ขอบคุณสำหรับคอมเมนต์ (โทนกลางๆ ใช้ได้ทั้งสองกลุ่ม)
             * emoji    = ชุดสั้นมาก ไว้ตอบคอมเมนต์ที่เป็นอีโมจิล้วน
             */
            $table->string('category', 20)->default('blessing');

            // ภาษา — ตอนนี้มีแต่ไทย (สายลาวปิดตายแล้ว ห้ามเพิ่ม)
            $table->string('locale', 8)->default('th');

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // ตัวนับการใช้งาน — ไว้ดูว่าชุดไหนถูกหยิบบ่อยผิดปกติ
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ตัวหยิบกรองด้วย 3 คอลัมน์นี้เสมอ
            $table->index(['category', 'locale', 'is_active'], 'fcr_pick_idx');
        });
    }

    /**
     * ลบตาราง fortune_comment_replies
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_comment_replies');
    }
};
