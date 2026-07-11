<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 📋 (2026-07-11) ระบบ "แบบสอบถามยืนยันเจตนา 5 ข้อ" ก่อนสร้างบิล (เฉพาะคนสร้างบิลแล้วไม่จ่าย)
     *
     * เจ้าของสั่ง: คนแบบ "หลอด" ที่สร้างแต่บิลไม่เคยจ่าย → ก่อนสร้างบิลให้ตอบคำถาม
     *   เชิงจิตวิทยา 5 ข้อ (ใช่/ไม่ใช่) เพื่อยืนยันว่า "ตั้งใจดูดวงจริง เข้าใจว่าต้องจ่าย
     *   และยอมรับว่า ถ้าสร้างบิลรอบนี้แล้วไม่จ่าย จะถูกงดใช้งานเพจ 7 วัน"
     *
     * คอลัมน์:
     *   - enable_consent_quiz           : เปิด/ปิดระบบ (default ปิด — opt-in, เจ้าของเปิดเองหน้า admin)
     *   - consent_quiz_min_unpaid_bills : เกณฑ์บิลค้างไม่จ่าย (0 = ทุกคน / N = เฉพาะคนค้าง >= N ; default 2)
     *   - consent_quiz_ban_days         : ระยะเวลาแบน (วัน) ถ้ายอมรับแล้วยังไม่จ่าย (default 7)
     *
     * ⚠️ ALTER TABLE — เช็คคอลัมน์ก่อนเพิ่ม (ห้าม hasTable()+return)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_consent_quiz')) {
                $table->boolean('enable_consent_quiz')
                    ->default(false)
                    ->after('consent_audio_code_min_unpaid_bills');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'consent_quiz_min_unpaid_bills')) {
                $table->unsignedSmallInteger('consent_quiz_min_unpaid_bills')
                    ->default(2)
                    ->after('enable_consent_quiz');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'consent_quiz_ban_days')) {
                $table->unsignedSmallInteger('consent_quiz_ban_days')
                    ->default(7)
                    ->after('consent_quiz_min_unpaid_bills');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['enable_consent_quiz', 'consent_quiz_min_unpaid_bills', 'consent_quiz_ban_days'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
