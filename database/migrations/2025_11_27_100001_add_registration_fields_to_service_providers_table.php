<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม fields สำหรับการลงทะเบียนผู้ให้บริการ
 *
 * เพิ่ม: display_name, bank info, verification_status, description
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์สำหรับการลงทะเบียน Provider
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง service_providers มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('service_providers')) {
            return;
        }

        Schema::table('service_providers', function (Blueprint $table) {
            // ชื่อที่ใช้แสดง (แยกจาก name จริง)
            if (!Schema::hasColumn('service_providers', 'display_name')) {
                $table->string('display_name', 255)->nullable()->after('name')
                    ->comment('ชื่อที่ใช้แสดงต่อลูกค้า');
            }

            // คำอธิบาย/แนะนำตัว
            if (!Schema::hasColumn('service_providers', 'description')) {
                $table->text('description')->nullable()->after('bio')
                    ->comment('คำอธิบาย/แนะนำตัว');
            }

            // รูปโปรไฟล์
            if (!Schema::hasColumn('service_providers', 'profile_photo')) {
                $table->string('profile_photo', 500)->nullable()->after('avatar')
                    ->comment('รูปโปรไฟล์');
            }

            // รูปบัตรประชาชน (id_card_photo alias สำหรับ id_card_image)
            if (!Schema::hasColumn('service_providers', 'id_card_photo')) {
                $table->string('id_card_photo', 500)->nullable()->after('id_card_image')
                    ->comment('รูปบัตรประชาชน');
            }

            // สถานะการยืนยัน
            if (!Schema::hasColumn('service_providers', 'verification_status')) {
                $table->enum('verification_status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('is_verified')
                    ->comment('สถานะการยืนยัน: pending=รอตรวจสอบ, approved=อนุมัติ, rejected=ปฏิเสธ');
            }

            // ข้อมูลบัญชีธนาคาร
            if (!Schema::hasColumn('service_providers', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('license_image')
                    ->comment('ชื่อธนาคาร');
            }

            if (!Schema::hasColumn('service_providers', 'bank_account_number')) {
                $table->string('bank_account_number', 30)->nullable()->after('bank_name')
                    ->comment('เลขบัญชีธนาคาร');
            }

            if (!Schema::hasColumn('service_providers', 'bank_account_name')) {
                $table->string('bank_account_name', 255)->nullable()->after('bank_account_number')
                    ->comment('ชื่อบัญชีธนาคาร');
            }

            // Total earnings
            if (!Schema::hasColumn('service_providers', 'total_earnings')) {
                $table->decimal('total_earnings', 15, 2)->default(0)->after('total_rejected')
                    ->comment('รายได้สะสมทั้งหมด');
            }

            // Notification settings (email, line, sms)
            if (!Schema::hasColumn('service_providers', 'notification_email')) {
                $table->boolean('notification_email')->default(true)->after('notification_settings')
                    ->comment('แจ้งเตือนทางอีเมล');
            }

            if (!Schema::hasColumn('service_providers', 'notification_line')) {
                $table->boolean('notification_line')->default(true)->after('notification_email')
                    ->comment('แจ้งเตือนทาง LINE');
            }

            if (!Schema::hasColumn('service_providers', 'notification_sms')) {
                $table->boolean('notification_sms')->default(false)->after('notification_line')
                    ->comment('แจ้งเตือนทาง SMS');
            }

            // Max distance
            if (!Schema::hasColumn('service_providers', 'max_distance_km')) {
                $table->decimal('max_distance_km', 5, 2)->nullable()->after('auto_accept_max_daily_bookings')
                    ->comment('ระยะทางสูงสุดที่รับงาน (km)');
            }

            // Working hours
            if (!Schema::hasColumn('service_providers', 'working_hours_start')) {
                $table->time('working_hours_start')->nullable()->after('max_distance_km')
                    ->comment('เวลาเริ่มทำงาน');
            }

            if (!Schema::hasColumn('service_providers', 'working_hours_end')) {
                $table->time('working_hours_end')->nullable()->after('working_hours_start')
                    ->comment('เวลาเลิกทำงาน');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $columns = [
                'display_name',
                'description',
                'profile_photo',
                'id_card_photo',
                'verification_status',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'total_earnings',
                'notification_email',
                'notification_line',
                'notification_sms',
                'max_distance_km',
                'working_hours_start',
                'working_hours_end',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('service_providers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
