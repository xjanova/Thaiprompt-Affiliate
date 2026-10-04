<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛵 POS → ไรเดอร์ Thai Prompt (2026-10-04) — คำขอชำระเงินจากเครื่อง POS
 *
 * แคชเชียร์กด "ส่งไรเดอร์ Thai Prompt" → POS สร้างคำขอ (แถวนี้) แล้วแสดง QR `TPPOS1.{token}`
 * ลูกค้าสแกนด้วยแอป → เลือกที่อยู่ที่ปักหมุด → ใส่ PIN → จ่ายจากกระเป๋าเงิน (ShopCheckoutService เส้นเดิม)
 * → ได้ออเดอร์ร้านค้า (orders) + งานไรเดอร์ แล้วระบบส่งมอบรอบ 2 เป็นตัวปลดเงิน
 *
 * - token: สุ่ม 40 ตัว ใช้ได้ครั้งเดียว อายุ 15 นาที (สถานะออกจาก pending = ใช้ไม่ได้อีก)
 * - items: รายการที่ resolve แล้วตอนสร้าง (product_id, sku, name, qty, price ณ เวลานั้น) — ราคาจริงตอนจ่ายมาจาก products
 * - customer_phone: เก็บแบบ normalize (0XXXXXXXXX) ใช้หาผู้ใช้เพื่อส่ง push เท่านั้น ห้ามส่งกลับใน API / ห้าม log
 * - (pos_terminal_id, local_id) ไม่ซ้ำ → POS ส่งซ้ำได้ผลเดิม
 *
 * ⏱️ คอลัมน์เวลาทุกตัวเป็น nullable (กันกับดัก explicit_defaults_for_timestamp=0 ของ MariaDB บน prod)
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง pos_delivery_requests
     */
    public function up(): void
    {
        // ✅ CREATE TABLE: มีตารางแล้วข้ามได้
        if (Schema::hasTable('pos_delivery_requests')) {
            return;
        }

        Schema::create('pos_delivery_requests', function (Blueprint $table) {
            $table->id();

            // โทเคนใน QR (TPPOS1.{token}) — ใช้แทนสิทธิ์ของลูกค้า
            $table->string('token', 64)->unique('pos_dlv_req_token_uq');

            // เครื่อง POS ที่สร้างคำขอ
            $table->foreignId('pos_terminal_id')
                ->constrained('pos_terminals')
                ->cascadeOnDelete();

            // ร้าน (vendor_stores — ตารางเดียวกับ products.store_id และ pos_terminals.shop_id)
            $table->foreignId('store_id')
                ->constrained('vendor_stores')
                ->cascadeOnDelete();

            // เลขอ้างอิงฝั่ง POS (คำขอ / บิลขาย)
            $table->string('local_id', 64);
            $table->string('order_local_id', 64)->nullable();

            // รายการที่ resolve แล้ว: [{product_id, sku, name, qty, price}]
            $table->json('items');
            $table->decimal('subtotal', 12, 2)->default(0);

            // เบอร์ลูกค้า (normalize แล้ว) + ผู้ใช้ที่จับคู่ได้ (ส่ง push)
            $table->string('customer_phone', 20)->nullable();
            $table->foreignId('target_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('note')->nullable();

            // pending | paid | expired | cancelled
            $table->string('status', 20)->default('pending');

            // ผลการจ่าย
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();
            $table->unsignedBigInteger('paid_by_user_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // ดัชนี (ชื่อสั้น)
            $table->unique(['pos_terminal_id', 'local_id'], 'pos_dlv_req_terminal_local_uq');
            $table->index('status', 'pos_dlv_req_status_idx');
            $table->index(['store_id', 'status'], 'pos_dlv_req_store_status_idx');
            $table->index('paid_by_user_id', 'pos_dlv_req_paid_by_idx');
        });
    }

    /**
     * ลบตาราง pos_delivery_requests
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_delivery_requests');
    }
};
