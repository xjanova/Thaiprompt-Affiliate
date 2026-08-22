<?php

namespace App\Services\Marketplace;

use App\Models\FortuneProductOffer;
use App\Models\MarketplaceProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * MuProductPicker — เลือกสินค้า "คู่หนึ่ง" (ราคาต่ำ + ราคาสูง) ให้บอทแม่หมอเสนอลูกค้า
 *
 * ทำไมต้องเสนอ 2 ชิ้น: ให้ลูกค้าได้ "เลือก" ไม่ใช่ "รับหรือไม่รับ"
 * คนที่ไม่พร้อมจ่าย 890 อาจหยิบ 92 ไป — ถ้าเสนอชิ้นเดียวจะเสียลูกค้ากลุ่มนั้นทั้งหมด
 *
 * ลำดับการเลือกกลุ่มของ (บริบทก่อน สุ่มทีหลัง):
 *   1. ลูกค้าคุยเรื่องปีชง            → ของแก้ชง (pichong)
 *   2. รู้ปีเกิดลูกค้า                → เครื่องรางประจำปีนักษัตร (zodiac)
 *   3. หัวข้อที่ถาม เงิน/ค้าขาย       → ปี่เซี้ยะ (pixiu)
 *      ความรัก                       → เครื่องรางมงคล (charm)
 *      ป้องกันภัย/คุณไสย              → เครื่องรางมงคล (charm)
 *      บ้าน/ฮวงจุ้ย                   → พีระมิดคริสตัล (pyramid)
 *   4. ไพ่ที่เปิดได้                  → Tower/Devil → charm · Pentacles → pixiu
 *   5. ตกทุกข้อ                      → สุ่มถ่วงน้ำหนักด้วย "ค่าคอมเป็นบาท"
 *
 * 🚨 ข้อ 5 ถ่วงด้วย **บาท ไม่ใช่ %** โดยตั้งใจ
 *    ปี่เซี้ยะ 129฿ @17% = ได้ 22฿ · ปี่เซี้ยะ 39฿ @17% = ได้ 6.6฿
 *    เปอร์เซ็นต์เท่ากันเป๊ะ แต่รายได้ต่างกัน 3 เท่า
 */
class MuProductPicker
{
    /** ไม่ส่งของชิ้นเดิมให้คนเดิมซ้ำภายในกี่วัน */
    private const DEDUPE_DAYS = 30;

    /** ดึงผู้เข้ารอบมากี่ชิ้นก่อนคัดเหลือ 2 */
    private const CANDIDATE_LIMIT = 60;

    /**
     * แผนที่ "หัวข้อที่ลูกค้าคุย" → กลุ่มของที่ควรเสนอ
     *
     * ⚠️ ลำดับใน array มีความหมาย — ตัวแรกที่ตรงชนะ
     *    'ชง' ต้องมาก่อน 'เงิน' เพราะ "ปีชงเรื่องเงิน" ควรได้ของแก้ชง ไม่ใช่ปี่เซี้ยะ
     *
     * @var array<string,array{0:string,1:string}> regex => [กลุ่ม, เหตุผลไว้ debug]
     */
    private const TOPIC_MAP = [
        // 🚨 "ชง" ต้องจับแบบคำเดี่ยว ไม่ใช่ "ปีชง" ติดกัน —
        //    คนจริงพิมพ์ "ปีนี้ชงไหม" / "ชงปีนี้" / "ผมชงรึเปล่า" ซึ่งไม่มีคำว่า "ปีชง" เลย
        //    (ทดสอบบนพร็อด 2026-08-22: "ปีนี้ชงเรื่องเงิน" เคยตกไปกลุ่มปี่เซี้ยะเพราะไปโดนคำว่า "เงิน")
        //    lookahead กันคำพ้อง "ชง" ที่แปลว่าเตรียมเครื่องดื่ม/ยา
        '/(ชง(?!\s*(?:กาแฟ|ชา|นม|ยา|เครื่องดื่ม|โอวัลติน|ไมโล|น้ำ))|ไท้ส่วยเอี๊ยะ|สะเดาะเคราะห์|ตัดกรรม|เคราะห์ร้าย)/u' => ['pichong', 'ปีชง'],
        '/(คุณไสย|มนต์ดำ|ของเข้าตัว|โดนของ|ผีเข้า|สิ่งไม่ดี|ป้องกันภัย|คุ้มครอง|แคล้วคลาด)/u' => ['charm', 'ป้องกันภัย'],
        '/(การเงิน|เรื่องเงิน|เงินทอง|ค้าขาย|ธุรกิจ|การงาน|ทำมาหากิน|โชคลาภ|ร่ำรวย|หนี้สิน|รายได้)/u' => ['pixiu', 'การเงิน'],
        '/(ความรัก|เนื้อคู่|คู่ครอง|แฟน|คนรัก|เสน่ห์|มัดใจ|ครอบครัว)/u' => ['charm', 'ความรัก'],
        '/(ฮวงจุ้ย|บ้าน|ที่อยู่|ห้องนอน|ออฟฟิศ|ร้านค้า|พลังงานบ้าน)/u' => ['pyramid', 'ฮวงจุ้ย'],
        '/(สุขภาพ|เจ็บป่วย|โรค|ร่างกาย|หมอ|โรงพยาบาล)/u' => ['charm', 'สุขภาพ'],
    ];

