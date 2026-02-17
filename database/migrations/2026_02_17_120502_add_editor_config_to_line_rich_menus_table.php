<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ editor_config และ editor_mode ในตาราง line_rich_menus
     *
     * สำหรับระบบ Rich Menu Editor ที่แก้ไข config ได้ผ่าน Admin UI
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('line_rich_menus', function (Blueprint $table) {
            // เก็บ config ทั้งหมด (ปุ่ม, สี, theme) เป็น JSON
            if (! Schema::hasColumn('line_rich_menus', 'editor_config')) {
                $table->json('editor_config')->nullable()->after('areas')
                    ->comment('Config สำหรับ editor (theme, buttons, actions)');
            }

            // โหมดการสร้างภาพ: config = auto-generate, custom = อัปโหลดเอง
            if (! Schema::hasColumn('line_rich_menus', 'editor_mode')) {
                $table->string('editor_mode', 20)->default('config')->after('editor_config')
                    ->comment('โหมด editor: config หรือ custom');
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
        Schema::table('line_rich_menus', function (Blueprint $table) {
            if (Schema::hasColumn('line_rich_menus', 'editor_config')) {
                $table->dropColumn('editor_config');
            }
            if (Schema::hasColumn('line_rich_menus', 'editor_mode')) {
                $table->dropColumn('editor_mode');
            }
        });
    }
};
