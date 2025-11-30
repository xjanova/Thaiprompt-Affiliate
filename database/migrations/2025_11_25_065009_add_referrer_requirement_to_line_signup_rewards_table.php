<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ referrer_requirement ในตาราง line_signup_rewards
 *
 * เงื่อนไขผู้แนะนำสำหรับรางวัล:
 * - any: ไม่กำหนด (ทุกคนได้รับรางวัล)
 * - required: ต้องมีผู้แนะนำจึงจะได้รางวัล
 * - none: ไม่มีผู้แนะนำจึงจะได้รางวัล (สมัครเอง)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง line_signup_rewards มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('line_signup_rewards')) {
            return;
        }

        Schema::table('line_signup_rewards', function (Blueprint $table) {
            // เพิ่มคอลัมน์ referrer_requirement
            if (!Schema::hasColumn('line_signup_rewards', 'referrer_requirement')) {
                $table->enum('referrer_requirement', ['any', 'required', 'none'])
                    ->default('any')
                    ->comment('เงื่อนไขผู้แนะนำ: any=ไม่กำหนด, required=ต้องมี, none=ไม่มี');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('line_signup_rewards', function (Blueprint $table) {
            if (Schema::hasColumn('line_signup_rewards', 'referrer_requirement')) {
                $table->dropColumn('referrer_requirement');
            }
        });
    }
};
