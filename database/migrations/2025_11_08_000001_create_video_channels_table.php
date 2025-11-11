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
        Schema::create('video_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('channel_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('reward_enabled')->default(true); // เปิด/ปิดให้รางวัล
            $table->decimal('base_coins_per_video', 10, 2)->default(0); // เหรียญพื้นฐานต่อวิดีโอ
            $table->integer('priority')->default(0); // ลำดับความสำคัญ
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('reward_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_channels');
    }
};
