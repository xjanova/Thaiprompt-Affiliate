<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง developer_profiles สำหรับข้อมูลนักพัฒนาซอฟต์แวร์
     *
     * ตารางนี้เก็บข้อมูล:
     * - ข้อมูลพื้นฐานของนักพัฒนา
     * - ข้อมูล KYC (Facebook, LINE, Phone, Company)
     * - สถานะการอนุมัติ
     * - การตั้งค่าค่าคอมมิชชั่น
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('developer_profiles')) {
            return;
        }

        Schema::create('developer_profiles', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์กับ User
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ผู้ใช้ที่เป็นนักพัฒนา');

            // ข้อมูลพื้นฐาน
            $table->string('display_name')->comment('ชื่อที่แสดง');
            $table->string('company_name')->nullable()->comment('ชื่อบริษัท (ถ้ามี)');
            $table->text('bio')->nullable()->comment('ประวัติ/คำอธิบาย');
            $table->string('website')->nullable()->comment('เว็บไซต์');
            $table->string('avatar_url')->nullable()->comment('รูปโปรไฟล์');

            // ข้อมูล KYC (Know Your Customer)
            $table->string('facebook_profile')->nullable()->comment('โปรไฟล์ Facebook');
            $table->string('line_id')->nullable()->comment('LINE ID');
            $table->string('phone')->nullable()->comment('เบอร์โทรศัพท์');
            $table->string('email')->comment('อีเมล');

            // ข้อมูลบริษัท (ถ้ามี)
            $table->string('company_registration_number')->nullable()->comment('เลขทะเบียนบริษัท');
            $table->string('tax_id')->nullable()->comment('เลขประจำตัวผู้เสียภาษี');
            $table->text('company_address')->nullable()->comment('ที่อยู่บริษัท');

            // เอกสาร KYC
            $table->string('id_card_image')->nullable()->comment('รูปบัตรประชาชน');
            $table->string('company_registration_doc')->nullable()->comment('เอกสารทะเบียนบริษัท');

            // สถานะการอนุมัติ
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])
                ->default('pending')
                ->comment('สถานะ: pending=รออนุมัติ, approved=อนุมัติแล้ว, rejected=ปฏิเสธ, suspended=ระงับ');
            $table->text('rejection_reason')->nullable()->comment('เหตุผลที่ปฏิเสธ');
            $table->timestamp('approved_at')->nullable()->comment('วันที่อนุมัติ');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ผู้อนุมัติ (Admin)');

            // การตั้งค่าค่าคอมมิชชั่น
            $table->decimal('commission_rate', 5, 2)
                ->default(70.00)
                ->comment('เปอร์เซ็นต์ค่าคอมมิชชั่นที่นักพัฒนาได้รับ (default: 70%)');

            // สถิติ
            $table->integer('total_products')->default(0)->comment('จำนวนสินค้าทั้งหมด');
            $table->integer('total_sales')->default(0)->comment('จำนวนการขายทั้งหมด');
            $table->decimal('total_earnings', 15, 2)->default(0)->comment('รายได้รวมทั้งหมด');

            // Flags
            $table->boolean('is_verified')->default(false)->comment('ยืนยัน KYC แล้ว');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique('user_id', 'dev_profile_user_unique');
            $table->index('status', 'dev_profile_status_idx');
            $table->index('is_verified', 'dev_profile_verified_idx');
            $table->index(['is_active', 'status'], 'dev_profile_active_status_idx');
        });
    }

    /**
     * ลบตาราง developer_profiles
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_profiles');
    }
};
