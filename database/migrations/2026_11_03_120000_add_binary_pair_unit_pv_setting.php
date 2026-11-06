<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มค่าตั้ง binary_pair_unit_pv (PV ต่อขาที่นับเป็น 1 คู่) — audit F1 / เจ้าของตัดสิน M2
     *
     * ⚠️ เพิ่มแถวเฉพาะเมื่อยังไม่มีเท่านั้น ไม่เขียนทับค่าที่แอดมินตั้งไว้
     * และไม่แตะ binary_max_pairs_per_day บน prod (ค่า NULL ถูก engine อ่านเป็นเพดานเริ่มต้น 10 คู่อยู่แล้ว)
     */
    public function up(): void
    {
        if (! Schema::hasTable('mlm_global_settings')) {
            return;
        }

        // ฐานใหม่ (ตารางว่าง: CI/ติดตั้งใหม่/เทสต์) → ไม่ใส่ ให้ MlmGlobalSettingsSeeder ใส่พร้อมค่าตั้งอื่น
        //    ถ้าใส่ตรงนี้ แถวนี้จะกลายเป็น id 1 แล้วโค้ดที่อ่าน MlmGlobalSetting::first() (เช่น MlmProspect)
        //    จะอ่านค่า prospect ผิดแถว
        if (! DB::table('mlm_global_settings')->exists()) {
            return;
        }

        if (DB::table('mlm_global_settings')->where('key', 'binary_pair_unit_pv')->exists()) {
            return;
        }

        $row = [
            'key' => 'binary_pair_unit_pv',
            'value' => '100',
            'type' => 'decimal',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // คอลัมน์ metadata มีเฉพาะบางฐาน — ใส่เท่าที่ตารางมีจริง
        $optional = [
            'input_type' => 'number',
            'group' => 'binary',
            'description' => 'PV per leg that counts as one pair',
            'description_th' => 'PV ต่อขาที่นับเป็น 1 คู่',
            'placeholder' => '100',
            'unit' => 'PV',
            'sort_order' => 30,
            'is_editable' => 1,
            'is_visible' => 1,
        ];
        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('mlm_global_settings', $column)) {
                $row[$column] = $value;
            }
        }

        DB::table('mlm_global_settings')->insert($row);
    }

    /**
     * ไม่ลบแถวตอน rollback — ค่าตั้งอาจถูกแอดมินแก้แล้ว และ engine มีค่าเริ่มต้นสำรองอยู่
     */
    public function down(): void
    {
        // ตั้งใจไม่ทำอะไร (ไม่ทำลายข้อมูล)
    }
};
