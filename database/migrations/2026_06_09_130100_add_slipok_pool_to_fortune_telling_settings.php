<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ตั้งค่า SlipOK Account Pool ในตาราง fortune_telling_settings
     * + ย้าย key เดี่ยวเดิม (slipok_branch_id/slipok_api_key) เข้าเป็นบัญชีแรกของ pool
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return (ตาม CLAUDE.md)
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // เปิด/ปิดระบบ pool (ปิด = ใช้ key เดี่ยวเดิมแบบเดิม 100% backward-compatible)
            if (! Schema::hasColumn('fortune_telling_settings', 'slipok_pool_enabled')) {
                $table->boolean('slipok_pool_enabled')->default(false)->after('slipok_use_log');
            }

            // โหมดหมุนบัญชี: near_empty | failover | balance
            if (! Schema::hasColumn('fortune_telling_settings', 'slipok_pool_mode')) {
                $table->string('slipok_pool_mode')->default('near_empty')->after('slipok_pool_enabled');
            }

            // เกณฑ์ "ใกล้หมด" (โหมด near_empty) — โควต้าคงเหลือต่ำกว่านี้ → สลับบัญชีถัดไป
            if (! Schema::hasColumn('fortune_telling_settings', 'slipok_pool_threshold')) {
                $table->unsignedInteger('slipok_pool_threshold')->default(10)->after('slipok_pool_mode');
            }
        });

        // 🔁 ย้าย key เดี่ยวเดิม → บัญชีแรกใน pool (ครั้งเดียว ถ้ายังไม่มีบัญชีใน pool)
        try {
            if (Schema::hasTable('slipok_accounts')
                && class_exists(\App\Models\SlipOkAccount::class)
                && \App\Models\SlipOkAccount::count() === 0) {

                $settings = \App\Models\FortuneTellingSetting::query()->first();
                $branch = $settings?->slipok_branch_id;
                $key = $settings?->slipok_api_key;

                if (! empty($branch) && ! empty($key)) {
                    \App\Models\SlipOkAccount::create([
                        'label' => 'บัญชีหลัก (ย้ายจากตั้งค่าเดิม)',
                        'branch_id' => $branch,
                        'api_key' => $key,         // model mutator encrypt ให้เอง
                        'priority' => 1,
                        'is_active' => true,
                        'monthly_quota' => 100,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // non-blocking — ถ้า migrate ข้อมูลไม่ได้ ระบบยัง fallback ใช้ key เดี่ยวเดิม
        }
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม (ไม่ลบ slipok_accounts ที่ migrate ไป — กันข้อมูลหาย)
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['slipok_pool_enabled', 'slipok_pool_mode', 'slipok_pool_threshold'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
