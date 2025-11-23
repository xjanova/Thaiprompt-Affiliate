<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง nfc_verification_logs
     *
     * เก็บ log การตรวจสอบบัตร NFC
     * - ตรวจสอบรหัสป้องกันปลอม
     * - ตรวจสอบ UID
     * - ตรวจสอบ Digital Signature
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('nfc_verification_logs')) {
            return;
        }

        Schema::create('nfc_verification_logs', function (Blueprint $table) {
            $table->id();

            // ข้อมูลบัตร
            $table->unsignedBigInteger('nfc_card_id')->nullable();
            $table->string('card_number');

            // ประเภทการตรวจสอบ
            $table->string('verification_type', 50);
            // - anti_counterfeit: ตรวจสอบรหัสป้องกันปลอม
            // - uid: ตรวจสอบ UID
            // - signature: ตรวจสอบ Digital Signature
            // - full: ตรวจสอบแบบเต็ม

            // ผลการตรวจสอบ
            $table->boolean('verified')->default(false);
            $table->boolean('signature_valid')->nullable();
            $table->boolean('uid_match')->nullable();

            // ข้อมูลการตรวจสอบ
            $table->text('verification_data')->nullable();
            $table->text('error_message')->nullable();

            // ข้อมูลการเข้าถึง
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 50)->nullable();

            // ข้อมูลสถานที่
            $table->string('country', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('nfc_card_id');
            $table->index('card_number');
            $table->index('verification_type');
            $table->index('verified');
            $table->index('created_at');
            $table->index(['nfc_card_id', 'verification_type']);
            $table->index(['card_number', 'created_at']);

            // Foreign Keys
            $table->foreign('nfc_card_id')
                ->references('id')
                ->on('nfc_cards')
                ->onDelete('set null');
        });
    }

    /**
     * ลบตาราง nfc_verification_logs
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_verification_logs');
    }
};
