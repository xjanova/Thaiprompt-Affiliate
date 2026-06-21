<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม "โหมดเสียงสรุปต่อแพคเกจ" (Celtic 99 / Deep 39) — เลือก ปิด/อัตโนมัติ/กดเอง แยกกัน
     *
     * แทน voice_summary_tier_scope เดิม (ที่คุมแค่ availability) ด้วยโหมด 3 สถานะต่อ tier:
     *   'auto'      → จบทำนายแล้วส่งเสียงให้อัตโนมัติ
     *   'on_demand' → ไม่ส่งอัตโนมัติ แต่ลูกค้ากดปุ่ม "🎧 อ่านให้ฟัง" ได้ (ประหยัด TTS)
     *   'off'       → ปิดเสียงสรุปสำหรับแพคเกจนี้
     *
     * voice_summary_enabled = master ยังคงคุมเปิด/ปิดรวม (ปิด = ทั้งคู่ off)
     * tier_scope เดิมไม่ลบ (เก็บไว้ backward-compat) แต่เลิกใช้แล้ว
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable()+return
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        $justAdded = false;

        Schema::table('fortune_telling_settings', function (Blueprint $table) use (&$justAdded) {
            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_celtic_mode')) {
                $table->string('voice_summary_celtic_mode', 12)->default('auto')->after('voice_summary_tier_scope');
                $justAdded = true;
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'voice_summary_deep_mode')) {
                $table->string('voice_summary_deep_mode', 12)->default('off')->after('voice_summary_celtic_mode');
                $justAdded = true;
            }
        });

        // ── ย้ายค่าจาก tier_scope เดิม → โหมดต่อ tier (รักษาพฤติกรรมเดิม) ──
        //   celtic เคยเป็น auto เสมอเมื่อ scope ครอบ Celtic (celtic_99_only/paid_all/all)
        //   deep auto เฉพาะ scope = paid_all/all (celtic_99_only → deep ปิด)
        //   ⚠️ idempotent: backfill เฉพาะตอน "เพิ่งสร้างคอลัมน์" — กันรัน up() ซ้ำทับค่าที่แอดมินตั้งเอง
        //   หมายเหตุ: legacy scope='all' ที่เคยให้เสียง reading ฟรี (free_card/basic) ไม่ carry ต่อ
        //            (โดยตั้งใจ — เสียงสรุป = เฉพาะแพคเกจจ่ายเงิน Celtic 99/Deep 39)
        if ($justAdded) {
            foreach (DB::table('fortune_telling_settings')->get() as $row) {
                $scope = $row->voice_summary_tier_scope ?? 'celtic_99_only';

                DB::table('fortune_telling_settings')->where('id', $row->id)->update([
                    'voice_summary_celtic_mode' => 'auto',
                    'voice_summary_deep_mode' => in_array($scope, ['paid_all', 'all'], true) ? 'auto' : 'off',
                ]);
            }
        }
    }

    /**
     * ลบโหมดต่อ tier
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['voice_summary_celtic_mode', 'voice_summary_deep_mode'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
