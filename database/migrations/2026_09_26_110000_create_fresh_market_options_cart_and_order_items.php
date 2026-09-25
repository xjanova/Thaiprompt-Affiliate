<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตัวเลือกสินค้า + ตะกร้าหลายรายการ + รายการในออเดอร์ ของตลาดสด (เปิดตัว 2026-09-26)
 *
 * 1. fresh_market_listings.track_stock      : ตัดสต็อกหรือไม่ (อาหารปรุงสด = ไม่ตัด) ค่าเริ่มต้น true
 * 2. fresh_market_listing_option_groups     : กลุ่มตัวเลือก (เลือกเนื้อสัตว์ / เพิ่มเติม) single|multi, บังคับ, min/max
 * 3. fresh_market_listing_options           : ตัวเลือก (หมูสับ +0, กุ้ง +20) ราคาเพิ่ม/รูป/เปิดขาย
 * 4. fresh_market_cart_items                : ตะกร้าฝั่งเซิร์ฟเวอร์ (แยกตามร้าน)
 * 5. fresh_market_order_items               : รายการสินค้าในออเดอร์ (snapshot ชื่อ/ตัวเลือก/ราคา ตอนสั่ง)
 *
 * ⚠️ idempotent: สร้างตารางเฉพาะที่ยังไม่มี, เพิ่มคอลัมน์เฉพาะที่ยังไม่มี
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        // 1) ตัดสต็อกหรือไม่ (ALTER TABLE → เช็คทีละคอลัมน์ ห้าม hasTable + return)
        if (Schema::hasTable('fresh_market_listings')) {
            Schema::table('fresh_market_listings', function (Blueprint $table) {
                $this->safeAddColumn($table, 'fresh_market_listings', 'track_stock', function ($table) {
                    $table->boolean('track_stock')->default(true)->after('quantity_available')
                        ->comment('true = ตัดสต็อกตอนสั่ง, false = ทำตามสั่ง/ไม่จำกัดจำนวน');
                });
            });
        }

        // 2) กลุ่มตัวเลือก
        $this->safeCreateTable('fresh_market_listing_option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('fresh_market_listings')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('selection_type', 10)->default('single')->comment('single|multi');
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('min_select')->default(0);
            $table->unsignedTinyInteger('max_select')->nullable()->comment('null = ไม่จำกัด (เฉพาะ multi)');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'sort_order'], 'fm_opt_group_listing_idx');
        });

        // 3) ตัวเลือก
        $this->safeCreateTable('fresh_market_listing_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('fresh_market_listing_option_groups')->onDelete('cascade');
            // เก็บ listing_id ซ้ำไว้ตรวจ "ตัวเลือกนี้เป็นของสินค้านี้จริง" ได้ในคำสั่งเดียว
            $table->foreignId('listing_id')->constrained('fresh_market_listings')->onDelete('cascade');
            $table->string('name', 100);
            $table->decimal('price_delta', 10, 2)->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'sort_order'], 'fm_opt_group_sort_idx');
            $table->index('listing_id', 'fm_opt_listing_idx');
        });

        // 4) ตะกร้า
        $this->safeCreateTable('fresh_market_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('fresh_market_sellers')->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('fresh_market_listings')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->json('option_ids')->nullable();
            // คีย์รวมรายการซ้ำ (listing + ตัวเลือกเรียงแล้ว + โน้ต) → กดเพิ่มของเดิมซ้ำ = เพิ่มจำนวน
            $table->string('line_key', 64);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'seller_id'], 'fm_cart_user_seller_idx');
            $table->index(['user_id', 'line_key'], 'fm_cart_user_line_idx');
        });

        // 5) รายการในออเดอร์
        $this->safeCreateTable('fresh_market_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('fresh_market_orders')->onDelete('cascade');
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->string('title');
            $table->string('unit', 30)->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('base_price', 10, 2)->comment('ราคาสินค้าตอนสั่ง (ยังไม่รวมตัวเลือก)');
            $table->decimal('options_price', 10, 2)->default(0)->comment('ราคาตัวเลือกรวมต่อชิ้น');
            $table->decimal('unit_price', 10, 2)->comment('ราคาต่อชิ้นรวมตัวเลือก');
            $table->decimal('line_total', 12, 2);
            $table->json('selected_options')->nullable()->comment('snapshot [{group_id,group_name,option_id,name,price_delta}]');
            $table->string('note', 255)->nullable();
            $table->boolean('track_stock')->default(true);
            $table->boolean('stock_deducted')->default(false)->comment('ตัดสต็อกไปแล้ว → ยกเลิกต้องคืน');
            $table->decimal('gp_rate', 5, 2)->nullable();
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('cashback_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('listing_id', 'fm_order_item_listing_fk')
                ->references('id')
                ->on('fresh_market_listings')
                ->onDelete('set null');

            $table->index('order_id', 'fm_order_item_order_idx');
            $table->index('listing_id', 'fm_order_item_listing_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fresh_market_order_items');
        Schema::dropIfExists('fresh_market_cart_items');
        Schema::dropIfExists('fresh_market_listing_options');
        Schema::dropIfExists('fresh_market_listing_option_groups');

        $this->safeDropColumn('fresh_market_listings', 'track_stock');
    }
};
