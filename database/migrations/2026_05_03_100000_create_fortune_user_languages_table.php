<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_user_languages
     *
     * เก็บภาษาที่ผู้ใช้เลือก / auto-detect ต่อ platform+user
     * ใช้สำหรับ Fortune Bot บน Facebook (รองรับลาว) — LINE ยังเป็นไทยล้วน
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_user_languages')) {
            return;
        }

        Schema::create('fortune_user_languages', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->default('facebook')->comment('facebook|line');
            $table->string('platform_user_id', 100)->comment('PSID หรือ LINE user ID');
            $table->string('locale', 5)->default('th')->comment('th|lo');
            $table->string('source', 20)->default('auto')->comment('auto|manual|admin');
            $table->timestamps();

            $table->unique(['platform', 'platform_user_id'], 'fortune_lang_platform_user_uniq');
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_user_languages');
    }
};