    /**
     * ไพ่ที่เปิดได้ → กลุ่มของ (ใช้เมื่อไม่มีสัญญาณจากหัวข้อ)
     *
     * @var array<string,array{0:string,1:string}>
     */
    private const CARD_MAP = [
        '/(tower|หอคอย|devil|ปีศาจ|death|ความตาย|ten of swords)/iu' => ['charm', 'ไพ่เตือนภัย'],
        '/(pentacle|เพนตาเคิล|เหรียญ|coin|wheel of fortune|กงล้อ)/iu' => ['pixiu', 'ไพ่การเงิน'],
        '/(cup|ถ้วย|lovers|คู่รัก)/iu' => ['charm', 'ไพ่ความรัก'],
        '/(star|ดารา|ดวงดาว|sun|พระอาทิตย์|temperance)/iu' => ['pyramid', 'ไพ่พลังงาน'],
    ];

    public function __construct(private ProductQueryParser $parser) {}

    /**
     * เลือกสินค้าคู่หนึ่งให้เสนอ
     *
     * @param  MuPickContext  $ctx  บริบทของลูกค้า + เกณฑ์คัด
     * @return array{items:array<int,array{product:MarketplaceProduct,slot:string}>,group:?string,reason:string}
     *
     * @example
     * $result = $picker->pick(MuPickContext::proactive('facebook', $psid)->withTopic('เรื่องเงิน'));
     * // items: [['product' => …, 'slot' => 'low'], ['product' => …, 'slot' => 'high']]
     */
    public function pick(MuPickContext $ctx): array
    {
        [$group, $reason] = $this->resolveGroup($ctx);

        // ลองกลุ่มที่บริบทชี้ก่อน — ไม่พอค่อยเปิดกว้างเป็นสายมูทุกกลุ่ม
        $pool = $this->candidates($ctx, $group);

        // ขยายเฉพาะเส้น "บอทเสนอเอง" — เส้นลูกค้าถามเองไม่ได้กรองด้วยกลุ่มอยู่แล้ว
        // (ยิงซ้ำจะได้ผลเดิมเป๊ะ = เสีย query ฟรีทุกครั้งที่พูลบาง)
        if ($ctx->requireMu && $pool->count() < 2 && $group !== null) {
            $wider = $this->candidates($ctx, null);
            if ($wider->count() > $pool->count()) {
                $pool = $wider;
                $reason .= ' → ขยายทุกกลุ่ม';
                $group = null;
            }
        }

        if ($pool->isEmpty()) {
            return ['items' => [], 'group' => $group, 'reason' => $reason.' (ไม่มีของที่ผ่านเกณฑ์)'];
        }

        return [
            'items' => $this->splitLowHigh($pool),
            'group' => $group,
            'reason' => $reason,
        ];
    }

