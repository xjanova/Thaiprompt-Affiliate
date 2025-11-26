<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง platform_transactions
     * บันทึกรายการเข้าออกของกระเป๋าเงิน Platform
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('platform_transactions', function (Blueprint $table) {
            $table->id();

            // กระเป๋าที่เกี่ยวข้อง
            $table->foreignId('platform_wallet_id')
                ->constrained('platform_wallets')
                ->onDelete('cascade');

            // ประเภทรายการ
            $table->enum('type', [
                'income',           // รายได้เข้า
                'expense',          // รายจ่ายออก
                'transfer_in',      // โอนเข้าจากกระเป๋าอื่น
                'transfer_out',     // โอนออกไปกระเป๋าอื่น
                'adjustment',       // ปรับยอด
                'refund'            // คืนเงิน
            ]);

            // ประเภทย่อย (รายละเอียด)
            $table->string('sub_type')->nullable();  // order_fee, mlm_commission, vat_collection, etc.

            // จำนวนเงิน
            $table->decimal('amount', 20, 4);
            $table->decimal('balance_before', 20, 4);
            $table->decimal('balance_after', 20, 4);

            // ที่มา/ปลายทาง
            $table->string('source_type')->nullable();  // Order, ServiceBooking, Refund, etc.
            $table->unsignedBigInteger('source_id')->nullable();

            // ผู้ที่เกี่ยวข้อง (Seller/User ที่เกี่ยวข้อง)
            $table->foreignId('related_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // รายละเอียด
            $table->string('description')->nullable();
            $table->string('reference_number')->nullable();  // เลขอ้างอิง

            // ข้อมูลเพิ่มเติมสำหรับ Analytics
            $table->json('metadata')->nullable();
            /*
             * metadata เก็บข้อมูล:
             * - order_id, order_number
             * - product_id, product_name
             * - seller_id, seller_name
             * - commission_rate
             * - vat_rate
             * - calculation_breakdown
             */

            // สถานะ
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');

            // ผู้ดำเนินการ (Admin ที่ทำรายการ manual)
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['platform_wallet_id', 'type']);
            $table->index(['source_type', 'source_id']);
            $table->index('related_user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('sub_type');
        });
    }

    /**
     * ลบตาราง platform_transactions
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_transactions');
    }
};
