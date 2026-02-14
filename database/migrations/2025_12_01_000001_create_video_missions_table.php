<?php

/**
 * สร้างตาราง video_missions
 *
 * ตารางหลักสำหรับเก็บข้อมูลภารกิจดูคลิปรับรางวัล
 * Admin สามารถกำหนดคลิป, รางวัล, เงื่อนไข, และเวลาที่ต้องดู
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง video_missions สำหรับระบบภารกิจดูคลิป
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('video_missions')) {
            return;
        }

        Schema::create('video_missions', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐานของภารกิจ
            $table->string('title');                                   // ชื่อภารกิจ
            $table->string('title_th')->nullable();                    // ชื่อภารกิจภาษาไทย
            $table->text('description')->nullable();                   // รายละเอียด
            $table->text('description_th')->nullable();                // รายละเอียดภาษาไทย
            $table->string('thumbnail')->nullable();                   // รูปปก
            $table->string('icon')->nullable();                        // ไอคอน/Emoji

            // ข้อมูลวิดีโอ
            $table->string('video_url');                               // URL ของวิดีโอ
            $table->enum('video_type', [
                'youtube',
                'vimeo',
                'tiktok',
                'facebook',
                'direct',                                              // Direct MP4/WebM URL
                'embed',                                                // Custom embed code
            ])->default('youtube');
            $table->string('video_id')->nullable();                    // ID จาก platform (เช่น YouTube ID)
            $table->text('embed_code')->nullable();                    // Custom embed code (ถ้า video_type = embed)
            $table->integer('video_duration_seconds')->nullable();     // ความยาววิดีโอ (วินาที)

            // เงื่อนไขการดู
            $table->integer('required_watch_seconds');                  // เวลาที่ต้องดู (วินาที)
            $table->integer('required_watch_percentage')->default(80); // % ที่ต้องดู (ถ้าไม่ระบุ seconds)
            $table->boolean('must_watch_complete')->default(false);    // ต้องดูจบจริงๆ
            $table->boolean('allow_skip')->default(false);             // อนุญาตให้ข้ามได้หรือไม่
            $table->integer('max_skip_seconds')->default(0);           // จำนวนวินาทีที่ข้ามได้สูงสุด
            $table->boolean('allow_speed_change')->default(false);     // อนุญาตปรับความเร็วได้หรือไม่
            $table->json('allowed_speeds')->nullable();                // ความเร็วที่อนุญาต เช่น [1.0, 1.25]

            // รางวัล
            $table->enum('reward_type', [
                'money',          // เงินสด (THB)
                'coins',          // Video Coins
                'points',         // แต้มสะสม
                'tpix',           // TPIX Token
                'exp',            // Experience Points
                'mixed',           // รางวัลผสม
            ])->default('coins');
            $table->decimal('reward_money', 12, 2)->default(0);        // รางวัลเงินสด
            $table->decimal('reward_coins', 12, 2)->default(0);        // รางวัล Coins
            $table->integer('reward_points')->default(0);              // รางวัลแต้ม
            $table->decimal('reward_tpix', 18, 8)->default(0);         // รางวัล TPIX
            $table->integer('reward_exp')->default(0);                 // รางวัล EXP

            // คุณสมบัติของผู้ทำภารกิจ
            $table->unsignedBigInteger('min_rank_id')->nullable();     // Rank ขั้นต่ำที่ทำได้
            $table->unsignedBigInteger('max_rank_id')->nullable();     // Rank สูงสุดที่ทำได้
            $table->integer('min_user_level')->default(1);             // Level ขั้นต่ำ
            $table->integer('min_days_registered')->default(0);        // สมัครมาแล้วกี่วัน
            $table->boolean('require_kyc')->default(false);            // ต้อง KYC ก่อน
            $table->boolean('require_email_verified')->default(false); // ต้อง verify email
            $table->boolean('require_phone_verified')->default(false); // ต้อง verify phone

            // จำกัดการทำภารกิจ
            $table->enum('frequency', [
                'once',           // ทำได้ครั้งเดียว
                'daily',          // ทำได้วันละครั้ง
                'weekly',         // ทำได้สัปดาห์ละครั้ง
                'monthly',        // ทำได้เดือนละครั้ง
                'unlimited',       // ไม่จำกัด (cooldown period)
            ])->default('daily');
            $table->integer('cooldown_minutes')->default(0);           // เวลา cooldown (นาที) สำหรับ unlimited
            $table->integer('max_completions_per_user')->nullable();   // จำนวนครั้งสูงสุดต่อ user
            $table->integer('max_total_completions')->nullable();      // จำนวนครั้งสูงสุดทั้งหมด (budget)
            $table->integer('daily_limit_global')->nullable();         // จำกัดต่อวันทั้งระบบ

            // ช่วงเวลาที่เปิดให้ทำ
            $table->dateTime('start_at')->nullable();                  // เริ่มเปิดให้ทำ
            $table->dateTime('end_at')->nullable();                    // ปิดการทำภารกิจ
            $table->json('available_days')->nullable();                // วันที่เปิดให้ทำ [0=อาทิตย์, 1=จันทร์, ...]
            $table->time('available_time_start')->nullable();          // เวลาเริ่มต้นแต่ละวัน
            $table->time('available_time_end')->nullable();            // เวลาสิ้นสุดแต่ละวัน

            // ระบบป้องกันการโกง
            $table->boolean('anti_cheat_enabled')->default(true);      // เปิดใช้ระบบป้องกันโกง
            $table->boolean('require_focus')->default(true);           // ต้องอยู่หน้าเว็บ
            $table->boolean('detect_tab_switch')->default(true);       // ตรวจจับการสลับ tab
            $table->integer('max_tab_switches')->default(3);           // จำนวน tab switch สูงสุด
            $table->boolean('require_interaction')->default(false);    // ต้องมี interaction ระหว่างดู
            $table->integer('interaction_interval_seconds')->default(60); // ทุกกี่วินาทีต้อง interact
            $table->boolean('verify_completion')->default(true);       // ต้อง verify ก่อนให้รางวัล
            $table->json('verification_questions')->nullable();        // คำถามยืนยัน (ถ้ามี)

            // สถานะและการจัดเรียง
            $table->boolean('is_active')->default(true);               // เปิดใช้งาน
            $table->boolean('is_featured')->default(false);            // แนะนำพิเศษ
            $table->boolean('is_premium')->default(false);             // สำหรับสมาชิก premium
            $table->integer('priority')->default(0);                   // ลำดับความสำคัญ
            $table->integer('sort_order')->default(0);                 // ลำดับการแสดงผล

            // สถิติ
            $table->integer('view_count')->default(0);                 // จำนวนคนเข้าดู
            $table->integer('completion_count')->default(0);           // จำนวนคนทำสำเร็จ
            $table->decimal('total_rewards_given', 15, 2)->default(0); // รางวัลที่แจกไปแล้ว

            // หมวดหมู่และ tags
            $table->string('category')->nullable();                    // หมวดหมู่
            $table->json('tags')->nullable();                          // แท็ก

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();      // สร้างโดย
            $table->unsignedBigInteger('updated_by')->nullable();      // แก้ไขโดย

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('min_rank_id')
                ->references('id')
                ->on('ranks')
                ->onDelete('set null');

            $table->foreign('max_rank_id')
                ->references('id')
                ->on('ranks')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('frequency');
            $table->index('priority');
            $table->index('sort_order');
            $table->index('start_at');
            $table->index('end_at');
            $table->index('category');
            $table->index(['is_active', 'start_at', 'end_at'], 'vm_active_period_idx');
        });
    }

    /**
     * ลบตาราง video_missions
     */
    public function down(): void
    {
        Schema::dropIfExists('video_missions');
    }
};
