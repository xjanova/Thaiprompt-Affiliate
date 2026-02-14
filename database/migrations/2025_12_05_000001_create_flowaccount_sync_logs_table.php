<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง accounting_flowaccount_sync_logs สำหรับเก็บ log การซิงค์
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือยัง
        if (Schema::hasTable('accounting_flowaccount_sync_logs')) {
            return;
        }

        Schema::create('accounting_flowaccount_sync_logs', function (Blueprint $table) {
            $table->id();

            // เชื่อมกับตาราง connection
            $table->foreignId('connection_id')
                ->constrained('accounting_flowaccount_connections')
                ->onDelete('cascade');

            // ประเภทข้อมูลที่ซิงค์
            $table->string('type', 50)->comment('ประเภท: contact, product, invoice, expense, etc.');

            // การดำเนินการ
            $table->string('action', 50)->comment('การดำเนินการ: create, update, delete, sync');

            // ID ของ record ในระบบ
            $table->unsignedBigInteger('record_id')->nullable()->comment('ID ของ record ในระบบ');

            // ID ใน FlowAccount
            $table->string('flowaccount_id', 100)->nullable()->comment('ID ใน FlowAccount');

            // สถานะ
            $table->boolean('success')->default(false)->comment('สำเร็จหรือไม่');

            // ข้อความ error
            $table->text('error_message')->nullable()->comment('ข้อความ error ถ้ามี');

            // ข้อมูล request (สำหรับ debug)
            $table->json('request_data')->nullable()->comment('ข้อมูลที่ส่งไป');

            // ข้อมูล response (สำหรับ debug)
            $table->json('response_data')->nullable()->comment('ข้อมูลที่ได้รับกลับมา');

            // เวลาที่ซิงค์
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Indexes สำหรับการค้นหา
            $table->index(['connection_id', 'type']);
            $table->index(['connection_id', 'success']);
            $table->index(['type', 'action']);
            $table->index('synced_at');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_flowaccount_sync_logs');
    }
};
