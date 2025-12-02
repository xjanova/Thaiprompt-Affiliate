<?php

/**
 * เพิ่มคอลัมน์ reset_period_value สำหรับระบบรีเซ็ตภารกิจ
 *
 * ใช้ร่วมกับ frequency เพื่อกำหนดว่าภารกิจรีเซ็ตทุกกี่หน่วย
 * เช่น frequency='daily' + reset_period_value=3 = ทุก 3 วัน
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ reset_period_value
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('video_missions', function (Blueprint $table) {
            // เพิ่มคอลัมน์ reset_period_value สำหรับกำหนดว่ารีเซ็ตทุกกี่หน่วย
            // เช่น reset_period_value=3 กับ frequency='daily' หมายถึง ทุก 3 วัน
            if (!Schema::hasColumn('video_missions', 'reset_period_value')) {
                $table->integer('reset_period_value')->default(1)->after('frequency')
                    ->comment('จำนวนหน่วยเวลาที่รีเซ็ต เช่น 3 = ทุก 3 วัน/สัปดาห์/เดือน');
            }
        });
    }

    /**
     * ลบคอลัมน์ reset_period_value
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('video_missions', function (Blueprint $table) {
            if (Schema::hasColumn('video_missions', 'reset_period_value')) {
                $table->dropColumn('reset_period_value');
            }
        });
    }
};