    /**
     * ตัดสินว่าควรเสนอของกลุ่มไหน
     *
     * @return array{0:?string,1:string} [กลุ่ม (null = ทุกกลุ่ม), เหตุผล]
     */
    private function resolveGroup(MuPickContext $ctx): array
    {
        // 0. ผู้เรียกระบุมาเอง (เช่น ลูกค้าถามหาปี่เซี้ยะตรงๆ)
        if ($ctx->forcedGroup !== null) {
            return [$ctx->forcedGroup, 'ระบุกลุ่มมาโดยตรง'];
        }

        $haystack = trim($ctx->topicText);

        // 1-3. หัวข้อที่ลูกค้าคุย
        if ($haystack !== '') {
            foreach (self::TOPIC_MAP as $pattern => [$group, $why]) {
                if (preg_match($pattern, $haystack)) {
                    return [$group, "หัวข้อ: {$why}"];
                }
            }
        }

        // 4. ไพ่ที่เปิดได้
        if ($ctx->cardsText !== '') {
            foreach (self::CARD_MAP as $pattern => [$group, $why]) {
                if (preg_match($pattern, $ctx->cardsText)) {
                    return [$group, $why];
                }
            }
        }

        // 2'. รู้ปีเกิด → เครื่องรางประจำปีนักษัตร (ตรงตัวลูกค้าที่สุด แต่ทั่วไปกว่าหัวข้อ)
        if ($ctx->birthYear !== null) {
            return ['zodiac', 'ปีเกิด '.$ctx->birthYear];
        }

        // 5. ไม่มีสัญญาณอะไรเลย
        return [null, 'สุ่มตามค่าคอม'];
    }

