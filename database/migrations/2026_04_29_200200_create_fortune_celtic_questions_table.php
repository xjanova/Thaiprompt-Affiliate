<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เก็บประวัติคำถาม-คำตอบใน Celtic Cross reading (ทั้ง Q1, Q2, Q3)
     *
     * 1 row = 1 คำถาม
     * unique (fortune_reading_id, sequence) — กันบันทึกซ้ำ slot เดียวกัน
     *
     * Q1 (sequence=1) = full storytelling
     * Q2-3 (sequence=2,3) = follow-up no card explain
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_celtic_questions')) {
            return;
        }

        Schema::create('fortune_celtic_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fortune_reading_id')
                ->constrained('fortune_readings')
                ->onDelete('cascade');

            $table->unsignedTinyInteger('sequence')
                ->comment('ลำดับ 1=Q1 (full storytelling), 2,3=follow-up');

            $table->text('question')
                ->comment('คำถามที่ลูกค้าถาม');
            $table->text('response')
                ->nullable()
                ->comment('คำทำนายที่ AI ตอบ');

            // AI metadata (audit trail)
            $table->string('ai_provider', 50)->nullable();
            $table->string('ai_model', 100)->nullable();
            $table->unsignedInteger('ai_tokens_used')->default(0);
            $table->unsignedInteger('ai_response_time_ms')->default(0);

            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['fortune_reading_id', 'sequence'], 'fcq_reading_seq_unique');
            $table->index('answered_at', 'fcq_answered_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fortune_celtic_questions');
    }
};
