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
        Schema::create('rank_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_rank_id')->nullable()->constrained('ranks')->onDelete('set null');
            $table->foreignId('to_rank_id')->constrained('ranks')->onDelete('cascade');

            // Promotion Type
            $table->enum('promotion_type', [
                'auto',      // ขึ้นอัตโนมัติ
                'manual',    // ขึ้นด้วยการอนุมัติ
                'purchase',  // ซื้อเพื่อขึ้น
                'admin'      // admin จัดการเอง
            ])->default('auto');

            // Status
            $table->enum('status', [
                'pending',   // รอการอนุมัติ
                'approved',  // อนุมัติแล้ว
                'rejected',  // ไม่อนุมัติ
                'active',    // ใช้งานอยู่
                'expired'    // หมดอายุ (สำหรับ purchase)
            ])->default('pending');

            // Purchase Info
            $table->decimal('purchase_amount', 10, 2)->nullable(); // ราคาที่ซื้อ
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();

            // Approval Info
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Rejection Info
            $table->text('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Requirements met snapshot
            $table->json('requirements_snapshot')->nullable(); // เก็บข้อมูล requirements ที่ผ่าน

            // Expiry (สำหรับ purchase type)
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['promotion_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_promotions');
    }
};
