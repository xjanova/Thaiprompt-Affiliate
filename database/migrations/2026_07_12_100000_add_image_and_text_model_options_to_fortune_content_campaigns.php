<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มออปชั่นภาพประกอบ + เลือกโมเดล AI รายแคมเปญ (fortune_content_campaigns)
     *
     * - generate_image          : เปิด/ปิดการสร้างภาพประกอบโพส (default true = พฤติกรรมเดิม)
     * - image_options           : preset ติ๊กเลือกคุมแนวภาพ (null = ชุด default เดิมของระบบ)
     * - image_custom_prompt     : พร้อมท์ภาพหลักกำหนดเอง (ผสานกับ AI ที่อ่าน caption ได้)
     * - image_provider_choice   : โมเดลเจนภาพ "slug:model" (null = chain อัตโนมัติเดิม CF→Pollinations)
     * - text_provider           : provider เจนบทความ (null = gemini-first เดิม)
     * - text_model              : บังคับโมเดลเจนบทความของ provider นั้น (null = model ของ key ใน pool)
     */
    public function up(): void
    {
        Schema::table('fortune_content_campaigns', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_content_campaigns', 'generate_image', function ($table) {
                $table->boolean('generate_image')->default(true)->after('image_style_hint');
            });

            $this->safeAddColumn($table, 'fortune_content_campaigns', 'image_options', function ($table) {
                $table->json('image_options')->nullable()->after('generate_image');
            });

            $this->safeAddColumn($table, 'fortune_content_campaigns', 'image_custom_prompt', function ($table) {
                $table->text('image_custom_prompt')->nullable()->after('image_options');
            });

            $this->safeAddColumn($table, 'fortune_content_campaigns', 'image_provider_choice', function ($table) {
                $table->string('image_provider_choice', 120)->nullable()->after('image_custom_prompt');
            });

            $this->safeAddColumn($table, 'fortune_content_campaigns', 'text_provider', function ($table) {
                $table->string('text_provider', 50)->nullable()->after('image_provider_choice');
            });

            $this->safeAddColumn($table, 'fortune_content_campaigns', 'text_model', function ($table) {
                $table->string('text_model', 120)->nullable()->after('text_provider');
            });
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_content_campaigns', [
            'generate_image',
            'image_options',
            'image_custom_prompt',
            'image_provider_choice',
            'text_provider',
            'text_model',
        ]);
    }
};
