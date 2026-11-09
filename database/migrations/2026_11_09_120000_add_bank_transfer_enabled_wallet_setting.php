<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มสวิตช์ bank_transfer_enabled (ค่าเริ่มต้น = ปิด) ให้หน้า Admin → Wallet Settings
     *
     * เดิมช่องทางโอนธนาคาร + แนบสลิป เปิดแบบ hardcode และไม่มีแถว setting ให้แอดมินสลับ
     * ใช้ migration (ไม่ใช่แค่ seeder) เพราะ prod ไม่ได้รัน seeder ตอน deploy — ไม่มีแถว = เปิดจากหน้าแอดมินไม่ได้
     * ถ้ามีแถวอยู่แล้วไม่แตะค่าเดิม
     */
    public function up(): void
    {
        if (! Schema::hasTable('wallet_settings')) {
            return;
        }

        if (DB::table('wallet_settings')->where('key', 'bank_transfer_enabled')->exists()) {
            return;
        }

        DB::table('wallet_settings')->insert([
            'key' => 'bank_transfer_enabled',
            'group' => 'payment_methods',
            'value' => '0',
            'type' => 'boolean',
            'label' => 'เปิดใช้งานโอนผ่านธนาคาร (แนบสลิป)',
            'description' => 'ให้ผู้ใช้โอนเงินแล้วแนบสลิป — ทุกรายการรอแอดมินอนุมัติในแอป SMS Checker ก่อนเงินเข้า',
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 13,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('wallet_settings');
    }

    /**
     * ลบสวิตช์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        if (Schema::hasTable('wallet_settings')) {
            DB::table('wallet_settings')->where('key', 'bank_transfer_enabled')->delete();
            Cache::forget('wallet_settings');
        }
    }
};
