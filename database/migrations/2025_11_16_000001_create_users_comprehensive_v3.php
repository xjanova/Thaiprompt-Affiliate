<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: สร้างตาราง users แบบครบวงจร (Version 3)
 *
 * รวม columns ทั้งหมดที่กระจัดกระจายจาก migrations เก่า 25+ ไฟล์
 * เข้ามาเป็น 1 ไฟล์เดียว เพื่อความง่ายในการจัดการ
 *
 * @version 3.0.0
 * @date 2025-11-16
 */
return new class extends Migration
{
    /**
     * สร้างตาราง users พร้อม columns ครบถ้วน
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางยังไม่มีอยู่ก่อนสร้าง
        if (Schema::hasTable('users')) {
            $this->command->warn('⚠️  ตาราง users มีอยู่แล้ว - ข้าม migration นี้');
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            // ===============================================
            // 🔑 PRIMARY & BASIC FIELDS
            // ===============================================
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamp('blocked_at')->nullable()->comment('วันที่ถูกระงับการใช้งาน');

            // ===============================================
            // 👤 MEMBER SYSTEM
            // ===============================================
            $table->string('member_number', 50)->unique()->nullable()->comment('รหัสสมาชิก (auto-generated)');
            $table->string('profile_picture')->nullable()->comment('รูปโปรไฟล์');

            // ===============================================
            // 🎭 ROLE & PERMISSIONS
            // ===============================================
            $table->string('role')->default('user')->comment('user, seller, admin, super_admin');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null')->comment('Role ID (ระบบใหม่)');
            $table->boolean('is_super_admin')->default(false)->comment('ผู้ดูแลระบบสูงสุด');
            $table->json('permissions')->nullable()->comment('สิทธิ์การใช้งาน (JSON array)');

            // ===============================================
            // 🏨 HOTEL ADMIN FIELDS
            // ===============================================
            $table->boolean('is_hotel_admin')->default(false)->comment('เป็นผู้ดูแลโรงแรมหรือไม่');
            $table->foreignId('managed_hotel_id')->nullable()->constrained('hotels')->onDelete('set null')->comment('โรงแรมที่ดูแล');

            // ===============================================
            // 💼 AFFILIATE & RANK SYSTEM
            // ===============================================
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->onDelete('set null')->comment('Affiliate ID');
            $table->foreignId('current_rank_id')->nullable()->constrained('ranks')->onDelete('set null')->comment('Rank ปัจจุบัน');
            $table->integer('rank_points')->default(0)->comment('คะแนน Rank');
            $table->timestamp('rank_updated_at')->nullable()->comment('วันที่อัพเดท Rank');

            // ===============================================
            // 📱 LINE INTEGRATION
            // ===============================================
            $table->string('line_user_id')->nullable()->unique()->comment('LINE User ID');
            $table->string('line_display_name')->nullable()->comment('ชื่อแสดงใน LINE');
            $table->string('line_picture_url')->nullable()->comment('รูปโปรไฟล์จาก LINE');
            $table->text('line_access_token')->nullable()->comment('LINE Access Token');
            $table->timestamp('line_linked_at')->nullable()->comment('วันที่เชื่อมต่อ LINE');
            $table->boolean('line_verified')->default(false)->comment('ยืนยัน LINE แล้วหรือยัง');

            // ===============================================
            // 📞 CONTACT INFORMATION
            // ===============================================
            $table->string('phone', 20)->nullable()->comment('เบอร์โทรศัพท์');
            $table->boolean('phone_verified')->default(false)->comment('ยืนยันเบอร์แล้วหรือยัง');
            $table->timestamp('phone_verified_at')->nullable()->comment('วันที่ยืนยันเบอร์');

            // ===============================================
            // 🏠 ADDRESS INFORMATION
            // ===============================================
            $table->text('address')->nullable()->comment('ที่อยู่');
            $table->string('city', 100)->nullable()->comment('เมือง/อำเภอ');
            $table->string('state', 100)->nullable()->comment('จังหวัด');
            $table->string('postal_code', 20)->nullable()->comment('รหัสไปรษณีย์');
            $table->string('country', 10)->default('TH')->comment('ประเทศ (ISO code)');

            // ===============================================
            // 📦 SHIPPING ADDRESS
            // ===============================================
            $table->text('shipping_address')->nullable()->comment('ที่อยู่จัดส่ง');
            $table->string('shipping_city', 100)->nullable()->comment('เมือง/อำเภอ (จัดส่ง)');
            $table->string('shipping_state', 100)->nullable()->comment('จังหวัด (จัดส่ง)');
            $table->string('shipping_postal_code', 20)->nullable()->comment('รหัสไปรษณีย์ (จัดส่ง)');
            $table->string('shipping_country', 10)->nullable()->comment('ประเทศ (จัดส่ง)');
            $table->string('shipping_phone', 20)->nullable()->comment('เบอร์โทร (จัดส่ง)');

            // ===============================================
            // 🎂 PERSONAL INFORMATION
            // ===============================================
            $table->date('date_of_birth')->nullable()->comment('วันเกิด');
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->comment('เพศ');

            // ===============================================
            // 🆔 THAI ID CARD (KYC)
            // ===============================================
            $table->string('id_card_number', 20)->nullable()->unique()->comment('เลขบัตรประชาชน');
            $table->string('thai_first_name')->nullable()->comment('ชื่อไทย');
            $table->string('thai_last_name')->nullable()->comment('นามสกุลไทย');
            $table->string('english_first_name')->nullable()->comment('ชื่ออังกฤษ');
            $table->string('english_last_name')->nullable()->comment('นามสกุลอังกฤษ');
            $table->date('id_card_birth_date')->nullable()->comment('วันเกิดจากบัตร');
            $table->string('id_card_religion', 50)->nullable()->comment('ศาสนาจากบัตร');
            $table->text('id_card_address')->nullable()->comment('ที่อยู่จากบัตร');
            $table->date('id_card_issue_date')->nullable()->comment('วันออกบัตร');
            $table->date('id_card_expiry_date')->nullable()->comment('วันหมดอายุบัตร');

            // ===============================================
            // ✅ KYC VERIFICATION
            // ===============================================
            $table->enum('kyc_status', ['pending', 'approved', 'rejected', 'expired'])->nullable()->comment('สถานะ KYC');
            $table->timestamp('kyc_verified_at')->nullable()->comment('วันที่ยืนยัน KYC');

            // ===============================================
            // 🎮 GAME SYSTEM
            // ===============================================
            $table->json('game_preferences')->nullable()->comment('ตั้งค่าเกม (JSON)');

            // ===============================================
            // 📺 VIDEO REWARD SYSTEM
            // ===============================================
            $table->foreignId('video_referred_by')->nullable()->constrained('users')->onDelete('set null')->comment('ผู้แนะนำ (ระบบวิดีโอ)');

            // ===============================================
            // 🎨 UI/UX PREFERENCES
            // ===============================================
            $table->string('preferred_language', 5)->default('th')->comment('ภาษาที่ต้องการ (th, en, ja, zh, ko)');
            $table->string('menu_theme_preference', 50)->nullable()->comment('ธีมเมนูที่ต้องการ');

            // ===============================================
            // 🕐 TIMESTAMPS
            // ===============================================
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete support');

            // ===============================================
            // 📊 INDEXES (สำหรับ Performance)
            // ===============================================
            $table->index('email');
            $table->index('member_number');
            $table->index('role');
            $table->index('is_super_admin');
            $table->index('affiliate_id');
            $table->index('current_rank_id');
            $table->index('line_user_id');
            $table->index('phone');
            $table->index('id_card_number');
            $table->index('kyc_status');
            $table->index('created_at');
            $table->index('deleted_at');
        });

        // ===============================================
        // 🔐 PASSWORD RESET TOKENS
        // ===============================================
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // ===============================================
        // 🔒 SESSIONS
        // ===============================================
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        $this->command->info('✅ สร้างตาราง users (Version 3) สำเร็จ!');
    }

    /**
     * ลบตาราง users และตารางที่เกี่ยวข้อง
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
