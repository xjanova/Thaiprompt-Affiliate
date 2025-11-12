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
        // LINE Signup Sessions - Track ongoing signup conversations
        Schema::create('line_signup_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id')->index();
            $table->string('session_token')->unique();
            $table->string('current_step')->default('welcome'); // welcome, name, email, phone, otp, password, referral, confirmation
            $table->json('collected_data')->nullable(); // Stores form data as it's collected
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->integer('otp_attempts')->default(0);
            $table->enum('status', ['active', 'completed', 'abandoned', 'expired'])->default('active');
            $table->string('language')->default('th');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('affiliate_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamps();

            $table->index(['line_user_id', 'status']);
            $table->index('session_token');
        });

        // LINE Signup Steps - Track individual step completions
        Schema::create('line_signup_step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('line_signup_sessions')->onDelete('cascade');
            $table->string('step_name');
            $table->enum('status', ['started', 'completed', 'skipped', 'failed'])->default('started');
            $table->json('step_data')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'step_name']);
        });

        // LINE Signup AI Conversations - Store AI chat history
        Schema::create('line_signup_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('line_signup_sessions')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system']); // user message, AI response, system prompt
            $table->text('message');
            $table->json('metadata')->nullable(); // Store intent, confidence, etc.
            $table->string('message_type')->default('text'); // text, flex, template, quick_reply
            $table->json('line_message_payload')->nullable(); // Original LINE message payload
            $table->timestamps();

            $table->index(['session_id', 'created_at']);
        });

        // LINE Signup Templates - Store reusable Flex Message templates
        Schema::create('line_signup_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique(); // welcome_message, name_input, otp_verify, etc.
            $table->string('template_name');
            $table->text('description')->nullable();
            $table->json('flex_message_json'); // Complete Flex Message JSON
            $table->json('variables')->nullable(); // List of variables that can be replaced
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('template_key');
        });

        // LINE Signup Analytics - Track signup funnel metrics
        Schema::create('line_signup_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('step_name');
            $table->integer('visitors')->default(0); // Users who reached this step
            $table->integer('completions')->default(0); // Users who completed this step
            $table->integer('drop_offs')->default(0); // Users who abandoned at this step
            $table->decimal('average_time_seconds', 10, 2)->default(0); // Average time spent on step
            $table->timestamps();

            $table->unique(['date', 'step_name']);
            $table->index('date');
        });

        // LINE Signup Rewards - Incentives for completing signup
        Schema::create('line_signup_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained('line_signup_sessions')->onDelete('cascade');
            $table->string('reward_type'); // points, coins, bonus, coupon
            $table->string('reward_name');
            $table->decimal('reward_amount', 15, 2)->default(0);
            $table->text('reward_description')->nullable();
            $table->enum('status', ['pending', 'granted', 'claimed', 'expired'])->default('pending');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // LINE Signup Invitations - Track referral invitations
        Schema::create('line_signup_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_user_id')->constrained('users')->onDelete('cascade');
            $table->string('invitation_token')->unique();
            $table->string('line_user_id')->nullable(); // Target LINE user if known
            $table->string('referral_code')->index();
            $table->integer('max_uses')->default(1);
            $table->integer('uses_count')->default(0);
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->json('metadata')->nullable(); // Campaign info, source, etc.
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('first_used_at')->nullable();
            $table->timestamps();

            $table->index(['invitation_token', 'status']);
            $table->index('referral_code');
        });

        // LINE Signup Webhook Logs - Debug webhook events
        Schema::create('line_signup_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id')->nullable();
            $table->string('event_type'); // message, follow, postback, etc.
            $table->json('webhook_payload');
            $table->json('response_payload')->nullable();
            $table->enum('processing_status', ['received', 'processing', 'completed', 'failed'])->default('received');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['line_user_id', 'created_at']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_signup_webhook_logs');
        Schema::dropIfExists('line_signup_invitations');
        Schema::dropIfExists('line_signup_rewards');
        Schema::dropIfExists('line_signup_analytics');
        Schema::dropIfExists('line_signup_templates');
        Schema::dropIfExists('line_signup_conversations');
        Schema::dropIfExists('line_signup_step_logs');
        Schema::dropIfExists('line_signup_sessions');
    }
};
