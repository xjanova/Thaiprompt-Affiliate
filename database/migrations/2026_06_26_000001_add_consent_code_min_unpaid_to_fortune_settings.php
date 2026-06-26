<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เกณฑ์ "จำนวนบิลค้างไม่จ่าย" ที่ทำให้ลูกค้าต้องผ่านระบบรหัสเสียง
     *
     * consent_audio_code_min_unpaid_bills:
     *   - 0  = บังคับกรอกรหัสจากเสียง "ทุกบิล" (ทุกคน)
     *   - N>0 = บังคับเฉพาะลูกค้าที่มีประวัติสร้างบิลแล้วไม่จ่าย >= N บิล (ลูกค้าดี/ใหม่ = กล่องกติกาปกติ)
     *
     * ⚠️ ALTER TABLE — เช็คคอลัมน์ก่อนเพิ่ม
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'consent_audio_code_min_unpaid_bills')) {
                $table->unsignedSmallInteger('consent_audio_code_min_unpaid_bills')
                    ->default(0)
                    ->after('consent_audio_code_voice_provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'consent_audio_code_min_unpaid_bills')) {
                $table->dropColumn('consent_audio_code_min_unpaid_bills');
            }
        });
    }
};
