<?php

/**
 * สร้างตาราง video_mission_rank_limits
 *
 * กำหนดจำนวนภารกิจที่แต่ละ Rank สามารถทำได้
 * Admin สามารถตั้งค่าได้ตาม Rank และประเภทความถี่
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_mission_rank_limits
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_mission_rank_limits')) {
            return;
        }

        Schema::create('video_mission_rank_limits', function (Blueprint $table) {
            $table->id();

            // Foreign key ไปยัง ranks
            $table->unsignedBigInteger('rank_id');

            // จำกัดจำนวนภารกิจ
            $table->integer('daily_mission_limit')->default(5);        // ภารกิจต่อวัน
            $table->integer('weekly_mission_limit')->default(20);      // ภารกิจต่อสัปดาห์
            $table->integer('monthly_mission_limit')->default(60);     // ภารกิจต่อเดือน

            // จำกัดรางวัล
            $table->decimal('daily_reward_limit', 12, 2)->nullable();  // รางวัลสูงสุดต่อวัน
            $table->decimal('weekly_reward_limit', 12, 2)->nullable(); // รางวัลสูงสุดต่อสัปดาห์
            $table->decimal('monthly_reward_limit', 12, 2)->nullable(); // รางวัลสูงสุดต่อเดือน

            // โบนัสตาม Rank
            $table->decimal('reward_multiplier', 5, 2)->default(1.00); // ตัวคูณรางวัล (1.0 = ปกติ, 1.5 = 50% เพิ่ม)
            $table->decimal('bonus_reward_percentage', 5, 2)->default(0); // % โบนัสเพิ่มเติม
            $table->integer('bonus_exp_percentage')->default(0);       // % EXP เพิ่มเติม

            // สิทธิพิเศษ
            $table->boolean('can_access_premium_missions')->default(false); // เข้าภารกิจ premium ได้
            $table->boolean('can_access_featured_missions')->default(true); // เข้าภารกิจแนะนำได้
            $table->boolean('skip_cooldown')->default(false);          // ข้าม cooldown ได้
            $table->integer('cooldown_reduction_percent')->default(0); // % ลด cooldown

            // ลำดับความสำคัญในคิว
            $table->integer('priority_in_queue')->default(0);          // ลำดับในคิว (สูง = ก่อน)

            // สถานะ
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Foreign key
            $table->foreign('rank_id')
                ->references('id')
                ->on('ranks')
                ->onDelete('cascade');

            // Unique constraint: หนึ่ง rank = หนึ่ง record
            $table->unique('rank_id');

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * ลบตาราง video_mission_rank_limits
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('video_mission_rank_limits');
    }
};
