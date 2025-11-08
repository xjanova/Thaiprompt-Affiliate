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
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('software_quotation_id')->nullable()->constrained('software_quotations')->nullOnDelete();

            $table->string('plan_number')->unique();
            $table->decimal('total_amount', 12, 2); // ยอดรวมทั้งหมด
            $table->decimal('down_payment', 12, 2)->default(0); // เงินดาวน์
            $table->decimal('remaining_amount', 12, 2); // ยอดที่เหลือต้องผ่อน
            $table->integer('total_installments'); // จำนวนงวดทั้งหมด
            $table->integer('paid_installments')->default(0); // จำนวนงวดที่ชำระแล้ว
            $table->decimal('installment_amount', 12, 2); // ยอดเงินแต่ละงวด
            $table->decimal('interest_rate', 5, 2)->default(0); // อัตราดอกเบี้ย %
            $table->decimal('total_interest', 12, 2)->default(0); // ดอกเบี้ยรวม
            $table->decimal('total_paid', 12, 2)->default(0); // ยอดที่ชำระแล้วทั้งหมด
            $table->enum('status', [
                'pending',      // รอชำระเงินดาวน์
                'active',       // กำลังผ่อนชำระ
                'completed',    // ชำระครบแล้ว
                'overdue',      // ค้างชำระ
                'cancelled',    // ยกเลิก
                'defaulted'     // ผิดนัด
            ])->default('pending');
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('first_payment_date')->nullable(); // วันครบกำหนดชำระงวดแรก
            $table->date('last_payment_date')->nullable(); // วันครบกำหนดชำระงวดสุดท้าย
            $table->date('next_payment_date')->nullable(); // วันครบกำหนดชำระงวดถัดไป
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id');
            $table->index('user_id');
            $table->index('plan_number');
            $table->index('status');
            $table->index('next_payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
