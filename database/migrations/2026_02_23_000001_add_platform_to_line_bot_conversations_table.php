<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ platform ในตาราง line_bot_conversations
     *
     * รองรับ conversation history หลาย platform (LINE, Facebook)
     * ใช้ร่วมกับ FortuneChannelManager เพื่อเก็บประวัติสนทนาข้าม platform
     *
     * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return
     * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('line_bot_conversations', function (Blueprint $table) {
            // เพิ่ม platform column (line, facebook, web, etc.)
            if (! Schema::hasColumn('line_bot_conversations', 'platform')) {
                $table->string('platform', 20)->default('line')->after('line_user_id');
            }
        });

        // เพิ่ม composite index สำหรับค้นหา conversation ตาม platform + user + status
        if (Schema::hasColumn('line_bot_conversations', 'platform')) {
            try {
                Schema::table('line_bot_conversations', function (Blueprint $table) {
                    $table->index(
                        ['platform', 'line_user_id', 'status'],
                        'conv_platform_user_status_idx'
                    );
                });
            } catch (\Exception $e) {
                // Index อาจมีอยู่แล้ว — ข้ามไป
            }
        }
    }

    /**
     * ลบคอลัมน์ platform ออก
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('line_bot_conversations', function (Blueprint $table) {
            // ลบ index ก่อน
            try {
                $table->dropIndex('conv_platform_user_status_idx');
            } catch (\Exception $e) {
                // Index อาจไม่มี — ข้ามไป
            }

            // ลบคอลัมน์
            if (Schema::hasColumn('line_bot_conversations', 'platform')) {
                $table->dropColumn('platform');
            }
        });
    }
};
