<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง software_downloads
     *
     * Tracking การดาวน์โหลดซอฟต์แวร์
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('software_downloads')) {
            return;
        }

        Schema::create('software_downloads', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('software_product_id')
                ->constrained('software_products')
                ->onDelete('cascade')
                ->comment('สินค้าซอฟต์แวร์');

            $table->foreignId('software_license_id')
                ->nullable()
                ->constrained('software_licenses')
                ->onDelete('set null')
                ->comment('License ที่ใช้ดาวน์โหลด');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ดาวน์โหลด');

            // Download Info
            $table->string('download_token', 64)->unique()->comment('Token สำหรับดาวน์โหลด (one-time/temporary)');
            $table->timestamp('token_expires_at')->nullable()->comment('Token หมดอายุ');
            $table->boolean('token_used')->default(false)->comment('ใช้ Token แล้ว');

            // Download Metadata
            $table->ipAddress('ip_address')->nullable()->comment('IP Address');
            $table->string('user_agent')->nullable()->comment('User Agent');
            $table->string('device_type', 50)->nullable()->comment('ประเภทอุปกรณ์ (desktop, mobile, tablet)');
            $table->string('browser', 50)->nullable()->comment('Browser');
            $table->string('os', 50)->nullable()->comment('Operating System');

            // File Info
            $table->bigInteger('file_size')->nullable()->comment('ขนาดไฟล์ (bytes)');
            $table->integer('download_duration')->nullable()->comment('ระยะเวลาดาวน์โหลด (seconds)');

            // Status
            $table->enum('status', ['pending', 'downloading', 'completed', 'failed', 'cancelled'])
                ->default('pending')
                ->comment('สถานะการดาวน์โหลด');

            $table->timestamp('started_at')->nullable()->comment('เริ่มดาวน์โหลด');
            $table->timestamp('completed_at')->nullable()->comment('ดาวน์โหลดเสร็จ');

            // Error Info
            $table->text('error_message')->nullable()->comment('ข้อความ Error (ถ้ามี)');

            $table->timestamps();

            // Indexes
            $table->index('download_token', 'dl_token_idx');
            $table->index('status', 'dl_status_idx');
            $table->index(['user_id', 'software_product_id'], 'dl_user_product_idx');
            $table->index('token_expires_at', 'dl_token_exp_idx');
            $table->index('created_at', 'dl_created_idx');
        });
    }

    /**
     * ลบตาราง software_downloads
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('software_downloads');
    }
};
