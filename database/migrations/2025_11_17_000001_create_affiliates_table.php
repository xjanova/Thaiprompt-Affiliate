<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง affiliates
     * ⚠️ ต้องมีตาราง users ก่อน!
     *
     * หมายเหตุ: ถ้าตารางมีอยู่แล้ว จะลบแล้วสร้างใหม่
     * เพื่อป้องกันปัญหา foreign key และ structure ไม่ตรงกัน
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่แล้วหรือยัง
        if (!Schema::hasTable('users')) {
            throw new \Exception('Table "users" must exist before creating "affiliates" table. Please run users migration first.');
        }

        // ลบตารางเก่าถ้ามี (เพื่อสร้างใหม่ให้ถูกต้อง)
        // ⚠️ ข้อมูลจะหายไป! ใช้เฉพาะตอน fresh install หรือ development
        Schema::dropIfExists('affiliates');

        // สร้างตารางใหม่
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('affiliates')->onDelete('set null');
            $table->string('referral_code')->unique();
            $table->integer('level')->default(1);
            $table->integer('total_referrals')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();

            $table->index(['parent_id', 'level']);
            $table->index('referral_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
