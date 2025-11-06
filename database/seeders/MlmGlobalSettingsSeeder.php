<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MlmGlobalSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'mlm_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Enable/Disable MLM System',
                'description_th' => 'เปิด/ปิดระบบ MLM',
                'sort_order' => 1,
            ],
            [
                'key' => 'mlm_system_name',
                'value' => 'Thai Prompt MLM',
                'type' => 'string',
                'group' => 'general',
                'description' => 'MLM System Name',
                'description_th' => 'ชื่อระบบ MLM',
                'sort_order' => 2,
            ],
            [
                'key' => 'default_currency',
                'value' => 'THB',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default Currency',
                'description_th' => 'สกุลเงินเริ่มต้น',
                'sort_order' => 3,
            ],

            // PV System
            [
                'key' => 'global_pv_rate',
                'value' => '1.00',
                'type' => 'decimal',
                'group' => 'pv',
                'description' => 'Global PV Rate (1 THB = X PV)',
                'description_th' => 'อัตราแปลง PV ทั่วไป (1 บาท = X PV)',
                'sort_order' => 10,
            ],
            [
                'key' => 'commission_per_pv',
                'value' => '1.00',
                'type' => 'decimal',
                'group' => 'pv',
                'description' => 'Commission per 1 PV (THB)',
                'description_th' => 'ค่าคอมมิชชันต่อ 1 PV (บาท)',
                'sort_order' => 11,
            ],

            // Unilevel Settings
            [
                'key' => 'unilevel_max_depth',
                'value' => '10',
                'type' => 'integer',
                'group' => 'unilevel',
                'description' => 'Maximum Unilevel Depth',
                'description_th' => 'ระดับ Unilevel สูงสุด',
                'sort_order' => 20,
            ],
            [
                'key' => 'unilevel_levels',
                'value' => json_encode([
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
                ]),
                'type' => 'json',
                'group' => 'unilevel',
                'description' => 'Unilevel Commission Percentages by Level',
                'description_th' => 'เปอร์เซ็นต์คอมมิชชัน Unilevel แต่ละระดับ',
                'sort_order' => 21,
            ],
            [
                'key' => 'unilevel_compression_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'unilevel',
                'description' => 'Enable Unilevel Compression (Skip inactive members)',
                'description_th' => 'เปิดใช้ Compression (ข้ามคนไม่ active)',
                'sort_order' => 22,
            ],
            [
                'key' => 'unilevel_max_commission_per_level',
                'value' => null,
                'type' => 'decimal',
                'group' => 'unilevel',
                'description' => 'Maximum Commission per Level (null = unlimited)',
                'description_th' => 'คอมมิชชันสูงสุดต่อระดับ (null = ไม่จำกัด)',
                'sort_order' => 23,
            ],

            // Binary Settings
            [
                'key' => 'binary_pair_commission',
                'value' => '100.00',
                'type' => 'decimal',
                'group' => 'binary',
                'description' => 'Commission per Pair (THB)',
                'description_th' => 'ค่าคอมมิชชันต่อคู่ (บาท)',
                'sort_order' => 30,
            ],
            [
                'key' => 'binary_match_percentage',
                'value' => '50.00',
                'type' => 'decimal',
                'group' => 'binary',
                'description' => 'Binary Match Percentage (%)',
                'description_th' => 'เปอร์เซ็นต์การจับคู่ Binary (%)',
                'sort_order' => 31,
            ],
            [
                'key' => 'binary_max_pairs_per_day',
                'value' => null,
                'type' => 'integer',
                'group' => 'binary',
                'description' => 'Maximum Pairs per Day (null = unlimited)',
                'description_th' => 'จำนวนคู่สูงสุดต่อวัน (null = ไม่จำกัด)',
                'sort_order' => 32,
            ],
            [
                'key' => 'binary_max_commission_per_day',
                'value' => null,
                'type' => 'decimal',
                'group' => 'binary',
                'description' => 'Maximum Commission per Day (THB, null = unlimited)',
                'description_th' => 'คอมมิชชันสูงสุดต่อวัน (บาท, null = ไม่จำกัด)',
                'sort_order' => 33,
            ],
            [
                'key' => 'binary_spillover_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'binary',
                'description' => 'Enable Binary Spillover',
                'description_th' => 'เปิดใช้ Binary Spillover',
                'sort_order' => 34,
            ],
            [
                'key' => 'binary_pairing_type',
                'value' => '1:1',
                'type' => 'string',
                'group' => 'binary',
                'description' => 'Binary Pairing Type (1:1 or 2:1)',
                'description_th' => 'ประเภทการจับคู่ Binary (1:1 หรือ 2:1)',
                'sort_order' => 35,
            ],

            // Flush Settings
            [
                'key' => 'binary_flush_mode',
                'value' => 'percentage',
                'type' => 'string',
                'group' => 'flush',
                'description' => 'Flush Mode (percentage, full, none)',
                'description_th' => 'โหมดการล้าง PV (percentage, full, none)',
                'sort_order' => 40,
            ],
            [
                'key' => 'binary_flush_percentage',
                'value' => '100.00',
                'type' => 'decimal',
                'group' => 'flush',
                'description' => 'Flush Percentage (%)',
                'description_th' => 'เปอร์เซ็นต์การล้าง PV (%)',
                'sort_order' => 41,
            ],
            [
                'key' => 'binary_carry_forward_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'flush',
                'description' => 'Enable Carry Forward PV',
                'description_th' => 'เปิดใช้การยกยอด PV',
                'sort_order' => 42,
            ],
            [
                'key' => 'binary_carry_forward_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'flush',
                'description' => 'Carry Forward Days',
                'description_th' => 'จำนวนวันที่ยกยอด',
                'sort_order' => 43,
            ],

            // Auto Placement
            [
                'key' => 'auto_placement_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'placement',
                'description' => 'Enable Auto Placement',
                'description_th' => 'เปิดใช้การจัดวางอัตโนมัติ',
                'sort_order' => 50,
            ],
            [
                'key' => 'auto_placement_strategy',
                'value' => 'balanced',
                'type' => 'string',
                'group' => 'placement',
                'description' => 'Auto Placement Strategy (left_first, right_first, fill_level, weak_leg, balanced)',
                'description_th' => 'กลยุทธ์การจัดวางอัตโนมัติ',
                'sort_order' => 51,
            ],

            // Roll-up/Compression
            [
                'key' => 'rollup_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'rollup',
                'description' => 'Enable Roll-up (Commission compression for inactive members)',
                'description_th' => 'เปิดใช้ Roll-up (ปันผลข้ามคนที่ไม่รักษายอด)',
                'sort_order' => 60,
            ],
            [
                'key' => 'rollup_prevent_duplicate',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'rollup',
                'description' => 'Prevent Duplicate Roll-up Commission',
                'description_th' => 'ป้องกันการได้ปันผล Roll-up ซ้ำ',
                'sort_order' => 61,
            ],
            [
                'key' => 'rollup_max_levels',
                'value' => '10',
                'type' => 'integer',
                'group' => 'rollup',
                'description' => 'Maximum Levels to Roll-up',
                'description_th' => 'จำนวนระดับสูงสุดที่จะ Roll-up',
                'sort_order' => 62,
            ],

            // Volume Retention (รักษายอด)
            [
                'key' => 'volume_retention_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'retention',
                'description' => 'Enable Volume Retention Requirement',
                'description_th' => 'เปิดใช้ระบบรักษายอด',
                'sort_order' => 70,
            ],
            [
                'key' => 'volume_retention_monthly_pv',
                'value' => '100',
                'type' => 'decimal',
                'group' => 'retention',
                'description' => 'Monthly PV Requirement',
                'description_th' => 'PV ที่ต้องรักษาต่อเดือน',
                'sort_order' => 71,
            ],
            [
                'key' => 'volume_retention_grace_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'retention',
                'description' => 'Grace Period Days (Yellow warning)',
                'description_th' => 'จำนวนวันผ่อนผัน (แจ้งเตือนเหลือง)',
                'sort_order' => 72,
            ],

            // Overpay Protection
            [
                'key' => 'overpay_protection_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'commission',
                'description' => 'Enable Overpay Protection',
                'description_th' => 'เปิดใช้ระบบป้องกัน Overpay',
                'sort_order' => 80,
            ],
            [
                'key' => 'max_commission_percentage',
                'value' => '50.00',
                'type' => 'decimal',
                'group' => 'commission',
                'description' => 'Maximum Total Commission Percentage (%)',
                'description_th' => 'เปอร์เซ็นต์คอมมิชชันรวมสูงสุด (%)',
                'sort_order' => 81,
            ],
        ];

        // Clear existing settings first
        DB::table('mlm_global_settings')->truncate();

        foreach ($settings as $setting) {
            DB::table('mlm_global_settings')->insert(array_merge($setting, [
                'is_visible' => $setting['is_visible'] ?? true,
                'is_editable' => $setting['is_editable'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
