<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_providers - ผู้ให้บริการ/พนักงาน
     *
     * เก็บข้อมูลผู้ให้บริการที่รับงาน พร้อมระบบแจ้งเตือนผ่าน LINE OA
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_providers')) {
            return;
        }

        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('บัญชี user ของ provider');

            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('เจ้าของระบบที่ provider สังกัด');

            // ข้อมูลส่วนตัว
            $table->string('name', 255)->comment('ชื่อ-นามสกุล provider');
            $table->string('phone', 20)->comment('เบอร์โทรศัพท์');
            $table->string('email', 255)->nullable()->comment('อีเมล');
            $table->string('avatar', 500)->nullable()->comment('รูปโปรไฟล์');
            $table->text('bio')->nullable()->comment('ประวัติ/แนะนำตัว');

            // สถิติและคะแนน
            $table->decimal('rating', 2, 1)->default(0)->comment('คะแนนเฉลี่ย 0-5');
            $table->integer('total_reviews')->default(0)->comment('จำนวนรีวิวทั้งหมด');
            $table->integer('total_bookings')->default(0)->comment('จำนวนงานทั้งหมด');
            $table->integer('total_accepted')->default(0)->comment('จำนวนงานที่รับ');
            $table->integer('total_rejected')->default(0)->comment('จำนวนงานที่ปฏิเสธ');
            $table->decimal('acceptance_rate', 5, 2)->default(100.00)->comment('อัตราการรับงาน (%)');
            $table->integer('average_response_minutes')->default(0)->comment('เวลาเฉลี่ยในการตอบกลับ (นาที)');

            // สถานะ
            $table->enum('status', ['available', 'busy', 'offline'])
                ->default('offline')
                ->comment('สถานะปัจจุบัน');
            $table->boolean('is_verified')->default(false)->comment('ยืนยันตัวตนแล้ว');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');

            // LINE Integration 🔔
            $table->string('line_user_id', 100)->nullable()->comment('LINE User ID สำหรับส่ง notification');
            $table->string('line_notify_token', 500)->nullable()->comment('LINE Notify Token');
            $table->dateTime('line_oa_linked_at')->nullable()->comment('เวลาที่เชื่อม LINE OA');

            // Notification Settings
            $table->json('notification_settings')->nullable()->comment('การตั้งค่าการแจ้งเตือน');
            // {"in_app": true, "line": true, "email": false, "new_booking": true}

            // Auto Accept Settings
            $table->boolean('auto_accept_bookings')->default(false)->comment('รับงานอัตโนมัติ');
            $table->decimal('auto_accept_max_distance_km', 5, 2)->nullable()->comment('รับงานไม่เกิน X km');
            $table->integer('auto_accept_max_daily_bookings')->nullable()->comment('รับงานสูงสุดต่อวัน');

            // ตำแหน่งปัจจุบัน (สำหรับคำนวณระยะทาง)
            $table->decimal('current_latitude', 10, 8)->nullable()->comment('ละติจูดปัจจุบัน');
            $table->decimal('current_longitude', 11, 8)->nullable()->comment('ลองจิจูดปัจจุบัน');
            $table->dateTime('last_location_update')->nullable()->comment('อัพเดทตำแหน่งล่าสุด');

            // Documents
            $table->string('id_card_number', 20)->nullable()->comment('เลขบัตรประชาชน');
            $table->string('id_card_image', 500)->nullable()->comment('รูปบัตรประชาชน');
            $table->string('license_image', 500)->nullable()->comment('รูปใบอนุญาต (ถ้ามี)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['owner_id', 'is_active'], 'service_prov_owner_idx');
            $table->index('status', 'service_prov_status_idx');
            $table->index('line_user_id', 'service_prov_line_idx');
            $table->index(['current_latitude', 'current_longitude'], 'service_prov_location_idx');
        });
    }

    /**
     * ลบตาราง service_providers
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
