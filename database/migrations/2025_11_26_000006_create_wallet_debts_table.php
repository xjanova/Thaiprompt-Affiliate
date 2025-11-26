<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง wallet_debts
     * ระบบหนี้/ติดลบ - เมื่อ refund แล้วผู้ขายถอนเงินไปแล้ว
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('wallet_debts', function (Blueprint $table) {
            $table->id();

            // ผู้ที่ติดหนี้
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // จำนวนหนี้
            $table->decimal('original_amount', 20, 4);        // หนี้เริ่มต้น
            $table->decimal('deducted_amount', 20, 4)->default(0);  // หักไปแล้ว
            $table->decimal('remaining_amount', 20, 4);       // หนี้คงเหลือ

            // ที่มาของหนี้
            $table->string('source_type');      // Refund, Adjustment, Chargeback
            $table->unsignedBigInteger('source_id');

            // เหตุผล
            $table->text('reason');
            $table->text('admin_note')->nullable();

            // สถานะ
            $table->enum('status', [
                'active',       // หนี้ยังคงค้าง รอหัก
                'paid',         // ชำระหนี้ครบแล้ว
                'waived',       // ยกเว้นหนี้ (Admin ยกเลิก)
                'cancelled'     // ยกเลิก
            ])->default('active');

            // การชำระหนี้
            $table->timestamp('fully_paid_at')->nullable();  // วันที่ชำระครบ

            // ผู้สร้างหนี้ (Admin)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // ผู้ยกเว้นหนี้
            $table->foreignId('waived_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('waived_at')->nullable();
            $table->text('waive_reason')->nullable();

            // ลำดับความสำคัญในการหัก (1 = หักก่อน)
            $table->integer('priority')->default(1);

            // ข้อมูลเพิ่มเติม
            $table->json('metadata')->nullable();
            /*
             * metadata เก็บข้อมูล:
             * - refund_order_id
             * - original_earning_id
             * - deduction_history: [{ date, amount, from_earning_id }]
             */

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index('status');
            $table->index('priority');
        });
    }

    /**
     * ลบตาราง wallet_debts
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_debts');
    }
};
