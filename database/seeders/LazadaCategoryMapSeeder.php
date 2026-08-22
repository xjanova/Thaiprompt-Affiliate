<?php

namespace Database\Seeders;

use App\Models\LazadaCategoryMap;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างแผนที่แปลงหมวด Lazada → หมวดบนหน้าร้านเรา
 *
 * ที่มาของตัวเลข (ไม่ใช่การเดา):
 *   ดึงจากพร็อดจริงวันที่ 2026-08-22 — จับคู่ `category_l1_id` กับ `category` ของสินค้า 1,109 ชิ้น
 *   ที่นำเข้ามาแล้ว แล้วเลือก "เสียงข้างมาก" ของแต่ละเลขหมวด
 *   (เลขหมวดเดียวกันเคยถูกยัดหลายหมวดตอนเติมแคตตาล็อก 16 หมวด — 28 ใน 35 เลขกำกวม
 *    แต่ทุกเลขมีตัวเด่นชัด เช่น 6277 → pets 50 ชิ้น vs food 5 ชิ้น)
 *
 * ⚠️ ค่า sample_count น้อย (1-5) = หลักฐานบาง ให้ owner แก้ในหลังบ้านได้
 *    ของที่คนแก้เองจะถูกทำเครื่องหมาย `manual` แล้วตัวเดาอัตโนมัติจะไม่เขียนทับอีก
 */
class LazadaCategoryMapSeeder extends Seeder
{
    /**
     * แผนที่ตั้งต้น: เลขหมวด Lazada => [slug หมวดเรา, จำนวนสินค้าที่ใช้เดา]
     *
     * @var array<string,array{0:string,1:int}>
     */
    private const BASELINE = [
        '3008' => ['watches-and-eyewear', 33],
        '3833' => ['home-appliances', 45],
        '3834' => ['it-computer', 1],
        '3835' => ['books-and-stationery', 1],
        '3836' => ['cameras-and-photography', 5],
        '3838' => ['beauty-and-personal-care', 48],
        '5090' => ['mother-and-baby', 42],
        '5095' => ['toys-and-hobbies', 39],
        '5761' => ['sports-and-outdoors', 33],
        '6277' => ['pets', 50],
        '7513' => ['it-computer', 1],
        '7587' => ['food-and-beverages', 35],
        '8428' => ['automotive', 44],
        '9154' => ['fashion-and-apparel', 9],
        '11828' => ['home-and-garden', 19],
        '11829' => ['home-and-garden', 18],
        '11830' => ['books-and-stationery', 8],
        '11831' => ['mother-and-baby', 1],
        '11832' => ['sports-and-outdoors', 6],
        '11833' => ['books-and-stationery', 33],
        '10100083' => ['home-and-garden', 7],
        '10100245' => ['home-and-garden', 3],
        '10100386' => ['cameras-and-photography', 13],
        '10100387' => ['it-computer', 19],
        '10100412' => ['watches-and-eyewear', 12],
        '10100539' => ['beauty-and-personal-care', 1],
        '10100869' => ['health-and-supplements', 33],
        '42062201' => ['electronics', 32],
        '42062401' => ['fashion-and-apparel', 4],
        '62188201' => ['fashion-and-apparel', 1],
        '62540402' => ['fashion-and-apparel', 4],
        '62540802' => ['fashion-and-apparel', 13],
        '62541004' => ['fashion-and-apparel', 3],
        '62541201' => ['fashion-and-apparel', 7],
    ];

    /**
     * สร้างแผนที่หมวด
     */
    public function run(): void
    {
        if (! Schema::hasTable('lazada_category_map')) {
            $this->command->warn('⏭️  ยังไม่มีตาราง lazada_category_map (รัน migrate ก่อน) — ข้าม');

            return;
        }

        $this->command->info('🗂️  กำลังสร้างแผนที่หมวด Lazada → หมวดหน้าร้าน...');

        $slugToId = ProductCategory::pluck('id', 'slug')->all();
        if (empty($slugToId)) {
            $this->command->warn('⏭️  ยังไม่มีหมวดสินค้า (รัน ProductCategorySeeder ก่อน) — ข้าม');

            return;
        }

        $written = 0;
        $skipped = 0;

        foreach (self::BASELINE as $lazadaL1 => [$slug, $samples]) {
            if (! isset($slugToId[$slug])) {
                $skipped++;

                continue;
            }

            LazadaCategoryMap::put(
                $lazadaL1,
                (int) $slugToId[$slug],
                LazadaCategoryMap::CONFIDENCE_DERIVED,
                $samples
            );
            $written++;
        }

        // เติมจากข้อมูลจริงในเครื่องนี้ (ถ้ามี) — เผื่อมีเลขหมวดใหม่ที่ baseline ยังไม่รู้จัก
        $extra = $this->deriveFromLiveData($slugToId);

        $this->command->info("✅ แผนที่หมวดพร้อมใช้ — ตั้งต้น {$written} เลข".($extra > 0 ? " + เพิ่มจากข้อมูลจริง {$extra} เลข" : '').($skipped > 0 ? " (ข้าม {$skipped} เพราะไม่มีหมวดปลายทาง)" : ''));
    }

    /**
     * เดาแผนที่เพิ่มจากสินค้าที่นำเข้ามาแล้วในเครื่องนี้ (เสียงข้างมากต่อเลขหมวด)
     *
     * ⚠️ ข้ามแถวที่คอลัมน์ `category` เป็นตัวเลขล้วน — นั่นคือเลขหมวด Lazada ที่หลุดมา
     *    ตอนเติมแคตตาล็อก ไม่ใช่ slug หมวดของเรา (ถ้าเอามาใช้ = แผนที่ชี้กลับหาตัวเอง)
     *
     * @param  array<string,int>  $slugToId
     * @return int จำนวนเลขหมวดที่เพิ่มใหม่
     */
    private function deriveFromLiveData(array $slugToId): int
    {
        if (! Schema::hasTable('marketplace_products')) {
            return 0;
        }

        $known = LazadaCategoryMap::pluck('lazada_category_l1')->flip();

        $rows = DB::table('marketplace_products')
            ->selectRaw('category_l1_id as l1, category as cat, COUNT(*) as n')
            ->whereNotNull('category_l1_id')
            ->whereNotNull('category')
            ->groupBy('l1', 'cat')
            ->orderByDesc('n')
            ->get();

        $best = [];
        foreach ($rows as $r) {
            $slug = trim((string) $r->cat);
            $l1 = trim((string) $r->l1);

            if ($l1 === '' || $slug === '' || ctype_digit($slug) || ! isset($slugToId[$slug])) {
                continue;
            }
            if ($known->has($l1)) {
                continue; // baseline ครอบแล้ว
            }
            // orderByDesc('n') ⇒ แถวแรกของแต่ละเลขคือเสียงข้างมาก
            if (! isset($best[$l1])) {
                $best[$l1] = [$slug, (int) $r->n];
            }
        }

        foreach ($best as $l1 => [$slug, $n]) {
            LazadaCategoryMap::put($l1, (int) $slugToId[$slug], LazadaCategoryMap::CONFIDENCE_DERIVED, $n);
        }

        return count($best);
    }
}
