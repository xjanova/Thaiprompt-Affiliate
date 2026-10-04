<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛵 ไรเดอร์รอบ 2 — รอบแก้หลังรีวิว (2026-10-04, เลน handover/state)
 *
 * 1) orders.client_app_build / fresh_market_orders.client_app_build
 *    เลข build ของแอปที่ใช้สั่งซื้อ (จาก header X-App-Build ตอน checkout ผ่านแอป)
 *    หน้าเว็บ/LINE/แอปรุ่นเก่าที่ไม่ส่ง header = NULL
 *    ใช้ตัดสินตอนไรเดอร์กดรับงานว่างานนั้นต้อง "สแกนส่งมอบ" หรือใช้ปุ่มส่งของแบบเดิม
 *    (ต้องเป็นแอปรุ่นที่มีหน้าส่งมอบทั้งฝั่งผู้ซื้อและฝั่งไรเดอร์ — ไม่งั้นงานค้างส่งมอบไม่ได้)
 *
 * 2) delivery_handovers.rider_code_attempts / rider_code_locked_until
 *    ตัวนับครั้งที่ "ผู้ซื้อกรอกรหัส 6 หลักของไรเดอร์" ผิด (แยกจาก code_attempts ที่นับฝั่งไรเดอร์กรอกรหัสผู้ซื้อ)
 *    ผิดครบ 5 ครั้ง ล็อก 10 นาที เหมือนฝั่งไรเดอร์
 *
 * ทุกคอลัมน์ nullable/มีค่าเริ่มต้น — แถวเดิมไม่ถูกแตะ รันซ้ำได้ (SafeMigration)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        foreach (['orders', 'fresh_market_orders'] as $orders) {
            if (! Schema::hasTable($orders)) {
                continue;
            }

            Schema::table($orders, function (Blueprint $table) use ($orders) {
                // เลข build ของแอปที่สั่ง (NULL = เว็บ / LINE / แอปรุ่นเก่า)
                $this->safeAddColumn($table, $orders, 'client_app_build', fn ($t) => $t->unsignedSmallInteger('client_app_build')->nullable());
            });
        }

        if (Schema::hasTable('delivery_handovers')) {
            Schema::table('delivery_handovers', function (Blueprint $table) {
                // ผู้ซื้อกรอกรหัสของไรเดอร์ผิดกี่ครั้งแล้ว (ล้างเมื่อกรอกถูก/พ้นเวลาล็อก)
                $this->safeAddColumn($table, 'delivery_handovers', 'rider_code_attempts', fn ($t) => $t->unsignedTinyInteger('rider_code_attempts')->default(0));
                // ล็อกการกรอกรหัสของไรเดอร์ถึงเวลานี้
                $this->safeAddColumn($table, 'delivery_handovers', 'rider_code_locked_until', fn ($t) => $t->timestamp('rider_code_locked_until')->nullable());
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_handovers')) {
            $this->safeDropColumn('delivery_handovers', ['rider_code_attempts', 'rider_code_locked_until']);
        }

        foreach (['orders', 'fresh_market_orders'] as $orders) {
            if (Schema::hasTable($orders)) {
                $this->safeDropColumn($orders, ['client_app_build']);
            }
        }
    }
};
