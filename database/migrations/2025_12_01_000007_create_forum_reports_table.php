<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง forum_reports สำหรับเก็บการรายงานโพสต์ที่ไม่เหมาะสม
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('forum_reports')) {
            return;
        }

        Schema::create('forum_reports', function (Blueprint $table) {
            $table->id();

            // ผู้รายงาน
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Polymorphic relation (thread หรือ post)
            $table->morphs('reportable');

            // เหตุผลการรายงาน
            $table->enum('reason_type', [
                'spam',                 // สแปม
                'harassment',           // คุกคาม
                'inappropriate',        // เนื้อหาไม่เหมาะสม
                'misinformation',       // ข้อมูลเท็จ
                'hate_speech',          // Hate speech
                'copyright',            // ละเมิดลิขสิทธิ์
                'other',                // อื่นๆ
            ])->default('other');
            $table->text('reason_detail')->nullable();      // รายละเอียดเพิ่มเติม

            // สถานะ
            $table->enum('status', [
                'pending',              // รอตรวจสอบ
                'reviewed',             // กำลังตรวจสอบ
                'resolved',             // แก้ไขแล้ว
                'dismissed',            // ยกเลิก/ไม่พบปัญหา
            ])->default('pending');

            // การตรวจสอบ
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();     // บันทึกของผู้ตรวจสอบ

            // การดำเนินการ
            $table->enum('action_taken', [
                'none',                 // ไม่มีการดำเนินการ
                'warning',              // เตือน
                'hide_content',         // ซ่อนเนื้อหา
                'delete_content',       // ลบเนื้อหา
                'ban_user',             // แบนผู้ใช้
            ])->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('reason_type');
            // หมายเหตุ: morphs() สร้าง index สำหรับ reportable_type และ reportable_id ให้อัตโนมัติแล้ว
        });
    }

    /**
     * ลบตาราง forum_reports
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_reports');
    }
};
