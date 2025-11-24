<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง ai_rental_cloud_providers
     *
     * เก็บข้อมูล Cloud GPU providers สำหรับ AI rental system
     * รองรับทั้ง free tier และ paid tier
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('ai_rental_cloud_providers')) {
            return;
        }

        Schema::create('ai_rental_cloud_providers', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name', 100)->comment('ชื่อ provider');
            $table->string('slug', 100)->unique()->comment('slug สำหรับ URL');
            $table->text('description')->nullable()->comment('คำอธิบาย provider');
            $table->string('logo_url')->nullable()->comment('URL logo');
            $table->string('website_url')->nullable()->comment('เว็บไซต์ provider');

            // ประเภทและราคา
            $table->enum('tier', ['free', 'paid', 'both'])->default('paid')
                ->comment('ประเภท: ฟรี, ต้องเสียเงิน, หรือทั้งสองแบบ');
            $table->boolean('has_free_tier')->default(false)
                ->comment('มี free tier หรือไม่');
            $table->decimal('min_price_per_hour', 10, 4)->nullable()
                ->comment('ราคาต่ำสุดต่อชั่วโมง (USD)');
            $table->decimal('max_price_per_hour', 10, 4)->nullable()
                ->comment('ราคาสูงสุดต่อชั่วโมง (USD)');

            // ข้อมูล GPU
            $table->json('gpu_types')->nullable()
                ->comment('ประเภท GPU ที่รองรับ (array)');
            $table->integer('free_gpu_hours')->nullable()
                ->comment('จำนวนชั่วโมง GPU ฟรี (ถ้ามี)');
            $table->integer('free_storage_gb')->nullable()
                ->comment('พื้นที่เก็บข้อมูลฟรี GB');

            // Features
            $table->boolean('supports_huggingface')->default(true)
                ->comment('รองรับ Hugging Face หรือไม่');
            $table->boolean('supports_docker')->default(false)
                ->comment('รองรับ Docker หรือไม่');
            $table->boolean('supports_jupyter')->default(false)
                ->comment('รองรับ Jupyter Notebook หรือไม่');
            $table->boolean('supports_api')->default(false)
                ->comment('รองรับ API หรือไม่');
            $table->boolean('supports_ssh')->default(false)
                ->comment('รองรับ SSH หรือไม่');

            // คุณภาพและความนิยม
            $table->integer('popularity_score')->default(0)
                ->comment('คะแนนความนิยม (0-100)');
            $table->decimal('user_rating', 3, 2)->nullable()
                ->comment('คะแนนจากผู้ใช้ (0-5)');
            $table->integer('total_reviews')->default(0)
                ->comment('จำนวนรีวิวทั้งหมด');

            // ข้อมูลการตั้งค่า
            $table->text('setup_guide_url')->nullable()
                ->comment('ลิงก์คู่มือการตั้งค่า');
            $table->json('setup_steps')->nullable()
                ->comment('ขั้นตอนการตั้งค่า (JSON)');
            $table->text('api_documentation_url')->nullable()
                ->comment('ลิงก์เอกสาร API');

            // ข้อจำกัดและข้อแม้
            $table->text('limitations')->nullable()
                ->comment('ข้อจำกัดต่างๆ');
            $table->text('requirements')->nullable()
                ->comment('ข้อกำหนดในการใช้งาน');

            // สถานะ
            $table->boolean('is_active')->default(true)
                ->comment('เปิดใช้งานหรือไม่');
            $table->boolean('is_recommended')->default(false)
                ->comment('แนะนำหรือไม่');
            $table->integer('display_order')->default(0)
                ->comment('ลำดับการแสดงผล');

            // ข้อมูลเพิ่มเติม
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม (JSON)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tier');
            $table->index('has_free_tier');
            $table->index('is_active');
            $table->index('is_recommended');
            $table->index('popularity_score');
            $table->index('display_order');
        });
    }

    /**
     * ลบตาราง ai_rental_cloud_providers
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_rental_cloud_providers');
    }
};
