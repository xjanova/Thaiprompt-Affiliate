<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง user_notification_tokens สำหรับเก็บ Push Token ของผู้ใช้
 */
return new class extends Migration
{
    /**
     * สร้างตาราง user_notification_tokens
     */
    public function up(): void
    {
        if (Schema::hasTable('user_notification_tokens')) {
            return;
        }

        Schema::create('user_notification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Push Token (Expo Push Token หรือ FCM Token)
            $table->string('token')->unique();
            $table->enum('provider', ['expo', 'fcm', 'apns'])->default('expo');

            // ข้อมูลอุปกรณ์
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->enum('platform', ['android', 'ios', 'web'])->default('android');
            $table->string('app_version')->nullable();

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * ลบตาราง user_notification_tokens
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_tokens');
    }
};
