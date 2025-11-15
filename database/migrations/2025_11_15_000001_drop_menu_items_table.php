<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลบตาราง menu_items ที่ไม่ได้ใช้แล้ว
     *
     * V3 Menu System ใช้ config-based approach แทน database-driven
     * เมนูทั้งหมดอยู่ใน config/menus.php
     *
     * @return void
     */
    public function up(): void
    {
        // ลบตาราง menu_items ถ้ามี
        if (Schema::hasTable('menu_items')) {
            Schema::dropIfExists('menu_items');
        }
    }

    /**
     * Rollback: สร้างตารางกลับคืน (สำหรับ backward compatibility)
     *
     * @return void
     */
    public function down(): void
    {
        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->id();
                $table->string('menu_location')->default('header');
                $table->string('title');
                $table->string('url')->nullable();
                $table->string('route')->nullable();
                $table->string('target')->default('_self');
                $table->string('icon')->nullable();
                $table->integer('order')->default(0);
                $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
                $table->boolean('is_active')->default(true);
                $table->json('conditions')->nullable();
                $table->timestamps();

                $table->index(['menu_location', 'order']);
                $table->index('parent_id');
                $table->index('is_active');
            });
        }
    }
};
