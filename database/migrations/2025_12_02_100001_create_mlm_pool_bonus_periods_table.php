<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง mlm_pool_bonus_periods
 *
 * เก็บข้อมูลรอบการคำนวณ Pool Bonus (All Sale Bonus)
 * - เก็บยอดขายรวมในแต่ละรอบ
 * - เก็บจำนวน % ที่หักเข้า pool
 * - เก็บจำนวนสมาชิกที่ได้รับ
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('mlm_pool_bonus_periods')) {
            return;
        }

        Schema::create('mlm_pool_bonus_periods', function (Blueprint $table) {
            $table->id();

            // ช่วงเวลาของ period
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->date('period_start'); // วันเริ่มต้น
            $table->date('period_end');   // วันสิ้นสุด

            // ยอดรวมในรอบนี้
            $table->decimal('total_sales', 20, 2)->default(0);    // ยอดขายรวม
            $table->decimal('total_pv', 20, 2)->default(0);       // PV รวม
            $table->decimal('pool_percentage', 5, 2)->default(0); // % ที่หักเข้า pool
            $table->decimal('pool_amount', 20, 2)->default(0);    // จำนวนเงินใน pool

            // การกระจาย
            $table->integer('qualified_members_count')->default(0);  // จำนวนสมาชิกที่ qualify
            $table->decimal('amount_per_share', 15, 2)->default(0); // จำนวนต่อ share
            $table->integer('total_shares')->default(0);             // จำนวน shares ทั้งหมด

            // สถานะ
            $table->enum('status', ['pending', 'calculating', 'distributed', 'cancelled'])->default('pending');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('distributed_at')->nullable();
            $table->text('notes')->nullable();

            // JSON สำหรับเก็บรายละเอียดเพิ่มเติม
            $table->json('calculation_details')->nullable();
            $table->json('distribution_summary')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['period_type', 'status']);
            $table->index(['period_start', 'period_end']);
            $table->unique(['period_type', 'period_start'], 'unique_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_pool_bonus_periods');
    }
};
