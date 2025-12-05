<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * แก้ไขปัญหาสินค้าไม่แสดงใน Storefront
     *
     * ปัญหา: สินค้าที่มี published_at = null จะถูก filter ออกใน scopeActive()
     * เนื่องจาก scopeActive() ต้องการ:
     *   - is_active = true
     *   - published_at IS NOT NULL
     *   - published_at <= now()
     *
     * การแก้ไข: Update สินค้าที่มี is_active = true แต่ published_at = null
     * ให้มี published_at = now() เพื่อให้แสดงในหน้าเว็บได้
     *
     * @return void
     */
    public function up(): void
    {
        // นับจำนวนสินค้าที่จะถูกแก้ไข
        $countBefore = DB::table('products')
            ->where('is_active', true)
            ->whereNull('published_at')
            ->count();

        if ($countBefore > 0) {
            // Update สินค้าที่ is_active = true แต่ยังไม่มี published_at
            DB::table('products')
                ->where('is_active', true)
                ->whereNull('published_at')
                ->update([
                    'published_at' => now(),
                    'updated_at' => now(),
                ]);

            // Log ผลลัพธ์
            \Log::info("[Migration] fix_products_published_at_null: Updated {$countBefore} products with published_at = now()");
        }
    }

    /**
     * Reverse the migrations.
     *
     * หมายเหตุ: ไม่สามารถ rollback ได้อย่างสมบูรณ์
     * เพราะเราไม่รู้ว่าสินค้าไหนเคยมี published_at = null
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่ทำอะไร - การ rollback นี้จะไม่ย้อนกลับการเปลี่ยนแปลง
        // เพราะไม่สามารถระบุได้ว่าสินค้าไหนเคยมี published_at = null
        \Log::info('[Migration] fix_products_published_at_null: Rollback - no changes made (cannot identify which products had null published_at)');
    }
};
