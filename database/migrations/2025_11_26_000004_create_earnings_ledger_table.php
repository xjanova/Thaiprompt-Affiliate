<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง earnings_ledger
     * บัญชีรายได้รอจ่าย - บันทึกทุกรายการรายได้ก่อนโอนเข้า wallet
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('earnings_ledger', function (Blueprint $table) {
            $table->id();

            // เจ้าของรายได้
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // ประเภทรายได้
            $table->enum('earning_type', [
                'seller_sale',          // รายได้จากการขายสินค้า
                'service_provider',     // รายได้จากการให้บริการ
                'mlm_commission',       // คอมมิชชัน MLM
                'affiliate_commission', // คอมมิชชัน Affiliate
                'referral_bonus',       // โบนัสแนะนำ
                'other'                 // อื่นๆ
            ]);

            // ที่มาของรายได้
            $table->string('source_type');      // Order, ServiceBooking, MlmCommission
            $table->unsignedBigInteger('source_id');

            // จำนวนเงิน
            $table->decimal('gross_amount', 20, 4);       // ยอดก่อนหัก
            $table->decimal('platform_fee', 20, 4)->default(0);     // ค่า Platform Fee
            $table->decimal('vat_amount', 20, 4)->default(0);       // ภาษี VAT
            $table->decimal('mlm_commission', 20, 4)->default(0);   // คอมมิชชัน MLM ที่หัก
            $table->decimal('other_deductions', 20, 4)->default(0); // ค่าใช้จ่ายอื่น
            $table->decimal('debt_deduction', 20, 4)->default(0);   // หักหนี้
            $table->decimal('net_amount', 20, 4);         // ยอดสุทธิที่จะได้รับ

            // สถานะ
            $table->enum('status', [
                'pending',      // รอดำเนินการ (อยู่ในช่วง holding)
                'available',    // พร้อมจ่าย
                'processing',   // กำลังประมวลผล
                'paid',         // จ่ายแล้ว
                'held',         // ถูกระงับ
                'cancelled'     // ยกเลิก
            ])->default('pending');

            // วันที่พร้อมจ่าย (หลังจาก holding period)
            $table->timestamp('available_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // การจ่ายเงิน (FK จะเพิ่มใน migration แยกหลัง payout_requests ถูกสร้าง)
            $table->unsignedBigInteger('payout_request_id')->nullable();

            // Wallet Transaction ที่เกี่ยวข้อง
            $table->foreignId('wallet_transaction_id')
                ->nullable()
                ->constrained('wallet_transactions')
                ->onDelete('set null');

            // รายละเอียด
            $table->string('description')->nullable();
            $table->json('breakdown')->nullable();  // รายละเอียดการคำนวณ
            /*
             * breakdown เก็บข้อมูล:
             * - items: [{ name, price, pv, qty }]
             * - calculations: { subtotal, fee_rate, vat_rate, etc. }
             */

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['earning_type', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index('available_at');
            $table->index('created_at');
        });
    }

    /**
     * ลบตาราง earnings_ledger
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('earnings_ledger');
    }
};
