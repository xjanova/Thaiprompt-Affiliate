<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🧾 บิลขายหน้าร้านที่เครื่อง POS อัปโหลดขึ้นมา (POST /api/pos/sync/orders)
 *
 * ทำไมไม่เก็บใน orders:
 *   - orders.user_id เป็น NOT NULL (ผู้ซื้อออนไลน์) แต่ลูกค้าหน้าร้านไม่มีบัญชี
 *   - orders ผูกกับระบบเงิน (OrderObserver แบ่งเงินร้าน/GP/เงินคืน เมื่อ paid หรือ delivered)
 *     บิลหน้าร้านรับเงินสด/QR นอกระบบ — ถ้าเข้า orders จะกลายเป็นรายได้ร้านที่ไม่มีเงินจริงรองรับ
 *   - pos_transactions ต้องมี pos_device_id + pos_session_id (ระบบ POS บนเว็บอีกชุด) ซึ่งเครื่อง POS แบบ terminal ไม่มี
 * ⇒ ตารางบันทึกอย่างเดียว ไม่แตะเงิน ไม่แตะสต็อก (POS จัดการสต็อกหน้าร้านเอง)
 *
 * (pos_terminal_id, local_id) ไม่ซ้ำ → POS อัปโหลดซ้ำได้โดยไม่เกิดบิลซ้ำ
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง pos_terminal_sales
     */
    public function up(): void
    {
        // ✅ CREATE TABLE: มีตารางแล้วข้ามได้
        if (Schema::hasTable('pos_terminal_sales')) {
            return;
        }

        Schema::create('pos_terminal_sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pos_terminal_id')
                ->constrained('pos_terminals')
                ->cascadeOnDelete();

            // ร้านของเครื่อง ณ เวลาอัปโหลด (vendor_stores.id)
            $table->unsignedBigInteger('store_id')->nullable();

            // เลขบิลฝั่ง POS
            $table->string('local_id', 64);

            $table->decimal('total', 12, 2)->default(0);

            // รายการ: [{product_id, name, qty, price, line_total}]
            $table->json('items');

            $table->string('payment_method', 30)->nullable();

            // เวลาขายจริงที่เครื่อง POS
            $table->timestamp('sold_at')->nullable();

            $table->timestamps();

            $table->unique(['pos_terminal_id', 'local_id'], 'pos_sales_terminal_local_uq');
            $table->index(['store_id', 'sold_at'], 'pos_sales_store_sold_idx');
        });
    }

    /**
     * ลบตาราง pos_terminal_sales
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_terminal_sales');
    }
};
