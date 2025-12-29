<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง mobile_auth_tokens
     *
     * เก็บ tokens สำหรับ Web-Based Mobile Authentication
     * ใช้ PKCE (Proof Key for Code Exchange) เพื่อความปลอดภัย
     */
    public function up(): void
    {
        if (Schema::hasTable('mobile_auth_tokens')) {
            return;
        }

        Schema::create('mobile_auth_tokens', function (Blueprint $table) {
            $table->id();

            // Login token (สำหรับเปิด browser)
            $table->string('login_token', 64)->index();
            $table->timestamp('login_token_expires_at');

            // PKCE code challenge
            $table->string('code_challenge', 128);

            // Auth code (หลัง user login สำเร็จ)
            $table->string('auth_code', 64)->nullable()->index();
            $table->timestamp('auth_code_expires_at')->nullable();

            // State (CSRF protection)
            $table->string('state', 64)->index();

            // User (set หลัง login สำเร็จ)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            // Device info
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Usage tracking
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['login_token', 'state']);
            $table->index(['auth_code', 'state']);
            $table->index('device_id');
        });
    }

    /**
     * ลบตาราง mobile_auth_tokens
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_auth_tokens');
    }
};
