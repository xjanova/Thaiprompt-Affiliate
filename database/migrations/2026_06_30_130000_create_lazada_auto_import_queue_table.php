<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * คิวนำเข้าสินค้าอัตโนมัติ (Lazada Hub)
 *
 * แอดมินวางลิงก์สินค้าที่อยากให้ระบบ "ค่อยๆ นำเข้า" + ตั้งช่วงราคา/โหมด
 * → cron (lazada-hub:auto-import) ดึงเข้า catalog ทีละ N รายการทุก 5 นาที จนครบ
 *   (กัน Lazada บล็อก + ไม่ต้องนั่งกดเอง)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lazada_auto_import_queue')) {
            return;
        }

        Schema::create('lazada_auto_import_queue', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->decimal('price_min', 15, 2)->nullable();
            $table->decimal('price_max', 15, 2)->nullable();
            $table->string('fulfillment_mode', 20)->nullable();         // affiliate|resell (ว่าง = ตามค่า default)
            $table->string('status', 20)->default('pending');           // pending|done|skipped|failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->unsignedBigInteger('marketplace_product_id')->nullable();
            $table->string('batch_label', 100)->nullable();             // ป้ายล็อต (ไว้จัดกลุ่ม)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lazada_auto_import_queue');
    }
};
