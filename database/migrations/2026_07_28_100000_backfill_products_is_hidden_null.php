<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ล้างค่า is_hidden ที่เป็น NULL → 0 (ไม่ซ่อน) + ตั้ง default กันเกิดซ้ำ
     *
     * ทำไมต้องมี: scope Product::visible() ใช้ where('is_hidden', false) ซึ่ง NULL
     * ไม่เท่ากับ false ใน SQL → สินค้าที่ is_hidden เป็น NULL "หายจากหน้าร้านทั้งเว็บ"
     * ทั้งที่ active + published อยู่ (พบบน prod 92 ชิ้น รวมสินค้าขายดีอันดับ 1
     * "น้ำมันเครื่อง Fully Synthetic" ที่ขายไปแล้ว 476 ชิ้น)
     *
     * ต้นเหตุ: คอลัมน์ nullable ไม่มี default — ฟอร์มสร้างสินค้าที่ไม่ส่งค่านี้มา
     * จะ insert เป็น NULL เงียบๆ จึงต้องตั้ง NOT NULL DEFAULT 0 ปิดทางเกิดซ้ำด้วย
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'is_hidden')) {
            return;
        }

        // 1) ล้างของเก่า: NULL = ไม่เคยตั้งค่า → ถือว่า "ไม่ซ่อน" (สินค้าเหล่านี้ตั้งใจขายอยู่แล้ว)
        DB::table('products')->whereNull('is_hidden')->update(['is_hidden' => 0]);

        // 2) ปิดทางเกิดซ้ำ: บังคับ NOT NULL + default 0
        //    (raw statement เพราะ change() ต้องพึ่ง doctrine/dbal ซึ่งบางที่ติดตั้งไม่มี)
        try {
            DB::statement('ALTER TABLE products MODIFY is_hidden TINYINT(1) NOT NULL DEFAULT 0');
        } catch (\Throwable $e) {
            // ตั้ง default ไม่ได้ (สิทธิ์/เอนจินต่าง) ก็ไม่เป็นไร — ข้อ 1 แก้อาการหลักแล้ว
        }
    }

    /**
     * ย้อนกลับ: คืนเป็น nullable (ไม่คืนค่า NULL เดิม — แยกไม่ออกแล้วว่าแถวไหนเคยเป็น NULL)
     *
     * @return void
     */
    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'is_hidden')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE products MODIFY is_hidden TINYINT(1) NULL DEFAULT 0');
        } catch (\Throwable $e) {
            // best-effort
        }
    }
};
