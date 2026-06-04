<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛡️ (2026-06-04) SlipOK Flood Guard — กันคนส่งสลิป/บิลปลอมรัวๆ ดูดโควต้า SlipOK ฟรี
 *
 * เพิ่ม 3 ค่า config (admin ปรับได้):
 *   - slipok_max_checks_per_user : เพดานจำนวนครั้งที่ยิง SlipOK ได้ต่อคน/หน้าต่างเวลา (default 2)
 *   - slipok_check_window_hours  : ความยาวหน้าต่างเวลานับเพดาน (ชั่วโมง, default 24)
 *   - slipok_ban_after_rounds    : ก่อกวนเกินเพดานกี่ "รอบ" (หน้าต่าง) แล้วแบนอัตโนมัติ (default 2 ; 0 = ไม่แบน)
 *
 * @see App\Services\Fortune\SlipOkService (budget + overflow strike)
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ flood guard ในตาราง fortune_telling_settings
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // nullable — กันเคสแอดมินล้างช่อง (empty → null) เขียนทับ NOT NULL แล้ว save error ; โค้ดมี ?? default รองรับ
            if (! Schema::hasColumn('fortune_telling_settings', 'slipok_max_checks_per_user')) {
                $table->unsignedSmallInteger('slipok_max_checks_per_user')
                    ->nullable()
                    ->default(2)
                    ->after('slipok_use_log')
                    ->comment('เพดานยิง SlipOK ต่อคน/หน้าต่าง (กัน flood ดูดโควต้า) — 0 = ไม่จำกัด');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'slipok_check_window_hours')) {
                $table->unsignedSmallInteger('slipok_check_window_hours')
                    ->nullable()
                    ->default(24)
                    ->after('slipok_max_checks_per_user')
                    ->comment('ความยาวหน้าต่างเวลานับเพดานยิง SlipOK (ชั่วโมง)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'slipok_ban_after_rounds')) {
                $table->unsignedSmallInteger('slipok_ban_after_rounds')
                    ->nullable()
                    ->default(2)
                    ->after('slipok_check_window_hours')
                    ->comment('ก่อกวนเกินเพดานกี่รอบแล้วแบนอัตโนมัติ — 0 = ไม่แบนอัตโนมัติ');
            }
        });
    }

    /**
     * ลบคอลัมน์ flood guard
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['slipok_max_checks_per_user', 'slipok_check_window_hours', 'slipok_ban_after_rounds'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
