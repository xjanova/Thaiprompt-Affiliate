<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ขยาย mobile_auth_tokens.pair_code จาก 16 → 64 ตัวอักษร
     *
     * เหตุผล: โค้ดเก็บ sha256 ของรหัสจับคู่ (64 ตัว hex) แต่คอลัมน์สร้างไว้ 16 ตัว
     * → MySQL/MariaDB strict mode ตอบ "Data too long" ทุกครั้งที่กดสร้าง QR (หน้า /admin/mobile-pair)
     * = จับคู่แอปแอดมินไม่เคยสำเร็จบน prod (0 แถว ณ 2026-10-06)
     */
    public function up(): void
    {
        if (! Schema::hasTable('mobile_auth_tokens') || ! Schema::hasColumn('mobile_auth_tokens', 'pair_code')) {
            return;
        }

        Schema::table('mobile_auth_tokens', function (Blueprint $table) {
            $table->string('pair_code', 64)
                ->nullable()
                ->comment('sha256 ของรหัส QR pairing (8 ตัว) สำหรับ admin app')
                ->change();
        });
    }

    /**
     * ไม่ย่อคอลัมน์กลับ — ค่า sha256 ที่มีอยู่จะถูกตัดจนใช้ไม่ได้
     */
    public function down(): void
    {
        // ตั้งใจไม่ทำอะไร
    }
};
