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
        // ตรวจสอบว่าตาราง commissions มีอยู่แล้วหรือยัง
        if (Schema::hasTable('commissions')) {
            return;
        }

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();

            // ⚠️ IMPORTANT: เช็คว่า affiliates table มีอยู่แล้วหรือยัง
            // ถ้ายังไม่มี → ใช้ unsignedBigInteger แทน foreignId
            if (Schema::hasTable('affiliates')) {
                $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            } else {
                // ถ้ายังไม่มี affiliates table → สร้างเป็น column ธรรมดาไปก่อน
                // Foreign key จะถูกเพิ่มโดย migration อื่นภายหลัง
                $table->unsignedBigInteger('affiliate_id');
                $table->index('affiliate_id');
            }

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('order_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('percentage', 5, 2);
            $table->enum('type', ['direct', 'indirect', 'bonus'])->default('direct');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
