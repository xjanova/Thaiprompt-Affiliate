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
        Schema::create('translation_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('คำหรือวลีภาษาต้นทาง (source text)');
            $table->string('source_lang', 10)->default('th')->comment('ภาษาต้นทาง');
            $table->string('target_lang', 10)->comment('ภาษาปลายทาง');
            $table->text('translation')->comment('คำแปลที่กำหนดเอง');
            $table->text('context')->nullable()->comment('บริบทหรือคำอธิบาย');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งานหรือไม่');
            $table->integer('priority')->default(0)->comment('ลำดับความสำคัญ (ค่ามากกว่าจะถูกใช้ก่อน)');
            $table->timestamps();

            // Indexes สำหรับการค้นหาที่เร็ว
            $table->index(['key', 'source_lang', 'target_lang', 'is_active'], 'translation_lookup');
            $table->index('is_active');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_mappings');
    }
};
