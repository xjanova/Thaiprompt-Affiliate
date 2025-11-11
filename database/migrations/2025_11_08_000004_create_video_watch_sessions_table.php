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
        Schema::create('video_watch_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('video_id')->constrained('video_contents')->onDelete('cascade');
            $table->string('session_token')->unique(); // Token สำหรับ session นี้
            $table->integer('start_position')->default(0); // เริ่มที่วินาทีที่เท่าไหร่
            $table->integer('end_position')->default(0); // จบที่วินาทีที่เท่าไหร่
            $table->integer('watch_duration')->default(0); // ระยะเวลาที่ดูจริง
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_valid')->default(true); // Session นี้ valid หรือไม่ (ตรวจจับการโกง)
            $table->json('heartbeats')->nullable(); // เก็บ heartbeat เพื่อตรวจสอบการดูจริง
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('video_id');
            $table->index('session_token');
            $table->index(['user_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_watch_sessions');
    }
};
