<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration resets all Start Menu items to use the hard-coded version
     * which includes 53 new menu items that were previously missing.
     */
    public function up(): void
    {
        // Delete old menu configurations from database
        // This forces the system to use the updated hard-coded menus
        DB::table('windows_ui_settings')
            ->whereIn('key', [
                'windows_start_menu_items_admin',
                'windows_start_menu_items_user',
                'windows_start_menu_items_seller',
            ])
            ->delete();

        // Log the reset
        \Log::info('Start Menu reset: Deleted database menu entries to use hard-coded menus');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot rollback - old menu data was deleted
        // Users can customize menus again through admin panel
        \Log::info('Start Menu reset rollback: No action taken (original menu data was deleted)');
    }
};
