<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_data_deletion_requests
     *
     * บันทึกคำขอลบข้อมูลตาม PDPA (ทั้งจากในแชท "ลบข้อมูลของฉัน"
     * และจาก Facebook Data Deletion Callback) — ใช้เป็น audit trail
     * และเป็นหลังบ้านของหน้าเช็คสถานะ (confirmation_code) ที่ส่งกลับให้ Facebook
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CREATE TABLE → เช็คตารางก่อนสร้างได้
        if (Schema::hasTable('fortune_data_deletion_requests')) {
            return;
        }

        Schema::create('fortune_data_deletion_requests', function (Blueprint $table) {
            $table->id();

            // รหัสยืนยัน (ส่งกลับให้ Facebook + ใช้เปิดหน้าเช็คสถานะ) — สุ่ม ไม่ซ้ำ
            $table->string('confirmation_code', 64)->unique();

            // ช่องทางที่ขอลบ: line | facebook
            $table->string('platform', 20)->index();

            // ตัวตนลูกค้า ณ ตอนขอ (LINE userId / FB PSID) — เก็บเพื่อ traceability
            $table->string('platform_user_id', 191)->nullable()->index();

            // ที่มาของคำขอ: in_chat (พิมพ์ในแชท) | facebook_callback (signed_request)
            $table->string('source', 30)->default('in_chat');

            // สถานะ: pending | processing | completed | failed
            $table->string('status', 20)->default('pending')->index();

            // สรุปผลการลบ (counts ต่อ table) — เก็บเป็น JSON เพื่อตรวจสอบย้อนหลัง
            $table->json('summary')->nullable();

            // ข้อความ error ถ้าลบไม่สำเร็จ (ไม่เก็บ PII)
            $table->text('error')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * ลบตาราง fortune_data_deletion_requests
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_data_deletion_requests');
    }
};
