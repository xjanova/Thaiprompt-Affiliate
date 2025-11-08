<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตารางสำหรับเก็บผู้มุ่งหวัง (Prospects) ที่กดลิงก์เชิญแล้ว
     */
    public function up(): void
    {
        Schema::create('mlm_prospects', function (Blueprint $table) {
            $table->id();

            // ผู้เชิญ (Sponsor)
            $table->foreignId('sponsor_mlm_member_id')->constrained('mlm_members')->onDelete('cascade');
            $table->foreignId('sponsor_user_id')->constrained('users')->onDelete('cascade');

            // ข้อมูล Prospect
            $table->string('line_user_id')->nullable(); // LINE User ID ของผู้ที่กดลิงก์
            $table->string('line_display_name')->nullable(); // ชื่อ LINE ของผู้ที่กดลิงก์
            $table->string('line_picture_url')->nullable(); // รูปโปรไฟล์ LINE

            // รหัสเชิญและการติดตาม
            $table->string('referral_token')->unique(); // Token พิเศษสำหรับลิงก์เชิญ
            $table->string('invitation_url')->nullable(); // URL ลิงก์เชิญ

            // สถานะ
            $table->enum('status', [
                'pending',      // รอการสมัคร
                'in_progress',  // กำลังสมัคร (กรอกข้อมูล)
                'completed',    // สมัครเสร็จแล้ว
                'expired',      // หมดอายุ
                'cancelled'     // ยกเลิก
            ])->default('pending');

            // ข้อมูลการล็อก
            $table->timestamp('clicked_at')->nullable(); // เวลาที่กดลิงก์
            $table->timestamp('locked_until')->nullable(); // ล็อกผู้เชิญจนถึง
            $table->boolean('is_locked')->default(true); // สถานะล็อก

            // ข้อมูลการสมัคร
            $table->foreignId('registered_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('registered_at')->nullable(); // เวลาที่สมัครสำเร็จ

            // ข้อมูลการติดตาม
            $table->integer('visit_count')->default(0); // จำนวนครั้งที่เข้าเยี่ยมชม
            $table->timestamp('last_visited_at')->nullable(); // เข้าเยี่ยมชมล่าสุด

            // Session และ Step ของการสนทนา
            $table->string('conversation_step')->nullable(); // Step ปัจจุบันในการสนทนา
            $table->json('conversation_data')->nullable(); // ข้อมูลการสนทนา

            // IP และข้อมูลเพิ่มเติม
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('utm_params')->nullable(); // UTM parameters สำหรับติดตาม

            // หมายเหตุ
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('line_user_id');
            $table->index('referral_token');
            $table->index('sponsor_mlm_member_id');
            $table->index(['status', 'locked_until']);
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_prospects');
    }
};
