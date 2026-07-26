<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * หมวดหมู่สินค้า "สายมู" (เครื่องรางเสริมดวง) — หมวดแม่ + หมวดย่อย 5 หมวด
 *
 * ใช้กับสินค้า affiliate ที่นำเข้าจาก Lazada (คำสั่ง lazada:mu-import)
 * โครงสร้าง: สายมู เครื่องรางเสริมดวง
 *   ├─ ปี่เซี้ยะ เรียกทรัพย์
 *   ├─ พีระมิด & คริสตัลพลังงาน
 *   ├─ ของแก้ปีชง
 *   ├─ 12 นักษัตร
 *   └─ เครื่องรางมงคลอื่นๆ
 *
 * ⚠️ idempotent — รันซ้ำได้ ไม่สร้างซ้ำ (firstOrCreate ด้วย slug)
 */
class MuCategorySeeder extends Seeder
{
    /** slug หมวดแม่ — คำสั่งนำเข้าอ้างอิงค่านี้ */
    public const ROOT_SLUG = 'sai-mu';

    /**
     * หมวดย่อย: กลุ่ม => [ชื่อไทย, slug, คำอธิบาย]
     * key ต้องตรงกับ field "group" ในไฟล์ database/data/lazada-mu-products.json
     *
     * @var array<string, array{0:string,1:string,2:string}>
     */
    public const GROUPS = [
        'pixiu' => ['ปี่เซี้ยะ เรียกทรัพย์', 'sai-mu-pixiu', 'ปี่เซี้ยะคาบเหรียญ กำไล จี้ แหวน เสริมการเงินการค้า ดูดทรัพย์'],
        'pyramid' => ['พีระมิด & คริสตัลพลังงาน', 'sai-mu-pyramid', 'พีระมิดหินแท้ คริสตัลพลังงาน ออร์โกไนต์ ปรับฮวงจุ้ย เสริมพลังบวก'],
        'pichong' => ['ของแก้ปีชง', 'sai-mu-pichong', 'ผ้ายันต์แก้ชง องค์ไท้ส่วยเอี๊ยะ ของแก้ปีชง สะเดาะเคราะห์ต่อชะตา'],
        'zodiac' => ['12 นักษัตร', 'sai-mu-zodiac', 'จี้ กำไล เครื่องรางประจำปีนักษัตร ชวด ฉลู ขาล ... กุน เสริมดวงตามปีเกิด'],
        'charm' => ['เครื่องรางมงคลอื่นๆ', 'sai-mu-charm', 'กบคาบเหรียญ นางกวัก พระพิฆเนศ ต้นไม้มงคล ถุงเงินถุงทอง หินนำโชค'],
    ];

    /**
     * สร้างหมวดสายมู (แม่ + ย่อย)
     */
    public function run(): void
    {
        $this->command->info('🔮 กำลัง seed หมวดหมู่สินค้าสายมู...');

        $created = 0;

        // ── หมวดแม่ ──
        $root = $this->upsertCategory(self::ROOT_SLUG, [
            'name' => 'สายมู เครื่องรางเสริมดวง',
            'description' => 'ของมงคลสายมู ปี่เซี้ยะ พีระมิดคริสตัล ของแก้ปีชง 12 นักษัตร เครื่องรางเรียกทรัพย์ คัดสรรจาก Lazada',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 10,
        ], $created);

        // ── หมวดย่อย ──
        $sort = 1;
        foreach (self::GROUPS as [$name, $slug, $desc]) {
            $child = $this->upsertCategory($slug, [
                'name' => $name,
                'description' => $desc,
                'parent_id' => $root->id,
                'is_active' => true,
                'sort_order' => $sort,
            ], $created);

            // เผื่อหมวดเคยถูกสร้างไว้ก่อนโดยไม่มี parent — ผูกให้ถูกต้อง
            if ($child->parent_id !== $root->id) {
                $child->update(['parent_id' => $root->id]);
            }

            $sort++;
        }

        $this->command->info("✅ หมวดสายมูพร้อมใช้งาน — หมวดแม่ id={$root->id}, สร้างใหม่ {$created} หมวด (หมวดย่อยทั้งหมด ".count(self::GROUPS).')');
    }

    /**
     * สร้าง/คืนหมวดตาม slug แบบปลอดภัย
     *
     * ⚠️ ProductCategory ใช้ SoftDeletes แต่คอลัมน์ slug เป็น unique ในระดับ DB
     *    → ถ้าหมวดเคยถูกลบแบบ soft ไว้ firstOrCreate จะ "หาไม่เจอ" แล้วพยายามสร้างใหม่
     *      = ชน unique constraint ทันที → ต้องมองหาแบบรวม trashed แล้วกู้คืนแทน
     *
     * @param  array<string,mixed>  $attrs
     * @param  int  $created  ตัวนับหมวดที่สร้างใหม่ (by reference)
     */
    private function upsertCategory(string $slug, array $attrs, int &$created): ProductCategory
    {
        /** @var ProductCategory|null $cat */
        $cat = ProductCategory::withTrashed()->where('slug', $slug)->first();

        if ($cat) {
            // กู้คืนถ้าเคยถูกลบ แล้วเปิดใช้งานให้เห็นหน้าร้าน
            if ($cat->trashed()) {
                $cat->restore();
                $this->command->warn("   ♻️  กู้คืนหมวดที่เคยถูกลบ: {$slug}");
            }
            if (! $cat->is_active) {
                $cat->update(['is_active' => true]);
            }

            return $cat;
        }

        $created++;

        return ProductCategory::create($attrs + ['slug' => $slug]);
    }
}
