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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อของ API Key
            $table->string('key', 64)->unique(); // API Key (hash)
            $table->string('prefix', 16); // Prefix สำหรับแสดงผล (เช่น sk_live_...)
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // เจ้าของ API Key
            $table->text('description')->nullable(); // คำอธิบาย
            $table->boolean('is_active')->default(true); // สถานะการใช้งาน
            $table->json('allowed_endpoints')->nullable(); // API endpoints ที่อนุญาต (array of endpoint IDs)
            $table->json('allowed_ips')->nullable(); // IP addresses ที่อนุญาต
            $table->json('scopes')->nullable(); // สิทธิ์การเข้าถึง ['read', 'write', 'delete']
            $table->integer('rate_limit_per_minute')->nullable(); // จำกัดการใช้งานต่อนาที (override endpoint limit)
            $table->integer('rate_limit_per_hour')->nullable(); // จำกัดการใช้งานต่อชั่วโมง
            $table->integer('rate_limit_per_day')->nullable(); // จำกัดการใช้งานต่อวัน
            $table->integer('monthly_quota')->nullable(); // โควต้ารายเดือน
            $table->integer('monthly_usage')->default(0); // การใช้งานในเดือนนี้
            $table->timestamp('last_used_at')->nullable(); // ใช้งานครั้งล่าสุด
            $table->timestamp('expires_at')->nullable(); // วันหมดอายุ
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
