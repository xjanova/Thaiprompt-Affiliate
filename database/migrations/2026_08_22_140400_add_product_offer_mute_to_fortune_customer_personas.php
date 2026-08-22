<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มธง "ห้ามเสนอสินค้ากับคนนี้" ลงตาราง fortune_customer_personas
     *
     * ใช้เมื่อลูกค้าแสดงความรำคาญ ("พอแล้ว" / "ไม่ต้องส่ง" / "รำคาญ") —
     * บอทต้องตัดจบสุภาพแล้วเงียบยาว ไม่ใช่แค่ข้ามรอบเดียว
     *
     * 🚨 ทำไมต้องอยู่ DB ไม่ใช่ Cache
     *   `deploy.sh` รัน `cache:clear` ทุกครั้ง = flushdb ทั้ง DB (ไม่ใช่ลบตาม prefix)
     *   ⇒ ถ้าเก็บบน Cache คนที่บอก "รำคาญ" จะถูกรีเซ็ตทุกครั้งที่เราพุชโค้ด
     *     แล้วโดนยัดของขายซ้ำอีก = พฤติกรรมที่แย่กว่าไม่มีระบบนี้เลย
     *   (ตารางนี้เคยย้าย freechat_count จาก Cache มา DB ด้วยเหตุผลเดียวกัน)
     *
     * ⚠️ แยกจาก `chat_silenced_until` ที่มีอยู่แล้วโดยตั้งใจ —
     *   ตัวนั้นคือ "ห้ามคุยด้วย" (ปิดปากทั้งบทสนทนา)
     *   ตัวนี้คือ "คุยได้ปกติ แต่ห้ามขายของ" ซึ่งเบากว่ามาก
     *   ถ้าใช้ตัวเดียวกัน ลูกค้าที่แค่ไม่อยากซื้อของจะโดนปิดปากจากการดูดวงไปด้วย
     */
    public function up(): void
    {
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_customer_personas', 'product_offer_muted_until', function ($table) {
                $table->timestamp('product_offer_muted_until')->nullable()
                    ->comment('ห้ามเสนอสินค้าจนถึงเมื่อไหร่ (null = เสนอได้)');
            });

            $this->safeAddColumn($table, 'fortune_customer_personas', 'product_offer_mute_reason', function ($table) {
                $table->string('product_offer_mute_reason', 100)->nullable()
                    ->comment('ทำไมถึงถูกปิด — annoyed / customer_optout / admin');
            });
        });

        $this->safeAddIndex('fortune_customer_personas', 'product_offer_muted_until', 'fcp_offer_mute_idx');
    }

    /**
     * ลบคอลัมน์ที่เพิ่มไป
     */
    public function down(): void
    {
        $this->safeDropIndex('fortune_customer_personas', 'fcp_offer_mute_idx');
        $this->safeDropColumn('fortune_customer_personas', [
            'product_offer_muted_until',
            'product_offer_mute_reason',
        ]);
    }
};
