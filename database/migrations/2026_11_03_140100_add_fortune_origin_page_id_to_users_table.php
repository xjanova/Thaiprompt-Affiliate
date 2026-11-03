<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * 🏬 (2026-11-03) FB web-only — สาขาต้นทางของลูกค้า (invariant M6)
     *
     * users.fortune_origin_page_id = fortune_pages.id ของเพจที่พาลูกค้ามาเว็บครั้งแรก
     *   gateway (/web-fortune/go) เขียนครั้งเดียว เฉพาะตอนยังเป็น null — ไม่ย้ายสาขาภายหลัง
     *   ใช้รายงานบิลเว็บแยกตามสาขา (ไม่ใช่ตัวกำหนดสายงาน)
     *
     * ⚠️ ALTER TABLE — ใช้ SafeMigration ห้าม hasTable + return
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $this->safeAddColumn($table, 'users', 'fortune_origin_page_id', function ($table) {
                $table->unsignedBigInteger('fortune_origin_page_id')
                    ->nullable()
                    ->comment('fortune_pages.id สาขาแรกที่พาลูกค้ามาเว็บ (เขียนครั้งเดียว)');
            });
        });

        $this->safeAddIndex('users', 'fortune_origin_page_id', 'users_fortune_origin_page_idx');
    }

    /**
     * ลบคอลัมน์ + index เมื่อ rollback
     */
    public function down(): void
    {
        $this->safeDropIndex('users', 'users_fortune_origin_page_idx');
        $this->safeDropColumn('users', ['fortune_origin_page_id']);
    }
};
