<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตารางแพ็กเกจราคา SMS Payment Gateway
 * แอดมินกำหนดราคาค่าเช่าสำหรับร้านค้า
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateway_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // เช่น "แพ็กเกจ Basic"
            $table->text('description')->nullable();
            $table->enum('plan_type', ['monthly', 'yearly']);
            $table->decimal('price', 10, 2);           // ราคา (THB)
            $table->string('currency', 3)->default('THB');
            $table->integer('max_devices')->default(1); // จำนวนอุปกรณ์สูงสุด
            $table->integer('trial_days')->default(7);  // ทดลองใช้ฟรี
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('features_json')->nullable();  // รายละเอียดฟีเจอร์
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateway_pricing');
    }
};
