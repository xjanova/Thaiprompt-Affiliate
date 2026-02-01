<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * บัญชีธนาคารรายร้าน สำหรับ SMS Payment Gateway
 * แต่ละร้านค้าสามารถตั้งบัญชีธนาคารของตัวเองได้
 * store_id = null หมายถึงบัญชีของ platform/admin
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateway_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable(); // null = admin/platform
            $table->string('bank_code', 20);         // KBANK, SCB, KTB, BBL, GSB, BAY, TTB
            $table->string('bank_name')->nullable();
            $table->string('account_name');
            $table->string('account_number', 50);
            $table->string('branch')->nullable();
            $table->string('promptpay_id', 20)->nullable();
            $table->enum('promptpay_type', ['phone', 'citizen_id', 'ewallet'])->nullable();
            $table->string('qr_image')->nullable();  // path to QR code image
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('store_id');
            $table->index(['bank_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateway_bank_accounts');
    }
};
