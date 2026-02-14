<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง page_views สำหรับเก็บสถิติการเข้าชมหน้าเว็บ
     *
     * ใช้สำหรับวิเคราะห์พฤติกรรมผู้ใช้:
     * - หน้าไหนมีคนเข้าชมมากที่สุด
     * - ผู้ใช้มาจากแหล่งใด (referrer)
     * - ใช้อุปกรณ์อะไร (device/browser)
     * - เวลาที่คนเข้าชมมากที่สุด
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('page_views')) {
            return;
        }

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();

            // ข้อมูลผู้ใช้
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('session_id', 100)->nullable()->index();

            // ข้อมูลหน้าที่เข้าชม
            $table->string('url', 2048); // URL ที่เข้าชม
            $table->string('path', 500)->index(); // Path เช่น /user/dashboard
            $table->string('route_name', 255)->nullable()->index(); // Route name เช่น user.dashboard
            $table->string('page_title', 500)->nullable(); // Title ของหน้า (ถ้ามี)

            // ข้อมูล HTTP
            $table->string('method', 10)->default('GET'); // GET, POST, etc
            $table->smallInteger('status_code')->default(200); // HTTP status code

            // ข้อมูล Referrer
            $table->string('referrer', 2048)->nullable(); // หน้าก่อนหน้า
            $table->string('referrer_domain', 255)->nullable()->index(); // Domain ของ referrer

            // ข้อมูลอุปกรณ์
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable()->index(); // desktop, mobile, tablet
            $table->string('browser', 100)->nullable(); // Chrome, Firefox, Safari
            $table->string('browser_version', 50)->nullable();
            $table->string('platform', 100)->nullable(); // Windows, macOS, iOS, Android
            $table->boolean('is_robot')->default(false); // เป็น bot หรือไม่

            // ข้อมูล Location (จาก IP)
            $table->string('country', 100)->nullable()->index();
            $table->string('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();

            // ข้อมูลการโหลดหน้า
            $table->integer('response_time_ms')->nullable(); // เวลาโหลดหน้า (ms)

            // UTM Tracking
            $table->string('utm_source', 255)->nullable()->index();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable()->index();
            $table->string('utm_term', 255)->nullable();
            $table->string('utm_content', 255)->nullable();

            // Timestamps
            $table->timestamp('viewed_at')->useCurrent()->index();
            $table->timestamps();

            // Indexes สำหรับ Query ที่ใช้บ่อย
            $table->index(['user_id', 'viewed_at']);
            $table->index(['path', 'viewed_at']);
            $table->index(['device_type', 'viewed_at']);
            $table->index(['country_code', 'viewed_at']);
        });
    }

    /**
     * ลบตาราง page_views
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
