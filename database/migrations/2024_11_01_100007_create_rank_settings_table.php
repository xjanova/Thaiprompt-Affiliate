<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rank_settings', function (Blueprint $table) {
            $table->id();

            // General Settings
            $table->boolean('enable_ranking_system')->default(true);
            $table->boolean('enable_auto_promotion')->default(true);
            $table->boolean('enable_manual_approval')->default(false);
            $table->boolean('enable_rank_purchase')->default(false);

            // Auto Promotion Settings
            $table->enum('promotion_check_frequency', ['realtime', 'hourly', 'daily', 'weekly'])->default('daily');
            $table->boolean('notify_on_eligible')->default(true); // แจ้งเตือนเมื่อมีสิทธิ์ขึ้น
            $table->boolean('notify_on_promotion')->default(true); // แจ้งเตือนเมื่อขึ้นระดับ

            // Manual Approval Settings
            $table->text('approval_admins')->nullable(); // JSON array of admin IDs
            $table->boolean('require_all_approvals')->default(false); // ต้องการอนุมัติจากทุกคนหรือไม่

            // Purchase Settings
            $table->boolean('allow_skip_requirements')->default(false); // ซื้อข้ามเงื่อนไขได้หรือไม่
            $table->integer('purchase_discount_percentage')->default(0); // ส่วนลด % สำหรับการซื้อ
            $table->boolean('purchase_requires_approval')->default(true);

            // Display Settings
            $table->boolean('show_leaderboard')->default(true);
            $table->boolean('show_rank_badges')->default(true);
            $table->boolean('show_progress_bar')->default(true);
            $table->boolean('show_team_ranks')->default(true);

            // Point System
            $table->integer('points_per_referral')->default(10); // คะแนนต่อการแนะนำ 1 คน
            $table->decimal('points_per_sale', 8, 2)->default(1.00); // คะแนนต่อยอดขาย 1 บาท
            $table->integer('points_per_active_month')->default(5); // คะแนนต่อเดือนที่ active

            // Bonus Calculation
            $table->enum('bonus_calculation_period', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->boolean('accumulate_bonuses')->default(true); // สะสมโบนัสหรือใช้แค่ระดับปัจจุบัน

            $table->timestamps();
        });

        // Insert default settings
        DB::table('rank_settings')->insert([
            'enable_ranking_system' => true,
            'enable_auto_promotion' => true,
            'enable_manual_approval' => false,
            'enable_rank_purchase' => false,
            'promotion_check_frequency' => 'daily',
            'notify_on_eligible' => true,
            'notify_on_promotion' => true,
            'show_leaderboard' => true,
            'show_rank_badges' => true,
            'show_progress_bar' => true,
            'show_team_ranks' => true,
            'points_per_referral' => 10,
            'points_per_sale' => 1.00,
            'points_per_active_month' => 5,
            'bonus_calculation_period' => 'monthly',
            'accumulate_bonuses' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_settings');
    }
};
