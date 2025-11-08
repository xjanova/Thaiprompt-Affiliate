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
        if (Schema::hasTable('installment_payments')) {
            return;
        }

        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained('installment_plans')->cascadeOnDelete();
            $table->string('payment_number')->unique();
            $table->integer('installment_number'); // งวดที่ (1, 2, 3, ...)
            $table->decimal('amount', 12, 2); // ยอดเงินที่ต้องชำระในงวดนี้
            $table->decimal('principal_amount', 12, 2)->default(0); // เงินต้น
            $table->decimal('interest_amount', 12, 2)->default(0); // ดอกเบี้ย
            $table->decimal('late_fee', 12, 2)->default(0); // ค่าปรับชำระช้า
            $table->decimal('paid_amount', 12, 2)->default(0); // ยอดที่ชำระจริง
            $table->date('due_date'); // วันครบกำหนดชำระ
            $table->timestamp('paid_at')->nullable(); // วันที่ชำระ
            $table->enum('status', [
                'pending',      // รอชำระ
                'paid',         // ชำระแล้ว
                'overdue',      // เกินกำหนด
                'partial',      // ชำระบางส่วน
                'cancelled',    // ยกเลิก
                'refunded'      // คืนเงิน
            ])->default('pending');
            $table->string('payment_method')->nullable(); // วิธีชำระเงิน
            $table->string('payment_reference')->nullable(); // หมายเลขอ้างอิง
            $table->text('payment_note')->nullable(); // หมายเหตุการชำระเงิน
            $table->string('receipt_url')->nullable(); // URL ใบเสร็จ
            $table->integer('days_overdue')->default(0); // จำนวนวันที่ค้างชำระ
            $table->timestamp('reminder_sent_at')->nullable(); // วันที่ส่งการแจ้งเตือน
            $table->timestamps();

            $table->index('installment_plan_id');
            $table->index('payment_number');
            $table->index('status');
            $table->index('due_date');
            $table->index(['installment_plan_id', 'installment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};
