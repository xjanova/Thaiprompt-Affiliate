<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง line_signup_reward_claims
     *
     * บันทึกประวัติการรับรางวัลของผู้ใช้
     * ใช้ติดตามว่าใครได้รับรางวัลอะไรไปแล้วบ้าง
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('line_signup_reward_claims')) {
            return;
        }

        Schema::create('line_signup_reward_claims', function (Blueprint $table) {
            $table->id();

            // ผู้รับรางวัล
            $table->foreignId('user_id')
                ->constrained('users')->onDelete('cascade')
                ->comment('ผู้ใช้ที่รับรางวัล');

            // รางวัลที่รับ
            $table->foreignId('reward_id')
                ->constrained('line_signup_rewards')->onDelete('cascade')
                ->comment('รางวัลที่รับ');

            // Session การสมัคร
            $table->foreignId('signup_session_id')->nullable()
                ->constrained('line_signup_sessions')->onDelete('set null')
                ->comment('Session การสมัคร LINE');

            // รายละเอียดรางวัลที่รับ (snapshot)
            $table->enum('reward_type', [
                'wallet_points', 'tpix_tokens', 'coupon', 'rank_points',
                'special_benefit', 'bonus_item', 'free_downlines', 'experience_points'
            ])->comment('ประเภทรางวัล');
            $table->decimal('amount', 20, 2)->default(0)->comment('จำนวน');
            $table->json('reward_data')->nullable()->comment('ข้อมูลรางวัลแบบเต็ม (JSON)');

            // สถานะ
            $table->enum('status', ['pending', 'claimed', 'failed', 'expired'])
                ->default('pending')
                ->comment('สถานะ: pending=รอดำเนินการ, claimed=รับแล้ว, failed=ล้มเหลว, expired=หมดอายุ');
            $table->timestamp('claimed_at')->nullable()->comment('วันที่รับรางวัล');
            $table->timestamp('expires_at')->nullable()->comment('วันหมดอายุ (สำหรับบางประเภท)');

            // ข้อมูลเพิ่มเติม
            $table->text('notes')->nullable()->comment('หมายเหตุ');
            $table->text('error_message')->nullable()->comment('ข้อความ error (ถ้า failed)');

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('reward_id');
            $table->index('status');
            $table->index('claimed_at');
        });
    }

    /**
     * ลบตาราง line_signup_reward_claims
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_reward_claims');
    }
};
