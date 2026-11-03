<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔐 (2026-11-03 รีวิว) ประวัติการรวมบัญชีเงาของลูกค้าจันทรา ที่ต้องอยู่รอดแม้แถว juntra_accounts ถูกแทนที่
     *
     * แถว juntra_accounts เก็บ merged_from_user_id ได้ช่องเดียว — ตอนบัญชีจันทราใหม่มารับ Thaiprompt คนเดิม
     * (แถวเก่าที่ "ปลดแล้ว" ถูกลบ) ประวัติว่า "ผู้ใช้เงา S เคยถูกรวมเข้า T" หายไปด้วย
     * ⇒ ลิงก์เชิญเดิมของเงาไม่พามาที่บัญชีจริง และ sweep เก็บตกของเงาไม่ได้อีก
     * ตารางนี้เก็บคู่ (เงา → บัญชีจริง) ไว้ถาวร
     */
    public function up(): void
    {
        if (Schema::hasTable('juntra_account_merge_aliases')) {
            return;
        }

        Schema::create('juntra_account_merge_aliases', function (Blueprint $table) {
            $table->id();
            // ผู้ใช้เงาที่ถูกรวม
            $table->unsignedBigInteger('merged_from_user_id');
            // บัญชี Thaiprompt ที่รับไป
            $table->unsignedBigInteger('user_id');
            // บัญชีจันทราเดิมที่ทำให้เกิดการรวม (เพื่อตรวจย้อนหลัง)
            $table->unsignedBigInteger('juntra_user_id')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamps();

            $table->unique(['merged_from_user_id', 'user_id'], 'jama_from_user_unique');
            $table->index('user_id', 'jama_user_idx');
        });
    }

    /**
     * ลบตารางประวัติ
     */
    public function down(): void
    {
        Schema::dropIfExists('juntra_account_merge_aliases');
    }
};
