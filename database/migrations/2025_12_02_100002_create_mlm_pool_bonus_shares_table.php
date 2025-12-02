<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง mlm_pool_bonus_shares
 *
 * เก็บ shares ของสมาชิกแต่ละคนในแต่ละ period
 * - จำนวน shares ที่ได้ (ตาม rank หรือเงื่อนไข)
 * - จำนวนเงินที่ได้รับ
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('mlm_pool_bonus_shares')) {
            return;
        }

        Schema::create('mlm_pool_bonus_shares', function (Blueprint $table) {
            $table->id();

            // FK
            $table->foreignId('pool_period_id')
                ->constrained('mlm_pool_bonus_periods')
                ->onDelete('cascade');
            $table->foreignId('mlm_member_id')
                ->constrained('mlm_members')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Share details
            $table->integer('shares')->default(1);              // จำนวน shares (ตาม rank)
            $table->decimal('share_amount', 15, 2)->default(0); // จำนวนเงินต่อ share
            $table->decimal('total_amount', 15, 2)->default(0); // shares × share_amount

            // Qualification details
            $table->string('qualification_reason')->nullable(); // เหตุผลที่ qualify
            $table->integer('rank_id')->nullable();             // rank ที่ใช้คำนวณ
            $table->decimal('personal_pv', 15, 2)->default(0);  // PV ส่วนตัวในรอบนี้
            $table->decimal('team_pv', 15, 2)->default(0);      // PV ทีมในรอบนี้

            // Status
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->foreignId('mlm_commission_id')->nullable()
                ->constrained('mlm_commissions')
                ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['pool_period_id', 'status']);
            $table->index(['mlm_member_id', 'status']);
            $table->unique(['pool_period_id', 'mlm_member_id'], 'unique_member_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_pool_bonus_shares');
    }
};
