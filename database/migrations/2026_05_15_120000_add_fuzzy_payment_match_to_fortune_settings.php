<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔍 (2026-05-15) Fuzzy Payment Match — auto-approve บิลที่โอนยอดใกล้เคียง
 *
 * จัดการเคสที่ลูกค้าโอน:
 *   - เกินจากบิล: 99.27 → 100, 39.48 → 40 (พบบ่อยที่สุด — ปัดเศษขึ้น)
 *   - ขาดจากบิล: 39.48 → 39 (ขาด 0.48฿ — บางคนตัดทศนิยม)
 *   - ใช้บัญชีคนอื่น/ภาษาอังกฤษ: ชื่อใน SMS ไม่ตรง Facebook
 *
 * Flow:
 *   1. ลูกค้าเช็คสถานะ → bot ค้น SmsPaymentNotification pending ในช่วงเวลา
 *   2. Score amount + name → ตัดสิน auto-approve / ถามยืนยัน / push admin
 *
 * Settings (เพิ่มใน fortune_telling_settings):
 *   - enable_fuzzy_payment_match: kill switch (default false ปิดไว้รอ test)
 *   - fuzzy_overpay_max_baht: รับเกินได้กี่บาท (default 11.00 — รองรับ 99→110)
 *   - fuzzy_underpay_max_baht: รับขาดได้กี่บาท (default 1.00 — เผื่อตัดเศษ)
 *   - fuzzy_window_minutes: ช่วงเวลา SMS หลังบิล (default 60)
 *   - fuzzy_name_auto_threshold: % similar_text ที่ถือว่าชื่อตรงพอ auto (default 70)
 *   - fuzzy_admin_alert_above_baht: ถ้า delta มากกว่านี้ → แจ้งแอดมิน (default 5.00)
 *
 * Audit (เพิ่มใน fortune_readings):
 *   - fuzzy_approved_at: timestamp อนุมัติ fuzzy
 *   - fuzzy_approved_delta: ส่วนต่าง (positive=เกิน, negative=ขาด)
 *   - fuzzy_approved_sms_id: SmsPaymentNotification ที่จับคู่
 *   - fuzzy_approved_name_score: % similar (0-100)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('fortune_telling_settings', 'enable_fuzzy_payment_match')) {
                    $table->boolean('enable_fuzzy_payment_match')
                        ->default(false)
                        ->comment('เปิด auto-approve บิลที่โอนใกล้เคียง (เช็คผ่าน SMS pending)');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'fuzzy_overpay_max_baht')) {
                    $table->decimal('fuzzy_overpay_max_baht', 6, 2)
                        ->default(11.00)
                        ->comment('รับเกินได้กี่บาท (default 11 — รองรับลูกค้าปัด 99→110)');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'fuzzy_underpay_max_baht')) {
                    $table->decimal('fuzzy_underpay_max_baht', 6, 2)
                        ->default(1.00)
                        ->comment('รับขาดได้กี่บาท (default 1 — เผื่อตัดเศษทศนิยม)');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'fuzzy_window_minutes')) {
                    $table->unsignedInteger('fuzzy_window_minutes')
                        ->default(60)
                        ->comment('ช่วงเวลา SMS หลังสร้างบิล (default 60 = อายุ UPA)');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'fuzzy_name_auto_threshold')) {
                    $table->unsignedTinyInteger('fuzzy_name_auto_threshold')
                        ->default(70)
                        ->comment('% similar_text ที่ถือว่าชื่อตรงพอ auto (default 70%)');
                }
                if (! Schema::hasColumn('fortune_telling_settings', 'fuzzy_admin_alert_above_baht')) {
                    $table->decimal('fuzzy_admin_alert_above_baht', 6, 2)
                        ->default(5.00)
                        ->comment('แจ้งแอดมิน LINE OA ถ้า delta > นี้ (default 5)');
                }
            });
        }

        if (Schema::hasTable('fortune_readings')) {
            Schema::table('fortune_readings', function (Blueprint $table) {
                if (! Schema::hasColumn('fortune_readings', 'fuzzy_approved_at')) {
                    $table->timestamp('fuzzy_approved_at')
                        ->nullable()
                        ->comment('เวลาที่ bot อนุมัติ fuzzy match');
                }
                if (! Schema::hasColumn('fortune_readings', 'fuzzy_approved_delta')) {
                    $table->decimal('fuzzy_approved_delta', 6, 2)
                        ->nullable()
                        ->comment('ส่วนต่าง (positive=เกิน, negative=ขาด)');
                }
                if (! Schema::hasColumn('fortune_readings', 'fuzzy_approved_sms_id')) {
                    $table->unsignedBigInteger('fuzzy_approved_sms_id')
                        ->nullable()
                        ->comment('SmsPaymentNotification.id ที่จับคู่');
                }
                if (! Schema::hasColumn('fortune_readings', 'fuzzy_approved_name_score')) {
                    $table->unsignedTinyInteger('fuzzy_approved_name_score')
                        ->nullable()
                        ->comment('% similar_text ของชื่อผู้โอน vs Facebook name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                $cols = [
                    'enable_fuzzy_payment_match',
                    'fuzzy_overpay_max_baht',
                    'fuzzy_underpay_max_baht',
                    'fuzzy_window_minutes',
                    'fuzzy_name_auto_threshold',
                    'fuzzy_admin_alert_above_baht',
                ];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('fortune_telling_settings', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('fortune_readings')) {
            Schema::table('fortune_readings', function (Blueprint $table) {
                $cols = ['fuzzy_approved_at', 'fuzzy_approved_delta', 'fuzzy_approved_sms_id', 'fuzzy_approved_name_score'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('fortune_readings', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
