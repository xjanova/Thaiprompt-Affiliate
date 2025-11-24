<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ลบการตั้งค่าที่ไม่ได้ใช้แล้วออกจากตาราง settings
     *
     * การตั้งค่าคอมมิชชั่นย้ายไปใช้ที่ MLM Global Settings แทน
     * การตั้งค่า API (Google Translate, TinyMCE) ใช้ config/env แทน
     *
     * @return void
     */
    public function up(): void
    {
        // รายการ keys ที่จะลบ
        $keysToRemove = [
            // Commission settings (ย้ายไป MLM Global Settings)
            'commission_rate',
            'multi_level_enabled',
            'commission_depth',
            'default_sponsor_referral_code',

            // API settings (ใช้ config/env แทน)
            'google_translate_enabled',
            'google_translate_api_key',
            'google_translate_project_id',
            'translate_source_language',
            'translate_cache_enabled',
            'tinymce_api_key',
        ];

        // ลบ settings ที่ไม่ใช้แล้ว
        DB::table('settings')
            ->whereIn('key', $keysToRemove)
            ->delete();
    }

    /**
     * ไม่สามารถ rollback ได้เพราะไม่ทราบค่าเดิม
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่สามารถ restore ข้อมูลได้เพราะไม่ทราบค่าเดิม
        // ถ้าต้องการกลับไปใช้ต้อง seed ข้อมูลใหม่
    }
};
