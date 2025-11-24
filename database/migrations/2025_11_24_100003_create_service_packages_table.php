<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_packages - แพคเกจบริการ
     *
     * แพคเกจที่รวมบริการ + สินค้า เช่น นวด 1 ชม. + โลชั่น + น้ำมันหอมระเหย
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_packages')) {
            return;
        }

        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();

            // เจ้าของ
            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('เจ้าของแพคเกจ');

            // ข้อมูลแพคเกจ
            $table->string('name', 255)->comment('ชื่อแพคเกจ');
            $table->string('slug', 255)->unique()->comment('URL slug');
            $table->text('description')->nullable()->comment('คำอธิบายแพคเกจ');
            $table->json('images')->nullable()->comment('รูปภาพแพคเกจ');

            // ราคา
            $table->decimal('total_price', 10, 2)->default(0)->comment('ราคารวมก่อนลด');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('ส่วนลดแพคเกจ (%)');
            $table->decimal('final_price', 10, 2)->default(0)->comment('ราคาสุทธิหลังหักส่วนลด');

            // การแสดงผล
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->boolean('is_featured')->default(false)->comment('แนะนำ');

            // ระยะเวลาโปรโมชั่น
            $table->dateTime('valid_from')->nullable()->comment('เริ่มใช้งานได้ตั้งแต่');
            $table->dateTime('valid_until')->nullable()->comment('ใช้งานได้ถึง');

            // สถิติ
            $table->integer('purchase_count')->default(0)->comment('จำนวนครั้งที่ซื้อ');
            $table->decimal('average_rating', 3, 2)->default(0)->comment('คะแนนเฉลี่ย');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug', 'service_pkg_slug_idx');
            $table->index(['owner_id', 'is_active'], 'service_pkg_owner_idx');
            $table->index('is_featured', 'service_pkg_featured_idx');
            $table->index(['valid_from', 'valid_until'], 'service_pkg_valid_idx');
        });
    }

    /**
     * ลบตาราง service_packages
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
