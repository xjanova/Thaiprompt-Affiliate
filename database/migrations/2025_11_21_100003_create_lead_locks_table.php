<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง lead_locks
     *
     * ตารางนี้เก็บข้อมูลการล็อคผู้มุ่งหวัง (Lead Lock) 48 ชั่วโมง
     * เมื่อผู้มุ่งหวังเข้าลิงก์แม่ทีมคนใด จะถูกล็อคไว้ 48 ชม.
     * ถ้ามีคนอื่นส่งลิงก์ซ้อน จะให้สิทธิ์คนแรกที่ล็อคไว้
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
        if (!Schema::hasTable('users')) {
            throw new \Exception('Table "users" must exist before creating "lead_locks" table.');
        }

        // ตรวจสอบว่าตาราง mlm_members มีอยู่หรือไม่
        if (!Schema::hasTable('mlm_members')) {
            throw new \Exception('Table "mlm_members" must exist before creating "lead_locks" table.');
        }

        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('lead_locks')) {
            return;
        }

        Schema::create('lead_locks', function (Blueprint $table) {
            $table->id();

            // ระบุตัวตนของผู้เข้าชม (ใช้ fingerprint)
            $table->string('visitor_identifier')->comment('IP + Browser fingerprint hash');
            $table->string('ip_address', 45)->nullable()->comment('IP Address');
            $table->text('user_agent')->nullable()->comment('Browser User Agent');

            // แม่ทีมที่ล็อคไว้
            $table->foreignId('team_leader_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('แม่ทีมที่ล็อคไว้');

            // ถ้าสมัครแล้ว
            $table->foreignId('mlm_member_id')
                ->nullable()
                ->constrained('mlm_members')
                ->onDelete('set null')
                ->comment('MLM Member ถ้าสมัครแล้ว');

            // เวลา
            $table->timestamp('locked_at')->comment('เวลาที่ล็อค');
            $table->timestamp('expires_at')->comment('เวลาหมดอายุ (locked_at + 48 hours)');
            $table->timestamp('converted_at')->nullable()->comment('เวลาที่สมัครสำเร็จ');

            // สถานะ
            $table->enum('status', ['locked', 'expired', 'converted'])->default('locked')->comment('สถานะ');

            // ข้อมูลเพิ่มเติม
            $table->string('source_url')->nullable()->comment('URL ที่เข้ามา');
            $table->string('referrer_url')->nullable()->comment('URL ที่อ้างอิง');
            $table->json('utm_params')->nullable()->comment('UTM Parameters');

            $table->timestamps();

            // Indexes
            $table->index('visitor_identifier');
            $table->index('team_leader_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index(['visitor_identifier', 'status']);
            $table->index(['team_leader_id', 'status']);
        });
    }

    /**
     * ลบตาราง lead_locks
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_locks');
    }
};
