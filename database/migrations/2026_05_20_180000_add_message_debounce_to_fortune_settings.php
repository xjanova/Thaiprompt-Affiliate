<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * 📦 (2026-05-20 Phase 4) เพิ่ม setting สำหรับ message debounce
     *
     * User spec 2026-05-20: ลูกค้าพิมพ์ทีละข้อความติด ๆ กัน บอทควรรอรวมก่อนตอบ
     *
     * field: message_debounce_seconds
     *   • 0      = ปิด feature (ตอบทันทีเหมือนเดิม)
     *   • 1-30  = รอ N วินาที — ถ้าลูกค้าพิมพ์เพิ่มภายใน window จะรวมเป็น message เดียว
     *   • default 3 — sweet spot สำหรับ chat ปกติ
     *
     * Scope: Phase 4a = Celtic Q2+ เฉพาะ — phase ถัดไปจะขยายไป flow อื่น
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_telling_settings', 'message_debounce_seconds', function ($table) {
                $table->unsignedTinyInteger('message_debounce_seconds')
                    ->default(3)
                    ->comment('Debounce window สำหรับรวมข้อความที่ลูกค้าพิมพ์ติด ๆ (0=ปิด, 1-30 วินาที)');
            });
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('fortune_telling_settings', 'message_debounce_seconds');
    }
};
