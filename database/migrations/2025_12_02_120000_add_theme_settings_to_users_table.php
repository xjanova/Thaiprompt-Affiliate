<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม Theme Settings ให้ User
 *
 * สำหรับเก็บค่าตั้งค่าธีมของแต่ละ user
 * รวมถึง Arrow X mode (classic, modern, etc.)
 *
 * @version 3.296.0
 * @since 2025-12-02
 */
return new class extends Migration
{
    /**
     * เพิ่ม theme_settings column
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // เช็คว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('users', 'theme_settings')) {
                $table->json('theme_settings')->nullable()->after('menu_theme_preference')
                    ->comment('ค่าตั้งค่าธีมสำหรับ Arrow X (classic/modern mode)');
            }

            if (!Schema::hasColumn('users', 'arrow_x_mode')) {
                $table->string('arrow_x_mode', 20)->default('classic')->after('theme_settings')
                    ->comment('โหมด Arrow X: classic, modern, gaming');
            }
        });
    }

    /**
     * ลบ theme_settings column
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'theme_settings')) {
                $table->dropColumn('theme_settings');
            }

            if (Schema::hasColumn('users', 'arrow_x_mode')) {
                $table->dropColumn('arrow_x_mode');
            }
        });
    }
};
