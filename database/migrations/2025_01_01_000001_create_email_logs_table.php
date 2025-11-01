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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('provider')->default('smtp'); // smtp, gmail_api, ses, mailgun, etc.
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('cc')->nullable(); // JSON array
            $table->string('bcc')->nullable(); // JSON array
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('template_name')->nullable();
            $table->json('template_data')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced', 'complained', 'opened', 'clicked'])->default('pending');
            $table->string('message_id')->nullable()->unique();
            $table->string('provider_message_id')->nullable(); // ID from provider (Gmail, SES, etc.)
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index('provider');
            $table->index('to_email');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
