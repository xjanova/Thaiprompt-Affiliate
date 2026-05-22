<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_user_bans
     *
     * ระบบ "คุก" (Ban/Jail) สำหรับห้ามบอทคุยกับ user ที่มีพฤติกรรมไม่เหมาะสม
     * (สแปม, ก่อกวน, คุกคาม) — แอดมินยังคุยได้ผ่าน Page Inbox/admin panel
     *
     * - banned_until = NULL → ถาวร (permanent)
     * - banned_until = future date → จำกัดเวลา (จะ auto-expire)
     * - unique (platform + platform_user_id) — กัน duplicate ban ต่อคน
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_user_bans')) {
            return;
        }

        Schema::create('fortune_user_bans', function (Blueprint $table) {
            $table->id();

            // Platform identifier
            $table->enum('platform', ['facebook', 'line'])
                ->comment('แพลตฟอร์มที่ถูกแบน');
            $table->string('platform_user_id', 100)
                ->comment('PSID (Facebook) หรือ LINE userId');

            // Optional display info (snapshot ณ ตอนแบน — for UI)
            $table->string('display_name', 200)->nullable()
                ->comment('ชื่อที่แสดงตอนแบน (snapshot)');

            // Ban metadata
            $table->text('reason')->nullable()
                ->comment('เหตุผลที่แบน (สำหรับ audit)');
            $table->timestamp('banned_until')->nullable()
                ->comment('NULL = ถาวร, อนาคต = แบนถึงเวลานี้');

            // Audit
            $table->foreignId('banned_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('admin user_id ที่กดแบน');

            // Anti-spam tracking — เพื่อไม่ตอบข้อความซ้ำๆ
            $table->timestamp('last_notified_at')->nullable()
                ->comment('ครั้งล่าสุดที่ส่งข้อความแจ้งสถานะแบน');
            $table->unsignedInteger('notify_count')->default(0)
                ->comment('จำนวนครั้งที่แจ้งสถานะ (ใช้ตรวจ spam)');
            $table->unsignedInteger('attempt_count')->default(0)
                ->comment('จำนวนครั้งที่ user พยายามทักหลังถูกแบน');

            $table->timestamps();

            // Indexes
            $table->unique(['platform', 'platform_user_id'], 'fortune_user_bans_unique_user');
            $table->index('banned_until', 'fortune_user_bans_until_idx');
        });
    }

    /**
     * ลบตาราง fortune_user_bans
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_user_bans');
    }
};
