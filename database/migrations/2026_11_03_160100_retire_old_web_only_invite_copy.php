<?php

use Database\Seeders\FortuneWebOnlyInviteMessageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔐 (2026-11-03 รีวิว) ข้อความชวนข้อ 6 เดิมสัญญา "ไพ่ประจำวันวางรอในห้องแม่หมอ" ซึ่งห้องไม่มี
     *
     * seed ถ้อยคำใหม่ แล้วปิดแถวที่ยังเป็นถ้อยคำเดิม (WebHandoffCopy::RETIRED_INVITES)
     * ไม่แก้ schema · idempotent · พังห้ามทำให้ deploy ล้ม
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')
            || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return;
        }

        try {
            $created = FortuneWebOnlyInviteMessageSeeder::seed();
            Log::info('🌐 migration: ปรับข้อความชวนชุด web แล้ว', ['created' => $created]);
        } catch (\Throwable $e) {
            Log::error('🌐 migration: ปรับข้อความชวนชุด web ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ไม่ย้อนกลับ (การปิดแถวถ้อยคำเก่าเป็นการแก้ไขที่ตั้งใจ)
     */
    public function down(): void
    {
        // ตั้งใจไม่ทำอะไร
    }
};
