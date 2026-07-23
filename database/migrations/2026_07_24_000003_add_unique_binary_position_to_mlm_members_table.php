<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม unique index กันตำแหน่ง binary ซ้ำ (สองสมาชิกอ้าง parent+leg เดียวกัน)
     *
     * เหตุผล: การจัดวาง binary อ่าน children โดยไม่ล็อก → registration พร้อมกัน
     * แย่ง slot เดียวกันได้ → node ซ้ำเงียบๆ (hasOne ซ่อนตัวที่สอง = orphan)
     * unique index นี้เป็น backstop สุดท้าย — placement code มี retry เมื่อชนแล้ว
     * (MlmBinaryService::placeNewMember)
     *
     * ⚠️ ถ้ามีข้อมูลซ้ำอยู่ก่อนแล้ว จะ log รายการซ้ำและ "ข้าม" การสร้าง index
     * (ไม่ทำ deploy พัง) — ต้องซ่อมข้อมูลก่อนแล้วรัน migrate อีกครั้ง
     */
    public function up(): void
    {
        if (! Schema::hasTable('mlm_members')) {
            return;
        }

        // idempotent: มี index แล้วไม่ต้องทำซ้ำ
        $existing = DB::select(
            "SHOW INDEX FROM `mlm_members` WHERE Key_name = 'mlm_binary_slot_unique'"
        );
        if (! empty($existing)) {
            return;
        }

        // เช็คข้อมูลซ้ำก่อนสร้าง unique index
        $duplicates = DB::table('mlm_members')
            ->select('binary_parent_id', 'binary_position', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('binary_parent_id')
            ->whereNotNull('binary_position')
            ->groupBy('binary_parent_id', 'binary_position')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            Log::error('พบตำแหน่ง binary ซ้ำ — ข้ามการสร้าง unique index (ต้องซ่อมข้อมูลก่อน)', [
                'duplicate_slots' => $duplicates->map(fn ($d) => [
                    'binary_parent_id' => $d->binary_parent_id,
                    'binary_position' => $d->binary_position,
                    'count' => $d->cnt,
                ])->all(),
            ]);

            return;
        }

        Schema::table('mlm_members', function (Blueprint $table) {
            // MySQL อนุญาตหลายแถวที่ binary_parent_id เป็น NULL ใน unique index
            // (สมาชิกที่ยังไม่ถูกวางไม่ชนกันเอง)
            $table->unique(['binary_parent_id', 'binary_position'], 'mlm_binary_slot_unique');
        });
    }

    /**
     * ลบ unique index
     */
    public function down(): void
    {
        if (! Schema::hasTable('mlm_members')) {
            return;
        }

        $existing = DB::select(
            "SHOW INDEX FROM `mlm_members` WHERE Key_name = 'mlm_binary_slot_unique'"
        );
        if (empty($existing)) {
            return;
        }

        Schema::table('mlm_members', function (Blueprint $table) {
            $table->dropUnique('mlm_binary_slot_unique');
        });
    }
};
