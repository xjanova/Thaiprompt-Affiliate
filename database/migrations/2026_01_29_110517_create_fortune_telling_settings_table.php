<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง fortune_telling_settings
     *
     * ตารางนี้เก็บการตั้งค่าระบบดูดวงผ่าน Facebook Messenger
     * รองรับ AI providers: Gemini, Groq, Qwen, OpenRouter
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::create('fortune_telling_settings', function (Blueprint $table) {
            $table->id();

            // การตั้งค่า Facebook
            $table->string('facebook_app_id', 100)->nullable()->comment('Facebook App ID');
            $table->string('facebook_app_secret')->nullable()->comment('Facebook App Secret');
            $table->string('facebook_page_id', 100)->nullable()->comment('Facebook Page ID');
            $table->text('facebook_page_token')->nullable()->comment('Facebook Page Access Token');
            $table->string('facebook_verify_token', 100)->nullable()->comment('Webhook Verify Token');

            // การตั้งค่า AI
            $table->enum('ai_provider', ['gemini', 'groq', 'qwen', 'openrouter'])
                ->default('gemini')
                ->comment('AI Provider ที่ใช้');
            $table->text('ai_api_key')->nullable()->comment('AI API Key');
            $table->string('ai_model', 100)
                ->default('gemini-2.0-flash-exp')
                ->comment('AI Model name');
            $table->text('prompt_template')->nullable()->comment('Template สำหรับ prompt');

            // การตั้งค่าการใช้งาน
            $table->integer('max_free_readings')
                ->default(3)
                ->comment('จำนวนครั้งฟรีต่อวัน (สำหรับผู้ไม่สมัครสมาชิก)');
            $table->decimal('reading_price', 10, 2)
                ->default(0)
                ->comment('ราคาต่อครั้ง (0 = ฟรี)');
            $table->string('payment_qr_image')->nullable()->comment('Path รูป QR Code ชำระเงิน');

            // การตั้งค่าพฤติกรรม
            $table->boolean('is_enabled')
                ->default(true)
                ->comment('เปิด/ปิดบริการดูดวง');
            $table->boolean('respond_in_comment')
                ->default(false)
                ->comment('true = ตอบในคอมเมนต์, false = ส่ง private message');
            $table->boolean('require_registration')
                ->default(false)
                ->comment('บังคับให้สมัครสมาชิกก่อนใช้งาน');

            // ข้อความอัตโนมัติ
            $table->text('welcome_message')->nullable()->comment('ข้อความต้อนรับ');
            $table->text('limit_exceeded_message')->nullable()->comment('ข้อความเมื่อครบจำนวนครั้งฟรี');
            $table->text('payment_message')->nullable()->comment('ข้อความแนะนำการชำระเงิน');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_enabled', 'fortune_settings_enabled_idx');
        });
    }

    /**
     * ลบตาราง fortune_telling_settings
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_telling_settings');
    }
};
