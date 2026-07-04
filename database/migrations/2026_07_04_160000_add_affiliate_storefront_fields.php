<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์รองรับ "หน้าร้าน affiliate หลายร้าน" (Lazada / AliExpress)
     *
     * - vendor_stores.store_type = ชนิดร้าน (vendor | lazada_affiliate | aliexpress_affiliate)
     * - products.is_affiliate = สินค้า affiliate (ปุ่มซื้อวิ่งไปลิงก์นอก ไม่เข้าตะกร้าเรา)
     * - products.affiliate_url = ลิงก์ค่าคอมปลายทาง (Lazada/AliExpress)
     * - products.external_platform/external_product_id = แหล่งที่มา
     * - products.shipping_speed = ป้ายบอกความเร็วส่ง (fast=ในไทยไว / slow=จีนช้า)
     *
     * ⚠️ ALTER TABLE → เช็ค hasColumn ทีละตัว
     */
    public function up(): void
    {
        if (Schema::hasTable('vendor_stores')) {
            Schema::table('vendor_stores', function (Blueprint $table) {
                if (! Schema::hasColumn('vendor_stores', 'store_type')) {
                    $table->string('store_type', 30)->default('vendor')->index()->after('status');
                }
                if (! Schema::hasColumn('vendor_stores', 'platform_slug')) {
                    $table->string('platform_slug', 30)->nullable()->after('store_type');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'is_affiliate')) {
                    $table->boolean('is_affiliate')->default(false)->index()->after('is_virtual');
                }
                if (! Schema::hasColumn('products', 'affiliate_url')) {
                    $table->text('affiliate_url')->nullable()->after('is_affiliate');
                }
                if (! Schema::hasColumn('products', 'external_platform')) {
                    $table->string('external_platform', 30)->nullable()->after('affiliate_url');
                }
                if (! Schema::hasColumn('products', 'external_product_id')) {
                    $table->string('external_product_id', 100)->nullable()->index()->after('external_platform');
                }
                if (! Schema::hasColumn('products', 'shipping_speed')) {
                    $table->string('shipping_speed', 20)->nullable()->after('external_product_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_stores')) {
            Schema::table('vendor_stores', function (Blueprint $table) {
                foreach (['store_type', 'platform_slug'] as $c) {
                    if (Schema::hasColumn('vendor_stores', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                foreach (['is_affiliate', 'affiliate_url', 'external_platform', 'external_product_id', 'shipping_speed'] as $c) {
                    if (Schema::hasColumn('products', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
