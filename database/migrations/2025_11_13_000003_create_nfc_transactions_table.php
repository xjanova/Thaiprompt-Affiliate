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
        Schema::create('nfc_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // Transaction ID
            $table->foreignId('nfc_card_id')->constrained()->onDelete('cascade'); // บัตร NFC
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // ผู้ใช้
            $table->foreignId('nfc_reader_id')->nullable()->constrained()->onDelete('set null'); // เครื่องอ่านบัตร

            // Transaction Details
            $table->enum('type', ['payment', 'topup', 'refund', 'transfer', 'verification'])->default('payment'); // ประเภท
            $table->decimal('amount', 15, 2); // จำนวนเงิน
            $table->decimal('balance_before', 15, 2); // ยอดก่อนทำรายการ
            $table->decimal('balance_after', 15, 2); // ยอดหลังทำรายการ
            $table->string('currency', 3)->default('THB'); // สกุลเงิน

            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('status_message')->nullable(); // ข้อความสถานะ

            // Encryption Verification
            $table->text('card_data_hash'); // Hash ของข้อมูลที่อ่านจากบัตร
            $table->boolean('encryption_verified')->default(false); // ตรวจสอบการเข้ารหัสแล้ว
            $table->text('verification_signature')->nullable(); // Signature สำหรับตรวจสอบ
            $table->integer('verification_attempts')->default(0); // จำนวนครั้งที่พยายามตรวจสอบ

            // Related Transaction
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->onDelete('set null'); // เชื่อมกับ payment_transactions
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->onDelete('set null'); // เชื่อมกับ wallet_transactions
            $table->string('reference_id')->nullable(); // เลขที่อ้างอิง
            $table->string('receipt_number')->nullable()->unique(); // เลขที่ใบเสร็จ

            // Location & Device Info
            $table->string('location')->nullable(); // สถานที่ทำรายการ
            $table->ipAddress('ip_address')->nullable(); // IP Address
            $table->text('user_agent')->nullable(); // User Agent
            $table->json('device_info')->nullable(); // ข้อมูลอุปกรณ์

            // Additional Data
            $table->text('description')->nullable(); // คำอธิบาย
            $table->json('metadata')->nullable(); // ข้อมูลเพิ่มเติม
            $table->text('error_message')->nullable(); // ข้อความ Error
            $table->text('notes')->nullable(); // หมายเหตุ

            // Timestamps
            $table->timestamp('processed_at')->nullable(); // เวลาที่ประมวลผล
            $table->timestamp('completed_at')->nullable(); // เวลาที่เสร็จสิ้น
            $table->timestamp('failed_at')->nullable(); // เวลาที่ล้มเหลว
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('transaction_id');
            $table->index('nfc_card_id');
            $table->index('user_id');
            $table->index('nfc_reader_id');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
            $table->index(['nfc_card_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_transactions');
    }
};
