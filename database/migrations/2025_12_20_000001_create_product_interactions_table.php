<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางสำหรับการโต้ตอบกับสินค้า
 * - product_likes: ถูกใจสินค้า
 * - product_favorites: รายการโปรด/wishlist
 * - product_views: ประวัติการดู
 * - store_followers: ติดตามร้านค้า
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตาราง product_likes - ถูกใจสินค้า
        if (! Schema::hasTable('product_likes')) {
            Schema::create('product_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->timestamps();

                // ป้องกันการกดถูกใจซ้ำ
                $table->unique(['user_id', 'product_id'], 'product_likes_unique');
                $table->index('product_id');
            });
        }

        // ตาราง product_favorites - รายการโปรด/wishlist
        if (! Schema::hasTable('product_favorites')) {
            Schema::create('product_favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('collection_name')->nullable()->comment('ชื่อ collection เช่น "อยากได้", "ซื้อภายหลัง"');
                $table->text('note')->nullable()->comment('โน้ตส่วนตัว');
                $table->boolean('notify_price_drop')->default(false)->comment('แจ้งเตือนเมื่อลดราคา');
                $table->decimal('target_price', 12, 2)->nullable()->comment('ราคาเป้าหมาย');
                $table->timestamps();

                $table->unique(['user_id', 'product_id'], 'product_favorites_unique');
                $table->index('product_id');
                $table->index(['user_id', 'collection_name']);
            });
        }

        // ตาราง product_views - ประวัติการดูสินค้า
        if (! Schema::hasTable('product_views')) {
            Schema::create('product_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('session_id', 100)->nullable()->comment('สำหรับ guest users');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('referrer', 500)->nullable();
                $table->integer('view_duration')->default(0)->comment('เวลาดูในวินาที');
                $table->timestamps();

                $table->index('product_id');
                $table->index(['user_id', 'product_id']);
                $table->index('session_id');
            });
        }

        // ตาราง store_followers - ติดตามร้านค้า
        if (! Schema::hasTable('store_followers')) {
            Schema::create('store_followers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');
                $table->boolean('notify_new_products')->default(true)->comment('แจ้งเตือนสินค้าใหม่');
                $table->boolean('notify_promotions')->default(true)->comment('แจ้งเตือนโปรโมชั่น');
                $table->boolean('notify_live')->default(false)->comment('แจ้งเตือนเมื่อ Live');
                $table->timestamps();

                $table->unique(['user_id', 'store_id'], 'store_followers_unique');
                $table->index('store_id');
            });
        }

        // เพิ่ม columns ใน products table
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0)->after('rating_count');
            }
            if (! Schema::hasColumn('products', 'favorites_count')) {
                $table->unsignedInteger('favorites_count')->default(0)->after('likes_count');
            }
        });

        // เพิ่ม columns ใน vendor_stores table
        Schema::table('vendor_stores', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_stores', 'followers_count')) {
                $table->unsignedInteger('followers_count')->default(0)->after('rating_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_likes');
        Schema::dropIfExists('product_favorites');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('store_followers');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'likes_count')) {
                $table->dropColumn('likes_count');
            }
            if (Schema::hasColumn('products', 'favorites_count')) {
                $table->dropColumn('favorites_count');
            }
        });

        Schema::table('vendor_stores', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_stores', 'followers_count')) {
                $table->dropColumn('followers_count');
            }
        });
    }
};