    /**
     * ดึงผู้เข้ารอบตามเกณฑ์
     *
     * @return Collection<int,MarketplaceProduct>
     */
    private function candidates(MuPickContext $ctx, ?string $group): Collection
    {
        try {
            $q = MarketplaceProduct::query()
                ->approved()
                ->sendableInChat();

            // ของสายมูเท่านั้น (บอทเสนอเอง) หรือของอะไรก็ได้ (ลูกค้าถามเอง)
            //
            // 🚨 ตอนลูกค้าถามเอง ห้ามกรองด้วย mu_group ที่ "เดา" มาจากหัวข้อ
            //    ลูกค้าพิมพ์ "หาหม้อทอดไร้น้ำมันให้หน่อย" แล้วบังเอิญมีคำว่า "บ้าน" อยู่ในบทสนทนา
            //    ⇒ เดาเป็นกลุ่ม pyramid ⇒ กรองเหลือแต่พีระมิดคริสตัล ⇒ คืนศูนย์ทั้งที่ของมีเพียบ
            //    กรองได้เฉพาะตอนผู้เรียก **ระบุกลุ่มมาเอง** (เช่น ลูกค้าถามหาปี่เซี้ยะตรงๆ) เท่านั้น
            if ($ctx->requireMu) {
                $q->mu($group);
            } elseif ($ctx->forcedGroup !== null) {
                $q->where('mu_group', $ctx->forcedGroup);
            }

            if ($ctx->minCommission > 0) {
                $q->where('commission_rate', '>=', $ctx->minCommission);
            }
            if ($ctx->minPrice > 0) {
                $q->where('price', '>=', $ctx->minPrice);
            }
            if ($ctx->maxPrice > 0) {
                $q->where('price', '<=', $ctx->maxPrice);
            }

            // กันส่งของชิ้นเดิมซ้ำ
            $seen = FortuneProductOffer::recentProductIds($ctx->platform, $ctx->platformUserId, self::DEDUPE_DAYS);
            if (! empty($seen)) {
                $q->whereNotIn('id', $seen);
            }

            // คำค้นจากลูกค้า (เฉพาะเส้น customer_ask)
            if ($ctx->searchQuery !== null && $ctx->searchQuery !== '') {
                $this->applyKeywordFilter($q, $ctx->searchQuery);
            }

            // เรียงตาม "ค่าคอมเป็นบาท" ให้ของที่ทำเงินได้จริงเข้ารอบก่อน
            return $q->orderByRaw('COALESCE(NULLIF(commission_amount, 0), price * commission_rate / 100) DESC')
                ->limit(self::CANDIDATE_LIMIT)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('MuProductPicker: ดึงผู้เข้ารอบล้มเหลว (ไม่กระทบ flow หลัก)', [
                'group' => $group,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * กรองด้วยคำค้นของลูกค้า + คำพ้อง
     *
     * 🔤 ต้องขยายคำพ้องเสมอ — ของสายมูกว่าครึ่งชื่ออังกฤษล้วน
     *    ("Pixiu Tiger's Eye Stone", "Nobel - Pi Xiu Bracelet", "Jadeite Zodiac")
     *    ลูกค้าพิมพ์ "ปี่เซี้ยะ" แล้ว LIKE ตรงๆ ไม่โดนสักตัว
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MarketplaceProduct>  $q
     */
    private function applyKeywordFilter($q, string $query): void
    {
        $primary = $this->parser->tokenize($query);
        if ($primary->isEmpty()) {
            return;
        }

        $terms = $primary->map(fn ($t) => mb_strtolower($t))
            ->merge($this->parser->expandSynonyms($query, $primary))
            ->unique()
            ->values();

        $q->where(function ($w) use ($terms) {
            foreach ($terms as $t) {
                // 🔒 escape ไวลด์การ์ด — ลูกค้าพิมพ์ "%" ต้องหมายถึงตัวอักษรนั้นจริงๆ
                //    ไม่งั้น "%%" = ตรงกับสินค้าทุกชิ้น (ผลลัพธ์มั่วไม่เกี่ยวกับที่ขอ)
                $t = addcslashes($t, '%_\\');
                $w->orWhere('name', 'like', "%{$t}%")
                    ->orWhere('brand', 'like', "%{$t}%");
            }
        });
    }

    /**
     * แบ่งผู้เข้ารอบเป็น "ตัวเลือกราคาต่ำ" กับ "ตัวเลือกราคาสูง"
     *
     * วิธี: เรียงตามราคา → ผ่าครึ่ง → แต่ละครึ่งเลือกตัวที่ได้ค่าคอมเป็นบาทสูงสุด
     * ⇒ ลูกค้าได้เลือกจริง และเราได้เงินสูงสุดในแต่ละช่วงราคา
     *
     * @param  Collection<int,MarketplaceProduct>  $pool
     * @return array<int,array{product:MarketplaceProduct,slot:string}>
     */
    private function splitLowHigh(Collection $pool): array
    {
        $sorted = $pool->sortBy(fn (MarketplaceProduct $p) => (float) $p->price)->values();

        if ($sorted->count() === 1) {
            return [['product' => $sorted->first(), 'slot' => FortuneProductOffer::SLOT_LOW]];
        }

        $half = (int) floor($sorted->count() / 2);
        $lowHalf = $sorted->slice(0, max(1, $half));
        $highHalf = $sorted->slice($half);

        $low = $this->bestEarner($lowHalf);
        $high = $this->bestEarner($highHalf);

        // ราคาเท่ากันหรือกลับด้าน = ไม่ได้ให้ "ทางเลือก" จริง → ส่งชิ้นเดียวพอ
        if ($low === null || $high === null || $low->id === $high->id
            || (float) $high->price <= (float) $low->price) {
            $single = $high ?? $low;

            return $single !== null
                ? [['product' => $single, 'slot' => FortuneProductOffer::SLOT_LOW]]
                : [];
        }

        return [
            ['product' => $low, 'slot' => FortuneProductOffer::SLOT_LOW],
            ['product' => $high, 'slot' => FortuneProductOffer::SLOT_HIGH],
        ];
    }

    /**
     * ในกองนี้ ชิ้นไหนทำเงินให้เราได้มากที่สุด (คิดเป็นบาท ไม่ใช่เปอร์เซ็นต์)
     *
     * @param  Collection<int,MarketplaceProduct>  $items
     */
    private function bestEarner(Collection $items): ?MarketplaceProduct
    {
        return $items->sortByDesc(fn (MarketplaceProduct $p) => $this->expectedEarning($p))->first();
    }

    /**
     * ค่าคอมที่คาดว่าจะได้ต่อ 1 ชิ้น (บาท)
     *
     * ⚠️ `commission_amount` จากฟีดว่าง/เป็น 0 ได้ ⇒ ต้องคำนวณสำรองจากราคา×เปอร์เซ็นต์
     *    ไม่งั้นของที่ฟีดไม่ได้ส่งยอดมาจะถูกจัดว่า "ทำเงินไม่ได้" แล้วจมท้ายตลอด
     */
    public function expectedEarning(MarketplaceProduct $p): float
    {
        $amount = (float) ($p->commission_amount ?? 0);
        if ($amount > 0) {
            return $amount;
        }

        return (float) $p->price * (float) ($p->commission_rate ?? 0) / 100;
    }
}
