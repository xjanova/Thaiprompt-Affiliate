<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ซ่อมชนิดคอลัมน์ fortune_readings.is_floating + sms_notification_id ที่ prod ไม่เคยได้ตาม repo
 *
 * 🐛 prod (ตรวจ 2026-09-26): is_floating = varchar(255) NULL และเป็น NULL ทุกแถว,
 *    sms_notification_id = varchar(255) — repo ตั้งไว้ tinyint(1) NOT NULL DEFAULT 0 / bigint unsigned NULL
 *    ต้นเหตุ: 2026_01_30_100000_add_sms_payment_to_fortune_readings_table ชื่อเข้า pattern add_..._to_X_table
 *    + ตารางมีอยู่แล้ว ⇒ migrate:smart ไม่เคยรัน up() แต่เพิ่มคอลัมน์ด้วยชนิดที่ "เดา" เอง (varchar nullable)
 *    ผล: where('is_floating', false) ไม่ตรงแถวไหนเลย (NULL ≠ 0) ⇒ ตัวกรอง "ชำระแล้ว"
 *    ใน FortuneBillingController คืน 0 แถว
 *
 * ⚠️ ชื่อไฟล์นี้จงใจไม่ขึ้นต้น create_/add_..._to_..._table — ไม่งั้น migrate:smart จะเดาคอลัมน์แทนการรัน up()
 *
 * idempotent: อ่านชนิดจริงจาก information_schema ก่อนทุกครั้ง (ไม่เชื่อ comment) · ชนิดถูกแล้ว = ไม่ทำอะไร
 * ค่าที่ไม่รู้จัก = throw (migration ค้าง pending + deploy ล้มดัง ๆ) ดีกว่าเดาแล้วแปลงผิด
 */
return new class extends Migration
{
    private const TABLE = 'fortune_readings';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $modifications = array_filter([
            $this->prepareIsFloating(),
            $this->prepareSmsNotificationId(),
        ]);

        if (empty($modifications)) {
            return;
        }

        // รวมเป็น ALTER เดียว — เปลี่ยนชนิดคอลัมน์ต้อง copy ทั้งตาราง ทำรอบเดียวพอ
        DB::statement('ALTER TABLE `'.self::TABLE.'` '.implode(', ', $modifications));
    }

    /**
     * ซ่อมไม่ย้อนกลับ — การคืนเป็น varchar คือการคืนบั๊ก
     */
    public function down(): void
    {
        //
    }

    /**
     * เตรียม is_floating: normalize ค่า แล้วคืนคำสั่ง MODIFY (null = ชนิดถูกอยู่แล้ว/ไม่มีคอลัมน์)
     */
    private function prepareIsFloating(): ?string
    {
        $column = $this->columnInfo('is_floating');

        if (! $column) {
            return null;
        }

        if ($column->data_type === 'tinyint' && $column->is_nullable === 'NO' && (string) $column->column_default === '0') {
            return null;
        }

        // ตั้ง default ก่อน (เปลี่ยนแค่ metadata) — queue worker ยังรันอยู่ระหว่าง deploy
        // แถวที่ถูกสร้างระหว่าง backfill กับ ALTER จะได้ '0' แทน NULL (ถ้าเป็น NULL จะทำให้ MODIFY ... NOT NULL ล้ม)
        DB::statement('ALTER TABLE `'.self::TABLE."` ALTER COLUMN `is_floating` SET DEFAULT '0'");

        DB::update('UPDATE `'.self::TABLE."` SET `is_floating` = '1'"
            ." WHERE LOWER(TRIM(`is_floating`)) IN ('1', 'true', 'yes', 'on')");
        DB::update('UPDATE `'.self::TABLE."` SET `is_floating` = '0'"
            ." WHERE `is_floating` IS NULL OR LOWER(TRIM(`is_floating`)) IN ('', '0', 'false', 'no', 'off')");

        $unknown = DB::select('SELECT `is_floating` AS value, COUNT(*) AS total FROM `'.self::TABLE.'`'
            ." WHERE `is_floating` NOT IN ('0', '1') GROUP BY `is_floating` LIMIT 10");

        if (! empty($unknown)) {
            throw new RuntimeException('fortune_readings.is_floating has values that are not boolean: '
                .json_encode($unknown, JSON_UNESCAPED_UNICODE).' — fix them by hand, then re-run the migration');
        }

        return "MODIFY `is_floating` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'บิลลอย = ยังไม่ระบุตัวตนลูกค้า'";
    }

    /**
     * เตรียม sms_notification_id: ค่าว่าง → NULL แล้วคืนคำสั่ง MODIFY (null = ชนิดถูกอยู่แล้ว/ไม่มีคอลัมน์)
     */
    private function prepareSmsNotificationId(): ?string
    {
        $column = $this->columnInfo('sms_notification_id');

        if (! $column) {
            return null;
        }

        if ($column->data_type === 'bigint' && str_contains($column->column_type, 'unsigned')) {
            return null;
        }

        DB::update('UPDATE `'.self::TABLE.'` SET `sms_notification_id` = NULL'
            ." WHERE TRIM(`sms_notification_id`) = ''");

        $invalid = DB::select('SELECT `sms_notification_id` AS value, COUNT(*) AS total FROM `'.self::TABLE.'`'
            ." WHERE `sms_notification_id` IS NOT NULL AND `sms_notification_id` NOT REGEXP '^[0-9]+\$'"
            .' GROUP BY `sms_notification_id` LIMIT 10');

        if (! empty($invalid)) {
            throw new RuntimeException('fortune_readings.sms_notification_id has non-numeric values: '
                .json_encode($invalid, JSON_UNESCAPED_UNICODE).' — fix them by hand, then re-run the migration');
        }

        return 'MODIFY `sms_notification_id` BIGINT UNSIGNED NULL DEFAULT NULL';
    }

    /**
     * ชนิดคอลัมน์จริงจากฐาน (ตั้ง alias ตัวเล็ก — MySQL/MariaDB คืนชื่อคอลัมน์ information_schema ต่างกันได้)
     */
    private function columnInfo(string $column): ?object
    {
        return DB::selectOne(
            'SELECT DATA_TYPE AS data_type, COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable,'
            .' COLUMN_DEFAULT AS column_default'
            .' FROM information_schema.COLUMNS'
            .' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [self::TABLE, $column]
        );
    }
};
