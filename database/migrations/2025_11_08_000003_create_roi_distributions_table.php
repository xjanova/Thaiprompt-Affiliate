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
        if (Schema::hasTable('roi_distributions')) {
            return;
        }

        Schema::create('roi_distributions', function (Blueprint $table) {
            $table->id();
            $table->string('distribution_number')->unique(); // รหัสการจ่าย ROI

            // ข้อมูลการจ่าย
            $table->foreignId('staking_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();

            // จำนวนเงิน
            $table->decimal('roi_amount', 20, 2); // ยอด ROI ที่จ่าย
            $table->decimal('base_amount', 20, 2); // ยอดฐาน (ก่อนโบนัส)
            $table->decimal('bonus_amount', 20, 2)->default(0); // โบนัสเพิ่มเติม
            $table->decimal('rank_bonus', 20, 2)->default(0); // โบนัสจาก rank
            $table->decimal('roi_rate', 8, 4); // อัตราที่ใช้คำนวณ

            // ประเภทการจ่าย
            $table->enum('distribution_type', [
                'daily',        // จ่ายรายวัน
                'weekly',       // จ่ายรายสัปดาห์
                'monthly',      // จ่ายรายเดือน
                'maturity',     // จ่ายเมื่อครบกำหนด
                'compound',     // รีลงทุน
                'bonus'         // โบนัสพิเศษ
            ])->default('daily');

            // สถานะ
            $table->enum('status', [
                'pending',      // รอจ่าย
                'processing',   // กำลังจ่าย
                'completed',    // จ่ายแล้ว
                'failed',       // ล้มเหลว
                'cancelled'     // ยกเลิก
            ])->default('pending');

            // วันที่
            $table->date('distribution_date'); // วันที่จ่าย ROI
            $table->timestamp('processed_at')->nullable(); // ประมวลผลเมื่อ
            $table->timestamp('completed_at')->nullable(); // เสร็จสิ้นเมื่อ

            // ข้อมูลเพิ่มเติม
            $table->integer('day_number')->default(0); // วันที่เท่าไหร่ของการลงทุน
            $table->integer('total_days')->default(0); // ทั้งหมดกี่วัน
            $table->decimal('accumulated_roi', 20, 2)->default(0); // ROI สะสม

            // Error handling
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            // Auto-compound
            $table->boolean('is_compounded')->default(false);
            $table->foreignId('new_position_id')->nullable()->constrained('staking_positions')->nullOnDelete();

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('staking_position_id');
            $table->index('user_id');
            $table->index('distribution_date');
            $table->index('status');
            $table->index('distribution_type');
            $table->index(['user_id', 'distribution_date']);
            $table->index(['staking_position_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roi_distributions');
    }
};
