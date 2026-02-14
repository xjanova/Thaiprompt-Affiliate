<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ rating, review, rated_at ในตาราง service_bookings
     *
     * สำหรับเก็บรีวิวและคะแนนโดยตรงในตารางการจอง
     * เพิ่มเติมจากตาราง service_reviews ที่เก็บรายละเอียดเพิ่มเติม
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง service_bookings มีอยู่แล้วหรือไม่
        if (! Schema::hasTable('service_bookings')) {
            return;
        }

        Schema::table('service_bookings', function (Blueprint $table) {
            // คะแนนและรีวิวจากลูกค้า
            if (! Schema::hasColumn('service_bookings', 'rating')) {
                $table->tinyInteger('rating')->nullable()->comment('คะแนน 1-5 ดาว');
            }

            if (! Schema::hasColumn('service_bookings', 'review')) {
                $table->text('review')->nullable()->comment('ความคิดเห็นจากลูกค้า');
            }

            if (! Schema::hasColumn('service_bookings', 'rated_at')) {
                $table->dateTime('rated_at')->nullable()->comment('เวลาที่ให้คะแนน');
            }

            // เพิ่ม final_price ถ้ายังไม่มี (ใช้ใน analytics)
            if (! Schema::hasColumn('service_bookings', 'final_price')) {
                $table->decimal('final_price', 10, 2)->nullable()->comment('ราคาสุดท้ายหลังส่วนลด');
            }
        });

        // เพิ่ม index สำหรับ query รีวิว (ใช้ SafeMigration trait)
        $this->safeAddIndex('service_bookings', 'rating', 'service_booking_rating_idx');
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('service_bookings', 'rating')) {
                $table->dropColumn('rating');
            }
            if (Schema::hasColumn('service_bookings', 'review')) {
                $table->dropColumn('review');
            }
            if (Schema::hasColumn('service_bookings', 'rated_at')) {
                $table->dropColumn('rated_at');
            }
            if (Schema::hasColumn('service_bookings', 'final_price')) {
                $table->dropColumn('final_price');
            }
        });
    }
};
