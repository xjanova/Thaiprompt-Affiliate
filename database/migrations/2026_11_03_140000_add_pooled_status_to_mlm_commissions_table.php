<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สถานะ 'pooled' ของ mlm_commissions = เงินโรลอัพที่ไม่มีผู้รับ อยู่ใน mlm_pool (audit F8 · เจ้าของตัดสิน M4)
     *
     * เดิม engine สร้างแถว pool_bonus สถานะ pending เป็นของ member #1 (ถ้าไม่มี member #1 เป็นของผู้ซื้อเอง)
     * พอแอดมินกดอนุมัติทั้งหมด + จ่ายทั้งหมด เงินกองเข้ากระเป๋าคน — ตอนนี้แถวแบบนี้เป็น ledger ของกอง:
     *   1. เพิ่มค่า 'pooled' เข้า enum status (คงค่าเดิมทุกค่า)
     *   2. ให้ mlm_member_id / user_id เป็น NULL ได้ (เงินกองไม่มีเจ้าของ) — FK เดิมยังอยู่
     *   3. แถวโรลอัพเข้ากองเดิมที่ยังไม่จ่าย (pending/approved) → pooled กันกดอนุมัติ/จ่ายต่อ
     *      ⚠️ ไม่ลบแถว ไม่แก้ยอด ไม่แก้เจ้าของเดิม และไม่แตะแถวที่ paid แล้ว (ต้องตรวจเป็นรายกรณีตาม M1/M17)
     *      ⚠️ แถว pool_bonus ของรอบแจกกองรายสัปดาห์ (is_rollup = 0) ไม่ถูกแตะ
     *   4. ซ่อนสวิตช์ unilevel_compression_enabled ที่ไม่มีผล (F29 / M5 — engine ใช้ rollup) ค่าเดิมไม่ถูกแก้
     *
     * idempotent: รันซ้ำได้ ไม่ทำลายข้อมูล
     */
    public function up(): void
    {
        if (! Schema::hasTable('mlm_commissions')) {
            return;
        }

        // 1) เพิ่ม 'pooled' เข้า enum — อ่านค่าที่มีอยู่จริงแล้วต่อท้าย (ไม่ hardcode รายการ กันค่าที่ migration อื่นเพิ่มไว้หาย)
        $status = DB::selectOne("SHOW COLUMNS FROM `mlm_commissions` WHERE Field = 'status'");
        $type = (string) ($status->Type ?? '');
        if (str_starts_with($type, 'enum(') && ! str_contains($type, "'pooled'")) {
            preg_match_all("/'((?:[^']|'')*)'/", $type, $m);
            $values = array_values(array_unique(array_merge($m[1], ['pooled'])));
            $list = implode(',', array_map(fn ($v) => "'".str_replace("'", "''", $v)."'", $values));
            DB::statement("ALTER TABLE `mlm_commissions` MODIFY COLUMN `status` ENUM({$list}) NOT NULL DEFAULT 'pending'");
        }

        // 2) เจ้าของแถวเป็น NULL ได้ (เฉพาะคอลัมน์ที่ยัง NOT NULL)
        foreach (['mlm_member_id', 'user_id'] as $column) {
            if (! Schema::hasColumn('mlm_commissions', $column)) {
                continue;
            }
            $info = DB::selectOne('SHOW COLUMNS FROM `mlm_commissions` WHERE Field = ?', [$column]);
            if ($info && strtoupper((string) $info->Null) === 'NO') {
                DB::statement("ALTER TABLE `mlm_commissions` MODIFY COLUMN `{$column}` BIGINT UNSIGNED NULL");
            }
        }

        // 3) แถวโรลอัพเข้ากองเดิมที่ยังไม่จ่าย → pooled (ไม่แตะแถว paid / แถวแจกกองรายสัปดาห์)
        DB::table('mlm_commissions')
            ->where('type', 'pool_bonus')
            ->where('is_rollup', true)
            ->where('tree_type', 'pool')
            ->whereIn('status', ['pending', 'approved'])
            ->update(['status' => 'pooled', 'updated_at' => now()]);

        // 4) audit F29 / M5: engine จ่ายแบบ rollup เสมอ — ซ่อนสวิตช์ compression ที่ไม่มีผล (ไม่แตะค่า value)
        if (Schema::hasTable('mlm_global_settings')) {
            DB::table('mlm_global_settings')
                ->where('key', 'unilevel_compression_enabled')
                ->update([
                    'is_visible' => false,
                    'description' => 'Not used: the commission engine always uses roll-up (owner decision M5)',
                    'description_th' => 'ไม่มีผล: ระบบจ่ายแบบ Roll-up เสมอ (ตามที่เจ้าของตัดสิน M5)',
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * ย้อนกลับ: แถว pooled → cancelled (ยังจ่ายไม่ได้เหมือนเดิม ไม่คืนเป็น pending เพราะจะกลับไปจ่ายเข้ากระเป๋าคนได้อีก)
     * แล้วตัด 'pooled' ออกจาก enum
     *
     * ⚠️ ไม่บังคับ mlm_member_id / user_id กลับเป็น NOT NULL — แถว ledger ของกองที่ไม่มีเจ้าของจะทำให้ ALTER ล้ม
     *    และห้ามเดาเจ้าของให้ (นั่นคือบั๊ก F8 เอง)
     */
    public function down(): void
    {
        if (! Schema::hasTable('mlm_commissions')) {
            return;
        }

        DB::table('mlm_commissions')->where('status', 'pooled')->update(['status' => 'cancelled', 'updated_at' => now()]);

        $status = DB::selectOne("SHOW COLUMNS FROM `mlm_commissions` WHERE Field = 'status'");
        $type = (string) ($status->Type ?? '');
        if (str_contains($type, "'pooled'")) {
            preg_match_all("/'((?:[^']|'')*)'/", $type, $m);
            $values = array_values(array_diff($m[1], ['pooled']));
            $list = implode(',', array_map(fn ($v) => "'".str_replace("'", "''", $v)."'", $values));
            DB::statement("ALTER TABLE `mlm_commissions` MODIFY COLUMN `status` ENUM({$list}) NOT NULL DEFAULT 'pending'");
        }
    }
};
