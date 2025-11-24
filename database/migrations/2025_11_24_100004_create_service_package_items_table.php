<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง service_package_items - รายการในแพคเกจ
     *
     * เก็บรายการบริการหรือสินค้าที่อยู่ในแพคเกจ
     * เช่น แพคเกจ "นวดผ่อนคลาย" ประกอบด้วย: บริการนวด + โลชั่น
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('service_package_items')) {
            return;
        }

        Schema::create('service_package_items', function (Blueprint $table) {
            $table->id();

            // แพคเกจที่เป็นของ
            $table->foreignId('package_id')
                ->constrained('service_packages')
                ->onDelete('cascade')
                ->comment('แพคเกจที่รายการนี้เป็นของ');

            // รายการ (service หรือ product)
            $table->enum('item_type', ['service', 'product'])->comment('ประเภทรายการ');
            $table->unsignedBigInteger('item_id')->comment('ID ของ service หรือ product');

            // จำนวนและราคา
            $table->integer('quantity')->default(1)->comment('จำนวน');
            $table->decimal('price', 10, 2)->default(0)->comment('ราคาของรายการนี้');

            // หมายเหตุ
            $table->text('notes')->nullable()->comment('หมายเหตุเพิ่มเติม');

            $table->timestamps();

            // Indexes
            $table->index(['package_id', 'item_type'], 'service_pkg_items_pkg_idx');
            $table->index(['item_type', 'item_id'], 'service_pkg_items_item_idx');
        });
    }

    /**
     * ลบตาราง service_package_items
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_package_items');
    }
};
