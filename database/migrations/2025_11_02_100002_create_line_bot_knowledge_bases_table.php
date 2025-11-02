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
        Schema::create('line_bot_knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_setting_id')->constrained('line_bot_ai_settings')->cascadeOnDelete();
            $table->string('name')->comment('ชื่อแหล่งข้อมูล');
            $table->enum('type', ['url', 'internal', 'file', 'text'])->comment('ประเภทข้อมูล');
            $table->text('source')->nullable()->comment('URL หรือ path');
            $table->longText('content')->nullable()->comment('เนื้อหาที่สอน');
            $table->string('file_path')->nullable()->comment('path ของไฟล์ที่อัปโหลด');
            $table->string('file_type')->nullable()->comment('pdf, txt, docx, csv');
            $table->integer('priority')->default(0)->comment('ลำดับความสำคัญ');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable()->comment('ซิงค์ข้อมูลล่าสุด');
            $table->json('metadata')->nullable()->comment('ข้อมูลเพิ่มเติม');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_bot_knowledge_bases');
    }
};
