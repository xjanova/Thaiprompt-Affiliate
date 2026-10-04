<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⏱️ (2026-10-04) ถอด ON UPDATE CURRENT_TIMESTAMP ออกจากคอลัมน์เวลาที่เหลืออีก 33 ตัว
 *
 * ต่อจาก 2026_10_04_100000 (sms_payment_notifications.sms_timestamp) — สาเหตุเดียวกัน:
 * prod MariaDB ตั้ง explicit_defaults_for_timestamp = 0 → `$table->timestamp('x')` ที่เป็น TIMESTAMP ตัวแรก
 * ของตารางได้ `DEFAULT current_timestamp() ON UPDATE current_timestamp()` → แก้คอลัมน์ไหนในแถวก็ตาม
 * x ถูกเขียนทับเป็นตอนนี้ (MySQL 8 ใน CI ไม่เป็น)
 *
 * ไล่ทีละคอลัมน์แล้วก่อนถอด (ตรวจ 2026-10-04):
 *   - otp_verifications.expires_at — พิมพ์ OTP ผิดครั้งเดียว (increment attempts) = หมดอายุทันที
 *   - mobile_auth_tokens.login_token_expires_at — ล็อกอินบนเว็บเสร็จ (update auth_code) = หมดอายุทันที
 *     → แอพถาม /status แล้วโทเคนถูกลบ แลกโทเคนไม่ได้
 *   - unique_payment_amounts.expires_at — ช่วงผ่อนผันรับเงินโอนช้าเคยพึ่ง "expires_at = เวลายกเลิก"
 *     ⇒ ตั้งให้ชัดในโค้ดแล้วก่อนถอด (UniquePaymentAmount::cancelledAttributes)
 *   - quality_checkpoints.checked_at — ตรวจซ้ำเคยรอให้เวลาขยับเอง ⇒ ตั้งให้ชัดใน QualityController::update
 *   - ที่เหลือ: แถวถูกแก้แต่ไม่มีใครต้องการให้เวลาขยับ / เขียนครั้งเดียว / ไม่มีการใช้งาน
 *     (POS: คืนเงินทำให้ยอดขายย้ายไปวันคืนเงิน + ระยะเวลากะเพี้ยน · ใบรับรอง · เช่าบอท AI ฯลฯ)
 *   prod มีข้อมูลจริงแค่ไม่กี่ตาราง และตารางที่มีข้อมูลไม่มีแถวเพี้ยน → ไม่ต้องซ่อมข้อมูลย้อนหลัง
 *
 * คงค่าเริ่มต้น CURRENT_TIMESTAMP ไว้ — บางเส้นสร้างแถวโดยไม่ส่งคอลัมน์นี้
 * ⚠️ ห้ามใช้ `->change()` เปล่า: บน MariaDB นี้ `TIMESTAMP NOT NULL` ที่ไม่มี default ได้ ON UPDATE กลับมา
 */
return new class extends Migration
{
    /** [ตาราง, คอลัมน์] */
    private const COLUMNS = [
        ['unique_payment_amounts', 'expires_at'],
        ['otp_verifications', 'expires_at'],
        ['mobile_auth_tokens', 'login_token_expires_at'],
        ['wallet_logs', 'created_at'],
        ['mlm_rank_achievements', 'achieved_at'],
        ['sms_payment_nonces', 'used_at'],
        ['quality_checkpoints', 'checked_at'],
        ['ai_bot_rentals', 'start_date'],
        ['ai_conversations', 'started_at'],
        ['ai_gen_subscriptions', 'started_at'],
        ['app_events', 'ts'],
        ['app_sessions', 'started_at'],
        ['bot_scheduled_posts', 'scheduled_for'],
        ['carbon_footprint_records', 'calculation_date'],
        ['certificates', 'issued_at'],
        ['consumer_scans', 'scanned_at'],
        ['fortune_product_offers', 'sent_at'],
        ['pos_sessions', 'opened_at'],
        ['pos_transactions', 'transaction_date'],
        ['product_impressions', 'ts'],
        ['product_journey', 'arrived_at'],
        ['qr_barcode_scans', 'scanned_at'],
        ['rider_locations', 'recorded_at'],
        ['trading_bot_subscriptions', 'starts_at'],
        ['trading_market_data', 'timestamp'],
        ['trading_portfolio_snapshots', 'snapshot_at'],
        ['trading_strategy_purchases', 'purchased_at'],
        ['training_enrollments', 'enrolled_at'],
        ['user_achievements', 'unlocked_at'],
        ['user_game_skins', 'purchased_at'],
        ['user_power_up_activations', 'activated_at'],
        ['vendor_features_usage', 'activated_at'],
        ['vendor_store_visits', 'visited_at'],
    ];

    /**
     * ถอด ON UPDATE — เฉพาะคอลัมน์ที่ยังติดอยู่ (MySQL 8 / รันซ้ำ = ข้าม)
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::COLUMNS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $info = DB::selectOne(
                'SELECT IS_NULLABLE AS nullable, EXTRA AS extra FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            );

            if ($info === null || stripos((string) $info->extra, 'on update') === false) {
                continue;
            }

            $null = $info->nullable === 'YES' ? 'NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP {$null} DEFAULT CURRENT_TIMESTAMP");
        }
    }

    /**
     * ไม่ย้อนกลับ — การเติม ON UPDATE กลับคือการคืนบั๊ก
     */
    public function down(): void
    {
        //
    }
};
