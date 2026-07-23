<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ซ่อมข้อมูล unilevel_levels ที่ถูกหน้าตั้งค่าเขียนทับเป็นตัวเลข (int)
     *
     * เหตุผล: บั๊กเดิมในหน้าตั้งค่า MLM บันทึก unilevel_levels เป็นจำนวนชั้น (เช่น "5")
     * แต่ engine (MlmCommissionService/MlmUnilevelService/MlmPvService) อ่านเป็น
     * array [{level, percentage}] → ถ้าค่าเป็น int คอมมิชชั่น Unilevel จะเป็น 0 ทั้งระบบ
     *
     * การซ่อม: ถ้าค่าปัจจุบัน decode แล้วไม่ใช่ array ให้กู้จาก
     * 1) unilevel_percentages (ถ้ามี — รองรับทั้ง JSON array และ comma-separated)
     * 2) ค่า default จาก MlmGlobalSettingsSeeder
     */
    public function up(): void
    {
        // ⚠️ ชื่อตารางจริงคือ mlm_global_settings (ตาม MlmGlobalSetting model)
        if (! Schema::hasTable('mlm_global_settings')) {
            return;
        }

        $row = DB::table('mlm_global_settings')->where('key', 'unilevel_levels')->first();

        if (! $row) {
            return;
        }

        $decoded = json_decode($row->value ?? '', true);

        // ค่าปกติต้องเป็น array ของ {level, percentage} — ถ้าใช่ ไม่ต้องซ่อม
        if (is_array($decoded) && isset($decoded[0]['level'])) {
            return;
        }

        // ─── กู้จาก unilevel_percentages ───
        $levels = null;
        $pctRow = DB::table('mlm_global_settings')->where('key', 'unilevel_percentages')->first();

        if ($pctRow && trim((string) $pctRow->value) !== '') {
            $raw = trim((string) $pctRow->value);
            $values = str_starts_with($raw, '[')
                ? json_decode($raw, true)
                : array_map('trim', explode(',', $raw));

            if (is_array($values) && $values !== []) {
                $levels = [];
                foreach (array_values($values) as $i => $pct) {
                    if (! is_numeric($pct)) {
                        $levels = null;
                        break;
                    }
                    $levels[] = ['level' => $i + 1, 'percentage' => (float) $pct];
                }
            }
        }

        // ─── fallback: ค่า default เดียวกับ MlmGlobalSettingsSeeder ───
        if ($levels === null) {
            $levels = [
                ['level' => 1, 'percentage' => 10],
                ['level' => 2, 'percentage' => 5],
                ['level' => 3, 'percentage' => 3],
                ['level' => 4, 'percentage' => 2],
                ['level' => 5, 'percentage' => 1],
                ['level' => 6, 'percentage' => 1],
                ['level' => 7, 'percentage' => 1],
                ['level' => 8, 'percentage' => 1],
                ['level' => 9, 'percentage' => 1],
                ['level' => 10, 'percentage' => 1],
            ];
        }

        // ถ้าค่าเดิมเป็นจำนวนชั้น (int) ให้ตัด array ตามจำนวนนั้น (รักษาเจตนาแอดมิน)
        if (is_int($decoded) && $decoded >= 1 && $decoded <= 10) {
            $levels = array_slice($levels, 0, $decoded);
        }

        DB::table('mlm_global_settings')
            ->where('key', 'unilevel_levels')
            ->update([
                'value' => json_encode($levels),
                'type' => 'json',
                'updated_at' => now(),
            ]);

        // ล้าง cache ให้ engine เห็นค่าใหม่ทันที
        Cache::forget('mlm_setting_unilevel_levels');
        Cache::forget('mlm_all_settings');

        Log::warning('ซ่อมข้อมูล unilevel_levels ที่ corrupt เรียบร้อย', [
            'old_value' => $row->value,
            'new_levels_count' => count($levels),
        ]);
    }

    /**
     * ไม่ต้อง rollback — การซ่อมข้อมูลเป็น one-way (ค่าเดิมคือค่าที่พัง)
     */
    public function down(): void
    {
        // intentionally empty
    }
};
