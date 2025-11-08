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
        Schema::create('video_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->nullable()->constrained('video_channels')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url'); // URL ของวิดีโอ
            $table->string('video_type')->default('youtube'); // youtube, vimeo, direct, etc.
            $table->string('video_id')->nullable(); // ID จาก platform
            $table->string('thumbnail')->nullable();
            $table->integer('duration_seconds'); // ระยะเวลาวิดีโอ (วินาที)
            $table->integer('required_watch_seconds')->nullable(); // ต้องดูกี่วินาทีถึงจะได้รางวัล (null = ดูหมด)
            $table->integer('required_watch_percentage')->default(80); // ต้องดูกี่ % (80 = 80%)
            $table->decimal('reward_coins', 10, 2)->default(0); // เหรียญที่ได้
            $table->integer('reward_exp')->default(0); // ประสบการณ์ที่ได้
            $table->boolean('is_active')->default(true);
            $table->boolean('reward_enabled')->default(true);
            $table->integer('view_count')->default(0); // จำนวนผู้ดู
            $table->integer('completion_count')->default(0); // จำนวนผู้ดูจบ
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('channel_id');
            $table->index('is_active');
            $table->index('reward_enabled');
            $table->index('video_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_contents');
    }
};
