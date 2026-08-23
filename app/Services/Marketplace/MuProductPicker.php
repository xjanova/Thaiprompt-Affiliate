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

    /** พูลต้องมีอย่างน้อยกี่ชิ้นถึงจะเสนอเป็น "คู่" ให้ลูกค้าเลือกได้ */
    private const MIN_POOL_FOR_PAIR = 2;

    /**
     * ข้อความแตะกี่กลุ่มพร้อมกันถึงถือว่า "ครอบจักรวาล" แล้วทิ้งสัญญาณหัวข้อ
     *
     * 1-2 กลุ่ม = ลูกค้าพูดถึงเรื่องนั้นจริง ("เรื่องเงินกับความรัก") เชื่อได้
     * ≥3 กลุ่ม = ข้อความตั้งต้นของระบบที่ไล่ชื่อทุกด้านของชีวิต ไม่ได้บอกอะไรเลย
     */
    private const TOPIC_AMBIGUITY_LIMIT = 3;

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

        // 🪜 (2026-08-23) บันไดผ่อนเกณฑ์ — "ความตรงเรื่อง" สำคัญกว่า "เปอร์เซ็นต์ค่าคอม"
        //
        //   ราก (วัดจากพร็อด 2026-08-23): ที่เกณฑ์ ≥9% กลุ่ม **pichong = 0 ชิ้น · pyramid = 0 ชิ้น**
        //   ⇒ ลูกค้าพูดเรื่องปีชง → resolveGroup คืน 'pichong' ถูกต้อง → พูลว่าง
        //     → ขยายทุกกลุ่ม → ได้ปี่เซี้ยะมั่วที่ไม่เกี่ยวกับชงเลย
        //   ⇒ การแก้ตัวจับคำว่า "ชง" ก่อนหน้านี้ ไม่มีผลใดๆ เพราะปลายทางไม่มีของ
        //     (บั๊กลูกโซ่: ตัวจับถูก แต่พูลว่าง = อาการเหมือนตัวจับพัง)
        //
        //   ลำดับที่ถูกต้องสำหรับบอทดูดวง — ของแก้ชงจริงที่ 6% ดีกว่าปี่เซี้ยะมั่วที่ 9%:
        //     1. กลุ่มที่บริบทชี้ + เกณฑ์ปกติ
        //     2. กลุ่มเดิม + เกณฑ์ผ่อน      ← รักษาความตรงเรื่องไว้ก่อน
        //     3. ทุกกลุ่ม + เกณฑ์ปกติ
        //     4. ทุกกลุ่ม + เกณฑ์ผ่อน
        if ($ctx->requireMu && $pool->count() < self::MIN_POOL_FOR_PAIR) {
            foreach ($this->fallbackLadder($ctx, $group) as [$tryCtx, $tryGroup, $note]) {
                $alt = $this->candidates($tryCtx, $tryGroup);
                if ($alt->count() > $pool->count()) {
                    $pool = $alt;
                    $group = $tryGroup;
                    $reason .= ' → '.$note;
                }
                if ($pool->count() >= self::MIN_POOL_FOR_PAIR) {
                    break;
                }
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
     * ขั้นบันไดที่จะลองไล่ลง เมื่อพูลของกลุ่มที่บริบทชี้ไม่พอ
     *
     * @return array<int,array{0:MuPickContext,1:?string,2:string}>
     */
    private function fallbackLadder(MuPickContext $ctx, ?string $group): array
    {
        $relaxed = $ctx->relaxed();
        $ladder = [];

        // ขั้น 2 — กลุ่มเดิม เกณฑ์ผ่อน (มีความหมายเฉพาะตอนเกณฑ์ผ่อนต่ำกว่าจริง)
        if ($group !== null && $relaxed->minCommission < $ctx->minCommission) {
            $ladder[] = [$relaxed, $group, 'ผ่อนค่าคอมในกลุ่มเดิม'];
        }

        // ขั้น 3 — ทุกกลุ่ม เกณฑ์ปกติ
        if ($group !== null) {
            $ladder[] = [$ctx, null, 'ขยายทุกกลุ่ม'];
        }

        // ขั้น 4 — ทุกกลุ่ม เกณฑ์ผ่อน
        if ($relaxed->minCommission < $ctx->minCommission) {
            $ladder[] = [$relaxed, null, 'ขยายทุกกลุ่ม + ผ่อนค่าคอม'];
        }

        return $ladder;
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
            $hits = [];
            foreach (self::TOPIC_MAP as $pattern => [$group, $why]) {
                if (preg_match($pattern, $haystack)) {
                    $hits[] = [$group, $why];
                }
            }

            // 🚨 (2026-08-23) ข้อความที่แตะหลายเรื่องพร้อมกัน = คำขอแบบครอบจักรวาล ไม่ใช่ความกังวลเฉพาะเรื่อง
            //
            //   ราก: `fortune_readings.questions` ของเส้นพื้นดวง **ไม่ใช่คำพูดลูกค้า** แต่เป็นข้อความ
            //   ตั้งต้นของระบบ: "ขอดูพื้นดวงโดยรวม… ทั้งนิสัยพื้นฐาน ความรัก การงาน การเงิน สุขภาพ
            //   โชคลาภ และสิ่งที่ควรระวัง" ⇒ ก้อนเดียวโดน 3 กลุ่มรวด
            //   ถ้าหยิบตัวแรกที่เจอ ลูกค้า **ทุกคน** จะได้กลุ่มเดียวกันเป๊ะทุกครั้ง
            //   = แย่กว่าสุ่ม เพราะมันไปทับการสุ่มถ่วงน้ำหนักที่อย่างน้อยยังให้ความหลากหลาย
            //   และดู "เหมือนเลือกให้ตรงคน" ทั้งที่ไม่ได้ดูอะไรเลย
            //
            //   ⇒ โดน 1-2 กลุ่ม = ลูกค้าพูดถึงเรื่องนั้นจริง เชื่อได้
            //     โดน ≥3 กลุ่ม = ข้อความครอบจักรวาล ทิ้งสัญญาณนี้ไปใช้ตัวอื่นแทน
            if (count($hits) > 0 && count($hits) < self::TOPIC_AMBIGUITY_LIMIT) {
                [$group, $why] = $hits[0];

                return [$group, "หัวข้อ: {$why}"];
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
            // 🛒 offerable() ไม่ใช่ approved() — owner สั่ง 2026-08-23:
            //    "ใช้สินค้าทุกอย่างที่นำเข้า แม้ยังไม่ได้อนุมัติเข้า thaiprompt"
            //    ด่านอนุมัติคุมแค่การขึ้นหน้าร้าน ไม่คุมลิงก์ที่ส่งในแชท
            //    (ถ้าใช้ approved() ของใหม่จากท่อนำเข้าจะติดคิวคนอนุมัติก่อนเสมอ = ท่อเดินแต่บอทไม่มีของเพิ่ม)
            $q = MarketplaceProduct::query()
                ->offerable()
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

        $low = $this->weightedPick($lowHalf);
        $high = $this->weightedPick($highHalf);

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
     * สุ่มเลือก 1 ชิ้นจากกอง โดยถ่วงน้ำหนักด้วย "ค่าคอมเป็นบาท"
     *
     * 🚨 (2026-08-23) เดิมเป็น `bestEarner()` = หยิบตัวค่าคอมสูงสุดแบบตายตัว
     *    ผลจริงบนพร็อด: ลูกค้า 12 คนแรกได้สินค้า **ชุดเดียวกันเป๊ะทั้งหมด**
     *    ใช้ไปแค่ 2 ชิ้นจากพูล 20 — อีก 18 ชิ้นไม่เคยถูกส่งเลยสักครั้ง
     *    (ตัวกันส่งซ้ำเป็นแบบ "รายคน 30 วัน" ⇒ คนใหม่ทุกคนเจอตัวเดิมเสมอ)
     *    ⇒ ดูเป็นหุ่นยนต์ · ของ 1,859฿ ชิ้นเดียวไปหาทุกคนรวมถึงคนที่ไม่มีกำลังซื้อ
     *
     *    ถ่วงน้ำหนักแทน: ของค่าคอมสูงยังโผล่บ่อยกว่า แต่ทั้งพูลมีโอกาสได้ออก
     *
     * @param  Collection<int,MarketplaceProduct>  $items
     */
    private function weightedPick(Collection $items): ?MarketplaceProduct
    {
        $items = $items->values();

        if ($items->isEmpty()) {
            return null;
        }
        if ($items->count() === 1) {
            return $items->first();
        }

        // ขั้นต่ำ 1.0 — ของที่คำนวณค่าคอมได้ 0 ต้องยังมีโอกาสออก ไม่ใช่ถูกตัดขาด
        $weights = $items->map(fn (MarketplaceProduct $p) => max(1.0, $this->expectedEarning($p)))->all();
        $total = array_sum($weights);

        if ($total <= 0) {
            return $items->first();
        }

        // คูณ 100 แล้วใช้จำนวนเต็ม — mt_rand รับ int เท่านั้น และค่าคอมมีทศนิยม
        $roll = mt_rand(1, max(1, (int) round($total * 100))) / 100;

        $acc = 0.0;
        foreach ($weights as $i => $w) {
            $acc += $w;
            if ($roll <= $acc) {
                return $items[$i];
            }
        }

        // ตกขอบจากปัดเศษ — คืนตัวสุดท้าย (ห้ามคืน null ทั้งที่กองไม่ว่าง)
        return $items->last();
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
