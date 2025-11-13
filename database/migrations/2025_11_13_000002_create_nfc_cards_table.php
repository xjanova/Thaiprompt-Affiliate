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
        Schema::create('nfc_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_number')->unique(); // หมายเลขบัตร (UID จาก NFC)
            $table->string('card_name')->nullable(); // ชื่อบัตร (สำหรับแสดงผล)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // ผู้ใช้ที่เชื่อมโยง

            // Two-way Encryption Data
            $table->text('encrypted_data'); // ข้อมูลที่เข้ารหัสบันทึกในบัตร
            $table->string('encryption_key_hash'); // Hash ของ encryption key สำหรับตรวจสอบ
            $table->string('card_signature'); // Digital signature ของบัตร
            $table->integer('encryption_version')->default(1); // Version ของการเข้ารหัส

            // Card Information
            $table->enum('card_type', ['standard', 'premium', 'vip'])->default('standard'); // ประเภทบัตร
            $table->decimal('balance', 15, 2)->default(0); // ยอดเงินในบัตร
            $table->decimal('credit_limit', 15, 2)->default(0); // วงเงินเครดิต

            // Status & Limits
            $table->enum('status', ['active', 'inactive', 'blocked', 'expired', 'pending'])->default('pending'); // สถานะบัตร
            $table->boolean('is_paired')->default(false); // จับคู่กับ user แล้วหรือยัง
            $table->timestamp('paired_at')->nullable(); // วันที่จับคู่
            $table->timestamp('activated_at')->nullable(); // วันที่เปิดใช้งาน
            $table->timestamp('expires_at')->nullable(); // วันหมดอายุ
            $table->timestamp('last_used_at')->nullable(); // ใช้งานล่าสุด

            // Security
            $table->integer('failed_attempts')->default(0); // จำนวนครั้งที่ใช้ผิด
            $table->timestamp('blocked_until')->nullable(); // ถูกล็อคจนถึง
            $table->string('blocked_reason')->nullable(); // เหตุผลที่ถูกล็อค
            $table->ipAddress('last_ip')->nullable(); // IP ที่ใช้ล่าสุด

            // Metadata
            $table->json('metadata')->nullable(); // ข้อมูลเพิ่มเติม
            $table->text('notes')->nullable(); // หมายเหตุ
            $table->foreignId('issued_by')->nullable()->constrained('users')->onDelete('set null'); // ผู้ออกบัตร
            $table->foreignId('paired_by')->nullable()->constrained('users')->onDelete('set null'); // ผู้จับคู่บัตร

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('card_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('is_paired');
            $table->index('card_type');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_cards');
    }
};
