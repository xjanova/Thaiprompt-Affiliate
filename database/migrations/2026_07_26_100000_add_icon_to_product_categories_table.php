<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ icon ให้ตาราง product_categories
     *
     * เดิมหน้าร้านต้องเดาไอคอนจากชื่อ/slug ด้วยการจับคู่แบบ substring
     * ทำให้ชนกันมั่ว (เช่น 'small-appliances' ไปโดน 'all', 'haircare' ไปโดน 'car')
     * คอลัมน์นี้ให้แอดมินกำหนดคลาส Font Awesome เองได้ตรงๆ เช่น 'fa-solid fa-shirt'
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
     * ใช้ SafeMigration::safeAddColumn() ซึ่งเช็ครายคอลัมน์ให้อยู่แล้ว
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $this->safeAddColumn($table, 'product_categories', 'icon', function (Blueprint $table) {
                $table->string('icon', 100)
                    ->nullable()
                    ->after('image_url')
                    ->comment('คลาสไอคอน Font Awesome ของหมวดหมู่ เช่น fa-solid fa-shirt');
            });
        });
    }

    /**
     * ลบคอลัมน์ icon ออกจากตาราง product_categories
     */
    public function down(): void
    {
        $this->safeDropColumn('product_categories', 'icon');
    }
};
