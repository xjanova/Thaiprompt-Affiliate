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
        Schema::create('bot_platform_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('platform_id')->constrained('bot_social_platforms')->onDelete('cascade');
            $table->string('account_name')->nullable();
            $table->string('account_id')->nullable(); // Platform-specific user ID
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('credentials')->nullable(); // Encrypted credentials
            $table->json('settings')->nullable(); // Platform-specific settings
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_reconnect')->default(true);
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_post_at')->nullable();
            $table->integer('total_posts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'platform_id', 'account_id'], 'unique_platform_connection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_platform_connections');
    }
};
