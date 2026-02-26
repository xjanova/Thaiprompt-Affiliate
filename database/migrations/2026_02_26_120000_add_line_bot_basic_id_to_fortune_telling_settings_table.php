<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม line_bot_basic_id สำหรับสร้าง LINE Add Friend URL
     *
     * line_channel_id = Messaging API Channel ID (ตัวเลข) → ใช้สำหรับ API
     * line_bot_basic_id = LINE OA Basic ID (เช่น @002dqcls) → ใช้สำหรับลิงก์เพิ่มเพื่อน
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'line_bot_basic_id')) {
                $table->string('line_bot_basic_id', 100)->nullable()
                    ->after('line_channel_id')
                    ->comment('LINE OA Basic ID เช่น @002dqcls สำหรับลิงก์เพิ่มเพื่อน');
            }
        });
    }

    /**
     * ลบ column line_bot_basic_id
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'line_bot_basic_id')) {
                $table->dropColumn('line_bot_basic_id');
            }
        });
    }
};
