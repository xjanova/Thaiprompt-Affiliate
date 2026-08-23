<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceSetting;

/**
 * MuPickContext — บริบท + เกณฑ์คัด สำหรับส่งให้ MuProductPicker
 *
 * แยกออกมาเป็นวัตถุเพราะพารามิเตอร์เยอะและมีค่าเริ่มต้นคนละชุด
 * ระหว่าง "บอทเสนอเอง" (เข้มงวด) กับ "ลูกค้าถามเอง" (ผ่อน) —
 * ถ้าใช้ argument list ยาวๆ จะสลับค่ากันโดยไม่รู้ตัว
 */
class MuPickContext
{
    /** ค่าคอมขั้นต่ำตอนบอทเสนอเอง (%) — ตั้งได้ที่หลังบ้าน */
    private const SETTING_MIN_COMMISSION = 'lazada_mu_min_commission';

    /** ราคาต่ำสุดที่ยอมเสนอ — กันของแถม/ของหลอกราคา 1 บาท */
    private const SETTING_MIN_PRICE = 'lazada_mu_min_price';

    /** ราคาสูงสุดที่ยอมเสนอตอนบอทเสนอเอง */
    private const SETTING_MAX_PRICE = 'lazada_mu_max_price';

    /** ค่าคอมขั้นต่ำตอนลูกค้าถามเอง (default 0 = ไม่บังคับ) */
    private const SETTING_ASK_MIN_COMMISSION = 'lazada_mu_ask_min_commission';

    /**
     * ค่าคอมขั้นต่ำ "ขั้นผ่อน" — ใช้เมื่อกลุ่มที่บริบทชี้ไม่มีของพอที่เกณฑ์ปกติ
     *
     * 🚨 ทำไมต้องมี (วัดจากพร็อด 2026-08-23):
     *    ที่เกณฑ์ ≥9% กลุ่ม pichong = 0 ชิ้น · pyramid = 0 ชิ้น
     *    ⇒ ลูกค้าคุยเรื่องปีชง แล้วได้ปี่เซี้ยะมั่ว เพราะพูลของแก้ชงว่างเปล่า
     *    ที่เกณฑ์ ≥5% ทั้ง 5 กลุ่มมีของครบ (pichong 10 · pyramid 7)
     *    สำหรับบอทดูดวง **ความตรงเรื่องคือตัวสินค้า** — ของแก้ชงจริง 6% ชนะปี่เซี้ยะมั่ว 9%
     */
    private const SETTING_RELAXED_MIN_COMMISSION = 'lazada_mu_relaxed_min_commission';

    public function __construct(
        public readonly string $platform,
        public readonly string $platformUserId,

        /** true = ต้องเป็นของสายมูเท่านั้น (บอทเสนอเอง) · false = ของอะไรก็ได้ (ลูกค้าถามเอง) */
        public readonly bool $requireMu = true,

        /** ค่าคอมขั้นต่ำเป็นเปอร์เซ็นต์ (0 = ไม่บังคับ) */
        public readonly float $minCommission = 0.0,

        public readonly float $minPrice = 0.0,

        /** 0 = ไม่จำกัดเพดานราคา */
        public readonly float $maxPrice = 0.0,

        /** บังคับกลุ่มของ (null = ให้ picker ตัดสินจากบริบท) */
        public readonly ?string $forcedGroup = null,

        /** ข้อความ/หัวข้อที่ลูกค้ากำลังคุย — ใช้เดาว่าควรเสนอของกลุ่มไหน */
        public readonly string $topicText = '',

        /** ชื่อไพ่ที่เปิดได้ในเซสชันนี้ (ต่อกันเป็นสตริงเดียว) */
        public readonly string $cardsText = '',

        /**
         * ปีเกิด **ค.ศ.** ของลูกค้า
         *
         * ⚠️ ต้องแปลงจาก พ.ศ. มาก่อนส่งเข้ามา — ใช้ `App\Support\ThaiBirthYear`
         *    (กฎของระบบ: ปีเกิด 2 หลัก = พ.ศ. เสมอ)
         *
         * 📌 ตอนนี้ใช้แค่ "รู้ปีเกิด ⇒ เสนอกลุ่ม zodiac" ยังไม่จับคู่ตัวนักษัตรตรงตัว
         *    เพราะสินค้ายังไม่ได้ติดป้ายรายนักษัตร — กรองตรงตัวแล้วพูลจะว่าง
         *    ซึ่งแย่กว่าได้เครื่องรางนักษัตรทั่วไป
         */
        public readonly ?int $birthYear = null,

        /** คำค้นที่ลูกค้าพิมพ์มาเอง (เฉพาะเส้น customer_ask) */
        public readonly ?string $searchQuery = null,
    ) {}

    /**
     * บริบทสำหรับ "บอทเสนอเอง" — บังคับของสายมู + ค่าคอมขั้นต่ำตามที่ตั้งไว้
     *
     * @example
     * MuPickContext::proactive('facebook', $psid)
     *     ->withTopic('ปีนี้ชงเรื่องเงิน')
     *     ->withBirthYear(1985);
     */
    public static function proactive(string $platform, string $platformUserId): self
    {
        return new self(
            platform: $platform,
            platformUserId: $platformUserId,
            requireMu: true,
            minCommission: (float) MarketplaceSetting::get(self::SETTING_MIN_COMMISSION, 9),
            minPrice: (float) MarketplaceSetting::get(self::SETTING_MIN_PRICE, 25),
            maxPrice: (float) MarketplaceSetting::get(self::SETTING_MAX_PRICE, 2000),
        );
    }

