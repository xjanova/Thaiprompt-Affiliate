<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ "ดีลจริง" ให้ products + marketplace_products
     *
     * ⚠️ IMPORTANT: เป็นการ "เพิ่มคอลัมน์" ห้ามใช้ Schema::hasTable() + return
     *
     * ทำไมต้องมี — Flash Deals หน้าแรกเดิมคัดจาก
     *   compare_at_price > price  หรือ  is_featured
     * ซึ่งวัดจริงบนพร็อด (2026-09-09) ได้ 85 ชิ้น **เป็นของ seeder เดโมทั้ง 85 ชิ้น**
     * (สินค้า affiliate 2,104 ชิ้นไม่มี compare_at_price สักชิ้น เพราะ
     *  marketplace_products.original_price เป็น NULL ครบทั้ง 12,076 แถว —
     *  ฟีด affiliate ทางการคืนแค่ discountPrice ไม่มีราคาก่อนลด)
     *
     * คอลัมน์ใหม่ 2 ตัวคือ "ใบรับรอง" ว่าราคาลดนี้ยืนยันกับหน้า Lazada จริงเมื่อไหร่:
     *   - deal_verified_at      = เวลาที่ยืนยันครั้งล่าสุด (ตัวคัดหน้าแรกใช้ตัวนี้เป็นเกณฑ์ความสด)
     *   - deal_discount_percent = % ส่วนลดที่ Lazada แสดง ณ เวลานั้น (ใช้เรียงลำดับ)
     *
     * 🔑 ทำไมต้องมี deal_verified_at แยกจาก compare_at_price
     *    ถ้าคัดด้วย compare_at_price อย่างเดียว โปรที่จบไปแล้วจะค้างโชว์ตลอดกาล
     *    (ไม่มีอะไรบอกว่าราคานั้น "ยังจริงอยู่ไหม") ⇒ ของหมดโปรต้องร่วงจากหน้าแรกเอง
     *    โดยไม่ต้องไปลบ/แก้ราคาสินค้า = ไม่มีการเขียนทำลายข้อมูล
     */
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'deal_verified_at')) {
                    $table->timestamp('deal_verified_at')->nullable()->after('compare_at_price')
                        ->comment('ยืนยันราคาลดกับหน้า Lazada ครั้งล่าสุดเมื่อไหร่ (NULL = ไม่ใช่ดีลจริง)');
                }
                if (! Schema::hasColumn('products', 'deal_discount_percent')) {
                    $table->unsignedTinyInteger('deal_discount_percent')->nullable()->after('deal_verified_at')
                        ->comment('% ส่วนลดที่ยืนยันจากปลายทาง (ใช้เรียง Flash Deals)');
                }
            });

            // index ผสม — ตัวคัด Flash Deals กรองด้วย deal_verified_at แล้วเรียงด้วย deal_discount_percent
            if (Schema::hasColumn('products', 'deal_verified_at') && ! $this->indexExists('products', 'products_deal_idx')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->index(['deal_verified_at', 'deal_discount_percent'], 'products_deal_idx');
                });
            }
        }

        if (Schema::hasTable('marketplace_products')) {
            Schema::table('marketplace_products', function (Blueprint $table) {
                if (! Schema::hasColumn('marketplace_products', 'deal_verified_at')) {
                    $table->timestamp('deal_verified_at')->nullable()->after('original_price')
                        ->comment('ยืนยันราคาลดจากหน้ารายการ Lazada ครั้งล่าสุดเมื่อไหร่');
                }
                if (! Schema::hasColumn('marketplace_products', 'deal_discount_percent')) {
                    $table->unsignedTinyInteger('deal_discount_percent')->nullable()->after('deal_verified_at')
                        ->comment('% ส่วนลดที่ Lazada แสดง ณ เวลายืนยัน');
                }
            });
        }
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            if ($this->indexExists('products', 'products_deal_idx')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropIndex('products_deal_idx');
                });
            }
            Schema::table('products', function (Blueprint $table) {
                foreach (['deal_verified_at', 'deal_discount_percent'] as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('marketplace_products')) {
            Schema::table('marketplace_products', function (Blueprint $table) {
                foreach (['deal_verified_at', 'deal_discount_percent'] as $column) {
                    if (Schema::hasColumn('marketplace_products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    /**
     * เช็คว่ามี index ชื่อนี้อยู่แล้วหรือยัง (กันรันซ้ำแล้วพัง)
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            return collect(Schema::getIndexes($table))
                ->contains(fn ($i) => ($i['name'] ?? '') === $index);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
