<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎯 Phase B.1 — DM 24-hour memory + AI warm-up tracking
 *
 * เพิ่มคอลัมน์สำหรับติดตามการ DM ของแต่ละลูกค้า:
 * - first_dm_at: เวลาที่ลูกค้า DM มาครั้งแรก
 * - last_dm_at: เวลาที่ลูกค้า DM ครั้งล่าสุด (ใช้ตัดสินใจ warm-up window)
 * - dm_count: จำนวน DM รวมของลูกค้าคนนี้
 * - last_warmup_sent_at: ครั้งล่าสุดที่ AI ส่งข้อความหว่านล้อม (กัน spam)
 *
 * ใช้ใน Flow:
 * - ถ้า last_dm_at ภายใน 24 ชม. และไม่มี active reading → AI warm-up mode
 * - ถ้าเลย 24 ชม. หรือเป็น DM ครั้งแรก → pattern เดิม (greeting + buttons)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        Schema::table('fortune_user_credits', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_user_credits', 'first_dm_at', function ($t) {
                $t->timestamp('first_dm_at')->nullable()->after('facebook_user_name');
            });

            $this->safeAddColumn($table, 'fortune_user_credits', 'last_dm_at', function ($t) {
                $t->timestamp('last_dm_at')->nullable()->after('first_dm_at');
            });

            $this->safeAddColumn($table, 'fortune_user_credits', 'dm_count', function ($t) {
                $t->unsignedInteger('dm_count')->default(0)->after('last_dm_at');
            });

            $this->safeAddColumn($table, 'fortune_user_credits', 'last_warmup_sent_at', function ($t) {
                $t->timestamp('last_warmup_sent_at')->nullable()->after('dm_count');
            });
        });

        // index เพิ่มประสิทธิภาพสำหรับ query "ใครเพิ่ง DM มาบ้างใน 24 ชม."
        $this->safeAddIndex('fortune_user_credits', 'last_dm_at', 'fortune_user_credits_last_dm_at_idx');
    }

    public function down(): void
    {
        $this->safeDropIndex('fortune_user_credits', 'fortune_user_credits_last_dm_at_idx');
        $this->safeDropColumn('fortune_user_credits', [
            'first_dm_at',
            'last_dm_at',
            'dm_count',
            'last_warmup_sent_at',
        ]);
    }
};