    /**
     * บริบทสำหรับ "ลูกค้าถามหาของเอง"
     *
     * 🚨 ไม่บังคับค่าคอมขั้นต่ำ และไม่บังคับว่าต้องเป็นของสายมู (owner สั่ง 2026-08-22)
     *    เหตุผล: ลูกค้าถาม "มีสร้อยหยกไหม" แล้วของทั้งหมดค่าคอม 4% —
     *    ถ้าบังคับ 9% บอทจะตอบ "ไม่มี" ทั้งที่ Lazada มีเพียบ = เสียลูกค้าเพื่อรักษาเปอร์เซ็นต์
     *    ตัวเรียงลำดับยังเอาค่าคอมสูงขึ้นก่อนอยู่แล้ว จึงไม่ได้เสียรายได้
     *
     * @param  string|null  $query  คำค้นที่แกะได้จากข้อความลูกค้า
     * @param  float|null  $budget  งบที่ลูกค้าบอก (null = ไม่ระบุ)
     */
    public static function customerAsk(
        string $platform,
        string $platformUserId,
        ?string $query,
        ?float $budget = null
    ): self {
        return new self(
            platform: $platform,
            platformUserId: $platformUserId,
            requireMu: false,
            minCommission: (float) MarketplaceSetting::get(self::SETTING_ASK_MIN_COMMISSION, 0),
            minPrice: (float) MarketplaceSetting::get(self::SETTING_MIN_PRICE, 25),
            maxPrice: $budget !== null && $budget > 0 ? $budget : 0.0,
            searchQuery: $query,
        );
    }

    /**
     * คืนสำเนาที่ใช้ "เกณฑ์ค่าคอมขั้นผ่อน" — เรียกโดย MuProductPicker ตอนพูลของกลุ่มไม่พอ
     *
     * ค่าเริ่มต้น 5% (จุดที่ทั้ง 5 กลุ่มมีของครบ) และ **ไม่มีวันเข้มกว่าเกณฑ์ปกติ**
     * — ถ้า owner ตั้งเกณฑ์ปกติไว้ต่ำกว่าขั้นผ่อนอยู่แล้ว บันไดขั้นนี้จะไม่ทำอะไร
     */
    public function relaxed(): self
    {
        $floor = (float) MarketplaceSetting::get(self::SETTING_RELAXED_MIN_COMMISSION, 5);

        return new self(
            platform: $this->platform,
            platformUserId: $this->platformUserId,
            requireMu: $this->requireMu,
            minCommission: min($floor, $this->minCommission),
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            forcedGroup: $this->forcedGroup,
            topicText: $this->topicText,
            cardsText: $this->cardsText,
            birthYear: $this->birthYear,
            searchQuery: $this->searchQuery,
        );
    }

    /**
     * คืนสำเนาที่ **เลิกบังคับว่าต้องเป็นของสายมู** — ทางออกสุดท้ายของบันไดใน MuProductPicker
     *
     * ของสายมูในคลังมี ~90 ชิ้น ส่วนของหมวดทั่วไปมีหลายพัน
     * ถ้าไม่มีขั้นนี้ บอทจะวนอยู่ในของสายมูชุดเดิมตลอดกาล ต่อให้นำเข้าของใหม่เท่าไหร่ก็ตาม
     *
     * ⚠️ ใช้เป็นขั้นสุดท้ายเท่านั้น — ของที่ตรงเรื่องต้องชนะของทั่วไปเสมอ
     */
    public function withoutMuRequirement(): self
    {
        return new self(
            platform: $this->platform,
            platformUserId: $this->platformUserId,
            requireMu: false,
            minCommission: $this->minCommission,
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            // ล้างกลุ่มที่บังคับไว้ด้วย — ไม่งั้น candidates() จะไปกรอง mu_group ต่อ
            forcedGroup: null,
            topicText: $this->topicText,
            cardsText: $this->cardsText,
            birthYear: $this->birthYear,
            searchQuery: $this->searchQuery,
        );
    }

    /**
     * คืนสำเนาที่ใส่หัวข้อที่ลูกค้าคุย
     */
    public function withTopic(string $topicText): self
    {
        return $this->copyWith(topicText: $topicText);
    }

    /**
     * คืนสำเนาที่ใส่ชื่อไพ่ที่เปิดได้
     */
    public function withCards(string $cardsText): self
    {
        return $this->copyWith(cardsText: $cardsText);
    }

    /**
     * คืนสำเนาที่ใส่ปีเกิด (ค.ศ.)
     */
    public function withBirthYear(?int $birthYear): self
    {
        return $this->copyWith(birthYear: $birthYear);
    }

    /**
     * คืนสำเนาที่บังคับกลุ่มของ
     */
    public function withGroup(?string $group): self
    {
        return $this->copyWith(forcedGroup: $group);
    }

    /**
     * สร้างสำเนาโดยแทนที่เฉพาะฟิลด์ที่ระบุ (วัตถุนี้เป็น readonly)
     */
    private function copyWith(
        ?string $topicText = null,
        ?string $cardsText = null,
        ?int $birthYear = null,
        ?string $forcedGroup = null,
    ): self {
        return new self(
            platform: $this->platform,
            platformUserId: $this->platformUserId,
            requireMu: $this->requireMu,
            minCommission: $this->minCommission,
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            forcedGroup: $forcedGroup ?? $this->forcedGroup,
            topicText: $topicText ?? $this->topicText,
            cardsText: $cardsText ?? $this->cardsText,
            birthYear: $birthYear ?? $this->birthYear,
            searchQuery: $this->searchQuery,
        );
    }
}
