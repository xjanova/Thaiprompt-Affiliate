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
        // ตาราง line_oa_configs - การตั้งค่า LINE OA
        Schema::create('line_oa_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อการตั้งค่า
            $table->string('channel_id')->unique(); // LINE Channel ID
            $table->string('channel_secret'); // LINE Channel Secret (encrypted)
            $table->text('channel_access_token'); // LINE Channel Access Token (encrypted)
            $table->string('webhook_url')->nullable(); // Webhook URL
            $table->boolean('is_active')->default(true); // ใช้งาน/ไม่ใช้งาน
            $table->boolean('require_kyc_for_withdrawal')->default(true); // บังคับ KYC สำหรับถอนเงิน
            $table->decimal('min_withdrawal_without_kyc', 10, 2)->default(0); // จำนวนเงินถอนขั้นต่ำที่ไม่ต้อง KYC
            $table->json('welcome_message')->nullable(); // ข้อความต้อนรับ
            $table->json('kyc_success_message')->nullable(); // ข้อความเมื่อ KYC สำเร็จ
            $table->json('rich_menu_id')->nullable(); // Rich Menu ID
            $table->json('settings')->nullable(); // การตั้งค่าเพิ่มเติม
            $table->timestamp('last_synced_at')->nullable(); // ครั้งสุดท้ายที่ sync
            $table->timestamps();
            $table->softDeletes();
        });

        // ตาราง user_kyc_verifications - สถานะ KYC ของผู้ใช้
        Schema::create('user_kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('line_user_id')->unique()->nullable(); // LINE User ID
            $table->string('line_display_name')->nullable(); // ชื่อแสดงใน LINE
            $table->string('line_picture_url')->nullable(); // รูปโปรไฟล์ LINE
            $table->string('verification_status')->default('pending'); // pending, verified, rejected, expired
            $table->string('verification_method')->default('line_oa'); // line_oa, manual, auto
            $table->timestamp('verified_at')->nullable(); // วันที่ยืนยันตัวตน
            $table->timestamp('rejected_at')->nullable(); // วันที่ปฏิเสธ
            $table->string('rejected_reason')->nullable(); // เหตุผลที่ปฏิเสธ
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); // ผู้ยืนยัน (admin)
            $table->json('verification_data')->nullable(); // ข้อมูลการยืนยันเพิ่มเติม
            $table->integer('verification_attempts')->default(0); // จำนวนครั้งที่พยายามยืนยัน
            $table->timestamp('last_attempt_at')->nullable(); // ครั้งสุดท้ายที่พยายาม
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable(); // หมายเหตุ
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'verification_status']);
            $table->index('verified_at');
        });

        // ตาราง line_oa_webhooks - บันทึก webhook จาก LINE
        Schema::create('line_oa_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_oa_config_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type'); // message, follow, unfollow, join, leave, postback, beacon
            $table->string('line_user_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload'); // ข้อมูล webhook ทั้งหมด
            $table->boolean('processed')->default(false); // ประมวลผลแล้ว/ยัง
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable(); // error ถ้ามี
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['processed', 'created_at']);
            $table->index('line_user_id');
        });

        // ตาราง line_oa_messages - ข้อความที่ส่งผ่าน LINE OA
        Schema::create('line_oa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_oa_config_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('line_user_id');
            $table->string('direction'); // incoming, outgoing
            $table->string('message_type'); // text, image, video, audio, file, location, sticker, template
            $table->text('message_content')->nullable(); // เนื้อหาข้อความ
            $table->json('message_data')->nullable(); // ข้อมูลข้อความแบบเต็ม
            $table->string('reply_token')->nullable(); // Reply token จาก LINE
            $table->string('status')->default('pending'); // pending, sent, delivered, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['line_user_id', 'direction']);
            $table->index(['status', 'created_at']);
        });

        // เพิ่มคอลัมน์ kyc_verified_at ในตาราง users
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('kyc_verified_at')->nullable()->after('email_verified_at');
            $table->boolean('kyc_required')->default(false)->after('kyc_verified_at');

            $table->index('kyc_verified_at');
        });

        // เพิ่มคอลัมน์ requires_kyc ในตาราง withdrawals
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->boolean('kyc_checked')->default(false)->after('status');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_checked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['kyc_checked', 'kyc_verified_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_verified_at', 'kyc_required']);
        });

        Schema::dropIfExists('line_oa_messages');
        Schema::dropIfExists('line_oa_webhooks');
        Schema::dropIfExists('user_kyc_verifications');
        Schema::dropIfExists('line_oa_configs');
    }
};
