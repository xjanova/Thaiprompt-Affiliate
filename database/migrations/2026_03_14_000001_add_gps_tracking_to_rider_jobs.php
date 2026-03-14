<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ GPS Tracking ในตาราง rider_jobs และ fresh_market_settings
     *
     * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return
     * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
     *
     * @return void
     */
    public function up(): void
    {
        // เพิ่มคอลัมน์ GPS Tracking ในตาราง rider_jobs
        Schema::table('rider_jobs', function (Blueprint $table) {
            // token สำหรับ URL ติดตาม
            if (!Schema::hasColumn('rider_jobs', 'tracking_token')) {
                $table->string('tracking_token', 64)->unique()->nullable()->after('customer_review');
            }

            // เวลาหมดอายุของ tracking token
            if (!Schema::hasColumn('rider_jobs', 'tracking_expires_at')) {
                $table->timestamp('tracking_expires_at')->nullable()->after('tracking_token');
            }

            // GPS ไรเดอร์เปิดอยู่หรือไม่
            if (!Schema::hasColumn('rider_jobs', 'gps_active')) {
                $table->boolean('gps_active')->default(true)->after('tracking_expires_at');
            }

            // เวลาที่ GPS หาย
            if (!Schema::hasColumn('rider_jobs', 'gps_lost_at')) {
                $table->timestamp('gps_lost_at')->nullable()->after('gps_active');
            }

            // จำนวนครั้งที่เตือน GPS หาย
            if (!Schema::hasColumn('rider_jobs', 'gps_warning_count')) {
                $table->unsignedTinyInteger('gps_warning_count')->default(0)->after('gps_lost_at');
            }

            // LINE user ID ของผู้ซื้อ
            if (!Schema::hasColumn('rider_jobs', 'buyer_line_user_id')) {
                $table->string('buyer_line_user_id', 50)->nullable()->after('gps_warning_count');
            }
        });

        // เพิ่มคอลัมน์ GPS settings ในตาราง fresh_market_settings
        Schema::table('fresh_market_settings', function (Blueprint $table) {
            // ความถี่ในการอัปเดตตำแหน่ง GPS (วินาที)
            if (!Schema::hasColumn('fresh_market_settings', 'gps_update_interval_seconds')) {
                $table->unsignedInteger('gps_update_interval_seconds')->default(30)->after('ai_max_context_messages');
            }

            // ระยะเวลาที่ถือว่า GPS หาย (วินาที)
            if (!Schema::hasColumn('fresh_market_settings', 'gps_lost_timeout_seconds')) {
                $table->unsignedInteger('gps_lost_timeout_seconds')->default(120)->after('gps_update_interval_seconds');
            }

            // ระยะเวลาหมดอายุของลิงก์ติดตาม (ชั่วโมง)
            if (!Schema::hasColumn('fresh_market_settings', 'tracking_link_expiry_hours')) {
                $table->unsignedInteger('tracking_link_expiry_hours')->default(24)->after('gps_lost_timeout_seconds');
            }

            // จำนวนครั้งสูงสุดที่เตือน GPS หาย
            if (!Schema::hasColumn('fresh_market_settings', 'gps_warning_max')) {
                $table->unsignedTinyInteger('gps_warning_max')->default(3)->after('tracking_link_expiry_hours');
            }
        });
    }

    /**
     * ลบคอลัมน์ GPS Tracking ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('rider_jobs', function (Blueprint $table) {
            // ลบ unique index ก่อนลบคอลัมน์
            if (Schema::hasColumn('rider_jobs', 'tracking_token')) {
                $table->dropUnique(['tracking_token']);
            }
        });

        Schema::table('rider_jobs', function (Blueprint $table) {
            $columns = ['tracking_token', 'tracking_expires_at', 'gps_active', 'gps_lost_at', 'gps_warning_count', 'buyer_line_user_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('rider_jobs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('fresh_market_settings', function (Blueprint $table) {
            $columns = ['gps_update_interval_seconds', 'gps_lost_timeout_seconds', 'tracking_link_expiry_hours', 'gps_warning_max'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('fresh_market_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
