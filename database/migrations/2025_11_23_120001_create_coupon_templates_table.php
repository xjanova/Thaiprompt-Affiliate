<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง coupon_templates
     *
     * Template สำหรับสร้างคูปองส่วนลดอัตโนมัติ
     * ใช้ในระบบรางวัลการสมัครสมาชิก
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('coupon_templates')) {
            return;
        }

        Schema::create('coupon_templates', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name')->comment('ชื่อ Template');
            $table->text('description')->nullable()->comment('คำอธิบาย');

            // เจ้าของ Template
            $table->enum('owner_type', ['admin', 'seller'])
                ->default('admin')
                ->comment('เจ้าของ: admin=ใช้ได้ทุกร้าน, seller=ใช้ได้เฉพาะร้าน');
            $table->foreignId('seller_id')->nullable()
                ->constrained('users')->onDelete('cascade')
                ->comment('ID ของผู้ขาย (ถ้าเป็น seller)');

            // รูปแบบรหัสคูปอง
            $table->string('code_prefix')->default('SIGNUP')->comment('Prefix รหัส (เช่น SIGNUP, WELCOME)');
            $table->enum('code_format', ['random', 'sequential', 'custom'])
                ->default('random')
                ->comment('รูปแบบรหัส');
            $table->integer('code_length')->default(8)->comment('ความยาวรหัส');

            // ประเภทส่วนลด
            $table->enum('discount_type', ['percentage', 'fixed', 'free_shipping'])
                ->comment('ประเภท: percentage=%, fixed=จำนวนเงิน, free_shipping=ส่งฟรี');
            $table->decimal('discount_value', 10, 2)->default(0)->comment('มูลค่าส่วนลด');
            $table->decimal('min_purchase', 10, 2)->default(0)->comment('ยอดซื้อขั้นต่ำ');
            $table->decimal('max_discount', 10, 2)->nullable()->comment('ส่วนลดสูงสุด (สำหรับ %)');

            // การใช้งาน
            $table->integer('usage_limit')->default(1)->comment('จำนวนครั้งที่ใช้ได้ต่อคน');
            $table->integer('validity_days')->default(30)->comment('ระยะเวลาใช้งาน (วัน)');

            // ข้อจำกัด
            $table->json('applicable_products')->nullable()->comment('สินค้าที่ใช้ได้ (IDs)');
            $table->json('applicable_categories')->nullable()->comment('หมวดหมู่ที่ใช้ได้ (IDs)');
            $table->json('excluded_products')->nullable()->comment('สินค้าที่ใช้ไม่ได้ (IDs)');

            // สถานะ
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน?');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('owner_type');
            $table->index('seller_id');
            $table->index('is_active');
        });
    }

    /**
     * ลบตาราง coupon_templates
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_templates');
    }
};
