<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง subscription สำหรับ SMS Payment Gateway
 * ร้านค้าต้องสมัครสมาชิกเพื่อใช้งานระบบ SMS Payment Gateway
 * ร้านพรีเมียม / Official Shop ได้ฟรี (ไม่ต้องมี record)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateway_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id'); // store owner
            $table->unsignedBigInteger('pricing_id')->nullable(); // แพ็กเกจที่เลือก
            $table->enum('status', ['active', 'expired', 'trial', 'cancelled', 'pending_payment'])
                ->default('pending_payment');
            $table->enum('plan_type', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('THB');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->unsignedBigInteger('payment_transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->date('next_billing_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index('user_id');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateway_subscriptions');
    }
};
