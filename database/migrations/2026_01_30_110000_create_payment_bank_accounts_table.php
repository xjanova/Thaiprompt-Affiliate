<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง payment_bank_accounts
     *
     * เก็บบัญชีธนาคารหลายบัญชีสำหรับรับชำระเงิน
     * รองรับทั้งโอนผ่านธนาคารและ PromptPay
     * เชื่อมกับ SMS Checker สำหรับยืนยันอัตโนมัติ
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('payment_bank_accounts')) {
            return;
        }

        Schema::create('payment_bank_accounts', function (Blueprint $table) {
            $table->id();

            // ข้อมูลธนาคาร
            $table->string('bank_code', 20)->comment('รหัสธนาคาร (KBANK, SCB, KTB, ...)');
            $table->string('bank_name')->comment('ชื่อธนาคาร');
            $table->string('account_number', 50)->comment('เลขที่บัญชี');
            $table->string('account_name')->comment('ชื่อบัญชี');
            $table->string('branch')->nullable()->comment('สาขา');

            // PromptPay (nullable - ไม่จำเป็นต้องมี)
            $table->string('promptpay_id', 50)->nullable()->comment('หมายเลข PromptPay (เบอร์โทร/บัตร ปชช.)');
            $table->enum('promptpay_type', ['phone', 'citizen_id', 'ewallet'])->nullable()->comment('ประเภท PromptPay');

            // QR Code
            $table->string('qr_image')->nullable()->comment('รูป QR Code สำหรับชำระเงิน (path)');

            // SMS Checker
            $table->boolean('sms_checker_enabled')->default(false)->comment('เปิดใช้งานยืนยันอัตโนมัติผ่าน SMS Checker');

            // สถานะ
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->boolean('is_default')->default(false)->comment('บัญชีหลัก (default)');
            $table->integer('sort_order')->default(0)->comment('ลำดับการแสดง');

            // หมายเหตุ
            $table->text('notes')->nullable()->comment('หมายเหตุสำหรับ admin');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('bank_code', 'pba_bank_code_idx');
            $table->index('is_active', 'pba_is_active_idx');
            $table->index('is_default', 'pba_is_default_idx');
        });
    }

    /**
     * ลบตาราง payment_bank_accounts
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_bank_accounts');
    }
};
