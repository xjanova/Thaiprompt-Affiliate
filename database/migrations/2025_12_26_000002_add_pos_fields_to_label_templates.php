<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับเพิ่มฟิลด์ POS ลงในตาราง label_templates
 *
 * เพิ่มฟิลด์สำหรับรองรับ POS Label Printing
 *
 * @author Claude AI
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ POS ในตาราง label_templates
     */
    public function up(): void
    {
        Schema::table('label_templates', function (Blueprint $table) {
            // เช็คว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (! Schema::hasColumn('label_templates', 'is_pos_template')) {
                $table->boolean('is_pos_template')
                    ->default(false)
                    ->after('is_system')
                    ->comment('เป็น template สำหรับ POS');
            }

            if (! Schema::hasColumn('label_templates', 'pos_category')) {
                $table->enum('pos_category', [
                    'product_label',    // ฉลากสินค้า
                    'shipping_label',   // ใบปะสินค้า
                    'price_tag',        // ฉลากราคา
                    'barcode_only',     // barcode อย่างเดียว
                    'receipt',          // ใบเสร็จ
                    'other',             // อื่นๆ
                ])->nullable()
                    ->after('is_pos_template')
                    ->comment('ประเภท template POS');
            }

            if (! Schema::hasColumn('label_templates', 'printer_type')) {
                $table->enum('printer_type', [
                    'thermal_roll',     // เครื่องพิมพ์แบบม้วน
                    'thermal_label',    // เครื่องพิมพ์ฉลาก thermal
                    'inkjet',           // inkjet
                    'laser',            // laser
                    'any',               // ทุกประเภท
                ])->default('any')
                    ->after('pos_category')
                    ->comment('ประเภทเครื่องพิมพ์ที่รองรับ');
            }

            if (! Schema::hasColumn('label_templates', 'is_continuous_roll')) {
                $table->boolean('is_continuous_roll')
                    ->default(false)
                    ->after('printer_type')
                    ->comment('รองรับพิมพ์แบบม้วนต่อเนื่อง');
            }
        });

        // เพิ่ม index
        if (! Schema::hasColumn('label_templates', 'is_pos_template')) {
            Schema::table('label_templates', function (Blueprint $table) {
                $table->index('is_pos_template', 'label_tpl_pos_idx');
                $table->index('pos_category', 'label_tpl_pos_cat_idx');
            });
        }
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('label_templates', function (Blueprint $table) {
            // ลบ indexes ก่อน
            $table->dropIndex('label_tpl_pos_idx');
            $table->dropIndex('label_tpl_pos_cat_idx');

            // ลบคอลัมน์
            if (Schema::hasColumn('label_templates', 'is_continuous_roll')) {
                $table->dropColumn('is_continuous_roll');
            }
            if (Schema::hasColumn('label_templates', 'printer_type')) {
                $table->dropColumn('printer_type');
            }
            if (Schema::hasColumn('label_templates', 'pos_category')) {
                $table->dropColumn('pos_category');
            }
            if (Schema::hasColumn('label_templates', 'is_pos_template')) {
                $table->dropColumn('is_pos_template');
            }
        });
    }
};
