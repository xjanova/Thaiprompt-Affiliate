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
        Schema::create('software_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('software_product_id')->nullable()->constrained('software_products')->nullOnDelete();

            // Contact Information
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('tax_id')->nullable();

            // Quotation Details
            $table->text('notes')->nullable(); // Customer notes/requirements
            $table->text('admin_notes')->nullable(); // Internal admin notes

            // Pricing
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(7); // VAT 7%
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('setup_total', 12, 2)->default(0); // Total setup fees
            $table->decimal('monthly_total', 12, 2)->default(0); // Total monthly fees

            // Status
            $table->enum('status', [
                'draft',           // ร่าง
                'pending',         // รอการตรวจสอบ
                'reviewed',        // ตรวจสอบแล้ว
                'sent',            // ส่งแล้ว
                'accepted',        // ลูกค้ายอมรับ
                'rejected',        // ลูกค้าปฏิเสธ
                'expired',         // หมดอายุ
                'converted'        // แปลงเป็นคำสั่งซื้อแล้ว
            ])->default('draft');

            // Dates
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('valid_until')->nullable(); // วันที่ใบเสนอราคาหมดอายุ

            // Conversion
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            // IP and User Agent for tracking
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('quotation_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_quotations');
    }
};
