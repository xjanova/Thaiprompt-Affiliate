<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: เพิ่มฟิลด์ menu_theme_preference ให้ตาราง users
 *
 * ⚠️ NOTE: ฟิลด์นี้ถูกรวมใน create_users_comprehensive_v3 แล้ว
 * Migration นี้จะข้ามถ้าตาราง users ยังไม่มี หรือมีคอลัมน์อยู่แล้ว
 */
return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ menu_theme_preference
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
        if (!Schema::hasTable('users')) {
            // ตาราง users ยังไม่มี - ข้าม migration นี้
            // ฟิลด์นี้จะถูกสร้างใน users_comprehensive migration แทน
            return;
        }

        // ตรวจสอบว่ามีคอลัมน์อยู่แล้วหรือไม่
        if (!Schema::hasColumn('users', 'menu_theme_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('menu_theme_preference')->default('millennium')->after('preferred_language');
            });
        }
    }

    /**
     * ลบฟิลด์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'menu_theme_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('menu_theme_preference');
            });
        }
    }
};
