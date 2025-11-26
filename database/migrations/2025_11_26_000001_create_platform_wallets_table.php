<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง platform_wallets
     * กระเป๋าเงินของระบบ สำหรับเก็บรายได้ Platform
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('platform_wallets', function (Blueprint $table) {
            $table->id();

            // ประเภทกระเป๋า
            $table->string('name');  // ค่า Fee, ค่า VAT, MLM Pool, Reserve
            $table->string('slug')->unique();  // fee, vat, mlm_pool, reserve
            $table->enum('type', [
                'platform_fee',     // ค่าธรรมเนียม platform
                'vat',              // ภาษี VAT
                'mlm_pool',         // กองทุน MLM Commission
                'reserve',          // เงินสำรอง
                'refund_pool',      // กองทุนคืนเงิน
                'admin_shop',       // รายได้ร้านแอดมิน
                'admin_services',   // รายได้บริการแอดมิน
                'other'             // อื่นๆ
            ])->default('platform_fee');

            // ยอดเงิน
            $table->decimal('balance', 20, 4)->default(0);
            $table->decimal('total_income', 20, 4)->default(0);      // รายได้สะสม
            $table->decimal('total_expense', 20, 4)->default(0);     // รายจ่ายสะสม
            $table->decimal('total_transferred', 20, 4)->default(0); // โอนออกสะสม

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            // การตั้งค่า
            $table->json('settings')->nullable();  // การตั้งค่าเพิ่มเติม

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * ลบตาราง platform_wallets
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_wallets');
    }
};
