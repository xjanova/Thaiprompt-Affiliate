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
        Schema::create('ticket_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Email notifications
            $table->boolean('email_on_new_ticket')->default(true);
            $table->boolean('email_on_reply')->default(true);
            $table->boolean('email_on_status_change')->default(true);
            $table->boolean('email_on_assignment')->default(true);
            $table->boolean('email_on_sla_breach')->default(true);

            // In-app notifications
            $table->boolean('notify_on_new_ticket')->default(true);
            $table->boolean('notify_on_reply')->default(true);
            $table->boolean('notify_on_status_change')->default(true);
            $table->boolean('notify_on_assignment')->default(true);

            // Digest settings
            $table->enum('digest_frequency', ['none', 'daily', 'weekly'])->default('none');
            $table->time('digest_time')->nullable()->default('09:00:00');

            $table->timestamps();

            $table->unique('user_id');
        });

        // Notification queue/log
        Schema::create('ticket_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->enum('type', [
                'new_ticket',
                'new_reply',
                'status_changed',
                'assigned',
                'sla_warning',
                'sla_breached',
                'ticket_closed',
                'rating_request'
            ]);

            $table->enum('channel', ['email', 'in_app', 'sms'])->default('email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['ticket_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_notification_logs');
        Schema::dropIfExists('ticket_notification_settings');
    }
};
