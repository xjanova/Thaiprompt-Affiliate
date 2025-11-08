<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default settings for floating tools
        $settings = [
            'floating_dark_mode_enabled' => true,
            'floating_scroll_top_enabled' => true,
            'floating_chatbot_enabled' => false,
            'floating_language_enabled' => false,
            'floating_fab_icon' => '⚙️',
            'floating_fab_position' => 'bottom-right',
            'floating_fab_color' => 'green',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'key' => $key,
                    'value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'floating_dark_mode_enabled',
            'floating_scroll_top_enabled',
            'floating_chatbot_enabled',
            'floating_language_enabled',
            'floating_fab_icon',
            'floating_fab_position',
            'floating_fab_color',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
