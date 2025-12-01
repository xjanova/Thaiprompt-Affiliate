<?php

/**
 * เพิ่มคอลัมน์ max_reward_budget ในตาราง video_missions
 *
 * ระบบจำกัดงบประมาณรางวัลต่อภารกิจ - เมื่อรางวัลที่แจกไปถึง max
 * ภารกิจจะปิดอัตโนมัติ (คำนวณจาก ผู้ทำสำเร็จ × รางวัลต่อครั้ง)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ max_reward_budget
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('video_missions', function (Blueprint $table) {
            // ตรวจสอบว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('video_missions', 'max_reward_budget')) {
                // งบประมาณรางวัลสูงสุด (เมื่อ total_rewards_given >= max_reward_budget ภารกิจจะปิด)
                $table->decimal('max_reward_budget', 15, 2)
                    ->nullable()
                    ->after('total_rewards_given')
                    ->comment('งบประมาณรางวัลสูงสุด (null = ไม่จำกัด)');
            }

            if (!Schema::hasColumn('video_missions', 'budget_exhausted_at')) {
                // เวลาที่งบประมาณหมด
                $table->timestamp('budget_exhausted_at')
                    ->nullable()
                    ->after('max_reward_budget')
                    ->comment('เวลาที่งบประมาณหมด');
            }

            if (!Schema::hasColumn('video_missions', 'budget_warning_threshold')) {
                // เปอร์เซ็นต์เตือนเมื่อใกล้หมดงบ (เช่น 80 = เตือนเมื่อใช้ไป 80%)
                $table->integer('budget_warning_threshold')
                    ->default(80)
                    ->after('budget_exhausted_at')
                    ->comment('เปอร์เซ็นต์เตือนเมื่อใกล้หมดงบ');
            }
        });

        // เพิ่ม index
        if (Schema::hasColumn('video_missions', 'max_reward_budget')) {
            Schema::table('video_missions', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('video_missions');

                if (!isset($indexes['video_missions_max_reward_budget_index'])) {
                    $table->index('max_reward_budget');
                }
            });
        }
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('video_missions', function (Blueprint $table) {
            // ลบ index ก่อน
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('video_missions');

            if (isset($indexes['video_missions_max_reward_budget_index'])) {
                $table->dropIndex('video_missions_max_reward_budget_index');
            }

            // ลบคอลัมน์
            $columns = Schema::getColumnListing('video_missions');

            if (in_array('max_reward_budget', $columns)) {
                $table->dropColumn('max_reward_budget');
            }

            if (in_array('budget_exhausted_at', $columns)) {
                $table->dropColumn('budget_exhausted_at');
            }

            if (in_array('budget_warning_threshold', $columns)) {
                $table->dropColumn('budget_warning_threshold');
            }
        });
    }
};
