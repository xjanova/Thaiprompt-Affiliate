<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง payout_requests
     * คำขอเบิกเงิน / รายการจ่ายเงิน
     */
    public function up(): void
    {
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();

            // ผู้ขอเบิก
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // ประเภทการจ่าย
            $table->enum('payout_type', [
                'seller',           // จ่ายให้ผู้ขาย
                'mlm',              // จ่าย MLM Commission
                'service',          // จ่ายให้ผู้ให้บริการ
                'withdrawal',       // ถอนเงินจาก wallet
            ]);

            // จำนวนเงิน
            $table->decimal('amount', 20, 4);
            $table->decimal('fee', 20, 4)->default(0);        // ค่าธรรมเนียม
            $table->decimal('net_amount', 20, 4);              // ยอดสุทธิที่จะได้รับ

            // สถานะ
            $table->enum('status', [
                'pending',          // รอดำเนินการ
                'approved',         // อนุมัติแล้ว รอจ่าย
                'processing',       // กำลังดำเนินการจ่าย
                'paid',             // จ่ายแล้ว
                'rejected',         // ปฏิเสธ
                'cancelled',        // ยกเลิก
                'failed',            // จ่ายไม่สำเร็จ
            ])->default('pending');

            // วิธีการจ่าย
            $table->enum('payment_method', [
                'wallet',           // โอนเข้า wallet
                'bank_transfer',    // โอนเงินผ่านธนาคาร
                'promptpay',        // พร้อมเพย์
                'other',             // อื่นๆ
            ])->default('wallet');

            // ข้อมูลบัญชีธนาคาร (ถ้าโอนผ่านธนาคาร)
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // การอนุมัติ
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();

            // การปฏิเสธ
            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // การจ่ายเงิน
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();  // เลขอ้างอิงการโอน
            $table->text('payment_note')->nullable();

            // Wallet Transaction ที่เกี่ยวข้อง
            $table->foreignId('wallet_transaction_id')
                ->nullable()
                ->constrained('wallet_transactions')
                ->onDelete('set null');

            // รายละเอียด
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            /*
             * metadata เก็บข้อมูล:
             * - earning_ids: [1, 2, 3]
             * - source_orders: [...]
             * - calculation_summary
             */

            // Batch processing (สำหรับ auto payout)
            $table->string('batch_id')->nullable();  // รหัส batch ถ้าจ่ายเป็นกลุ่ม

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['payout_type', 'status']);
            $table->index('batch_id');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง payout_requests
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};
