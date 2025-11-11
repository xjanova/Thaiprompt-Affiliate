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
        Schema::create('ai_gen_quotas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Free quota settings
            $table->integer('free_image_daily')->default(0);
            $table->integer('free_image_monthly')->default(0);
            $table->integer('free_video_daily')->default(0);
            $table->integer('free_video_monthly')->default(0);

            // Role-based (null = applies to all)
            $table->string('role')->nullable(); // user, admin, null

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_quotas');
    }
};
