<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🚫 (2026-05-16) ปิด keyword "refund" + ลบ "ยกเลิก" ออกจาก trigger_words
 *
 * เหตุผล:
 *   1. เราไม่มีนโยบายคืนเงิน — บริการดิจิทัล ส่งคำทำนายทันที (terms-of-service.html:248)
 *      แต่ keyword "refund" มี response_text เป็น "เราสามารถคืนเงินได้ภายใน 7 วัน" → ขัดกับ T&C
 *
 *   2. trigger_words มี "ยกเลิก" → ลูกค้ากดปุ่ม "❌ ยกเลิก" ใน Fortune Celtic flow
 *      → LineHybridBotService::matchKeyword() ใช้ str_contains() จับคู่ → ส่งกล่องนโยบายคืนเงิน
 *      → ลูกค้าสับสน
 *
 * Action:
 *   - is_active = false (ปิด keyword ทั้งหมด — กรณีลูกค้าพิมพ์ "คืนเงิน" / "refund" ก็ไม่ตอบ)
 *   - trigger_words ลบ "ยกเลิก" — backup safety ถ้า admin ไปเปิด is_active ใหม่ในอนาคต
 *
 * วิธีกลับคืน: UPDATE line_bot_keywords SET is_active = 1 WHERE keyword = 'refund';
 *   ก่อน enable ใหม่ — ต้องแก้ response_text ให้ตรง T&C จริงด้วย
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('line_bot_keywords')) {
            return;
        }

        $refund = DB::table('line_bot_keywords')->where('keyword', 'refund')->first();

        if (! $refund) {
            return;
        }

        // ลบ "ยกเลิก" จาก trigger_words (เป็น JSON column)
        $triggers = json_decode($refund->trigger_words ?? '[]', true) ?: [];
        $triggers = array_values(array_filter($triggers, fn ($w) => $w !== 'ยกเลิก'));

        DB::table('line_bot_keywords')
            ->where('keyword', 'refund')
            ->update([
                'is_active' => false,
                'trigger_words' => json_encode($triggers, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('line_bot_keywords')) {
            return;
        }

        $refund = DB::table('line_bot_keywords')->where('keyword', 'refund')->first();

        if (! $refund) {
            return;
        }

        // เพิ่ม "ยกเลิก" กลับ + เปิด is_active
        $triggers = json_decode($refund->trigger_words ?? '[]', true) ?: [];
        if (! in_array('ยกเลิก', $triggers, true)) {
            $triggers[] = 'ยกเลิก';
        }

        DB::table('line_bot_keywords')
            ->where('keyword', 'refund')
            ->update([
                'is_active' => true,
                'trigger_words' => json_encode($triggers, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
};
