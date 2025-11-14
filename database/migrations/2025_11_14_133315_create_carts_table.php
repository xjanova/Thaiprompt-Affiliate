<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง carts
     *
     * ตะกร้าสินค้าหลักสำหรับผู้ใช้
     * รองรับระบบเติมเงิน wallet และฟีเจอร์อื่นๆ
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('carts')) {
            return;
        }

        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // Foreign key สำหรับผู้ใช้
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้เจ้าของตะกร้า');

            // Session ID สำหรับผู้ใช้ที่ยังไม่ได้ล็อกอิน (optional)
            $table->string('session_id', 100)
                ->nullable()
                ->index()
                ->comment('Session ID สำหรับ guest user');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('created_at');
            $table->unique(['user_id', 'deleted_at'], 'carts_user_unique');
        });
    }

    /**
     * ลบตาราง carts
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
