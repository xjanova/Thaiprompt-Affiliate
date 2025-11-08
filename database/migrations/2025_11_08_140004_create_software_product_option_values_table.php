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
        if (Schema::hasTable('software_product_option_values')) {
            return;
        }

        Schema::create('software_product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_product_option_id')->constrained('software_product_options')->cascadeOnDelete();
            $table->string('value'); // ค่าของ option เช่น "10 users", "Payment Module"
            $table->string('display_label'); // Label ที่แสดงให้ลูกค้า
            $table->text('description')->nullable();
            $table->decimal('price_modifier', 12, 2)->default(0); // ราคาที่เพิ่ม/ลด
            $table->enum('price_type', ['fixed', 'percentage'])->default('fixed'); // ประเภทราคา
            $table->decimal('setup_fee', 12, 2)->default(0); // ค่าติดตั้งครั้งแรก
            $table->decimal('monthly_fee', 12, 2)->default(0); // ค่าบริการรายเดือน (ถ้ามี)
            $table->string('icon')->nullable();
            $table->string('image_url')->nullable();
            $table->json('additional_info')->nullable(); // ข้อมูลเพิ่มเติม
            $table->boolean('is_default')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('software_product_option_id');
            $table->index(['software_product_option_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_product_option_values');
    }
};
