<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง email_campaigns สำหรับระบบแคมเปญอีเมล
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง
        if (Schema::hasTable('email_campaigns')) {
            return;
        }

        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name')->comment('ชื่อแคมเปญ');
            $table->string('subject')->comment('หัวเรื่องอีเมล');
            $table->text('description')->nullable()->comment('คำอธิบายแคมเปญ');

            // เนื้อหาอีเมล
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('email_templates')
                ->onDelete('set null')
                ->comment('เทมเพลตที่ใช้');
            $table->longText('content_html')->nullable()->comment('เนื้อหา HTML');
            $table->text('content_plain')->nullable()->comment('เนื้อหา Plain Text');

            // ผู้สร้างและผู้อนุมัติ
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้สร้างแคมเปญ');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ผู้อนุมัติแคมเปญ');

            // กำหนดเวลา
            $table->timestamp('scheduled_at')->nullable()->comment('กำหนดส่งเวลา');
            $table->timestamp('started_at')->nullable()->comment('เริ่มส่งเมื่อ');
            $table->timestamp('completed_at')->nullable()->comment('ส่งเสร็จเมื่อ');

            // สถานะ
            $table->enum('status', [
                'draft',      // ฉบับร่าง
                'scheduled',  // กำหนดเวลาแล้ว
                'sending',    // กำลังส่ง
                'sent',       // ส่งเสร็จแล้ว
                'paused',     // หยุดชั่วคราว
                'cancelled',  // ยกเลิก
                'failed'      // ล้มเหลว
            ])->default('draft')->comment('สถานะแคมเปญ');

            // เป้าหมาย
            $table->enum('target_type', [
                'all_users',        // ทุกคน
                'specific_users',   // เลือกเอง
                'by_rank',          // ตาม Rank
                'by_status',        // ตามสถานะ
                'custom_query'      // Custom Query
            ])->default('all_users')->comment('ประเภทเป้าหมาย');
            $table->json('target_filters')->nullable()->comment('ตัวกรอง JSON');

            // สถิติ
            $table->integer('total_recipients')->default(0)->comment('จำนวนผู้รับทั้งหมด');
            $table->integer('sent_count')->default(0)->comment('ส่งแล้วจำนวน');
            $table->integer('delivered_count')->default(0)->comment('ส่งถึงแล้วจำนวน');
            $table->integer('opened_count')->default(0)->comment('เปิดอ่านแล้วจำนวน');
            $table->integer('clicked_count')->default(0)->comment('คลิกแล้วจำนวน');
            $table->integer('bounced_count')->default(0)->comment('ตีกลับจำนวน');
            $table->integer('failed_count')->default(0)->comment('ล้มเหลวจำนวน');

            // ตั้งค่าเพิ่มเติม
            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('email_providers')
                ->onDelete('set null')
                ->comment('ผู้ให้บริการที่ใช้');
            $table->integer('sending_rate')->default(100)->comment('อัตราการส่ง (อีเมล/นาที)');
            $table->boolean('track_opens')->default(true)->comment('ติดตามการเปิดอ่าน');
            $table->boolean('track_clicks')->default(true)->comment('ติดตามการคลิก');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('created_by');
            $table->index(['status', 'scheduled_at'], 'campaign_schedule_idx');
        });
    }

    /**
     * ลบตาราง email_campaigns
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
