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
        Schema::create('ai_gen_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('THB');

            // Quota limits
            $table->integer('image_credits')->default(0); // จำนวน credits สำหรับภาพ
            $table->integer('video_credits')->default(0); // จำนวน credits สำหรับวีดีโอ

            // Duration
            $table->integer('duration_days')->nullable(); // null = lifetime
            $table->boolean('is_recurring')->default(false); // สำหรับ subscription แบบต่ออายุอัตโนมัติ
            $table->string('recurring_period')->nullable(); // monthly, yearly

            // Features
            $table->json('features')->nullable(); // array of features
            $table->json('provider_access')->nullable(); // array of provider IDs that can be used

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_packages');
    }
};
