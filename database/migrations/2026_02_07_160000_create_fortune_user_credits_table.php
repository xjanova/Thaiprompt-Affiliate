<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_user_credits
     *
     * จัดการเครดิตดูดวงฟรีรายคน
     * แอดมินสามารถเพิ่ม/รีเซ็ต/ให้เครดิตฟรีเป็นรายคนได้
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_user_credits')) {
            return;
        }

        Schema::create('fortune_user_credits', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_user_id')->index();
            $table->string('facebook_user_name')->nullable();
            $table->string('platform')->default('facebook');
            $table->integer('bonus_credits')->default(0)->comment('เครดิตดูดวงฟรีที่ได้รับ (เพิ่มเติมจากสิทธิ์ประจำวัน)');
            $table->integer('credits_used')->default(0)->comment('เครดิตที่ใช้ไปแล้ว');
            $table->boolean('is_unlimited')->default(false)->comment('ดูดวงฟรีไม่จำกัด');
            $table->date('unlimited_until')->nullable()->comment('ดูฟรีไม่จำกัดจนถึงวันที่');
            $table->boolean('is_daily_reset')->default(false)->comment('รีเซ็ตสิทธิ์ฟรีวันนี้ (ไม่นับใช้ไปแล้ววันนี้)');
            $table->date('daily_reset_date')->nullable()->comment('วันที่รีเซ็ตล่าสุด');
            $table->text('note')->nullable()->comment('หมายเหตุจากแอดมิน');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('แอดมินที่แก้ไขล่าสุด');
            $table->timestamps();

            $table->unique(['facebook_user_id', 'platform'], 'fortune_user_credits_unique');
        });
    }

    /**
     * ลบตาราง fortune_user_credits
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_user_credits');
    }
};
