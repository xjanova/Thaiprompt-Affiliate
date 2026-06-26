<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * consent_gate_bypass — สวิตช์ข้ามกล่องกติกา/รหัสเสียงทั้งหมด → สร้างบิลทันที (ตาม tier ที่เลือก)
     *   เปิด = ไม่แสดงกล่องกติกาเลย (ข้ามทุกด่าน รวมรหัสเสียง) ออก QR ทันที
     *   ปิด = พฤติกรรมปกติ (กล่องกติกา + audio-code ตามการตั้งค่า)
     *   มีศักดิ์เหนือทุก setting ของ consent gate (เช็คเป็นอันดับแรก)
     *
     * ⚠️ ALTER TABLE — เช็คคอลัมน์ก่อนเพิ่ม
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'consent_gate_bypass')) {
                $table->boolean('consent_gate_bypass')
                    ->default(false)
                    ->after('consent_audio_code_min_unpaid_bills');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'consent_gate_bypass')) {
                $table->dropColumn('consent_gate_bypass');
            }
        });
    }
};
