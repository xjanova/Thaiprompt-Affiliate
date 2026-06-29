<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์สำหรับ "Lazada Hub" (โหมด Hybrid: affiliate / ขายเองบวกกำไร)
 *
 * ⚠️ นี่คือ ALTER TABLE (เพิ่มคอลัมน์) → ห้ามใช้ Schema::hasTable() + return
 *    ต้องเช็คทีละคอลัมน์ด้วย Schema::hasColumn() (ตาม CLAUDE.md รูปแบบที่ 2)
 *
 * ต่อยอดตาราง marketplace_* เดิม (ไม่สร้างตารางใหม่):
 *  - marketplace_products: โหมดขาย + ราคาต้นทุน/ราคาเรา/มาร์กอัป + ที่มา + ลิงก์ affiliate + ผูกสินค้าจริง
 *  - marketplace_accounts: ชนิดโปรแกรม (Seller Open Platform vs Affiliate/Involve Asia)
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์
     */
    public function up(): void
    {
        // ── marketplace_products: โหมด/ราคา/ที่มา ──
        if (Schema::hasTable('marketplace_products')) {
            Schema::table('marketplace_products', function (Blueprint $table) {
                // โหมดทำเงินต่อสินค้า: affiliate = ส่งลิงก์ได้ค่าคอม / resell = ขายเองบวกกำไร
                if (! Schema::hasColumn('marketplace_products', 'fulfillment_mode')) {
                    $table->enum('fulfillment_mode', ['affiliate', 'resell'])
                        ->default('affiliate')
                        ->after('platform_id');
                }

                // ราคาต้นทุน (ราคาบน Lazada ที่เราใช้เป็นฐานต้นทุน) — แยกจาก price เดิมเพื่อความชัดเจน
                if (! Schema::hasColumn('marketplace_products', 'cost_price')) {
                    $table->decimal('cost_price', 15, 2)->nullable()->after('original_price');
                }

                // ราคาที่เราตั้งขาย (โหมด resell) — ว่าง = คำนวณจาก cost_price × markup
                if (! Schema::hasColumn('marketplace_products', 'selling_price')) {
                    $table->decimal('selling_price', 15, 2)->nullable()->after('cost_price');
                }

                // เปอร์เซ็นต์บวกกำไร (ว่าง = ใช้ค่า default จากตั้งค่า)
                if (! Schema::hasColumn('marketplace_products', 'markup_percent')) {
                    $table->decimal('markup_percent', 6, 2)->nullable()->after('selling_price');
                }

                // true = แอดมินตั้งราคาเอง (ห้าม sync ทับ) / false = ให้ระบบคำนวณจาก markup
                if (! Schema::hasColumn('marketplace_products', 'price_is_manual')) {
                    $table->boolean('price_is_manual')->default(false)->after('markup_percent');
                }

                // ที่มาของข้อมูล: api (Open Platform) / scrape (PDP) / manual
                if (! Schema::hasColumn('marketplace_products', 'source')) {
                    $table->string('source', 20)->default('api')->after('sync_status');
                }

                // ลิงก์หน้าสินค้า Lazada ต้นทาง (ใช้ตอน scrape + sync ราคา)
                if (! Schema::hasColumn('marketplace_products', 'source_url')) {
                    $table->text('source_url')->nullable()->after('source');
                }

                // ลิงก์ affiliate/deeplink ที่สร้างจากระบบพันธมิตร (เช่น Involve Asia)
                if (! Schema::hasColumn('marketplace_products', 'affiliate_deeplink')) {
                    $table->text('affiliate_deeplink')->nullable()->after('affiliate_url');
                }

                // ผูกกับสินค้าจริงในร้าน (products.id) เมื่อโหมด resell ถูกเผยแพร่ขายแล้ว
                // ไม่ใส่ FK constraint (กัน migration ล้มถ้าตาราง products ต่าง engine/charset)
                if (! Schema::hasColumn('marketplace_products', 'internal_product_id')) {
                    $table->unsignedBigInteger('internal_product_id')->nullable()->after('source_url');
                    $table->index('internal_product_id', 'mp_internal_product_idx');
                }

                // เวลาเผยแพร่ขึ้นร้าน (โหมด resell)
                if (! Schema::hasColumn('marketplace_products', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('internal_product_id');
                }

                // index ช่วยกรองตามโหมด
                if (! $this->indexExists('marketplace_products', 'mp_fulfillment_mode_idx')) {
                    $table->index('fulfillment_mode', 'mp_fulfillment_mode_idx');
                }
            });
        }

        // ── marketplace_accounts: ชนิดโปรแกรม ──
        if (Schema::hasTable('marketplace_accounts')) {
            Schema::table('marketplace_accounts', function (Blueprint $table) {
                // seller = Lazada Open Platform (ดึงสินค้า/ราคา/สต็อก/ออเดอร์)
                // affiliate = ระบบพันธมิตร (เช่น Involve Asia — เก็บคีย์ใน additional_credentials)
                if (! Schema::hasColumn('marketplace_accounts', 'program_type')) {
                    $table->string('program_type', 20)->default('seller')->after('platform_id');
                }
            });
        }
    }

    /**
     * ย้อนคอลัมน์
     */
    public function down(): void
    {
        if (Schema::hasTable('marketplace_products')) {
            Schema::table('marketplace_products', function (Blueprint $table) {
                foreach ([
                    'mp_fulfillment_mode_idx',
                    'mp_internal_product_idx',
                ] as $idx) {
                    if ($this->indexExists('marketplace_products', $idx)) {
                        $table->dropIndex($idx);
                    }
                }

                foreach ([
                    'fulfillment_mode', 'cost_price', 'selling_price', 'markup_percent',
                    'price_is_manual', 'source', 'source_url', 'affiliate_deeplink',
                    'internal_product_id', 'published_at',
                ] as $col) {
                    if (Schema::hasColumn('marketplace_products', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('marketplace_accounts') && Schema::hasColumn('marketplace_accounts', 'program_type')) {
            Schema::table('marketplace_accounts', function (Blueprint $table) {
                $table->dropColumn('program_type');
            });
        }
    }

    /**
     * เช็คว่ามี index ชื่อนี้อยู่แล้วหรือยัง (กันเพิ่ม/ลบซ้ำ)
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schema = $connection->getDatabaseName();

        $rows = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$schema, $table, $index]
        );

        return ! empty($rows);
    }
};
