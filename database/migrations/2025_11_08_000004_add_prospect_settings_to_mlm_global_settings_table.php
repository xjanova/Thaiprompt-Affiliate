<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่มการตั้งค่าสำหรับระบบผู้มุ่งหวัง (Prospects)
     */
    public function up(): void
    {
        Schema::table('mlm_global_settings', function (Blueprint $table) {
            // Prospect Lock Settings
            $table->integer('prospect_lock_duration_hours')->default(24)->after('binary_matching_percentage'); // อายุล็อก (ชั่วโมง) - default 1 วัน
            $table->boolean('enable_prospect_lock')->default(true)->after('prospect_lock_duration_hours'); // เปิด/ปิดระบบล็อก

            // Auto Add Friend Settings
            $table->boolean('enable_auto_add_sponsor_friend')->default(true)->after('enable_prospect_lock'); // เปิด/ปิดการเพิ่มเพื่อนอัตโนมัติ

            // LINE Signup Settings
            $table->boolean('enable_line_signup')->default(true)->after('enable_auto_add_sponsor_friend'); // เปิด/ปิดการสมัครผ่าน LINE
            $table->boolean('require_line_verification')->default(false)->after('enable_line_signup'); // บังคับยืนยัน LINE
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_global_settings', function (Blueprint $table) {
            $table->dropColumn([
                'prospect_lock_duration_hours',
                'enable_prospect_lock',
                'enable_auto_add_sponsor_friend',
                'enable_line_signup',
                'require_line_verification',
            ]);
        });
    }
};
