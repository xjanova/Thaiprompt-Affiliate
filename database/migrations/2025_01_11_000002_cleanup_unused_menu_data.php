<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ลบข้อมูลเมนูที่ไม่ได้ใช้แล้วออกจาก windows_ui_settings
     * เนื่องจากระบบเปลี่ยนมาใช้ hard-coded menus แล้ว
     */
    public function up(): void
    {
        // ลบข้อมูลเมนูทั้งหมดที่บันทึกใน database
        DB::table('windows_ui_settings')
            ->whereIn('key', [
                'windows_start_menu_items_admin',
                'windows_start_menu_items_user',
                'windows_start_menu_items_seller',
                'windows_start_menu_items',
            ])
            ->delete();

        // ลบข้อมูล taskbar apps และ system tray icons ที่อาจไม่ได้ใช้
        DB::table('windows_ui_settings')
            ->whereIn('key', [
                'windows_taskbar_apps',
                'windows_system_tray_icons',
            ])
            ->delete();

        \Log::info('Cleanup: Deleted unused menu data from windows_ui_settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ไม่สามารถ rollback ได้เพราะข้อมูลถูกลบไปแล้ว
        \Log::info('Cleanup rollback: No action (data was deleted)');
    }
};
