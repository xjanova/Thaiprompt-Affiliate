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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shipping_address_id')->nullable()->constrained('shipping_addresses')->onDelete('set null');

            // Order Status
            $table->enum('status', [
                'pending',           // รอชำระเงิน
                'paid',             // ชำระเงินแล้ว
                'processing',       // กำลังเตรียมสินค้า
                'shipped',          // จัดส่งแล้ว
                'delivered',        // ส่งถึงแล้ว
                'completed',        // สำเร็จ
                'cancelled',        // ยกเลิก
                'refunded'          // คืนเงิน
            ])->default('pending');

            // Pricing
            $table->decimal('subtotal', 10, 2);                    // ยอดรวมสินค้า
            $table->decimal('discount_amount', 10, 2)->default(0); // ส่วนลด
            $table->decimal('shipping_fee', 10, 2)->default(0);    // ค่าจัดส่ง
            $table->decimal('tax_amount', 10, 2)->default(0);      // ภาษี
            $table->decimal('total_amount', 10, 2);                // ยอดรวมทั้งหมด

            // Commission & Earnings
            $table->decimal('platform_commission', 10, 2)->default(0);
            $table->decimal('seller_earning', 10, 2)->default(0);

            // Payment Information
            $table->enum('payment_method', ['promptpay', 'bank_transfer', 'credit_card', 'cod'])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();

            // Shipping Information
            $table->string('shipping_provider')->nullable();       // e.g., Kerry, Flash, ThaiPost
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Stored Shipping Address (in case address is deleted)
            $table->json('shipping_address_snapshot')->nullable();

            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['order_number']);
            $table->index(['status', 'created_at']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
