<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add language switcher settings to settings table
        $settings = [
            [
                'key' => 'language_switcher_style',
                'value' => 'dropdown',
                'type' => 'string',
                'group' => 'language',
            ],
            [
                'key' => 'language_switcher_show_flags',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'language',
            ],
            [
                'key' => 'language_switcher_flag_size',
                'value' => '24',
                'type' => 'integer',
                'group' => 'language',
            ],
            [
                'key' => 'language_switcher_show_name',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'language',
            ],
            [
                'key' => 'language_switcher_position',
                'value' => 'top-right',
                'type' => 'string',
                'group' => 'language',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'language_switcher_style',
            'language_switcher_show_flags',
            'language_switcher_flag_size',
            'language_switcher_show_name',
            'language_switcher_position',
        ])->delete();
    }
};
