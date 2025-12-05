<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * แก้ไข seller_id ให้เป็น nullable สำหรับสินค้าร้านทางการ (Official Shop)
     *
     * สินค้าที่มี seller_id = null หมายถึงสินค้าของร้านทางการ
     * ไม่ได้เป็นของ seller คนใดคนหนึ่ง
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ลบ foreign key constraint เดิม
            $table->dropForeign(['seller_id']);

            // เปลี่ยน seller_id ให้เป็น nullable
            $table->unsignedBigInteger('seller_id')->nullable()->change();

            // สร้าง foreign key constraint ใหม่ที่รองรับ null
            $table->foreign('seller_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * ย้อนกลับการเปลี่ยนแปลง
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ลบ foreign key constraint
            $table->dropForeign(['seller_id']);

            // เปลี่ยน seller_id กลับเป็น NOT NULL
            // หมายเหตุ: จะต้องลบหรืออัพเดทข้อมูลที่มี seller_id = null ก่อน
            $table->unsignedBigInteger('seller_id')->nullable(false)->change();

            // สร้าง foreign key constraint ใหม่
            $table->foreign('seller_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
