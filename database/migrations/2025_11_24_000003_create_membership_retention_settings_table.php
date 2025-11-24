<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * สร้างตาราง membership_retention_settings สำหรับการตั้งค่าระบบรักษายอดสมาชิก
     *
     * ตารางนี้เก็บการตั้งค่าต่างๆ เช่น:
     * - minimum_points_per_month: แต้มขั้นต่ำต่อเดือน
     * - repair_cost_per_point: ค่าซ่อมต่อแต้ม
     * - grace_period_days: ระยะเวลาผ่อนผัน
     * - enable_retention_system: เปิด/ปิดระบบ
     *
     * @return void
     */
    public function up(): void
    {
        // เช็คว่าตารางมีอยู่แล้วหรือยัง
        if (Schema::hasTable('membership_retention_settings')) {
            return;
        }

        Schema::create('membership_retention_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // เพิ่มข้อมูลเริ่มต้น
        DB::table('membership_retention_settings')->insert([
            [
                'key' => 'minimum_points_per_month',
                'value' => '1000',
                'type' => 'number',
                'description' => 'จำนวนแต้มขั้นต่ำที่ต้องรักษาต่อเดือน',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'repair_cost_per_point',
                'value' => '1.5',
                'type' => 'number',
                'description' => 'ค่าใช้จ่ายในการซ่อมต่อแต้ม (เท่าของราคาปกติ)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'advance_renewal_discount',
                'value' => '0.9',
                'type' => 'number',
                'description' => 'ส่วนลดสำหรับการเติมล่วงหน้า (0.9 = ลด 10%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'grace_period_days',
                'value' => '3',
                'type' => 'number',
                'description' => 'ระยะเวลาผ่อนผัน (วัน) หลังหมดเขตก่อนจะถูก expire',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'warning_days_before_expiry',
                'value' => '7',
                'type' => 'number',
                'description' => 'จำนวนวันที่จะแจ้งเตือนก่อนหมดอายุ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_retention_system',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'เปิด/ปิด ระบบรักษายอด',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'block_commission_if_expired',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'บล็อกการคำนวณคอมมิชชั่นหากไม่รักษายอด',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * ลบตาราง membership_retention_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_retention_settings');
    }
};
