<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางคูปองและระบบ Trophy ร้านค้า
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตาราง coupons - คูปองของผู้ใช้
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique()->comment('รหัสคูปอง');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('template_id')->nullable()->constrained('coupon_templates')->onDelete('set null');
                $table->foreignId('store_id')->nullable()->constrained('vendor_stores')->onDelete('cascade');

                // ประเภทส่วนลด
                $table->enum('discount_type', ['percentage', 'fixed', 'free_shipping'])->default('percentage');
                $table->decimal('discount_value', 12, 2)->default(0);
                $table->decimal('min_purchase', 12, 2)->default(0)->comment('ยอดซื้อขั้นต่ำ');
                $table->decimal('max_discount', 12, 2)->nullable()->comment('ส่วนลดสูงสุด');

                // ข้อจำกัด
                $table->integer('usage_limit')->nullable()->comment('จำนวนครั้งที่ใช้ได้');
                $table->integer('used_count')->default(0);
                $table->json('applicable_products')->nullable()->comment('รหัสสินค้าที่ใช้ได้');
                $table->json('applicable_categories')->nullable()->comment('หมวดหมู่ที่ใช้ได้');
                $table->json('excluded_products')->nullable()->comment('สินค้าที่ยกเว้น');

                // วันหมดอายุและสถานะ
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_public')->default(false)->comment('แสดงให้ทุกคนเห็น');

                // ข้อมูลเพิ่มเติม
                $table->string('name')->nullable()->comment('ชื่อคูปอง');
                $table->text('description')->nullable();
                $table->string('badge_color', 7)->nullable()->default('#10B981');
                $table->string('badge_icon')->nullable()->default('fa-tag');

                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index(['store_id', 'is_active']);
                $table->index('expires_at');
            });
        }

        // ตาราง coupon_usages - ประวัติการใช้คูปอง
        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('order_total', 12, 2)->default(0);
                $table->timestamps();

                $table->index(['coupon_id', 'user_id']);
            });
        }

        // ตาราง user_coupons - คูปองที่ผู้ใช้เก็บไว้
        if (!Schema::hasTable('user_coupons')) {
            Schema::create('user_coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('coupon_id')->constrained('coupons')->onDelete('cascade');
                $table->boolean('is_used')->default(false);
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'coupon_id']);
            });
        }

        // ตาราง store_trophies - Trophy สำหรับร้านค้า
        if (!Schema::hasTable('store_trophies')) {
            Schema::create('store_trophies', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('ชื่อ Trophy');
                $table->string('name_th')->comment('ชื่อภาษาไทย');
                $table->text('description')->nullable();
                $table->text('description_th')->nullable();

                // ไอคอนและสี
                $table->string('icon')->default('fa-trophy')->comment('FontAwesome icon');
                $table->string('icon_color', 7)->default('#FFD700');
                $table->string('badge_color', 7)->default('#FFD700');
                $table->string('badge_gradient_from', 7)->nullable();
                $table->string('badge_gradient_to', 7)->nullable();
                $table->string('image_url')->nullable()->comment('รูปภาพ Trophy');

                // เงื่อนไข
                $table->string('requirement_type')->comment('sales_count, order_count, rating, followers, products, revenue');
                $table->decimal('requirement_value', 15, 2)->default(0);
                $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('bronze');
                $table->integer('points')->default(0)->comment('คะแนนที่ได้รับ');

                // สถานะ
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->boolean('is_premium')->default(false)->comment('Trophy สำหรับร้าน Premium');

                $table->timestamps();

                $table->index(['requirement_type', 'is_active']);
                $table->index('tier');
            });
        }

        // ตาราง store_trophy_achievements - Trophy ที่ร้านค้าได้รับ
        if (!Schema::hasTable('store_trophy_achievements')) {
            Schema::create('store_trophy_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
                $table->foreignId('trophy_id')->constrained('store_trophies')->onDelete('cascade');
                $table->timestamp('achieved_at')->useCurrent();
                $table->json('snapshot')->nullable()->comment('สถิติ ณ เวลาที่ได้รับ');
                $table->boolean('is_displayed')->default(true)->comment('แสดงบนหน้าร้าน');
                $table->timestamps();

                $table->unique(['store_id', 'trophy_id']);
                $table->index('achieved_at');
            });
        }

        // ตาราง premium_stores - ร้านค้า Premium
        if (!Schema::hasTable('premium_stores')) {
            Schema::create('premium_stores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');

                // ข้อมูลการคัดเลือก
                $table->enum('selection_type', ['ai_selected', 'manual', 'promoted'])->default('ai_selected');
                $table->decimal('ai_score', 8, 4)->nullable()->comment('คะแนน AI');
                $table->json('ai_criteria')->nullable()->comment('เกณฑ์ที่ AI ใช้');

                // สถานะและระยะเวลา
                $table->enum('status', ['active', 'pending', 'expired', 'rejected'])->default('pending');
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

                // สถิติ
                $table->decimal('rating_snapshot', 3, 2)->nullable();
                $table->integer('sales_count_snapshot')->default(0);
                $table->integer('products_count_snapshot')->default(0);
                $table->integer('followers_count_snapshot')->default(0);

                // ตำแหน่งแสดงผล
                $table->integer('display_order')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->string('featured_badge')->nullable();
                $table->string('featured_label')->nullable();

                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->unique('store_id');
                $table->index(['status', 'display_order']);
                $table->index('ai_score');
            });
        }

        // เพิ่ม columns ใน vendor_stores
        Schema::table('vendor_stores', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_stores', 'trophy_points')) {
                $table->integer('trophy_points')->default(0)->after('followers_count');
            }
            if (!Schema::hasColumn('vendor_stores', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('trophy_points');
            }
            if (!Schema::hasColumn('vendor_stores', 'premium_since')) {
                $table->timestamp('premium_since')->nullable()->after('is_premium');
            }
            if (!Schema::hasColumn('vendor_stores', 'ai_score')) {
                $table->decimal('ai_score', 8, 4)->nullable()->after('premium_since');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('store_trophy_achievements');
        Schema::dropIfExists('store_trophies');
        Schema::dropIfExists('premium_stores');

        Schema::table('vendor_stores', function (Blueprint $table) {
            $columns = ['trophy_points', 'is_premium', 'premium_since', 'ai_score'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
