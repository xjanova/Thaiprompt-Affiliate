<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_saved_questions
     *
     * เก็บคำถามที่ AI ตอบไม่ได้ เพื่อให้แอดมินมาตอบกลับทีหลัง
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_saved_questions')) {
            return;
        }

        Schema::create('fortune_saved_questions', function (Blueprint $table) {
            $table->id();
            $table->string('platform_user_id', 100)->comment('LINE/Facebook user ID');
            $table->string('platform', 20)->default('line')->comment('line หรือ facebook');
            $table->string('user_name')->nullable()->comment('ชื่อผู้ใช้');
            $table->text('question')->comment('คำถามที่ AI ตอบไม่ได้');
            $table->text('ai_response')->nullable()->comment('คำตอบของ AI (ถ้ามี)');
            $table->string('reason', 50)->default('ai_cannot_answer')->comment('สาเหตุ: ai_cannot_answer, ai_failed, fallback');
            $table->text('admin_reply')->nullable()->comment('คำตอบจากแอดมิน');
            $table->unsignedBigInteger('replied_by')->nullable()->comment('แอดมินที่ตอบ');
            $table->timestamp('replied_at')->nullable()->comment('เวลาที่แอดมินตอบ');
            $table->boolean('is_replied')->default(false)->comment('ตอบแล้วหรือยัง');
            $table->boolean('is_sent_to_user')->default(false)->comment('ส่งคำตอบกลับไปหาผู้ใช้แล้วหรือยัง');
            $table->timestamps();

            $table->index('platform_user_id', 'fsq_platform_user_idx');
            $table->index('is_replied', 'fsq_is_replied_idx');
            $table->index('created_at', 'fsq_created_at_idx');
        });
    }

    /**
     * ลบตาราง fortune_saved_questions
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_saved_questions');
    }
};
