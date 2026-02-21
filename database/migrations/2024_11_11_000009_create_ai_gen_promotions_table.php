<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_gen_promotions สำหรับโปรโมชั่น AI Gen
     *
     * รองรับ:
     * - ส่วนลดเป็น % หรือจำนวนเงิน
     * - เครดิตฟรี / เครดิตโบนัส
     * - โค้ดโปรโมชั่น
     * - จำกัดจำนวนการใช้
     * - กำหนดระยะเวลา
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_gen_promotions')) {
            return;
        }

        Schema::create('ai_gen_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // ประเภทโปรโมชั่น
            $table->enum('type', [
                'discount_percent',   // ส่วนลด %
                'discount_fixed',     // ส่วนลดจำนวนเงิน
                'free_credits',       // เครดิตฟรี
                'bonus_credits',      // เครดิตโบนัส (ซื้อได้เพิ่ม)
            ])->default('discount_percent');

            $table->decimal('value', 10, 2)->default(0)
                ->comment('ค่าโปรโมชั่น: % หรือจำนวนเงิน/เครดิต');

            // โค้ดโปรโมชั่น (ถ้ามี)
            $table->string('code', 50)->nullable()->unique()
                ->comment('โค้ดโปรโมชั่น เช่น AIGEN50');

            // ใช้กับอะไรบ้าง
            $table->enum('applies_to', ['all', 'image', 'video'])->default('all');

            // จำกัดจำนวนการใช้
            $table->integer('max_uses')->nullable()
                ->comment('จำนวนครั้งสูงสุดที่ใช้ได้ (null = ไม่จำกัด)');
            $table->integer('max_uses_per_user')->nullable()
                ->comment('จำนวนครั้งสูงสุดต่อผู้ใช้ (null = ไม่จำกัด)');
            $table->integer('used_count')->default(0);

            // ระยะเวลา
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // เงื่อนไขเพิ่มเติม
            $table->decimal('min_wallet_balance', 10, 2)->nullable()
                ->comment('ยอด wallet ขั้นต่ำที่ต้องมี');

            // Provider เฉพาะ (ถ้ากำหนด)
            $table->foreignId('provider_id')->nullable()
                ->constrained('ai_gen_providers')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('code', 'promo_code_idx');
            $table->index(['is_active', 'starts_at', 'expires_at'], 'promo_active_period_idx');
        });
    }

    /**
     * ลบตาราง ai_gen_promotions
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_promotions');
    }
};
