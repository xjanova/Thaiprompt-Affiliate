<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * โลโก้ที่เป็น "ค่าเริ่มต้นของแบรนด์เดิม" → ปล่อยว่าง ให้ใช้โลโก้แบรนด์ใหม่ Thai Prompt จากโค้ด
     *
     * ทำไมต้องมี (เปลี่ยนโลโก้เว็บเป็นแบบ 2 ที่เจ้าของอนุมัติ 2026-09-26):
     *   x-theme-v4.brand-logo / หน้า login / sidebar arrow-x ใช้ theme_settings.logo_path ก่อนเสมอ
     *   บน prod ค่านี้คือ 'theme-logos/thaiprompt-brand.webp' = ไฟล์โลโก้แบรนด์เดิมที่วางไว้ด้วยมือ
     *   (ไฟล์ที่แอดมินอัปโหลดผ่านหน้าตั้งค่าจะได้ชื่อสุ่ม 40 ตัวอักษรเสมอ — ชื่อนี้จึงไม่ใช่ของที่แอดมินอัปโหลด)
     *   ถ้าไม่ล้าง โลโก้ใหม่ใน public/images/brand จะไม่โผล่บน prod เลย
     *
     * ทำอะไร (idempotent — รันซ้ำได้):
     *   1. logo_path ที่ยังเป็นค่าเริ่มต้นเดิมตัวใดตัวหนึ่งด้านล่าง → NULL (โค้ดถอยไปโลโก้แบรนด์ใหม่เอง)
     *   2. brand_name ที่ยังเป็นค่า default ของคอลัมน์ 'TP-Affiliate' → 'Thai Prompt'
     *   ❌ ไม่แตะแถวที่แอดมินอัปโหลดโลโก้เอง/ตั้งชื่อแบรนด์เอง และไม่แตะ site_settings / settings
     */
    private const OLD_DEFAULT_LOGOS = [
        'theme-logos/thaiprompt-brand.webp',
        'images/logo.png',
        'images/logo.svg',
        '/images/logo.png',
        '/images/logo.svg',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('theme_settings')) {
            return;
        }

        $hasUpdatedAt = Schema::hasColumn('theme_settings', 'updated_at');

        if (Schema::hasColumn('theme_settings', 'logo_path')) {
            $values = ['logo_path' => null];
            if ($hasUpdatedAt) {
                $values['updated_at'] = now();
            }

            DB::table('theme_settings')
                ->whereIn('logo_path', self::OLD_DEFAULT_LOGOS)
                ->update($values);
        }

        if (Schema::hasColumn('theme_settings', 'brand_name')) {
            $values = ['brand_name' => 'Thai Prompt'];
            if ($hasUpdatedAt) {
                $values['updated_at'] = now();
            }

            DB::table('theme_settings')
                ->where('brand_name', 'TP-Affiliate')
                ->update($values);
        }
    }

    /**
     * ไม่ย้อนกลับ — ไม่รู้ว่าแถวไหนเคยถือค่าเดิมตัวไหน และไฟล์โลโก้เดิมไม่ได้ถูกลบ
     * ถ้าต้องการโลโก้เดิมคืน ให้อัปโหลดผ่านหน้าตั้งค่าธีมได้ตามปกติ
     */
    public function down(): void
    {
        //
    }
};
