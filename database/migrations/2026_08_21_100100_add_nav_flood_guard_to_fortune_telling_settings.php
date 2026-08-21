<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มค่าตั้งของระบบเบรก/เตือน/ระงับ "กดปุ่มรัว" ลง fortune_telling_settings
     *
     * ⚠️ IMPORTANT: เป็นการ "เพิ่มคอลัมน์" ห้ามใช้ Schema::hasTable() + return
     *    ต้องใช้ Schema::table() แล้วเช็คทีละคอลัมน์ ไม่งั้นคอลัมน์ใหม่จะไม่ถูกสร้าง
     *
     * ค่าเริ่มต้นตั้งใจให้ "ปิด + log อย่างเดียว" — ต้องรัน shadow mode ดู distribution จริง
     * ก่อนเปิด enforce ไม่งั้นเสี่ยงปิดปากลูกค้าที่จ่ายเงิน
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_nav_flood_guard')) {
                $table->boolean('enable_nav_flood_guard')->default(false)
                    ->comment('เปิดระบบเบรกกดปุ่มรัว (ปิดไว้ก่อน ต้อง shadow mode ก่อนเปิด)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_mode')) {
                $table->string('nav_flood_mode', 20)->default('log_only')
                    ->comment('log_only = คำนวณครบแต่ไม่บล็อกใคร | enforce = บังคับใช้จริง');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_repeat_max')) {
                $table->unsignedTinyInteger('nav_flood_repeat_max')->default(4)
                    ->comment('ปุ่มเดิมกดซ้ำกี่ครั้งถึงนับเป็นความผิด');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_repeat_window_sec')) {
                $table->unsignedSmallInteger('nav_flood_repeat_window_sec')->default(120)
                    ->comment('หน้าต่างเวลาของกฎปุ่มเดิม (วินาที)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_rate_max')) {
                $table->unsignedTinyInteger('nav_flood_rate_max')->default(15)
                    ->comment('ปุ่มใดก็ได้รวมกันกี่ครั้งถึงนับเป็นความผิด');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_rate_window_sec')) {
                $table->unsignedSmallInteger('nav_flood_rate_window_sec')->default(300)
                    ->comment('หน้าต่างเวลาของกฎรวม (วินาที)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_same_payload_lock_sec')) {
                $table->unsignedSmallInteger('nav_flood_same_payload_lock_sec')->default(25)
                    ->comment('เบรกเงียบ: ปุ่มเดิมกดซ้ำภายในกี่วินาที = ไม่ตอบ (ไม่นับความผิด)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_cooldown_minutes')) {
                $table->unsignedSmallInteger('nav_flood_cooldown_minutes')->default(5)
                    ->comment('เตือนครั้งแรกแล้วเงียบกี่นาที');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'nav_flood_ban_days')) {
                $table->unsignedTinyInteger('nav_flood_ban_days')->default(7)
                    ->comment('ระงับการใช้งานกี่วันเมื่อครบ 3 strikes');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'enable_nav_flood_guard',
                'nav_flood_mode',
                'nav_flood_repeat_max',
                'nav_flood_repeat_window_sec',
                'nav_flood_rate_max',
                'nav_flood_rate_window_sec',
                'nav_flood_same_payload_lock_sec',
                'nav_flood_cooldown_minutes',
                'nav_flood_ban_days',
            ] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
