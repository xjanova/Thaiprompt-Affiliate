<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ smschecker_app_download_url ในตาราง site_settings
     *
     * แอดมินสามารถตั้งค่า URL ดาวน์โหลดแอป SMS Checker ได้
     * แทนที่จะ hardcode GitHub link ในโค้ด
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'smschecker_app_download_url')) {
                $table->string('smschecker_app_download_url', 500)
                    ->nullable()
                    ->after('app_appstore_enabled')
                    ->comment('URL ดาวน์โหลดแอป SMS Checker สำหรับ Seller');
            }
        });
    }

    /**
     * ลบคอลัมน์ smschecker_app_download_url
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'smschecker_app_download_url')) {
                $table->dropColumn('smschecker_app_download_url');
            }
        });
    }
};
