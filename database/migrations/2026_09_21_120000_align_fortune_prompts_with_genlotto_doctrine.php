<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🇹🇭 ปรับพรอมต์แม่หมอใน DB ให้ตรงตำรา GenLotto (เจ้าของสั่ง 2026-09-21 "ยึดตำราโหราจาก genlotto เป็นหลักทั้งหมด")
 *
 * ทำไมต้องเป็น data migration: พรอมต์ใน DB ทับค่าตั้งต้นในโค้ดเสมอ (rule_db_prompt_overrides_code)
 *   แก้แค่ในโค้ด = prod ไม่เปลี่ยน · ตรวจ prod 2026-09-21 แล้วพบของเก่าค้าง 5 จุด:
 *   - deep_prompt_template  : เสาร์ "มิตร=ราหู(8),พฤหัสบดี(5)" (ตารางจริง = ราหู, ศุกร์) · จันทร์ "สีมงคล=ขาว,ครีม" (ตำรา = เหลือง)
 *   - basic_prompt_template : "พฤหัสบดี ธาตุน้ำ" (ตำรา = ลม) · "เสาร์ ธาตุไฟ" (ตำรา = ดิน) · เสาร์ "มิตร=ราหู,พฤหัสบดี"
 *
 * แก้แบบ str_replace เฉพาะสตริงเดิมเป๊ะ — ไม่แตะบรรทัดอื่นที่แอดมินแก้เอง · ไม่เจอสตริงเดิม = ไม่ทำอะไร
 * สำรองค่าเดิมทั้งก้อนก่อนเขียนที่ backups/prompt-backups/ (โฟลเดอร์นี้รอด git clean ของ deploy.sh)
 */
return new class extends Migration
{
    /** [คอลัมน์ => [สตริงเดิม => สตริงใหม่]] */
    private const REPLACEMENTS = [
        'deep_prompt_template' => [
            'มิตร=ราหู(8),พฤหัสบดี(5)' => 'มิตร=ราหู(8),ศุกร์(6)',
            'เจ้าชนะ=จันทร์(2) มิตร=พุธ(4),ศุกร์(6) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=ขาว,ครีม' => 'เจ้าชนะ=จันทร์(2) มิตร=พุธ(4),ศุกร์(6) ศัตรู=ราหู(8),เสาร์(7) สีมงคล=เหลือง,ครีม',
        ],
        'basic_prompt_template' => [
            'ดาวประจำวันพฤหัสบดี ธาตุน้ำ' => 'ดาวประจำวันพฤหัสบดี ธาตุลม',
            'ดาวประจำวันเสาร์ ธาตุไฟ' => 'ดาวประจำวันเสาร์ ธาตุดิน',
            'ดาวเจ้าชนะ=เสาร์ มิตร=ราหู,พฤหัสบดี' => 'ดาวเจ้าชนะ=เสาร์ มิตร=ราหู,ศุกร์',
        ],
    ];

    /**
     * แทนที่สตริงตำราเก่าในพรอมต์ของทุกแถวตั้งค่า
     */
    public function up(): void
    {
        $this->apply(false);
    }

    /**
     * ย้อนกลับ — แทนที่สตริงใหม่กลับเป็นของเดิม (เฉพาะที่ยังเป็นสตริงใหม่เป๊ะ)
     */
    public function down(): void
    {
        $this->apply(true);
    }

    private function apply(bool $reverse): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        foreach (DB::table('fortune_telling_settings')->get() as $row) {
            $updates = [];
            foreach (self::REPLACEMENTS as $column => $pairs) {
                if (! Schema::hasColumn('fortune_telling_settings', $column)) {
                    continue;
                }
                $original = (string) ($row->{$column} ?? '');
                if ($original === '') {
                    continue; // ว่าง = ใช้ค่าตั้งต้นในโค้ด (แก้ในโค้ดแล้ว)
                }

                $text = $original;
                foreach ($pairs as $old => $new) {
                    $text = $reverse ? str_replace($new, $old, $text) : str_replace($old, $new, $text);
                }
                if ($text !== $original) {
                    $this->backup((int) $row->id, $column, $original, $reverse ? 'down' : 'up');
                    $updates[$column] = $text;
                }
            }

            if ($updates !== []) {
                DB::table('fortune_telling_settings')->where('id', $row->id)->update($updates);
            }
        }
    }

    /** สำรองค่าเดิมทั้งก้อน — ล้มเหลวก็ไม่หยุด migration (ค่าเดิมยังย้อนได้ด้วย down()) */
    private function backup(int $id, string $column, string $original, string $direction): void
    {
        try {
            $dir = base_path('backups/prompt-backups');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents(
                $dir.'/'.date('Ymd-His')."-genlotto-doctrine-{$direction}-{$column}-{$id}.txt",
                $original
            );
        } catch (\Throwable $e) {
            // ไม่บล็อก
        }
    }
};
