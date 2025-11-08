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
        Schema::create('staking_positions', function (Blueprint $table) {
            $table->id();
            $table->string('position_number')->unique(); // รหัสตำแหน่งการลงทุน

            // ข้อมูลผู้ลงทุน
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investment_plan_id')->constrained()->cascadeOnDelete();

            // ข้อมูลการลงทุน
            $table->decimal('amount', 20, 2); // จำนวนเงินที่ลงทุน
            $table->decimal('roi_rate', 8, 4); // อัตราผลตอบแทน (เก็บไว้สำหรับอ้างอิง)
            $table->enum('roi_frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->integer('term_days'); // ระยะเวลาทั้งหมด
            $table->integer('lock_days'); // ระยะเวลาล็อค

            // การคำนวณ ROI
            $table->decimal('expected_roi', 20, 2)->default(0); // ผลตอบแทนที่คาดหวัง
            $table->decimal('earned_roi', 20, 2)->default(0); // ผลตอบแทนที่ได้รับแล้ว
            $table->decimal('pending_roi', 20, 2)->default(0); // ผลตอบแทนที่รอจ่าย
            $table->decimal('total_withdrawn', 20, 2)->default(0); // ยอดถอนทั้งหมด

            // วันที่สำคัญ
            $table->timestamp('invested_at')->nullable(); // วันที่ลงทุน
            $table->timestamp('unlock_date')->nullable(); // วันที่ปลดล็อค
            $table->timestamp('maturity_date')->nullable(); // วันครบกำหนด
            $table->timestamp('last_roi_distribution_at')->nullable(); // จ่าย ROI ครั้งล่าสุด
            $table->timestamp('withdrawn_at')->nullable(); // วันที่ถอนเงิน

            // สถานะ
            $table->enum('status', [
                'pending',      // รอการอนุมัติ
                'active',       // กำลังลงทุน
                'locked',       // ถูกล็อคอยู่
                'matured',      // ครบกำหนดแล้ว
                'withdrawn',    // ถอนเงินแล้ว
                'cancelled',    // ยกเลิก
                'early_withdrawn' // ถอนก่อนกำหนด
            ])->default('pending');

            // คุณสมบัติพิเศษ
            $table->boolean('auto_compound')->default(false); // รีลงทุนอัตโนมัติ
            $table->decimal('compound_amount', 20, 2)->default(0); // ยอด compound
            $table->integer('compound_count')->default(0); // จำนวนครั้งที่ compound

            // Referral & Bonus
            $table->foreignId('referrer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('referral_bonus', 20, 2)->default(0); // โบนัสแนะนำเพื่อน
            $table->decimal('rank_bonus_multiplier', 5, 2)->default(1.00); // ตัวคูณโบนัสตาม rank

            // การอนุมัติ
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();

            // Early Withdrawal
            $table->decimal('early_withdrawal_penalty', 20, 2)->default(0);
            $table->text('withdrawal_reason')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // ข้อมูลเพิ่มเติม
            $table->string('transaction_hash')->nullable(); // สำหรับ blockchain integration

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('wallet_id');
            $table->index('investment_plan_id');
            $table->index('status');
            $table->index('invested_at');
            $table->index('maturity_date');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staking_positions');
    }
};
