<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง recruit_page_visits
     *
     * ตารางนี้เก็บสถิติการเข้าชมหน้า Recruit แต่ละครั้ง
     * ใช้สำหรับวิเคราะห์การตลาด (Marketing Analytics)
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
        if (!Schema::hasTable('users')) {
            throw new \Exception('Table "users" must exist before creating "recruit_page_visits" table.');
        }

        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('recruit_page_visits')) {
            return;
        }

        Schema::create('recruit_page_visits', function (Blueprint $table) {
            $table->id();

            // แม่ทีม
            $table->foreignId('team_leader_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('แม่ทีมเจ้าของหน้า');

            // ผู้เข้าชม
            $table->string('visitor_identifier')->comment('IP + Browser fingerprint hash');
            $table->string('ip_address', 45)->nullable()->comment('IP Address');
            $table->text('user_agent')->nullable()->comment('Browser User Agent');

            // อุปกรณ์และระบบ
            $table->string('device_type', 20)->nullable()->comment('desktop/mobile/tablet');
            $table->string('browser', 50)->nullable()->comment('Browser name');
            $table->string('os', 50)->nullable()->comment('Operating system');
            $table->string('country', 2)->nullable()->comment('Country code');
            $table->string('city', 100)->nullable()->comment('City name');

            // Source tracking
            $table->string('source_url')->nullable()->comment('URL ที่เข้ามา');
            $table->string('referrer_url')->nullable()->comment('URL ที่อ้างอิง');
            $table->json('utm_params')->nullable()->comment('UTM Parameters');

            // เวลาและพฤติกรรม
            $table->timestamp('visited_at')->comment('เวลาที่เข้าชม');
            $table->integer('time_spent')->nullable()->comment('เวลาที่อยู่บนหน้า (วินาที)');
            $table->boolean('scrolled_to_bottom')->default(false)->comment('เลื่อนดูจนถึงท้ายหน้า');
            $table->boolean('clicked_register_button')->default(false)->comment('กดปุ่มสมัคร');

            // Session tracking
            $table->string('session_id')->nullable()->comment('Session ID');
            $table->boolean('is_unique_visit')->default(true)->comment('เป็น visit ใหม่หรือไม่');

            $table->timestamps();

            // Indexes
            $table->index('team_leader_id');
            $table->index('visitor_identifier');
            $table->index('visited_at');
            $table->index(['team_leader_id', 'visited_at']);
            $table->index(['visitor_identifier', 'team_leader_id']);
        });
    }

    /**
     * ลบตาราง recruit_page_visits
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('recruit_page_visits');
    }
};
