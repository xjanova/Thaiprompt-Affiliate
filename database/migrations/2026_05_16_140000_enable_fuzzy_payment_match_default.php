<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🔍 (2026-05-16) เปิด fuzzy_payment_match เป็น default
 *
 * เหตุผล:
 *   - Migration 2026_05_15 ตั้ง default=false (รอ test)
 *   - User ยืนยันให้เปิดใช้งานจริง เพราะลูกค้าโอนยอดไม่ตรงบ่อย (39.00 แทน 39.37)
 *   - Code พร้อม 100% — แค่เปิดสวิตช์
 *
 * ค่า default ที่ใช้:
 *   - enable_fuzzy_payment_match=true
 *   - fuzzy_overpay_max_baht=11 (รองรับลูกค้าปัด 99→110)
 *   - fuzzy_underpay_max_baht=1 (เผื่อตัดเศษ — เช่น 39.37 → 39.00)
 *   - fuzzy_window_minutes=60
 *   - fuzzy_name_auto_threshold=70%
 *   - fuzzy_admin_alert_above_baht=5
 *
 * วิธีปิดกลับ: UPDATE fortune_telling_settings SET enable_fuzzy_payment_match=0
 *   หรือผ่าน admin UI /admin/fortune/settings → tab Payment
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')
            || ! Schema::hasColumn('fortune_telling_settings', 'enable_fuzzy_payment_match')) {
            return;
        }

        DB::table('fortune_telling_settings')->update([
            'enable_fuzzy_payment_match' => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')
            || ! Schema::hasColumn('fortune_telling_settings', 'enable_fuzzy_payment_match')) {
            return;
        }

        DB::table('fortune_telling_settings')->update([
            'enable_fuzzy_payment_match' => false,
            'updated_at' => now(),
        ]);
    }
};
