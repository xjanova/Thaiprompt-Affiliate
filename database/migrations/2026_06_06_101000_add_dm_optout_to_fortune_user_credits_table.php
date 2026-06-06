<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔕 (2026-06-06) เพิ่ม dm_snooze_until + dm_opted_out_at ใน fortune_user_credits
 *
 * รองรับปุ่มในข้อความชวน (invite) ให้ลูกค้าเลือก:
 *   - "พัก 7 วัน" → dm_snooze_until = now()+7d
 *   - "ไม่ต้องส่งอีก" → dm_opted_out_at = now()
 *
 * guard `canReceiveOutbound()` เช็ค 2 คอลัมน์นี้ก่อนส่ง DM ตาม comment/reaction
 * (ลูกค้ายัง inbound ทักมาเอง + ดูดวงได้ปกติ — guard เฉพาะ outbound ที่บอททักไปหา)
 *
 * ⚠️ IMPORTANT: ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_user_credits')) {
            return;
        }

        Schema::table('fortune_user_credits', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_user_credits', 'dm_snooze_until')) {
                $table->timestamp('dm_snooze_until')
                    ->nullable()
                    ->after('last_dm_image_at')
                    ->comment('🔕 (2026-06-06) พัก DM ตาม comment/reaction จนถึงเวลานี้ (ปุ่ม "พัก 7 วัน")');
            }

            if (! Schema::hasColumn('fortune_user_credits', 'dm_opted_out_at')) {
                $table->timestamp('dm_opted_out_at')
                    ->nullable()
                    ->after('dm_snooze_until')
                    ->comment('🚫 (2026-06-06) ลูกค้ากด "ไม่ต้องส่งอีก" — หยุด DM ตาม comment/reaction ถาวร (null = ปกติ)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_user_credits')) {
            return;
        }

        Schema::table('fortune_user_credits', function (Blueprint $table) {
            foreach (['dm_snooze_until', 'dm_opted_out_at'] as $col) {
                if (Schema::hasColumn('fortune_user_credits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
