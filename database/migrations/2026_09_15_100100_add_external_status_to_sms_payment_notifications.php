<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🌙 (2026-09-15) เพิ่มค่า 'external' ใน enum status ของ sms_payment_notifications
 *
 * 'external' = SMS เงินเข้าที่ยอดตรงกับยอดที่จองให้เว็บ จันทรา.online → เป็นเงินของจันทรา
 *   Thaiprompt บันทึกไว้แต่ "ไม่จับคู่บิลเรา" และไม่เตือนเงินกำพร้า
 *   ทุกเส้นที่ไล่หา SMS ค้าง (pending / requires_admin_review) จะข้ามแถวนี้เองโดยอัตโนมัติ
 *
 * แบบเดียวกับ 2026_05_05_000100 (MySQL/MariaDB เท่านั้น — driver อื่นไม่มี ENUM ให้แก้)
 *   ถ้าคอลัมน์ไม่ใช่ enum (เช่น varchar) ก็รับค่าอะไรก็ได้อยู่แล้ว → ข้าม
 */
return new class extends Migration
{
    private const VALUES_BEFORE = [
        'pending',
        'matched',
        'confirmed',
        'rejected',
        'expired',
        'cancelled',
        'processing',
        'requires_admin_review',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('sms_payment_notifications')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $columnType = $this->columnType();
        if ($columnType !== null) {
            if (! str_starts_with(strtolower($columnType), 'enum(')) {
                return; // ไม่ใช่ enum — เก็บ 'external' ได้อยู่แล้ว
            }
            if (str_contains($columnType, "'external'")) {
                return; // เคยเพิ่มแล้ว
            }
        }

        // เก็บค่าที่มีอยู่จริงทั้งหมดไว้ (กันลบค่าที่ migration อื่นเพิ่มมาแล้วเราไม่รู้จัก)
        $values = array_values(array_unique(array_merge(
            self::VALUES_BEFORE,
            $this->enumValues($columnType),
            ['external']
        )));

        DB::statement(sprintf(
            "ALTER TABLE sms_payment_notifications MODIFY COLUMN status ENUM(%s) NOT NULL DEFAULT 'pending'",
            implode(',', array_map(fn ($v) => "'".str_replace("'", "''", $v)."'", $values))
        ));

        Log::info("✅ Migration: เพิ่ม 'external' ใน enum status ของ sms_payment_notifications");
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_payment_notifications')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $columnType = $this->columnType();
        if ($columnType === null || ! str_contains($columnType, "'external'")) {
            return;
        }

        // มีแถวใช้ค่าใหม่แล้ว → ห้าม rollback (MySQL จะตัดค่าเป็น '' = ข้อมูลหาย)
        if (DB::table('sms_payment_notifications')->where('status', 'external')->exists()) {
            Log::warning("Migration rollback skip: มีแถว status='external' อยู่ — ห้าม rollback");

            return;
        }

        $values = array_values(array_diff($this->enumValues($columnType), ['external']));
        if (empty($values)) {
            $values = self::VALUES_BEFORE;
        }

        DB::statement(sprintf(
            "ALTER TABLE sms_payment_notifications MODIFY COLUMN status ENUM(%s) NOT NULL DEFAULT 'pending'",
            implode(',', array_map(fn ($v) => "'".str_replace("'", "''", $v)."'", $values))
        ));
    }

    private function columnType(): ?string
    {
        try {
            $row = DB::selectOne("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'sms_payment_notifications'
                  AND COLUMN_NAME = 'status'
            ");

            return $row ? (string) $row->COLUMN_TYPE : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return array<int,string> */
    private function enumValues(?string $columnType): array
    {
        if ($columnType === null || ! preg_match_all("/'((?:[^']|'')*)'/", $columnType, $m)) {
            return [];
        }

        return array_map(fn ($v) => str_replace("''", "'", $v), $m[1]);
    }
};
