<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สำหรับการตั้งค่าดาวน์โหลดแอป TP Ultra
     *
     * คอลัมน์ที่เพิ่ม:
     * - app_download_enabled: เปิด/ปิด section ดาวน์โหลดแอป
     * - app_name: ชื่อแอป (เช่น TP Ultra)
     * - app_description: คำอธิบายแอป
     * - app_icon: ไอคอนแอป (path)
     * - app_apk_url: ลิงก์ดาวน์โหลด APK โดยตรง
     * - app_apk_enabled: เปิด/ปิด ดาวน์โหลด APK
     * - app_playstore_url: ลิงก์ Play Store
     * - app_playstore_enabled: เปิด/ปิด Play Store
     * - app_appstore_url: ลิงก์ App Store
     * - app_appstore_enabled: เปิด/ปิด App Store
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // ตรวจสอบและเพิ่มคอลัมน์สำหรับดาวน์โหลดแอป
            if (!Schema::hasColumn('site_settings', 'app_download_enabled')) {
                $table->boolean('app_download_enabled')->default(true)->after('footer_scripts');
            }

            if (!Schema::hasColumn('site_settings', 'app_name')) {
                $table->string('app_name')->default('TP Ultra')->after('app_download_enabled');
            }

            if (!Schema::hasColumn('site_settings', 'app_description')) {
                $table->text('app_description')->nullable()->after('app_name');
            }

            if (!Schema::hasColumn('site_settings', 'app_icon')) {
                $table->string('app_icon')->nullable()->after('app_description');
            }

            if (!Schema::hasColumn('site_settings', 'app_apk_url')) {
                $table->string('app_apk_url', 500)->nullable()->after('app_icon');
            }

            if (!Schema::hasColumn('site_settings', 'app_apk_enabled')) {
                $table->boolean('app_apk_enabled')->default(true)->after('app_apk_url');
            }

            if (!Schema::hasColumn('site_settings', 'app_playstore_url')) {
                $table->string('app_playstore_url', 500)->nullable()->after('app_apk_enabled');
            }

            if (!Schema::hasColumn('site_settings', 'app_playstore_enabled')) {
                $table->boolean('app_playstore_enabled')->default(false)->after('app_playstore_url');
            }

            if (!Schema::hasColumn('site_settings', 'app_appstore_url')) {
                $table->string('app_appstore_url', 500)->nullable()->after('app_playstore_enabled');
            }

            if (!Schema::hasColumn('site_settings', 'app_appstore_enabled')) {
                $table->boolean('app_appstore_enabled')->default(false)->after('app_appstore_url');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $columns = [
                'app_download_enabled',
                'app_name',
                'app_description',
                'app_icon',
                'app_apk_url',
                'app_apk_enabled',
                'app_playstore_url',
                'app_playstore_enabled',
                'app_appstore_url',
                'app_appstore_enabled',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
