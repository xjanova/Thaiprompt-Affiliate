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
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // 'wallet', 'withdrawal', 'deposit', 'transfer', 'system', etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data (transaction_id, amount, etc.)

            // Related records
            $table->string('notifiable_type')->nullable(); // Model class name
            $table->unsignedBigInteger('notifiable_id')->nullable(); // Model ID

            // Actions
            $table->string('action_url')->nullable(); // URL to redirect when clicked
            $table->string('action_text')->nullable(); // Button text (e.g., "View Transaction")

            // Icon and styling
            $table->string('icon')->default('bell'); // Icon class or emoji
            $table->string('color')->default('blue'); // Notification color theme

            // Priority and importance
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_important')->default(false);

            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();

            // Email/Push notification status
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->boolean('push_sent')->default(false);
            $table->timestamp('push_sent_at')->nullable();

            // Auto-expire notifications
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('user_id');
            $table->index('type');
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'is_archived']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
