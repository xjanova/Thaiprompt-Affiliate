<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง lazada_category_map — แปลงหมวดของ Lazada เป็นหมวดบนเว็บเรา
     *
     * ปัญหาที่แก้:
     *   ฟีด Lazada คืน `categoryL1` เป็น **เลขของ Lazada** (3008, 42062201, 10100083 …)
     *   ส่วนหน้าร้านเราใช้ `product_categories` (electronics, beauty-and-personal-care, sai-mu …)
     *   ⇒ ไม่มีตัวแปลง = ของที่บอทหามาให้ลูกค้าจะกองรวมอยู่หมวด "สินค้า Lazada" หมดทุกชิ้น
     *
     * 🚨 ห้าม hardcode ในโค้ด: Lazada เพิ่ม/เปลี่ยนหมวดได้ตลอด และเลขที่ยังไม่รู้จักต้องให้
     *   owner จับคู่เองได้จากหลังบ้าน โดยไม่ต้อง deploy
     *
     * ค่าเริ่มต้น seed มาจาก **เสียงข้างมากของข้อมูลจริง** ที่มีอยู่แล้วใน marketplace_products
     * (ดู LazadaCategoryMapSeeder) ไม่ใช่การเดา — เลขที่ยังไม่มีในตาราง จะตกไปหมวด
     * `lazada-affiliate` แล้วขึ้นธงให้ owner มาจับคู่ทีหลัง
     */
    public function up(): void
    {
        if (Schema::hasTable('lazada_category_map')) {
            return;
        }

        Schema::create('lazada_category_map', function (Blueprint $table) {
            $table->id();

            // เก็บเป็น string เพราะเลขหมวด Lazada ยาวไม่แน่นอน (3008 ถึง 42062201)
            // และเคยเจอค่าที่ไม่ใช่ตัวเลขล้วนหลุดมาจากฟีด
            $table->string('lazada_category_l1', 32)->comment('เลขหมวดระดับ 1 ของ Lazada');
            $table->string('lazada_category_name', 191)->nullable()->comment('ชื่อหมวดฝั่ง Lazada (ถ้ารู้ — ไว้ให้คนอ่านออก)');

            $table->unsignedBigInteger('product_category_id')->comment('หมวดปลายทางบนเว็บเรา (product_categories.id)');

            $table->string('confidence', 16)->default('derived')
                ->comment('derived = เดาจากเสียงข้างมากของข้อมูลเดิม | manual = คนจับคู่เอง (เชื่อถือได้กว่า)');
            $table->unsignedInteger('sample_count')->default(0)->comment('เดาจากสินค้ากี่ชิ้น (ยิ่งมากยิ่งมั่นใจ)');

            $table->timestamps();

            $table->unique('lazada_category_l1', 'lazada_cat_map_l1_unique');
            $table->index('product_category_id', 'lazada_cat_map_target_idx');
        });
    }

    /**
     * ลบตาราง lazada_category_map
     */
    public function down(): void
    {
        Schema::dropIfExists('lazada_category_map');
    }
};
