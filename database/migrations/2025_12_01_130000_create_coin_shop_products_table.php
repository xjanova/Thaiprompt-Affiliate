<?php

/**
 * สร้างตาราง coin_shop_products
 *
 * สินค้าในร้านค้าที่ใช้ Video Coins ซื้อได้
 * รองรับสินค้าหลายประเภท: voucher, item, coupon, พิเศษ ฯลฯ
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง coin_shop_products
     */
    public function up(): void
    {
        if (Schema::hasTable('coin_shop_products')) {
            return;
        }

        Schema::create('coin_shop_products', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name');                              // ชื่อสินค้า
            $table->string('name_th')->nullable();               // ชื่อภาษาไทย
            $table->text('description')->nullable();             // รายละเอียด
            $table->text('description_th')->nullable();          // รายละเอียดภาษาไทย
            $table->string('thumbnail')->nullable();             // รูปสินค้า
            $table->string('icon')->nullable();                  // ไอคอน/Emoji

            // ประเภทสินค้า
            $table->enum('type', [
                'voucher',        // บัตรกำนัล/ส่วนลด
                'coupon',         // คูปอง
                'physical',       // สินค้าจริง (ต้องจัดส่ง)
                'digital',        // สินค้าดิจิทัล (ส่งทันที)
                'privilege',      // สิทธิพิเศษ
                'upgrade',        // อัพเกรด (Rank, VIP)
                'money',          // แลกเป็นเงิน
                'tpix',           // แลกเป็น TPIX
                'exp_boost',      // EXP boost
                'other',          // อื่นๆ
            ])->default('voucher');

            // หมวดหมู่
            $table->string('category')->nullable();              // หมวดหมู่สินค้า
            $table->json('tags')->nullable();                    // แท็ก

            // ราคา
            $table->decimal('price_coins', 15, 2);               // ราคา (Coins)
            $table->decimal('original_price_coins', 15, 2)->nullable();  // ราคาเดิม (ถ้ามีลด)
            $table->decimal('price_money', 12, 2)->nullable();   // มูลค่าเทียบเท่าเงิน

            // มูลค่าสินค้า (ใช้ตาม type)
            $table->decimal('value_amount', 15, 2)->nullable();  // มูลค่า/จำนวน
            $table->string('value_unit')->nullable();            // หน่วย (เช่น THB, %, days)
            $table->json('value_data')->nullable();              // ข้อมูลเพิ่มเติมของสินค้า

            // สต็อก
            $table->integer('stock_quantity')->nullable();        // จำนวนคงเหลือ (null = ไม่จำกัด)
            $table->integer('total_sold')->default(0);           // จำนวนที่ขายไปแล้ว
            $table->integer('max_per_user')->nullable();         // จำกัดต่อคน (null = ไม่จำกัด)
            $table->integer('max_per_day')->nullable();          // จำกัดต่อวัน

            // เงื่อนไข
            $table->integer('min_level')->default(1);            // Level ขั้นต่ำที่ซื้อได้
            $table->integer('min_rank_id')->nullable();          // Rank ขั้นต่ำ
            $table->boolean('require_kyc')->default(false);      // ต้อง KYC

            // ช่วงเวลาขาย
            $table->dateTime('sale_start_at')->nullable();       // เริ่มขาย
            $table->dateTime('sale_end_at')->nullable();         // สิ้นสุดการขาย
            $table->dateTime('use_expire_at')->nullable();       // หมดอายุการใช้งาน
            $table->integer('use_expire_days')->nullable();      // หมดอายุหลังจากซื้อกี่วัน

            // สถานะ
            $table->boolean('is_active')->default(true);         // เปิดขาย
            $table->boolean('is_featured')->default(false);      // แนะนำ
            $table->boolean('is_limited')->default(false);       // Limited Edition
            $table->boolean('is_new')->default(false);           // สินค้าใหม่
            $table->integer('priority')->default(0);             // ลำดับความสำคัญ
            $table->integer('sort_order')->default(0);           // ลำดับการแสดง

            // สถิติ
            $table->integer('view_count')->default(0);           // จำนวนคนดู

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index('type');
            $table->index('category');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('min_level');
            $table->index('price_coins');
            $table->index('sort_order');
            $table->index(['is_active', 'sale_start_at', 'sale_end_at'], 'csp_active_period_idx');
        });
    }

    /**
     * ลบตาราง
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_shop_products');
    }
};
