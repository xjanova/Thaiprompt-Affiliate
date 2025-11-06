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
        Schema::create('mlm_packages', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name'); // Bronze, Silver, Gold, Diamond, Premier
            $table->string('name_th')->nullable();
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();
            $table->string('slug')->unique();

            // Pricing
            $table->decimal('price', 10, 2)->default(0); // ราคาแพคเกจ
            $table->decimal('pv_value', 10, 2)->default(0); // PV ที่ได้จากการซื้อแพคเกจนี้

            // Display Settings
            $table->string('color')->default('#4F46E5'); // สีของแพคเกจสำหรับ UI
            $table->string('icon')->nullable(); // ไอคอน
            $table->integer('sort_order')->default(0); // ลำดับการแสดง
            $table->string('badge')->nullable(); // เช่น "Popular", "Best Value"

            // Features & Benefits (JSON)
            $table->json('features')->nullable(); // ฟีเจอร์ที่ได้ เช่น ["ฟีเจอร์ 1", "ฟีเจอร์ 2"]
            $table->json('products')->nullable(); // สินค้าที่ได้รับ เช่น [{"product_id": 1, "quantity": 2}]
            $table->json('benefits')->nullable(); // สิทธิพิเศษ

            // Limits & Quotas
            $table->integer('max_downline')->nullable(); // จำนวนดาวน์ไลน์สูงสุด (null = unlimited)
            $table->integer('max_withdrawal_per_month')->nullable(); // จำนวนครั้งถอนเงินต่อเดือน
            $table->decimal('min_withdrawal_amount', 10, 2)->nullable(); // ยอดถอนขั้นต่ำ

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false); // แสดงว่าเป็นแพคเกจยอดนิยม
            $table->boolean('is_available_for_new_members')->default(true); // สมาชิกใหม่ซื้อได้หรือไม่

            // Upgrade System
            $table->boolean('can_upgrade')->default(true); // อัพเกรดได้หรือไม่
            $table->json('upgrade_options')->nullable(); // แพคเกจที่สามารถอัพเกรดได้ [2, 3, 4, 5]

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'sort_order']);
            $table->index('slug');
            $table->index('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_packages');
    }
};
