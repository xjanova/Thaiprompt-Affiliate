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
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อ API endpoint
            $table->string('path'); // เส้นทาง เช่น /api/v1/users
            $table->string('method')->default('GET'); // HTTP method (GET, POST, PUT, DELETE, etc.)
            $table->string('category')->nullable(); // หมวดหมู่ เช่น Users, Products, Analytics
            $table->text('description')->nullable(); // อธิบายว่า API นี้ใช้ทำอะไร
            $table->text('parameters')->nullable(); // JSON ของ parameters ที่รับ
            $table->text('response_example')->nullable(); // ตัวอย่าง response
            $table->boolean('is_active')->default(true); // เปิด/ปิดการใช้งาน
            $table->boolean('requires_auth')->default(true); // ต้องการ authentication หรือไม่
            $table->integer('rate_limit_per_minute')->default(60); // จำนวนครั้งต่อนาที
            $table->integer('rate_limit_per_hour')->default(1000); // จำนวนครั้งต่อชั่วโมง
            $table->integer('rate_limit_per_day')->default(10000); // จำนวนครั้งต่อวัน
            $table->json('allowed_roles')->nullable(); // บทบาทที่สามารถเข้าถึงได้ ['admin', 'user', 'seller']
            $table->integer('priority')->default(0); // ลำดับความสำคัญ (ใช้สำหรับการแสดงผล)
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('path');
            $table->index('method');
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};
