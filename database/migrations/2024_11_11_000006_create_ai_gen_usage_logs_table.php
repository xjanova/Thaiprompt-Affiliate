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
        Schema::create('ai_gen_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('ai_gen_providers')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('ai_gen_subscriptions')->onDelete('set null');

            // Generation details
            $table->string('generation_type'); // image, video
            $table->text('prompt');
            $table->json('parameters')->nullable(); // style, size, duration, etc.

            // Credits & Quota
            $table->integer('credits_used')->default(1);
            $table->boolean('is_free_quota')->default(false);

            // Result
            $table->string('status'); // pending, processing, completed, failed
            $table->text('result_url')->nullable();
            $table->json('result_data')->nullable();
            $table->text('error_message')->nullable();

            // Metadata
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['provider_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_gen_usage_logs');
    }
};
