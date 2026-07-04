<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์จาก Lazada Affiliate feed (Open API /marketing/product/feed)
     * ที่ marketplace_products เดิมยังไม่มี — กันข้อมูลจาก Lazada ตกหล่น
     *
     * ⚠️ ALTER TABLE (เพิ่มคอลัมน์) → เช็คทีละคอลัมน์ด้วย hasColumn (ห้าม hasTable + return)
     */
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_products')) {
            return;
        }

        Schema::table('marketplace_products', function (Blueprint $table) {
            // ผู้ขาย / แบรนด์ (Lazada ให้ id + name)
            if (! Schema::hasColumn('marketplace_products', 'seller_id')) {
                $table->string('seller_id', 50)->nullable()->after('brand');
            }
            if (! Schema::hasColumn('marketplace_products', 'seller_name')) {
                $table->string('seller_name', 200)->nullable()->after('seller_id');
            }
            if (! Schema::hasColumn('marketplace_products', 'brand_id')) {
                $table->string('brand_id', 50)->nullable()->after('brand');
            }

            // หมวดระดับ 1 (id) — feed คืนเป็นตัวเลข ไม่มีชื่อ → เก็บ id ไว้ก่อน (map ชื่อทีหลัง)
            if (! Schema::hasColumn('marketplace_products', 'category_l1_id')) {
                $table->string('category_l1_id', 50)->nullable()->index()->after('category');
            }

            // สถิติ/ยอดขาย 7 วัน + เรตติ้ง
            if (! Schema::hasColumn('marketplace_products', 'sales_7d')) {
                $table->integer('sales_7d')->default(0)->after('sales_count');
            }
            if (! Schema::hasColumn('marketplace_products', 'rating')) {
                $table->decimal('rating', 3, 2)->nullable();
            }
            if (! Schema::hasColumn('marketplace_products', 'review_count')) {
                $table->integer('review_count')->default(0);
            }

            // ค่าคอมแบบละเอียดของ Lazada affiliate (เก็บเป็นเศษส่วน เช่น 0.05 = 5%)
            if (! Schema::hasColumn('marketplace_products', 'hyper_commission_rate')) {
                $table->decimal('hyper_commission_rate', 8, 5)->nullable();
            }
            if (! Schema::hasColumn('marketplace_products', 'cps_commission_rate')) {
                $table->decimal('cps_commission_rate', 8, 5)->nullable();
            }
            if (! Schema::hasColumn('marketplace_products', 'cps_commission_amount')) {
                $table->decimal('cps_commission_amount', 15, 2)->nullable();
            }

            // ธง/สถานะ affiliate
            if (! Schema::hasColumn('marketplace_products', 'bonus_offer_flag')) {
                $table->boolean('bonus_offer_flag')->default(false);
            }
            if (! Schema::hasColumn('marketplace_products', 'can_get_link')) {
                $table->boolean('can_get_link')->default(true);
            }
            if (! Schema::hasColumn('marketplace_products', 'offer_type')) {
                $table->unsignedTinyInteger('offer_type')->default(1); // 1=Regular 2=MM 3=DM
            }

            // เวลาที่ดึงลิงก์ค่าคอมล่าสุด (ไว้ retry ลิงก์ที่ยังไม่มี)
            if (! Schema::hasColumn('marketplace_products', 'affiliate_link_fetched_at')) {
                $table->timestamp('affiliate_link_fetched_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_products')) {
            return;
        }

        Schema::table('marketplace_products', function (Blueprint $table) {
            foreach ([
                'seller_id', 'seller_name', 'brand_id', 'category_l1_id', 'sales_7d', 'rating',
                'review_count', 'hyper_commission_rate', 'cps_commission_rate', 'cps_commission_amount',
                'bonus_offer_flag', 'can_get_link', 'offer_type', 'affiliate_link_fetched_at',
            ] as $col) {
                if (Schema::hasColumn('marketplace_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
