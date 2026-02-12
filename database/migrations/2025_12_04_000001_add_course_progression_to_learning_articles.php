<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มระบบเลื่อนคลาสเรียนสำหรับ Learning Articles
 *
 * ระบบนี้ช่วยให้ผู้เรียนต้องผ่านแบบทดสอบก่อนเลื่อนไปคอร์สถัดไป
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('learning_articles', function (Blueprint $table) {
            // เพิ่มคอลัมน์ course_level สำหรับระบุระดับของคอร์ส
            if (!Schema::hasColumn('learning_articles', 'course_level')) {
                $table->unsignedInteger('course_level')->default(1)->after('difficulty')
                    ->comment('ระดับคอร์ส (1=เริ่มต้น, 2=กลาง, 3=สูง ฯลฯ)');
            }

            // เพิ่มคอลัมน์ require_quiz_pass - ต้องผ่าน quiz ก่อนเลื่อนคลาส
            if (!Schema::hasColumn('learning_articles', 'require_quiz_pass')) {
                $table->boolean('require_quiz_pass')->default(false)->after('course_level')
                    ->comment('ต้องผ่านแบบทดสอบก่อนเลื่อนคอร์สถัดไป');
            }

            // เพิ่มคอลัมน์ min_quiz_score - คะแนนขั้นต่ำที่ต้องได้
            if (!Schema::hasColumn('learning_articles', 'min_quiz_score')) {
                $table->unsignedInteger('min_quiz_score')->default(70)->after('require_quiz_pass')
                    ->comment('คะแนนขั้นต่ำ (%) ที่ต้องได้จากแบบทดสอบ');
            }

            // เพิ่มคอลัมน์ unlock_condition - เงื่อนไขการปลดล็อค
            if (!Schema::hasColumn('learning_articles', 'unlock_condition')) {
                $table->string('unlock_condition')->default('none')->after('min_quiz_score')
                    ->comment('เงื่อนไข: none, prerequisite, quiz_pass, time_based');
            }

            // เพิ่มคอลัมน์ unlock_after_days - ปลดล็อคหลังกี่วัน (ถ้าเลือก time_based)
            if (!Schema::hasColumn('learning_articles', 'unlock_after_days')) {
                $table->unsignedInteger('unlock_after_days')->nullable()->after('unlock_condition')
                    ->comment('จำนวนวันก่อนปลดล็อค (ใช้กับ time_based)');
            }

            // เพิ่มคอลัมน์ points_reward - คะแนนที่ได้เมื่อเรียนจบ
            if (!Schema::hasColumn('learning_articles', 'points_reward')) {
                $table->unsignedInteger('points_reward')->default(10)->after('unlock_after_days')
                    ->comment('คะแนนที่ได้รับเมื่อเรียนจบคอร์ส');
            }

            // เพิ่มคอลัมน์ badge_reward_id - รางวัล Badge (ถ้ามี)
            if (!Schema::hasColumn('learning_articles', 'badge_reward_id')) {
                $table->unsignedBigInteger('badge_reward_id')->nullable()->after('points_reward')
                    ->comment('ID ของ Badge ที่ได้รับเมื่อจบคอร์ส');
            }
        });

        // เพิ่ม index สำหรับ course_level
        Schema::table('learning_articles', function (Blueprint $table) {
            // เพิ่ม index ถ้ายังไม่มี
            $indexNames = collect(Schema::getIndexes('learning_articles'))->pluck('name')->toArray();

            if (!in_array('learning_articles_course_level_index', $indexNames)) {
                $table->index('course_level', 'learning_articles_course_level_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_articles', function (Blueprint $table) {
            // ลบ index ก่อน
            $indexNames = collect(Schema::getIndexes('learning_articles'))->pluck('name')->toArray();

            if (in_array('learning_articles_course_level_index', $indexNames)) {
                $table->dropIndex('learning_articles_course_level_index');
            }

            // ลบคอลัมน์
            $columns = ['course_level', 'require_quiz_pass', 'min_quiz_score',
                       'unlock_condition', 'unlock_after_days', 'points_reward', 'badge_reward_id'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('learning_articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
