<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ use_global_ai_settings
     *
     * ให้ระบบดูดวงสามารถใช้ AI Config จากระบบหลัก (AiContentSetting)
     * แทนที่จะตั้งค่าแยกเป็นของตัวเอง
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // เช็คว่าฟิลด์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('fortune_telling_settings', 'use_global_ai_settings')) {
                $table->boolean('use_global_ai_settings')
                    ->default(true)
                    ->after('is_enabled')
                    ->comment('ใช้ AI Config จากระบบหลัก (AiContentSetting)');
            }
        });
    }

    /**
     * ลบฟิลด์ use_global_ai_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'use_global_ai_settings')) {
                $table->dropColumn('use_global_ai_settings');
            }
        });
    }
};
