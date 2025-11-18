<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลบตารางที่เกี่ยวข้องกับระบบ Affiliate (เนื่องจากใช้ระบบ MLM แทน)
     *
     * ตารางที่ลบ:
     * - affiliates: ข้อมูล affiliate members
     * - commissions: ข้อมูล affiliate commissions
     *
     * หมายเหตุ: ระบบ MLM จะครอบคลุมฟังก์ชันทั้งหมดของระบบ Affiliate
     *
     * @return void
     */
    public function up(): void
    {
        // ลบตาราง commissions ก่อน (มี foreign key ไปยัง affiliates)
        Schema::dropIfExists('commissions');

        // ลบตาราง affiliates
        Schema::dropIfExists('affiliates');
    }

    /**
     * Reverse the migrations (สร้างตารางกลับ - ไม่แนะนำ เนื่องจากจะไม่มีข้อมูล)
     *
     * @return void
     */
    public function down(): void
    {
        // สร้างตาราง affiliates กลับ
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('affiliates')->onDelete('set null');
            $table->string('referral_code')->unique();
            $table->integer('level')->default(1);
            $table->integer('total_referrals')->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('monthly_sales', 15, 2)->default(0);
            $table->decimal('team_sales', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            // Binary tree fields
            $table->foreignId('binary_parent_id')->nullable()->constrained('affiliates')->onDelete('set null');
            $table->enum('binary_position', ['left', 'right'])->nullable();

            // Rank relationship
            $table->foreignId('rank_id')->nullable()->constrained('ranks')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
            $table->index('binary_parent_id');
            $table->index('referral_code');
            $table->index('status');
        });

        // สร้างตาราง commissions กลับ
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('order_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('type', ['direct', 'indirect', 'bonus'])->default('direct');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('affiliate_id');
            $table->index('user_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('type');
        });
    }
};
