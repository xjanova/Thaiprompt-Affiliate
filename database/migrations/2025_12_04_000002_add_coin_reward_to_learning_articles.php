<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มระบบรางวัล Coin และ Money สำหรับ Academy
 *
 * ระบบนี้เชื่อมต่อกับ VideoCoin wallet เดียวกับระบบดูวิดีโอรับรางวัล
 * รองรับทั้งรางวัลเงินสด (THB) และ Video Coins
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('learning_articles', function (Blueprint $table) {
            // รางวัล Video Coins เมื่อเรียนจบ
            if (! Schema::hasColumn('learning_articles', 'coin_reward')) {
                $table->decimal('coin_reward', 10, 2)->default(0)->after('points_reward')
                    ->comment('รางวัล Video Coins เมื่อเรียนจบคอร์ส');
            }

            // รางวัลเงินสด (THB) เมื่อเรียนจบ
            if (! Schema::hasColumn('learning_articles', 'money_reward')) {
                $table->decimal('money_reward', 10, 2)->default(0)->after('coin_reward')
                    ->comment('รางวัลเงินสด (THB) เมื่อเรียนจบคอร์ส');
            }

            // รางวัล EXP เมื่อเรียนจบ
            if (! Schema::hasColumn('learning_articles', 'exp_reward')) {
                $table->unsignedInteger('exp_reward')->default(0)->after('money_reward')
                    ->comment('รางวัล EXP เมื่อเรียนจบคอร์ส');
            }

            // PV (Point Value) สำหรับ MLM
            if (! Schema::hasColumn('learning_articles', 'pv_value')) {
                $table->decimal('pv_value', 10, 2)->default(0)->after('exp_reward')
                    ->comment('Point Value สำหรับระบบ MLM');
            }

            // งบประมาณสูงสุดสำหรับรางวัล (เหมือน video missions)
            if (! Schema::hasColumn('learning_articles', 'max_reward_budget')) {
                $table->decimal('max_reward_budget', 15, 2)->nullable()->after('pv_value')
                    ->comment('งบประมาณรางวัลสูงสุด (null = ไม่จำกัด)');
            }

            // ยอดรางวัลที่จ่ายไปแล้ว
            if (! Schema::hasColumn('learning_articles', 'total_rewards_given')) {
                $table->decimal('total_rewards_given', 15, 2)->default(0)->after('max_reward_budget')
                    ->comment('ยอดรางวัลรวมที่จ่ายไปแล้ว');
            }

            // วันที่งบประมาณหมด
            if (! Schema::hasColumn('learning_articles', 'budget_exhausted_at')) {
                $table->timestamp('budget_exhausted_at')->nullable()->after('total_rewards_given')
                    ->comment('วันที่งบประมาณหมด');
            }

            // Instructor ID (ครูผู้สอน)
            if (! Schema::hasColumn('learning_articles', 'instructor_id')) {
                $table->unsignedBigInteger('instructor_id')->nullable()->after('created_by')
                    ->comment('ID ครูผู้สอน (ถ้ามี)');
            }

            // ค่าคอมมิชชั่นครูผู้สอน (%)
            if (! Schema::hasColumn('learning_articles', 'instructor_commission')) {
                $table->decimal('instructor_commission', 5, 2)->default(0)->after('instructor_id')
                    ->comment('% คอมมิชชั่นครูผู้สอน');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_articles', function (Blueprint $table) {
            $columns = [
                'coin_reward', 'money_reward', 'exp_reward', 'pv_value',
                'max_reward_budget', 'total_rewards_given', 'budget_exhausted_at',
                'instructor_id', 'instructor_commission',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('learning_articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
