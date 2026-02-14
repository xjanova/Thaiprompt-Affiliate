<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตาราง store_ratings สำหรับให้คะแนนร้านค้า
 *
 * ระบบดาวร้าน (Store Rating) แยกจาก:
 * - ProductReview: รีวิวสินค้า
 * - StoreTrophy: รางวัลความสำเร็จ
 *
 * ใช้สำหรับ:
 * - ให้คะแนนบริการร้านค้าหลังรับสินค้า
 * - คำนวณ rating_average ของร้าน
 * - แสดงดาวร้านค้าใน Official Shop
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตาราง store_ratings - ให้คะแนนร้านค้า
        if (! Schema::hasTable('store_ratings')) {
            Schema::create('store_ratings', function (Blueprint $table) {
                $table->id();

                // ความสัมพันธ์
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');

                // คะแนน
                $table->unsignedTinyInteger('rating')->comment('คะแนน 1-5 ดาว');
                $table->unsignedTinyInteger('service_rating')->nullable()->comment('คะแนนบริการ 1-5');
                $table->unsignedTinyInteger('shipping_rating')->nullable()->comment('คะแนนจัดส่ง 1-5');
                $table->unsignedTinyInteger('communication_rating')->nullable()->comment('คะแนนการสื่อสาร 1-5');

                // รีวิว
                $table->text('comment')->nullable()->comment('ความคิดเห็น');
                $table->json('images')->nullable()->comment('รูปภาพประกอบ');

                // สถานะ
                $table->boolean('is_anonymous')->default(false)->comment('รีวิวแบบไม่ระบุชื่อ');
                $table->boolean('is_verified_purchase')->default(false)->comment('ซื้อจริง');
                $table->boolean('is_approved')->default(true)->comment('ผ่านการอนุมัติ');
                $table->unsignedInteger('helpful_count')->default(0)->comment('จำนวนคนที่พบว่ามีประโยชน์');

                // การตอบกลับของร้าน
                $table->text('seller_response')->nullable();
                $table->timestamp('seller_responded_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['store_id', 'rating'], 'sr_store_rating_idx');
                $table->index(['user_id', 'store_id'], 'sr_user_store_idx');
                $table->index('is_approved', 'sr_approved_idx');

                // Unique: 1 user สามารถให้คะแนนร้าน 1 ครั้งต่อ 1 order
                $table->unique(['user_id', 'store_id', 'order_id'], 'sr_user_store_order_unique');
            });
        }

        // เพิ่มคอลัมน์ใน vendor_stores สำหรับสถิติ
        Schema::table('vendor_stores', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_stores', 'service_rating_average')) {
                $table->decimal('service_rating_average', 3, 2)->default(0)->after('rating_average');
            }
            if (! Schema::hasColumn('vendor_stores', 'shipping_rating_average')) {
                $table->decimal('shipping_rating_average', 3, 2)->default(0)->after('service_rating_average');
            }
            if (! Schema::hasColumn('vendor_stores', 'communication_rating_average')) {
                $table->decimal('communication_rating_average', 3, 2)->default(0)->after('shipping_rating_average');
            }
            if (! Schema::hasColumn('vendor_stores', 'store_rating_count')) {
                $table->unsignedInteger('store_rating_count')->default(0)->after('communication_rating_average');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_stores', function (Blueprint $table) {
            $columns = ['service_rating_average', 'shipping_rating_average', 'communication_rating_average', 'store_rating_count'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('store_ratings');
    }
};
