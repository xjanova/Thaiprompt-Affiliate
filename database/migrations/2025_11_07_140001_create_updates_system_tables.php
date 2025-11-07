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
        // System updates table - เก็บข้อมูลเวอร์ชันที่มีให้อัพเดท
        Schema::create('system_updates', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique(); // เช่น "2.0.0"
            $table->string('version_name')->nullable(); // codename เช่น "Phoenix"
            $table->text('description')->nullable();
            $table->longText('changelog')->nullable(); // markdown format
            $table->enum('type', ['major', 'minor', 'patch', 'hotfix'])->default('minor');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');

            // Release information
            $table->boolean('is_pre_release')->default(false);
            $table->boolean('is_stable')->default(true);
            $table->dateTime('released_at')->nullable();

            // Requirements
            $table->string('min_php_version')->default('8.1.0');
            $table->string('min_mysql_version')->default('8.0.0');
            $table->json('required_extensions')->nullable(); // PHP extensions

            // Download/Repository info
            $table->string('download_url')->nullable();
            $table->string('repository_url')->nullable();
            $table->string('documentation_url')->nullable();

            // Update instructions
            $table->longText('pre_update_instructions')->nullable(); // คำแนะนำก่อนอัพเดท
            $table->longText('post_update_instructions')->nullable(); // คำแนะนำหลังอัพเดท
            $table->json('breaking_changes')->nullable(); // รายการการเปลี่ยนแปลงที่ไม่ backward compatible

            // Migration info
            $table->boolean('requires_migration')->default(false);
            $table->json('migration_files')->nullable(); // รายการไฟล์ migration ที่ต้องรัน

            // Statistics
            $table->integer('download_count')->default(0);
            $table->integer('install_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('version');
            $table->index('type');
            $table->index('priority');
            $table->index('released_at');
        });

        // Update logs table - เก็บประวัติการอัพเดทของแต่ละระบบ
        Schema::create('update_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_update_id')->nullable()->constrained()->nullOnDelete();

            $table->string('from_version'); // เวอร์ชันเดิม
            $table->string('to_version'); // เวอร์ชันใหม่

            $table->enum('status', [
                'pending',
                'downloading',
                'downloaded',
                'preparing',
                'backing_up',
                'migrating',
                'updating',
                'completed',
                'failed',
                'rolled_back'
            ])->default('pending');

            $table->text('message')->nullable();
            $table->longText('error_details')->nullable(); // Error stack trace if failed
            $table->json('migration_results')->nullable(); // ผลลัพธ์การ migrate

            // Backup information
            $table->string('backup_path')->nullable();
            $table->integer('backup_size')->nullable(); // in bytes

            // Timing
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds

            // User who initiated
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();

            // Auto update or manual
            $table->boolean('is_auto_update')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('from_version');
            $table->index('to_version');
            $table->index('started_at');
        });

        // Update notifications - สำหรับแจ้งเตือนเมื่อมีอัพเดทใหม่
        Schema::create('update_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_update_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->enum('type', ['new_version', 'critical_update', 'security_update', 'maintenance'])->default('new_version');
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->dateTime('dismissed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'is_dismissed']);
        });

        // Update settings - การตั้งค่าสำหรับระบบอัพเดท
        Schema::create('update_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_notifications');
        Schema::dropIfExists('update_logs');
        Schema::dropIfExists('update_settings');
        Schema::dropIfExists('system_updates');
    }
};
