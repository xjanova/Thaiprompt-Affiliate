<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🧾 สลิปที่ลูกค้าส่งในแชทเพจ (โหมด web-only) เพื่อเติมเงินเว็บจันทรา
     *
     * บอทตรวจสลิปด้วย SlipOK แล้ว "พัก" ไว้ที่นี่ รอเว็บจันทรามาดึงไปเติมกระเป๋า
     * สลิปถูกจองใน slip_verifications ตั้งแต่ตอนพัก (trans_ref unique) → ใช้ซ้ำข้ามระบบไม่ได้
     */
    public function up(): void
    {
        if (Schema::hasTable('juntra_chat_slips')) {
            return;
        }

        Schema::create('juntra_chat_slips', function (Blueprint $table) {
            $table->id();
            $table->string('trans_ref', 64)->unique();
            $table->string('platform', 16);
            $table->string('platform_user_id', 64);
            $table->decimal('amount', 12, 2);
            $table->string('sender_name', 120)->nullable();
            $table->string('receiver_account', 64)->nullable();
            $table->string('sending_bank', 8)->nullable();
            $table->string('receiving_bank', 8)->nullable();
            $table->string('trans_timestamp', 64)->nullable();
            $table->string('slip_path')->nullable();
            // parked → credited | review | duplicate | no_user
            $table->string('status', 16)->default('parked');
            $table->string('juntra_user_ref', 64)->nullable();
            $table->string('topup_ref', 64)->nullable();
            $table->decimal('credited_amount', 12, 2)->nullable();
            $table->string('outcome_note', 255)->nullable();
            $table->unsignedSmallInteger('ping_attempts')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'jcs_status_created_idx');
            $table->index(['platform', 'platform_user_id'], 'jcs_platform_user_idx');
        });
    }

    /**
     * ลบตาราง juntra_chat_slips
     */
    public function down(): void
    {
        Schema::dropIfExists('juntra_chat_slips');
    }
};
