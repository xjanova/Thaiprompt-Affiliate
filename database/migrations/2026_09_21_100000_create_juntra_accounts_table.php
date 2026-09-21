<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌙 (2026-09-21) ลูกค้าเว็บ/แอพจันทรา 1 คน = ผู้ใช้ Thaiprompt 1 คน (ถาวร)
     *
     * เจ้าของสั่ง: ทุกบิลของจันทราต้องมาคำนวณปันผลที่ผังแม่หมอ ลูกค้าส่วนใหญ่ของจันทรา
     *   สมัครด้วยเบอร์/อีเมลโดยไม่เคยผูก Thaiprompt → ต้องมีตัวตนฝั่งเราให้ผูกบิลและสายงาน
     *
     * - ผูก Thaiprompt แล้ว (SSO) → ชี้ไปที่ผู้ใช้คนนั้น (linked_via = sso)
     * - ยังไม่ผูก → สร้างผู้ใช้ให้อัตโนมัติ เหมือนที่บอทสร้างให้ลูกค้า FB/LINE (linked_via = auto)
     *
     * ถาวร: สายงานและคอมทั้งหมดผูกกับ user_id นี้ — เปลี่ยนภายหลัง = ย้ายสายงานโดยไม่ตั้งใจ
     */
    public function up(): void
    {
        if (Schema::hasTable('juntra_accounts')) {
            return;
        }

        Schema::create('juntra_accounts', function (Blueprint $table) {
            $table->id();
            // users.id ฝั่งเว็บจันทรา
            $table->unsignedBigInteger('juntra_user_id')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('linked_via', 10)->default('auto');
            // รวมบัญชี (เจ้าของสั่ง: Thaiprompt เป็นตัวหลัก) — ผู้ใช้ที่ระบบเคยสร้างให้ก่อนลูกค้าผูก Thaiprompt
            $table->unsignedBigInteger('merged_from_user_id')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juntra_accounts');
    }
};
