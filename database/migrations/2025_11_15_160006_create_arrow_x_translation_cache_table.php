<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง translation_cache สำหรับ Arrow X Theme System
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('translation_cache')) {
            return;
        }

        Schema::create('translation_cache', function (Blueprint $table) {
            $table->id();

            // Source & Target
            $table->string('source_language', 5)->comment('th, en, ja, etc.');
            $table->string('target_language', 5)->comment('th, en, ja, etc.');

            // Text
            $table->text('source_text');
            $table->text('translated_text');

            // Hash for fast lookup
            $table->string('text_hash', 64)->comment('SHA256 hash of source_text + languages');

            // API Info
            $table->string('api_provider', 50)->default('google_translate');
            $table->timestamp('api_call_timestamp')->nullable();

            // Usage Stats
            $table->integer('hit_count')->default(1)->comment('จำนวนครั้งที่ใช้ cache นี้');
            $table->timestamp('last_used_at')->useCurrent();

            // Created/Updated
            $table->timestamps();

            // Indexes
            $table->unique(['text_hash', 'source_language', 'target_language'], 'unique_translation_idx');
            $table->index(['source_language', 'target_language'], 'idx_languages');
            $table->index('text_hash', 'idx_hash');
            $table->index('last_used_at', 'idx_last_used');
        });
    }

    /**
     * ลบตาราง translation_cache
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_cache');
    }
};
