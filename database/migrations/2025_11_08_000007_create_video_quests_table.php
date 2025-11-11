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
        Schema::create('video_quests', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อภาระกิจ
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->enum('type', [
                'watch_videos',      // ดูวิดีโอ X คลิป
                'watch_duration',    // ดูรวม X นาที
                'watch_channel',     // ดูวิดีโอจากช่อง X
                'daily_streak',      // ดูติดต่อกัน X วัน
                'refer_users',       // ชวนเพื่อน X คน
                'referred_watch',    // เพื่อนที่ชวนดูวิดีโอ X คลิป
                'complete_quests',   // ทำภาระกิจสำเร็จ X ภาระกิจ
                'reach_level',       // ถึงระดับ X
            ])->default('watch_videos');

            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'once', 'repeatable'])->default('daily');

            // เงื่อนไขภาระกิจ
            $table->integer('target_value'); // เป้าหมาย (เช่น ดู 5 คลิป)
            $table->foreignId('channel_id')->nullable()->constrained('video_channels')->onDelete('cascade'); // ระบุช่อง (ถ้ามี)
            $table->json('video_ids')->nullable(); // ระบุวิดีโอ (ถ้ามี)
            $table->integer('min_watch_percentage')->default(80); // ต้องดูอย่างน้อย % (สำหรับ watch_videos)

            // รางวัล
            $table->decimal('reward_coins', 10, 2)->default(0);
            $table->integer('reward_exp')->default(0);
            $table->decimal('reward_money', 10, 2)->default(0); // รางวัลเป็นเงินเข้า wallet โดยตรง

            // รางวัลเพิ่มตามเงื่อนไข
            $table->decimal('bonus_coins_per_extra', 10, 2)->default(0); // โบนัสเหรียญต่อหน่วยที่เกิน
            $table->integer('min_level')->default(1); // ระดับขั้นต่ำที่รับภาระกิจได้

            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('frequency');
            $table->index('is_active');
            $table->index('min_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_quests');
    }
};
