<?php

namespace Database\Seeders;

use App\Models\LazadaMuKeyword;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * คีย์เวิร์ดตั้งต้นสำหรับไล่เก็บสินค้าสายมูจาก Lazada
 *
 * owner เติม/แก้/ปิดได้เองจากหลังบ้าน (Lazada Hub → นำเข้าสายมู) — ที่นี่แค่ตั้งต้นให้มีของใช้วันแรก
 *
 * ⚠️ idempotent — รันซ้ำได้ ไม่เขียนทับค่าที่ owner ปรับไว้แล้ว
 *    (updateOrCreate จะทับ ⇒ ใช้ firstOrCreate แทน เพราะ owner อาจแก้เป้าหมาย/ราคาไปแล้ว)
 *
 * 🚨 ราคาสูงสุดตั้งไว้ต่ำโดยตั้งใจ (2,000 บาท)
 *    ฟีดของ Lazada เอียงไปทางของแพงสุดขั้ว — ของสายมูในฟีดทางการเคยเจอแต่ 30,000-750,000 บาท
 *    (พระนางพญา 845,959 บาท ค่าคอม 19%) ซึ่งไม่มีทางขายผ่านแชทได้
 *    ของที่ขายได้จริงคือ 25-1,859 บาท ⇒ เพดานต้องกันของแพงออกตั้งแต่ต้นทาง
 */
class LazadaMuKeywordSeeder extends Seeder
{
    /**
     * คีย์เวิร์ดตั้งต้น: [คำค้น, กลุ่มสายมู, slug หมวดปลายทาง, เป้าหมายจำนวนชิ้น]
     *
     * @var array<int,array{0:string,1:string,2:string,3:int}>
     */
    private const KEYWORDS = [
        // ── ของแก้ปีชง (owner สั่งเน้นกลุ่มนี้เป็นอันดับแรก) ─────────────
        ['บ่วงนาคบาศ', 'pichong', 'sai-mu-pichong', 20],
        ['ของแก้ปีชง', 'pichong', 'sai-mu-pichong', 20],
        ['ผ้ายันต์แก้ชง', 'pichong', 'sai-mu-pichong', 15],
        ['องค์ไท้ส่วยเอี๊ยะ', 'pichong', 'sai-mu-pichong', 10],
        ['สะเดาะเคราะห์ต่อชะตา', 'pichong', 'sai-mu-pichong', 10],

        // ── ปี่เซี้ยะ / เรียกทรัพย์ ─────────────────────────────────────
        ['ปี่เซี้ยะ', 'pixiu', 'sai-mu-pixiu', 25],
        ['กำไลปี่เซี้ยะ', 'pixiu', 'sai-mu-pixiu', 20],
        ['กบคาบเหรียญ', 'pixiu', 'sai-mu-pixiu', 10],
        ['ถุงเงินถุงทองมงคล', 'pixiu', 'sai-mu-pixiu', 10],

        // ── พีระมิด / คริสตัลพลังงาน ────────────────────────────────────
        ['พีระมิดคริสตัล', 'pyramid', 'sai-mu-pyramid', 15],
        ['หินมงคลเสริมดวง', 'pyramid', 'sai-mu-pyramid', 20],
        ['ออร์โกไนต์', 'pyramid', 'sai-mu-pyramid', 10],

        // ── 12 นักษัตร ─────────────────────────────────────────────────
        ['จี้ 12 นักษัตร', 'zodiac', 'sai-mu-zodiac', 20],
        ['เครื่องรางประจำปีเกิด', 'zodiac', 'sai-mu-zodiac', 15],

        // ── เครื่องรางมงคลอื่นๆ ────────────────────────────────────────
        ['ท้าวเวสสุวรรณ', 'charm', 'sai-mu-charm', 20],
        ['ตะกรุด', 'charm', 'sai-mu-charm', 15],
        ['นางกวัก', 'charm', 'sai-mu-charm', 10],
        ['พระพิฆเนศ', 'charm', 'sai-mu-charm', 15],
        ['น้ำเต้ามงคล', 'charm', 'sai-mu-charm', 10],
        ['สร้อยข้อมือหินนำโชค', 'charm', 'sai-mu-charm', 20],
    ];

    /** ราคาต่ำสุดที่รับ — กันของแถม/ของหลอกราคา 1 บาท */
    private const MIN_PRICE = 25.0;

    /** ราคาสูงสุดที่รับ — ของที่ขายผ่านแชทได้จริง */
    private const MAX_PRICE = 2000.0;

    /**
     * สร้างคีย์เวิร์ดตั้งต้น
     */
    public function run(): void
    {
        if (! Schema::hasTable('lazada_mu_keywords')) {
            $this->command->warn('⏭️  ยังไม่มีตาราง lazada_mu_keywords (รัน migrate ก่อน) — ข้าม');

            return;
        }

        $this->command->info('🔮 กำลังตั้งคีย์เวิร์ดนำเข้าสายมู...');

        $slugToId = ProductCategory::pluck('id', 'slug')->all();
        $created = 0;
        $existing = 0;

        foreach (self::KEYWORDS as [$keyword, $group, $categorySlug, $target]) {
            $row = LazadaMuKeyword::firstOrCreate(
                ['keyword' => $keyword],
                [
                    'mu_group' => $group,
                    'product_category_id' => $slugToId[$categorySlug] ?? null,
                    'min_price' => self::MIN_PRICE,
                    'max_price' => self::MAX_PRICE,
                    'target_count' => $target,
                    'source' => LazadaMuKeyword::SOURCE_SEED,
                    'is_active' => true,
                ]
            );

            $row->wasRecentlyCreated ? $created++ : $existing++;
        }

        $this->command->info("✅ คีย์เวิร์ดสายมูพร้อมใช้ — สร้างใหม่ {$created} คำ".($existing > 0 ? ", มีอยู่แล้ว {$existing} คำ (ไม่แตะ)" : ''));
    }
}
