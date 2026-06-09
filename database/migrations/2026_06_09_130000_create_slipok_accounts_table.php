<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง slipok_accounts — Pool บัญชี SlipOK หลายตัวสำหรับหมุนเวียนโควต้า
     *
     * แต่ละบัญชี = (branch_id + api_key) ของ SlipOK 1 ชุด มีโควต้าฟรี ~100/เดือน
     * ระบบ pool หมุนเวียน/เฉลี่ย/สลับเมื่อใกล้หมด เพื่อกัน API quota ตันทั้งระบบ
     */
    public function up(): void
    {
        // ✅ CREATE TABLE → ใช้ hasTable + return ได้ (ตาม CLAUDE.md)
        if (Schema::hasTable('slipok_accounts')) {
            return;
        }

        Schema::create('slipok_accounts', function (Blueprint $table) {
            $table->id();

            // ข้อมูลบัญชี SlipOK
            $table->string('label')->nullable();           // ชื่อเรียกบัญชี (เช่น "บัญชีหลัก", "สำรอง 1")
            $table->string('branch_id');                    // SlipOK Branch ID
            $table->text('api_key');                        // SlipOK API Key (encrypted ด้วย Crypt)

            // การจัดลำดับ + สถานะ
            $table->unsignedInteger('priority')->default(100); // น้อย = ใช้ก่อน
            $table->boolean('is_active')->default(true);       // เปิด/ปิดบัญชีนี้

            // โควต้า + การใช้งาน
            $table->unsignedInteger('monthly_quota')->default(100); // โควต้าฟรีต่อเดือน (แก้ได้)
            $table->unsignedInteger('used_this_month')->default(0); // นับการยิงจริงเดือนนี้
            $table->integer('quota_remaining')->nullable();         // โควต้าคงเหลือจริง (sync จาก API /quota)
            $table->date('quota_period_start')->nullable();         // เดือนที่เริ่มนับ used (reset เมื่อข้ามเดือน)

            // เวลา + สุขภาพบัญชี
            $table->timestamp('last_checked_at')->nullable();   // sync โควต้าล่าสุด
            $table->timestamp('last_used_at')->nullable();      // ยิง API ล่าสุด
            $table->timestamp('exhausted_until')->nullable();   // พัก (โควต้าหมด/พัง) จนถึงเวลานี้
            $table->unsignedInteger('consecutive_errors')->default(0); // error ติดต่อกัน
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();

            $table->string('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // index สำหรับ pool resolver (เลือกบัญชี active เรียงตาม priority)
            $table->index(['is_active', 'priority'], 'slipok_active_priority_idx');
        });
    }

    /**
     * ลบตาราง slipok_accounts
     */
    public function down(): void
    {
        Schema::dropIfExists('slipok_accounts');
    }
};
