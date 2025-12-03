<?php

/**
 * เพิ่มคอลัมน์ max_reward_budget ในตาราง video_missions
 *
 * ระบบจำกัดงบประมาณรางวัลต่อภารกิจ - เมื่อรางวัลที่แจกไปถึง max
 * ภารกิจจะปิดอัตโนมัติ (คำนวณจาก ผู้ทำสำเร็จ × รางวัลต่อครั้ง)
 */

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ max_reward_budget
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('video_missions', function (Blueprint $table) {
            // งบประมาณรางวัลสูงสุด (เมื่อ total_rewards_given >= max_reward_budget ภารกิจจะปิด)
            $this->safeAddColumn($table, 'video_missions', 'max_reward_budget', function ($t) {
                $t->decimal('max_reward_budget', 15, 2)
                    ->nullable()
                    ->after('total_rewards_given')
                    ->comment('งบประมาณรางวัลสูงสุด (null = ไม่จำกัด)');
            });

            // เวลาที่งบประมาณหมด
            $this->safeAddColumn($table, 'video_missions', 'budget_exhausted_at', function ($t) {
                $t->timestamp('budget_exhausted_at')
                    ->nullable()
                    ->after('max_reward_budget')
                    ->comment('เวลาที่งบประมาณหมด');
            });

            // เปอร์เซ็นต์เตือนเมื่อใกล้หมดงบ (เช่น 80 = เตือนเมื่อใช้ไป 80%)
            $this->safeAddColumn($table, 'video_missions', 'budget_warning_threshold', function ($t) {
                $t->integer('budget_warning_threshold')
                    ->default(80)
                    ->after('budget_exhausted_at')
                    ->comment('เปอร์เซ็นต์เตือนเมื่อใกล้หมดงบ');
            });
        });

        // เพิ่ม index อย่างปลอดภัย (ใช้ SafeMigration trait)
        $this->safeAddIndex('video_missions', 'max_reward_budget');
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        // ลบ index ก่อน
        $this->safeDropIndex('video_missions', 'video_missions_max_reward_budget_index');

        // ลบคอลัมน์
        $this->safeDropColumn('video_missions', [
            'max_reward_budget',
            'budget_exhausted_at',
            'budget_warning_threshold',
        ]);
    }
};
