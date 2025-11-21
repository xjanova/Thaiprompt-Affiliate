<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง email_campaign_recipients สำหรับผู้รับในแคมเปญ
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('email_campaign_recipients')) {
            return;
        }

        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('campaign_id')
                ->constrained('email_campaigns')
                ->onDelete('cascade')
                ->comment('แคมเปญที่เกี่ยวข้อง');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้รับอีเมล');

            // ข้อมูลการส่ง
            $table->string('email')->comment('อีเมลที่ส่งไป');
            $table->enum('status', [
                'pending',    // รอส่ง
                'sending',    // กำลังส่ง
                'sent',       // ส่งแล้ว
                'delivered',  // ส่งถึงแล้ว
                'opened',     // เปิดอ่านแล้ว
                'clicked',    // คลิกแล้ว
                'bounced',    // ตีกลับ
                'failed'      // ล้มเหลว
            ])->default('pending')->comment('สถานะการส่ง');

            // ข้อมูลเวลา
            $table->timestamp('sent_at')->nullable()->comment('ส่งเมื่อ');
            $table->timestamp('delivered_at')->nullable()->comment('ส่งถึงเมื่อ');
            $table->timestamp('opened_at')->nullable()->comment('เปิดอ่านเมื่อ');
            $table->timestamp('clicked_at')->nullable()->comment('คลิกเมื่อ');
            $table->timestamp('bounced_at')->nullable()->comment('ตีกลับเมื่อ');

            // ข้อมูลเพิ่มเติม
            $table->integer('open_count')->default(0)->comment('จำนวนครั้งที่เปิด');
            $table->integer('click_count')->default(0)->comment('จำนวนครั้งที่คลิก');
            $table->text('error_message')->nullable()->comment('ข้อความ Error');
            $table->string('provider_message_id')->nullable()->comment('Message ID จาก Provider');

            $table->timestamps();

            // Indexes
            $table->unique(['campaign_id', 'user_id'], 'campaign_user_unique');
            $table->index('status');
            $table->index('email');
            $table->index('sent_at');
            $table->index(['campaign_id', 'status'], 'campaign_status_idx');
        });
    }

    /**
     * ลบตาราง email_campaign_recipients
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaign_recipients');
    }
};
