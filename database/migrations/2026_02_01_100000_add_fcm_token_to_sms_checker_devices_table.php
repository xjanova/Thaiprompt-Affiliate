<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม fcm_token column สำหรับ Firebase Cloud Messaging
 * ใช้ส่ง push notification ไปยังอุปกรณ์ Android เมื่อมีคำสั่งซื้อใหม่
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_checker_devices', 'fcm_token')) {
                $table->text('fcm_token')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('sms_checker_devices', 'fcm_token_updated_at')) {
                $table->timestamp('fcm_token_updated_at')->nullable()->after('fcm_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            if (Schema::hasColumn('sms_checker_devices', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
            if (Schema::hasColumn('sms_checker_devices', 'fcm_token_updated_at')) {
                $table->dropColumn('fcm_token_updated_at');
            }
        });
    }
};
