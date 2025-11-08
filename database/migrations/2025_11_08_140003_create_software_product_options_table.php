<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('software_product_options')) {
            return;
        }

        Schema::create('software_product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_product_id')->constrained('software_products')->cascadeOnDelete();
            $table->string('name'); // เช่น "จำนวนผู้ใช้งาน", "โมดูล", "ฟีเจอร์เพิ่มเติม"
            $table->string('display_name'); // ชื่อที่แสดงบนหน้าเว็บ
            $table->text('description')->nullable();
            $table->enum('input_type', ['select', 'checkbox', 'radio', 'number', 'text'])->default('select');
            $table->boolean('is_required')->default(false);
            $table->boolean('allow_multiple')->default(false); // สำหรับ checkbox
            $table->json('validation_rules')->nullable(); // JSON validation rules
            $table->integer('min_selections')->nullable();
            $table->integer('max_selections')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('software_product_id');
            // Custom index name to avoid MySQL 64-char limit
            $table->index(['software_product_id', 'sort_order'], 'spo_sp_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_product_options');
    }
};
