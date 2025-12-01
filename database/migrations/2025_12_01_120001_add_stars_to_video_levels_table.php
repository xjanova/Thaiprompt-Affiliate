<?php

/**
 * เพิ่มคอลัมน์ดาว (stars) ในตาราง video_levels
 *
 * ระบบดาวแบบเกม - แต่ละ level จะมีจำนวนดาวไว้แสดงประดับใน profile
 * เช่น Level 1-3 = 1 ดาว, Level 4-6 = 2 ดาว, Level 7-9 = 3 ดาว
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ stars
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('video_levels', function (Blueprint $table) {
            // ตรวจสอบว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('video_levels', 'stars')) {
                // จำนวนดาว (1-5 ดาว)
                $table->integer('stars')
                    ->default(1)
                    ->after('icon')
                    ->comment('จำนวนดาว (1-5)');
            }

            if (!Schema::hasColumn('video_levels', 'star_color')) {
                // สีของดาว (bronze, silver, gold, platinum, diamond)
                $table->string('star_color', 20)
                    ->default('bronze')
                    ->after('stars')
                    ->comment('สีดาว (bronze/silver/gold/platinum/diamond)');
            }

            if (!Schema::hasColumn('video_levels', 'badge_image')) {
                // รูป badge สำหรับ level นี้
                $table->string('badge_image')
                    ->nullable()
                    ->after('star_color')
                    ->comment('รูป badge ของ level');
            }

            if (!Schema::hasColumn('video_levels', 'title_prefix')) {
                // คำนำหน้าชื่อ (เช่น "มือใหม่", "นักดู", "ผู้ชำนาญ")
                $table->string('title_prefix')
                    ->nullable()
                    ->after('badge_image')
                    ->comment('คำนำหน้าชื่อ เช่น มือใหม่');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('video_levels', function (Blueprint $table) {
            $columns = Schema::getColumnListing('video_levels');

            if (in_array('stars', $columns)) {
                $table->dropColumn('stars');
            }

            if (in_array('star_color', $columns)) {
                $table->dropColumn('star_color');
            }

            if (in_array('badge_image', $columns)) {
                $table->dropColumn('badge_image');
            }

            if (in_array('title_prefix', $columns)) {
                $table->dropColumn('title_prefix');
            }
        });
    }
};
