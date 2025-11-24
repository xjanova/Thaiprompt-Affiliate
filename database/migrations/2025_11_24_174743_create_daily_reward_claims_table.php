<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง daily_reward_claims
     *
     * ตารางนี้เก็บประวัติการรับรางวัลประจำวันของผู้ใช้
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (ใช้ได้เฉพาะ CREATE TABLE)
        if (Schema::hasTable('daily_reward_claims')) {
            return;
        }

        Schema::create('daily_reward_claims', function (Blueprint $table) {
            $table->id();

            // Foreign key - ระบุชื่อตารางชัดเจน
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้ที่รับรางวัล');

            // คอลัมน์ข้อมูลรางวัล
            $table->date('claim_date')->comment('วันที่รับรางวัล');
            $table->integer('day_number')->comment('เลขวันที่ในรอบ (1-7)');
            $table->string('reward_type', 50)->comment('ประเภทรางวัล (points, coins, item)');
            $table->decimal('reward_amount', 10, 2)->default(0)->comment('จำนวนรางวัล');
            $table->string('reward_item')->nullable()->comment('ชื่อไอเทมรางวัล (ถ้ามี)');
            $table->boolean('bonus_applied')->default(false)->comment('ใช้โบนัสหรือไม่');

            $table->timestamps();

            // Indexes สำหรับการค้นหา
            $table->index('user_id', 'drc_user_id_idx');
            $table->index('claim_date', 'drc_claim_date_idx');
            $table->unique(['user_id', 'claim_date'], 'drc_user_date_unique');
        });
    }

    /**
     * ลบตาราง daily_reward_claims
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reward_claims');
    }
};
