<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ approval_mode ในตาราง sms_checker_devices
 *
 * โหมดการอนุมัติ:
 * - auto: อนุมัติอัตโนมัติเมื่อ SMS ตรงกับบิล
 * - manual: ต้องอนุมัติด้วยตัวเองทุกครั้ง
 * - smart: อนุมัติอัตโนมัติเมื่อ confidence สูง, manual เมื่อต่ำ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            $table->string('approval_mode', 10)->default('auto')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sms_checker_devices', function (Blueprint $table) {
            $table->dropColumn('approval_mode');
        });
    }
};
