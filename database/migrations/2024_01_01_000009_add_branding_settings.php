<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default settings for branding
        $brandingSettings = [
            ['key' => 'logo', 'value' => '', 'type' => 'string', 'group' => 'branding'],
            ['key' => 'favicon', 'value' => '', 'type' => 'string', 'group' => 'branding'],
            ['key' => 'theme_primary_start', 'value' => '#3B82F6', 'type' => 'string', 'group' => 'theme'],
            ['key' => 'theme_primary_end', 'value' => '#1D4ED8', 'type' => 'string', 'group' => 'theme'],
            ['key' => 'theme_secondary_start', 'value' => '#10B981', 'type' => 'string', 'group' => 'theme'],
            ['key' => 'theme_secondary_end', 'value' => '#059669', 'type' => 'string', 'group' => 'theme'],
            ['key' => 'theme_accent_start', 'value' => '#8B5CF6', 'type' => 'string', 'group' => 'theme'],
            ['key' => 'theme_accent_end', 'value' => '#6D28D9', 'type' => 'string', 'group' => 'theme'],
        ];

        foreach ($brandingSettings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group']
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('group', ['branding', 'theme'])->delete();
    }
};
