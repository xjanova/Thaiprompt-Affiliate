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
        Schema::create('nfc_readers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อเครื่องอ่านบัตร
            $table->string('reader_id')->unique(); // รหัสเครื่องอ่านบัตร (Device ID)
            $table->string('location')->nullable(); // ที่ตั้ง/สาขา
            $table->string('serial_number')->unique(); // หมายเลขเครื่อง
            $table->text('description')->nullable(); // คำอธิบาย
            $table->string('ip_address')->nullable(); // IP Address
            $table->string('mac_address')->nullable(); // MAC Address
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active'); // สถานะ
            $table->json('settings')->nullable(); // การตั้งค่าเพิ่มเติม
            $table->timestamp('last_heartbeat')->nullable(); // เช็คสถานะล่าสุด
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // ผู้สร้าง
            $table->timestamps();
            $table->softDeletes(); // สำหรับลบแบบ soft delete

            $table->index('reader_id');
            $table->index('status');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_readers');
    }
};
