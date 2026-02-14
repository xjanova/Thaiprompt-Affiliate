<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง riders สำหรับเก็บข้อมูลไรเดอร์
 */
return new class extends Migration
{
    /**
     * สร้างตาราง riders
     */
    public function up(): void
    {
        if (Schema::hasTable('riders')) {
            return;
        }

        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // ข้อมูลส่วนตัว
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('id_card_number', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();

            // ข้อมูลยานพาหนะ
            $table->enum('vehicle_type', ['motorcycle', 'car', 'bicycle', 'walk'])
                ->default('motorcycle');
            $table->string('vehicle_plate', 20)->nullable();
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_color')->nullable();

            // เอกสาร
            $table->string('id_card_image')->nullable();
            $table->string('driver_license_image')->nullable();
            $table->string('vehicle_registration_image')->nullable();
            $table->string('profile_image')->nullable();

            // สถานะ
            $table->enum('status', [
                'pending',      // รอตรวจสอบ
                'approved',     // อนุมัติแล้ว
                'rejected',     // ถูกปฏิเสธ
                'suspended',    // ถูกระงับ
                'inactive',      // ไม่ใช้งาน
            ])->default('pending');
            $table->enum('availability', ['online', 'offline', 'busy'])
                ->default('offline');
            $table->text('rejection_reason')->nullable();

            // สิทธิ์การใช้งาน
            $table->boolean('gps_permission_granted')->default(false);
            $table->boolean('camera_permission_granted')->default(false);
            $table->boolean('microphone_permission_granted')->default(false);
            $table->boolean('notification_permission_granted')->default(false);
            $table->timestamp('permissions_granted_at')->nullable();

            // สถิติ
            $table->integer('total_jobs')->default(0);
            $table->integer('completed_jobs')->default(0);
            $table->integer('cancelled_jobs')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);

            // ตำแหน่งล่าสุด
            $table->decimal('last_latitude', 10, 8)->nullable();
            $table->decimal('last_longitude', 11, 8)->nullable();
            $table->timestamp('last_location_update')->nullable();

            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('availability');
            $table->index(['last_latitude', 'last_longitude']);
            $table->unique('user_id');
        });
    }

    /**
     * ลบตาราง riders
     */
    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
