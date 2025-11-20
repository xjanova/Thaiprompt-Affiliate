<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง affiliates
     * ⚠️ ต้องมีตาราง users ก่อน!
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่แล้วหรือยัง
        if (!Schema::hasTable('users')) {
            throw new \Exception('Table "users" must exist before creating "affiliates" table. Please run users migration first.');
        }

        // ตรวจสอบว่าตารางมีอยู่แล้วหรือยัง (safety check)
        if (Schema::hasTable('affiliates')) {
            return;
        }

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
