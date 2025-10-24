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
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Event Details
            $table->enum('event_type', [
                'login_attempt',
                'login_success',
                'login_failure',
                'logout',
                'password_change',
                'password_reset',
                'permission_change',
                'spam_detected',
                'rate_limit_exceeded',
                'suspicious_activity',
                'ip_blocked',
                'account_locked',
                'account_unlocked',
                '2fa_enabled',
                '2fa_disabled',
                '2fa_verified',
                'unauthorized_access',
                'data_export',
                'data_deletion',
                'employee_added',
                'employee_removed',
                'cloudflare_config_changed',
            ]);

            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');

            // Request Details
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();

            // Additional Context
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->text('description')->nullable();

            // Cloudflare Details
            $table->string('cf_ray')->nullable();
            $table->string('cf_connecting_ip', 45)->nullable();

            $table->timestamp('created_at')->nullable();

            // Indexes for efficient querying
            $table->index('user_id');
            $table->index('event_type');
            $table->index('severity');
            $table->index('ip_address');
            $table->index('created_at');
            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
