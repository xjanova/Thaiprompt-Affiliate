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
        // Insert OCR settings
        $settings = [
            [
                'key' => 'google_vision_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'ocr',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_vision_credentials_path',
                'value' => storage_path('app/google-credentials.json'),
                'type' => 'string',
                'group' => 'ocr',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_vision_project_id',
                'value' => '',
                'type' => 'string',
                'group' => 'ocr',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'google_vision_enabled',
            'google_vision_credentials_path',
            'google_vision_project_id',
        ])->delete();
    }
};
