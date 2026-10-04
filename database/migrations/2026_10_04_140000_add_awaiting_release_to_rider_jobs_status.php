<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🛵 ไรเดอร์รอบ 2 (2026-10-04, เลน money) — เพิ่มสถานะงาน awaiting_release ให้คอลัมน์ ENUM rider_jobs.status
 *
 * awaiting_release = ไรเดอร์วางของไว้ที่จุดส่งแล้ว (ถ่ายรูปครบ 2 รอบ) รอปลดเงินอัตโนมัติ/แอดมินตัดสิน
 * ไม่ใช่สถานะ "กำลังทำงาน" (ไรเดอร์รับงานใหม่ได้) และยังไม่ใช่สถานะจบ (เงินยังไม่ถูกแบ่ง)
 *
 * - แก้เฉพาะ MySQL/MariaDB (ตาราง rider_jobs ใช้ ENUM) · มีค่านี้อยู่แล้ว = ไม่ทำอะไร (รันซ้ำได้)
 * - เพิ่มค่าใหม่ท้าย ENUM ไม่กระทบแถวเดิม (ตอนเขียน prod มีงานไรเดอร์ 0 งาน)
 */
return new class extends Migration
{
    private const BASE_VALUES = ['pending', 'accepted', 'picking_up', 'picked_up', 'delivering', 'delivered', 'completed', 'cancelled', 'failed'];

    public function up(): void
    {
        if (! $this->isMysql() || ! Schema::hasTable('rider_jobs') || ! Schema::hasColumn('rider_jobs', 'status')) {
            return;
        }

        $type = $this->columnType();
        if ($type === null || ! str_starts_with(strtolower($type), 'enum(') || str_contains($type, "'awaiting_release'")) {
            return;
        }

        $values = array_merge($this->enumValues($type), ['awaiting_release']);

        DB::statement('ALTER TABLE `rider_jobs` MODIFY `status` '.$this->enumSql($values)." NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! $this->isMysql() || ! Schema::hasTable('rider_jobs') || ! Schema::hasColumn('rider_jobs', 'status')) {
            return;
        }

        $type = $this->columnType();
        if ($type === null || ! str_contains($type, "'awaiting_release'")) {
            return;
        }

        // งานที่ค้างรอปลดเงิน → กลับไปเป็น "กำลังจัดส่ง" ก่อนตัดค่าออก (ไม่ให้ข้อมูลถูกตัดเป็นค่าว่าง)
        DB::table('rider_jobs')->where('status', 'awaiting_release')->update(['status' => 'delivering']);

        $values = array_values(array_diff($this->enumValues($type), ['awaiting_release']));

        DB::statement('ALTER TABLE `rider_jobs` MODIFY `status` '.$this->enumSql($values)." NOT NULL DEFAULT 'pending'");
    }

    private function isMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function columnType(): ?string
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['rider_jobs', 'status']
        );

        return $row ? (string) $row->t : null;
    }

    /**
     * ค่าใน ENUM ปัจจุบัน (อ่านไม่ได้ = ใช้ชุดมาตรฐาน)
     *
     * @return array<int, string>
     */
    private function enumValues(string $type): array
    {
        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $m);

        return $m[1] !== [] ? $m[1] : self::BASE_VALUES;
    }

    /**
     * @param  array<int, string>  $values
     */
    private function enumSql(array $values): string
    {
        return 'ENUM('.implode(',', array_map(fn ($v) => "'".str_replace("'", "''", $v)."'", $values)).')';
    }
};
