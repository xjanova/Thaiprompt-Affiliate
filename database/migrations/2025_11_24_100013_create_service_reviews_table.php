<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_reviews - รีวิวบริการ
     *
     * เก็บรีวิวและคะแนนจากลูกค้าหลังใช้บริการ
     * แต่ละการจอง review ได้เพียงครั้งเดียว (unique booking_id)
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_reviews')) {
            return;
        }

        Schema::create('service_reviews', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('booking_id')
                ->unique()
                ->constrained('service_bookings')
                ->onDelete('cascade')
                ->comment('การจอง (1 booking = 1 review)');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ลูกค้าที่รีวิว');

            $table->foreignId('provider_id')
                ->constrained('service_providers')
                ->onDelete('cascade')
                ->comment('ผู้ให้บริการที่ถูกรีวิว');

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->onDelete('set null')
                ->comment('บริการที่ใช้');

            // คะแนนและรีวิว
            $table->tinyInteger('rating')->comment('คะแนน 1-5 ดาว');
            $table->text('comment')->nullable()->comment('ความคิดเห็น');

            // คะแนนย่อย (optional)
            $table->tinyInteger('quality_rating')->nullable()->comment('คะแนนคุณภาพ 1-5');
            $table->tinyInteger('punctuality_rating')->nullable()->comment('คะแนนตรงเวลา 1-5');
            $table->tinyInteger('friendliness_rating')->nullable()->comment('คะแนนความเป็นมิตร 1-5');

            // รูปภาพรีวิว
            $table->json('images')->nullable()->comment('รูปภาพรีวิว (URLs)');

            // การยืนยัน
            $table->boolean('is_verified')->default(false)->comment('ยืนยันว่าใช้บริการจริง');
            $table->boolean('is_visible')->default(true)->comment('แสดงรีวิวหรือไม่');

            // การตอบกลับจาก provider
            $table->text('provider_response')->nullable()->comment('คำตอบจาก provider');
            $table->dateTime('responded_at')->nullable()->comment('เวลาที่ตอบกลับ');

            // สถิติ
            $table->integer('helpful_count')->default(0)->comment('จำนวนคนกด "มีประโยชน์"');
            $table->integer('report_count')->default(0)->comment('จำนวนคนรายงาน');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['provider_id', 'is_visible'], 'service_review_provider_idx');
            $table->index(['service_id', 'is_visible'], 'service_review_service_idx');
            $table->index('rating', 'service_review_rating_idx');
            $table->index(['is_verified', 'is_visible'], 'service_review_verified_idx');
        });
    }

    /**
     * ลบตาราง service_reviews
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_reviews');
    }
};
