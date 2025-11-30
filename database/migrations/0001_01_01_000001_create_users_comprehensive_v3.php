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
            // ตาราง users มีอยู่แล้ว - ข้าม migration นี้
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
            // ⚠️ NOTE: ไม่ใช้ constrained() เพื่อความปลอดภัยกรณีติดตั้งซ้ำ
            $table->unsignedBigInteger('role_id')->nullable()->comment('Role ID (ระบบใหม่)');
            $table->boolean('is_super_admin')->default(false)->comment('ผู้ดูแลระบบสูงสุด');
            $table->json('permissions')->nullable()->comment('สิทธิ์การใช้งาน (JSON array)');

            // ===============================================
            // 🏨 HOTEL ADMIN FIELDS
            // ===============================================
            $table->boolean('is_hotel_admin')->default(false)->comment('เป็นผู้ดูแลโรงแรมหรือไม่');
            // ⚠️ NOTE: ไม่ใช้ constrained() เพื่อความปลอดภัยกรณีติดตั้งซ้ำ
            $table->unsignedBigInteger('managed_hotel_id')->nullable()->comment('โรงแรมที่ดูแล');

            // ===============================================
            // 💼 AFFILIATE & RANK SYSTEM
            // ===============================================
            // ⚠️ NOTE: ไม่ใช้ constrained() เพราะตารางอาจยังไม่มี
            // Foreign keys จะถูกเพิ่มทีหลังหลังจากสร้างตาราง
            $table->unsignedBigInteger('affiliate_id')->nullable()->comment('Affiliate ID');
            $table->unsignedBigInteger('current_rank_id')->nullable()->comment('Rank ปัจจุบัน');
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
            // ⚠️ NOTE: ไม่ใช้ constrained('users') เพราะเป็น self-reference
            // ต้องเพิ่ม foreign key หลังจากสร้างตารางเสร็จ
            $table->unsignedBigInteger('video_referred_by')->nullable()->comment('ผู้แนะนำ (ระบบวิดีโอ)');

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
            $table->index('role_id');
            $table->index('is_super_admin');
            $table->index('managed_hotel_id');
            $table->index('affiliate_id');
            $table->index('current_rank_id');
            $table->index('line_user_id');
            $table->index('phone');
            $table->index('id_card_number');
            $table->index('kyc_status');
            $table->index('video_referred_by');
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

        // ===============================================
        // 🔗 FOREIGN KEYS (เพิ่มหลังจากสร้างตารางเสร็จ)
        // ===============================================
        // เพิ่ม foreign keys เฉพาะเมื่อตารางที่อ้างอิงมีอยู่แล้ว
        Schema::table('users', function (Blueprint $table) {
            // role_id -> roles
            if (Schema::hasTable('roles') && !$this->hasForeignKey('users', 'role_id')) {
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            }

            // managed_hotel_id -> hotels
            if (Schema::hasTable('hotels') && !$this->hasForeignKey('users', 'managed_hotel_id')) {
                $table->foreign('managed_hotel_id')->references('id')->on('hotels')->onDelete('set null');
            }

            // current_rank_id -> ranks
            if (Schema::hasTable('ranks') && !$this->hasForeignKey('users', 'current_rank_id')) {
                $table->foreign('current_rank_id')->references('id')->on('ranks')->onDelete('set null');
            }

            // video_referred_by -> users (self-reference)
            if (!$this->hasForeignKey('users', 'video_referred_by')) {
                $table->foreign('video_referred_by')->references('id')->on('users')->onDelete('set null');
            }

            // affiliate_id -> affiliates (ตาราง affiliates อาจยังไม่มี - จะถูกเพิ่มทีหลัง)
            if (Schema::hasTable('affiliates') && !$this->hasForeignKey('users', 'affiliate_id')) {
                $table->foreign('affiliate_id')->references('id')->on('affiliates')->onDelete('set null');
            }
        });
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

    /**
     * ตรวจสอบว่ามี foreign key อยู่แล้วหรือไม่
     *
     * @param string $table ชื่อตาราง
     * @param string $column ชื่อคอลัมน์
     * @return bool
     */
    protected function hasForeignKey(string $table, string $column): bool
    {
        try {
            $foreignKeys = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableForeignKeys($table);

            return collect($foreignKeys)->contains(function ($fk) use ($column) {
                return in_array($column, $fk->getLocalColumns());
            });
        } catch (\Exception $e) {
            // หากเกิดข้อผิดพลาด ถือว่ายังไม่มี foreign key
            return false;
        }
    }
};
