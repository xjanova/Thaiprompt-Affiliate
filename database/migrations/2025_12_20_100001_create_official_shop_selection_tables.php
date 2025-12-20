<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางสำหรับระบบ Official Shop Selection
 *
 * ระบบนี้ใช้สำหรับ:
 * 1. AI คัดเลือกสินค้าจากร้านค้าต่างๆ เข้า Official Shop
 * 2. ระบบโปรโมทสินค้าใหม่ฟรี 7 วัน
 * 3. ระบบสินค้าขายดีรายเดือน
 * 4. ระบบเตือนก่อนถอดสินค้าจาก Official Shop
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // ตาราง 1: official_shop_products - สินค้าที่ถูก AI คัดเลือก
        if (!Schema::hasTable('official_shop_products')) {
            Schema::create('official_shop_products', function (Blueprint $table) {
                $table->id();

                // สินค้าต้นทาง
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');

                // ประเภทการเลือก
                $table->enum('selection_type', ['ai_featured', 'new_product', 'best_seller'])
                    ->default('ai_featured')
                    ->comment('ai_featured = AI คัดเลือก, new_product = สินค้าใหม่, best_seller = ขายดีรายเดือน');

                // คะแนน AI
                $table->decimal('ai_score', 5, 2)->default(0)->comment('คะแนน AI 0-100');
                $table->json('score_breakdown')->nullable()->comment('รายละเอียดการคำนวณคะแนน');

                // สถานะ
                $table->boolean('is_active')->default(true);
                $table->boolean('is_locked')->default(false)->comment('ล็อคไม่ให้แก้ไข/ลบ');

                // กรอบพิเศษ
                $table->string('badge_type', 30)->default('gold')->comment('ประเภทกรอบ: gold, new, bestseller');
                $table->string('badge_color', 7)->default('#FFD700');

                // วันที่
                $table->timestamp('selected_at')->useCurrent()->comment('วันที่ถูกเลือก');
                $table->timestamp('expires_at')->nullable()->comment('วันหมดอายุ (สำหรับ new_product 7 วัน)');
                $table->timestamp('removed_at')->nullable()->comment('วันที่ถูกถอด');

                // ข้อมูลเพิ่มเติม
                $table->string('removal_reason')->nullable()->comment('เหตุผลที่ถูกถอด');
                $table->unsignedInteger('display_order')->default(0);

                $table->timestamps();

                // Indexes
                $table->index(['selection_type', 'is_active'], 'osp_type_active_idx');
                $table->index(['store_id', 'selection_type'], 'osp_store_type_idx');
                $table->index('ai_score', 'osp_ai_score_idx');
                $table->index('expires_at', 'osp_expires_idx');

                // Unique: 1 product ใน 1 selection_type
                $table->unique(['product_id', 'selection_type'], 'osp_product_type_unique');
            });
        }

        // ตาราง 2: official_shop_warnings - แจ้งเตือนก่อนถอดสินค้า
        if (!Schema::hasTable('official_shop_warnings')) {
            Schema::create('official_shop_warnings', function (Blueprint $table) {
                $table->id();

                $table->foreignId('official_shop_product_id')
                    ->constrained('official_shop_products')
                    ->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');

                // สถานะการเตือน
                $table->enum('status', ['pending', 'improved', 'expired', 'removed'])
                    ->default('pending')
                    ->comment('pending = รอปรับปรุง, improved = ปรับปรุงแล้ว, expired = หมดเวลา, removed = ถูกถอดแล้ว');

                // ข้อมูลเตือน
                $table->decimal('current_score', 5, 2)->comment('คะแนนปัจจุบัน');
                $table->decimal('required_score', 5, 2)->comment('คะแนนขั้นต่ำที่ต้องการ');
                $table->text('warning_message')->nullable();

                // เวลา
                $table->timestamp('warned_at')->useCurrent();
                $table->timestamp('deadline_at')->comment('ต้องปรับปรุงภายในวันที่');
                $table->timestamp('resolved_at')->nullable();

                // การดำเนินการ
                $table->unsignedBigInteger('resolved_by')->nullable()->comment('Admin ที่ดำเนินการ');
                $table->text('resolution_note')->nullable();

                $table->timestamps();

                // Indexes
                $table->index(['status', 'deadline_at'], 'osw_status_deadline_idx');
                $table->index('store_id', 'osw_store_idx');
            });
        }

        // ตาราง 3: new_product_promotions - โปรโมทสินค้าใหม่ฟรี 7 วัน
        if (!Schema::hasTable('new_product_promotions')) {
            Schema::create('new_product_promotions', function (Blueprint $table) {
                $table->id();

                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
                $table->unsignedBigInteger('requested_by')->nullable()->comment('User ที่ขอโปรโมท');

                // สถานะ
                $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])
                    ->default('pending');

                // เวลาโปรโมท
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable()->comment('หมดอายุ 7 วัน');

                // ข้อจำกัด
                $table->boolean('is_locked')->default(false)->comment('ล็อคระหว่างโปรโมท');
                $table->text('lock_reason')->nullable();

                // สถิติ
                $table->unsignedInteger('views_during_promo')->default(0);
                $table->unsignedInteger('sales_during_promo')->default(0);

                $table->timestamps();

                // Indexes
                $table->index(['status', 'ends_at'], 'npp_status_ends_idx');
                $table->index(['store_id', 'status'], 'npp_store_status_idx');

                // Unique: 1 product โปรโมทได้ครั้งเดียว
                $table->unique('product_id', 'npp_product_unique');
            });
        }

        // ตาราง 4: best_seller_monthly - สินค้าขายดีรายเดือน
        if (!Schema::hasTable('best_seller_monthly')) {
            Schema::create('best_seller_monthly', function (Blueprint $table) {
                $table->id();

                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');

                // เดือน/ปี
                $table->unsignedTinyInteger('month')->comment('เดือน 1-12');
                $table->unsignedSmallInteger('year')->comment('ปี เช่น 2025');

                // สถิติ
                $table->unsignedInteger('sales_count')->default(0)->comment('ยอดขายในเดือน');
                $table->decimal('sales_amount', 12, 2)->default(0)->comment('ยอดเงินขายในเดือน');
                $table->unsignedInteger('rank_position')->default(0)->comment('อันดับในเดือน');

                // สถานะ
                $table->boolean('is_active')->default(true);
                $table->timestamp('calculated_at')->nullable()->comment('วันที่คำนวณ');

                $table->timestamps();

                // Indexes
                $table->index(['year', 'month', 'is_active'], 'bsm_year_month_idx');
                $table->index(['sales_count', 'year', 'month'], 'bsm_sales_idx');

                // Unique: 1 product ต่อ 1 เดือน
                $table->unique(['product_id', 'year', 'month'], 'bsm_product_period_unique');
            });
        }

        // ตาราง 5: official_shop_settings - การตั้งค่า
        if (!Schema::hasTable('official_shop_settings')) {
            Schema::create('official_shop_settings', function (Blueprint $table) {
                $table->id();

                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('type', 20)->default('string')->comment('string, integer, float, boolean, json');
                $table->text('description')->nullable();

                $table->timestamps();
            });
        }

        // ตาราง 6: store_promotion_cooldowns - ป้องกันการ spam โปรโมท
        if (!Schema::hasTable('store_promotion_cooldowns')) {
            Schema::create('store_promotion_cooldowns', function (Blueprint $table) {
                $table->id();

                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
                $table->string('promotion_type', 30)->comment('new_product, etc.');

                // Cooldown
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('available_at')->nullable()->comment('พร้อมใช้งานอีกครั้ง');
                $table->unsignedInteger('usage_count')->default(0)->comment('จำนวนครั้งที่ใช้');

                $table->timestamps();

                // Unique: 1 store 1 type
                $table->unique(['store_id', 'promotion_type'], 'spc_store_type_unique');
            });
        }

        // เพิ่มคอลัมน์ใน products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_in_official_shop')) {
                $table->boolean('is_in_official_shop')->default(false)
                    ->after('is_featured')
                    ->comment('อยู่ใน Official Shop');
            }

            if (!Schema::hasColumn('products', 'official_shop_badge')) {
                $table->string('official_shop_badge', 30)->nullable()
                    ->after('is_in_official_shop')
                    ->comment('ประเภทป้าย: gold_frame, new, bestseller');
            }

            if (!Schema::hasColumn('products', 'is_promotion_locked')) {
                $table->boolean('is_promotion_locked')->default(false)
                    ->after('official_shop_badge')
                    ->comment('ล็อคระหว่างโปรโมท');
            }

            if (!Schema::hasColumn('products', 'promotion_lock_until')) {
                $table->timestamp('promotion_lock_until')->nullable()
                    ->after('is_promotion_locked');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ลบคอลัมน์จาก products
        Schema::table('products', function (Blueprint $table) {
            $columns = ['is_in_official_shop', 'official_shop_badge', 'is_promotion_locked', 'promotion_lock_until'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // ลบตารางตามลำดับ dependency
        Schema::dropIfExists('store_promotion_cooldowns');
        Schema::dropIfExists('official_shop_settings');
        Schema::dropIfExists('best_seller_monthly');
        Schema::dropIfExists('new_product_promotions');
        Schema::dropIfExists('official_shop_warnings');
        Schema::dropIfExists('official_shop_products');
    }
};
