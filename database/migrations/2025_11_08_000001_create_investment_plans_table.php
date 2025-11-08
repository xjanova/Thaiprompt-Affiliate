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
        if (Schema::hasTable('investment_plans')) {
            return;
        }

        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อแผน เช่น "Gold Plan"
            $table->string('name_th')->nullable(); // ชื่อแผนภาษาไทย
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();

            // ข้อมูลการลงทุน
            $table->decimal('min_amount', 20, 2); // ยอดขั้นต่ำ
            $table->decimal('max_amount', 20, 2)->nullable(); // ยอดสูงสุด
            $table->decimal('roi_rate', 8, 4); // อัตราผลตอบแทน % (เช่น 1.5000 = 1.5%)
            $table->enum('roi_frequency', ['daily', 'weekly', 'monthly'])->default('daily'); // ความถี่ในการจ่าย ROI
            $table->integer('term_days'); // ระยะเวลาลงทุน (วัน)
            $table->integer('lock_days')->default(0); // ระยะเวลาล็อค (วัน)

            // คุณสมบัติพิเศษ
            $table->boolean('allow_compound')->default(false); // อนุญาตให้รีลงทุนอัตโนมัติ
            $table->boolean('allow_early_withdrawal')->default(false); // อนุญาตให้ถอนก่อนกำหนด
            $table->decimal('early_withdrawal_penalty', 5, 2)->default(0); // ค่าปรับถอนก่อนกำหนด %
            $table->decimal('referral_bonus_rate', 5, 2)->default(0); // โบนัสแนะนำเพื่อน %

            // การจัดการ
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('max_investors')->nullable(); // จำนวนนักลงทุนสูงสุด
            $table->integer('current_investors')->default(0); // จำนวนนักลงทุนปัจจุบัน
            $table->integer('sort_order')->default(0);

            // ข้อกำหนด
            $table->foreignId('required_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->boolean('requires_kyc')->default(true); // ต้องยืนยันตัวตน

            // รายละเอียดเพิ่มเติม
            $table->json('features')->nullable(); // คุณสมบัติเพิ่มเติม
            $table->json('terms_conditions')->nullable(); // เงื่อนไขข้อตกลง
            $table->string('icon')->nullable();
            $table->string('color')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('roi_rate');
            $table->index('term_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plans');
    }
};
