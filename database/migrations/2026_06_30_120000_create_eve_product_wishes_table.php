<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เก็บ "ของที่ลูกค้าอยากได้แต่ยังไม่มีในร้าน" — จากการคุยกับน้อง Eve
 *
 * เมื่อ Eve ค้นใน catalog เราแล้วไม่เจอ → บันทึก wish ไว้
 * ระบบ/แอดมิน (Phase 4 — affiliate auto-import) จะมาดึงสินค้าตาม wish เหล่านี้ทีหลัง
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eve_product_wishes')) {
            return;
        }

        Schema::create('eve_product_wishes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // ลูกค้า (ถ้าล็อกอิน)
            $table->string('query', 255);                               // คำที่ลูกค้าอยากได้
            $table->decimal('budget', 15, 2)->nullable();               // งบสูงสุด (ถ้าระบุ)
            $table->integer('results_found')->default(0);               // เจอกี่รายการตอนค้น (0 = ไม่เจอ)
            $table->string('status', 20)->default('pending');           // pending|searching|fulfilled|none
            $table->string('source', 30)->default('eve_chat');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eve_product_wishes');
    }
};
