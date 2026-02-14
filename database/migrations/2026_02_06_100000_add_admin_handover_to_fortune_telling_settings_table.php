<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ Admin Handover สำหรับระบบดูดวง
     *
     * เมื่อแอดมินตอบข้อความ user ผ่าน Facebook Page Inbox
     * บอทจะหยุดทำงานชั่วคราวเพื่อให้แอดมินดูแลลูกค้าได้
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'admin_handover_enabled')) {
                $table->boolean('admin_handover_enabled')->default(true)
                    ->after('comment_engagement_prompt')
                    ->comment('เปิด/ปิดระบบ Admin Handover (บอทหยุดเมื่อแอดมินดูแล)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'admin_handover_timeout')) {
                $table->unsignedInteger('admin_handover_timeout')->default(15)
                    ->after('admin_handover_enabled')
                    ->comment('ระยะเวลา (นาที) ที่บอทหยุดทำงานหลังแอดมินตอบ');
            }
        });
    }

    /**
     * ลบคอลัมน์ Admin Handover
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'admin_handover_enabled')) {
                $table->dropColumn('admin_handover_enabled');
            }

            if (Schema::hasColumn('fortune_telling_settings', 'admin_handover_timeout')) {
                $table->dropColumn('admin_handover_timeout');
            }
        });
    }
};
