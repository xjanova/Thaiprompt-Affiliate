<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Collection;

/**
 * ProductQueryParser — แกะ "คำค้นสินค้า + งบประมาณ" ออกจากข้อความลูกค้า และให้คะแนนความตรง
 *
 * เป็น service บริสุทธิ์ (ไม่แตะ DB ไม่แตะโมเดล) เพื่อให้ใช้ร่วมกันได้ทั้ง
 *   - น้อง Eve (ค้นตาราง `products` = หน้าร้านเรา)
 *   - บอทแม่หมอ (ค้นตาราง `marketplace_products` = ของ affiliate ลิงก์ออกไป Lazada)
 * ซึ่งค้นคนละตาราง คนละคอลัมน์ — ส่วนที่ใช้ร่วมกันได้จริงมีแค่ "แกะคำ" กับ "ให้คะแนน"
 *
 * 🚨 ทำไมต้องมี 2 ระดับความเข้ม (MODE_STRICT / MODE_RELAXED)
 *   ตัวจับเจตนาของ Eve ใช้คำสัญญาณกว้าง เช่น `ราคา|งบ|สนใจ|เอา|ต้องการ|แนะนำ|มี...ไหม`
 *   ซึ่งใช้กับเว็บร้านค้าได้ แต่ **เอามาใช้กับแชทแม่หมอตรงๆ ไม่ได้เด็ดขาด** —
 *   ลูกค้าดูดวงพิมพ์คำพวกนี้ตลอดเวลาโดยไม่ได้จะซื้อของ:
 *     "ราคาเท่าไหร่คะ" (ถามค่าดูดวง) · "สนใจดูดวง" · "อยากได้แฟนดีๆ"
 *     "แนะนำหน่อยว่าควรทำยังไง" · "มีดวงดีไหม"
 *   ถ้าใช้เกณฑ์กว้าง บอทจะเด้งไปโหมดขายของกลางวงดูดวง = พังทั้งบทสนทนา
 *
 *   ⇒ นอกโหมดช้อป ใช้ STRICT: ต้องมี "คำกริยาซื้อขายชัดเจน" + ต้องไม่ใช่ศัพท์สายดูดวง
 *   ⇒ ในโหมดช้อป (ลูกค้าตอบการ์ดสินค้าอยู่แล้ว) ใช้ RELAXED ได้ เพราะบริบทยืนยันแล้ว
 */
class ProductQueryParser
{
    /** นอกโหมดช้อป — ต้องมีสัญญาณซื้อขายชัดเจนเท่านั้น */
    public const MODE_STRICT = 'strict';

    /** ในโหมดช้อป — ลูกค้ากำลังคุยเรื่องของอยู่แล้ว ผ่อนเกณฑ์ได้ */
    public const MODE_RELAXED = 'relaxed';

    /**
     * 🔮 ศัพท์สายดูดวง — เจอคำพวกนี้ = กำลังคุยเรื่องดวง ไม่ใช่จะซื้อของ
     *
     * ⚠️ ต้องเช็ค "ก่อน" สัญญาณซื้อขายเสมอ เพราะประโยคดูดวงมีคำว่า ราคา/เอา/อยากได้ ปนอยู่บ่อย
     *    เช่น "ดูดวงราคาเท่าไหร่" มีทั้ง "ดูดวง" และ "ราคา" — ต้องแพ้ให้ฝั่งดูดวง
     */
    private const FORTUNE_DOMAIN = '/(ดูดวง|ดวงชะตา|ทำนาย|ไพ่ยิปซี|ไพ่ทาโรต์|เปิดไพ่|จับไพ่|ผูกดวง|พื้นดวง|'
        .'บูชาครู|ค่าครู|แพคเกจ|แพ็คเกจ|โอนแล้ว|สลิป|พร้อมเพย์|คิวอาร์|โอนเงิน|'
        .'ราศี|ลัคนา|เนื้อคู่|คู่ครอง|เคราะห์|กรรมเก่า|ชาติที่แล้ว|วันเกิด|เวลาเกิด|'
        .'คุณไสย|มนต์ดำ|แม่หมอ|จันทรา|ยกเลิกบิล|บิลค้าง)/u';

    /**
     * 🛒 สัญญาณ "จะซื้อของ" แบบเข้ม — ต้องเป็นกริยาซื้อขายที่ไม่กำกวมกับบทสนทนาดูดวง
     *
     * ตั้งใจ **ไม่ใส่** คำเหล่านี้ (กำกวมกับสายดูดวงสูงมาก):
     *   เอา · ต้องการ · สนใจ · แนะนำ · ราคา · งบ · มี…ไหม
     */
    private const BUY_SIGNAL_STRICT = '/(สั่งซื้อ|หาซื้อ|อยากซื้อ|จะซื้อ|ขอซื้อ|ซื้อได้ที่ไหน|ซื้อยังไง|ซื้อตรงไหน|'
        .'มีขาย|ขายไหม|ขายมั้ย|ขายมั๊ย|มีของ|สั่งของ|ฝากซื้อ|หาของ|หาสินค้า|'
        .'อยากได้ของ|อยากได้สินค้า|ช่วยหาของ|ช่วยหาสินค้า|หาให้หน่อย|หามาให้|หาให้ที)/u';

    /**
     * สัญญาณแบบผ่อน — ใช้เฉพาะตอนอยู่ในโหมดช้อปแล้วเท่านั้น (ยกมาจากตัวจับของ Eve)
     */
    private const BUY_SIGNAL_RELAXED = '/(หา|อยากได้|อยากซื้อ|อยากดู|อยากลอง|หาซื้อ|ซื้อ|ขาย|มีขาย|สนใจ|ต้องการ|'
        .'มองหา|ตามหา|มี.{0,20}(ไหม|มั้ย|ป่าว|รึเปล่า|อีก)|แนะนำ|อยากหา|ช่วยหา|ช่วยดู|ขอดู|เอา|สั่ง|'
        .'รุ่นไหน|ยี่ห้อ|ราคา|งบ|ถูกกว่า|แพงกว่า|อันอื่น|ตัวอื่น|แบบอื่น)/u';

    /** ทักทาย/ขอบคุณ/ถามเรื่องระบบ = ไม่ใช่การหาสินค้าแน่นอน */
    private const NON_PRODUCT_TALK = '/(สวัสดี|หวัดดี|ฮัลโหล|ขอบคุณ|ขอบใจ|ยินดี|เก่งมาก|น่ารัก|บาย|ลาก่อน|โอเค|ตกลง|ได้เลย|'
        .'สมัคร|สมาชิก|เข้าสู่ระบบ|ล็อกอิน|รหัสผ่าน|ยกเลิก|คืนเงิน|ค่าส่ง|กี่วัน|ส่งของ|ติดตามพัสดุ|'
        .'เธอคือใคร|คุณคือใคร|ชื่ออะไร|ทำอะไรได้)/u';

    /** คำค้นที่สั้นกว่านี้ = ไม่มีความหมายพอจะค้น */
    private const MIN_QUERY_LENGTH = 2;

    /** ยาวเกินนี้ = เป็นประโยคเล่าเรื่อง ไม่ใช่ชื่อสินค้า */
    private const MAX_QUERY_LENGTH = 60;

    /**
     * แกะข้อความลูกค้า → [คำค้น, งบประมาณ]
     *
     * @param  string  $message  ข้อความดิบจากลูกค้า
     * @param  string  $mode  MODE_STRICT (นอกโหมดช้อป) | MODE_RELAXED (ในโหมดช้อป)
     * @return array{0:?string,1:?float} [คำค้น (null = ไม่ใช่การหาสินค้า), งบ (null = ไม่ระบุ)]
     *
     * @example
     * [$q, $budget] = $parser->parse('อยากได้ปี่เซี้ยะ งบไม่เกิน 500');
     * // ['ปี่เซี้ยะ', 500.0]
     */
    public function parse(string $message, string $mode = self::MODE_STRICT): array
    {
        $q = trim((string) preg_replace('/\s+/u', ' ', $message));
        if ($q === '') {
            return [null, null];
        }

        // 💰 ดึงงบก่อนเสมอ — ลูกค้าตอบสั้นๆ ว่า "500 บาท" ก็ยังต้องได้ตัวเลขไปใช้กับคำค้นเดิม
        $budget = $this->extractBudget($q);

        if (preg_match(self::NON_PRODUCT_TALK, $q)) {
            return [null, $budget];
        }

        // 🔮 ด่านสำคัญที่สุด: กำลังคุยเรื่องดวงอยู่ = ห้ามตีความเป็นการซื้อของ
        //    เช็คก่อนสัญญาณซื้อขายเสมอ ("ดูดวงราคาเท่าไหร่" ต้องแพ้ให้ฝั่งดูดวง)
        if (preg_match(self::FORTUNE_DOMAIN, $q)) {
            return [null, $budget];
        }

        $signal = $mode === self::MODE_RELAXED ? self::BUY_SIGNAL_RELAXED : self::BUY_SIGNAL_STRICT;
        if (! preg_match($signal, $q)) {
            return [null, $budget];
        }

        $q = $this->stripNoise($q);

        if (! $this->isUsableQuery($q)) {
            return [null, $budget];
        }

        return [$q, $budget];
    }

    /**
     * แกะงบประมาณจากข้อความ ("งบ 500", "ไม่เกิน 1,200 บาท", "300 บาท")
     *
     * @return float|null ยอดเงิน หรือ null ถ้าไม่ระบุ
     */
    public function extractBudget(string $message): ?float
    {
        if (preg_match('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคา)\s*([0-9][0-9,.]*)/u', $message, $m)
            || preg_match('/([0-9][0-9,.]*)\s*บาท/u', $message, $m)) {
            $num = (float) preg_replace('/[^0-9.]/', '', $m[1]);

            return $num > 0 ? $num : null;
        }

        return null;
    }

    /**
     * ตัดคำสั่ง/คำลงท้าย/วลีราคา ออกจากคำค้น ให้เหลือแต่ชื่อของ
     *
     * ⚠️ ตัดคำนำหน้าแบบยึดหัวประโยค (^) เท่านั้น ห้ามตัดทั่วประโยค —
     *    ภาษาไทยไม่เว้นวรรค การตัดคำว่า "มี" ทั่วประโยคจะทำลาย "มีด" และ "ดู" จะทำลาย "ดูดวง"
     */
    private function stripNoise(string $q): string
    {
        // วลีงบ/ราคา (มีตัวเลขกำกับ = ปลอดภัย ไม่ทับชื่อสินค้า)
        $q = (string) preg_replace('/(?:งบ|ไม่เกิน|ภายใน|ไม่ถึง|ราคาไม่เกิน|ราคา)\s*[0-9][0-9,.]*\s*(?:บาท)?/u', ' ', $q);
        $q = (string) preg_replace('/[0-9][0-9,.]*\s*บาท/u', ' ', $q);

        // คำสั่งหัวประโยค — คำประสมที่ยาว/ชัดเจนกว่าต้องมาก่อน
        $q = (string) preg_replace('/^\s*(ช่วย|รบกวน|แม่หมอ)\s*/u', '', $q);
        $q = (string) preg_replace('/^\s*(ขอถาม|สอบถาม|อยากถาม|ขอสอบถาม)\s*/u', '', $q);
        $q = (string) preg_replace('/^\s*(ช่วยหาสินค้า|ช่วยหาของ|อยากได้สินค้า|อยากได้ของ|หาสินค้า|หาของ|สั่งซื้อ|หาซื้อ|'
            .'ขอดู|อยากได้|อยากซื้อ|อยากดู|อยากลอง|อยากหา|หาให้|ช่วยหา|ช่วยดู|แนะนำ|สนใจ|ต้องการ|มองหา|ตามหา|'
            .'หา|ซื้อ|สั่ง|เอา)\s*/u', '', $q);

        // คำลงท้าย/คำถามท้ายประโยค
        $q = (string) preg_replace('/\s*(ราคาถูกๆ|ราคาถูก|ราคาประหยัด|เท่าไหร่|เท่าไร|ถูกๆ|ถูก|ดีๆ|สวยๆ|'
            .'หน่อยค่ะ|หน่อยครับ|หน่อย|ด้วยค่ะ|ด้วยครับ|ด้วย|ให้หน่อย|ให้ที|ทีนะ|นะคะ|นะครับ|จ้า|ค่ะ|คะ|ครับ|'
            .'มีขายไหม|มีขายมั้ย|มีขาย|ขายไหม|ขายมั้ย|ขาย|มีไหม|มีมั้ย|ไหม|มั้ย|ป่าว|รึเปล่า|บ้าง|'
            .'อย่างอื่น|อื่นๆ|อื่น|อีก|ล่ะ|หละ|เลยสิ|เลย|สิ|อะ|อ่ะ)+\s*$/u', '', $q);

        // เครื่องหมายวรรคตอนหัว-ท้าย
        $q = (string) preg_replace('/^[\p{P}\p{S}\s]+|[\p{P}\p{S}\s]+$/u', '', $q);

        return trim((string) preg_replace('/\s+/u', ' ', $q));
    }

    /**
     * คำค้นที่เหลือ ใช้ค้นได้จริงไหม
     *
     * 🚨 สำคัญ: คำค้นขยะจะถูกบันทึกเป็นคีย์เวิร์ดนำเข้าและคิว "ของที่ลูกค้าอยากได้"
     *    ต้องกันตั้งแต่ตรงนี้ ไม่งั้นหลังบ้านจะเต็มไปด้วยคำว่า "อะไร" "อีก" "เอ่อ"
     */
    private function isUsableQuery(string $q): bool
    {
        if (mb_strlen($q) < self::MIN_QUERY_LENGTH || mb_strlen($q) > self::MAX_QUERY_LENGTH) {
            return false;
        }

        // เหลือแต่คำถาม/คำอุทานลังเล
        if (preg_match('/^(อะไร|เท่าไหร่|เท่าไร|ยังไง|ที่ไหน|อันไหน|แบบไหน|ไง|ดี|มี|ของ|ราคา|อีก|อื่น|อย่างอื่น|'
            .'เอ่อ|เอิ่ม|อืม|อึม|อ่า|อ๋อ|เอ๊ะ|หืม|งง|โอ้|ฮะ|ฮ่ะ|555+)$/u', $q)) {
            return false;
        }

        // ต้องมีตัวอักษร/ตัวเลขอย่างน้อย 2 ตัว — กันคำค้นที่เหลือแต่สัญลักษณ์/อีโมจิ
        return preg_match_all('/[\p{L}\p{N}]/u', $q) >= 2;
    }

    /**
     * แตกคำค้นเป็น token (เว้นวรรคเป็นตัวคั่น — ภาษาไทยส่วนใหญ่มาเป็นก้อนเดียว)
     *
     * @return Collection<int,string>
     */
    public function tokenize(string $query): Collection
    {
        return collect(preg_split('/\s+/u', trim($query)) ?: [])
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => mb_strlen($t) >= 2)
            ->take(6)
            ->values();
    }

    /**
     * 🔤 ขยายคำค้นด้วยพจนานุกรมคำพ้อง (ไทย↔อังกฤษ)
     *
     * จำเป็นมากกับของสายมู: ใน 10 อันดับค่าคอมสูงสุดของสายมูจริง มี 6 ชิ้นชื่ออังกฤษล้วน
     * (`Pixiu Tiger's Eye Stone`, `Nobel - Pi Xiu Bracelet`, `Jadeite Zodiac`)
     * ⇒ ลูกค้าพิมพ์ "ปี่เซี้ยะ" แล้ว LIKE ตรงๆ จะไม่โดนสักตัว
     *
     * วิธีจับ: คำในกลุ่มเป็น "substring" ของคำค้น (ไม่ใช่ token-equality)
     * เพราะภาษาไทยไม่เว้นวรรค — "หูฟังบลูทูธ" เป็นก้อนเดียว เทียบเท่ากับ "หูฟัง" ตรงๆ ไม่ได้
     *
     * @param  Collection<int,string>  $primary
     * @return array<int,string> คำค้นเสริม (ไม่ซ้ำกับ primary)
     */
    public function expandSynonyms(string $query, Collection $primary): array
    {
        $q = mb_strtolower($query);
        $extras = [];

        foreach ((array) config('eve_search_synonyms', []) as $group) {
            if (! is_array($group)) {
                continue;
            }

            $hit = false;
            foreach ($group as $word) {
                $w = mb_strtolower(trim((string) $word));
                if (mb_strlen($w) >= 3 && mb_strpos($q, $w) !== false) {
                    $hit = true;
                    break;
                }
            }

            if ($hit) {
                foreach ($group as $word) {
                    $extras[] = mb_strtolower(trim((string) $word));
                }
            }
        }

        $primaryLower = $primary->map(fn ($t) => mb_strtolower($t))->all();

        // จำกัดจำนวน — หลายกลุ่มพร้อมกันจะทำ WHERE บวมเกินจำเป็น
        return collect($extras)
            ->unique()
            ->reject(fn ($w) => $w === '' || in_array($w, $primaryLower, true))
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * 🎯 ให้คะแนนความตรงของสินค้า 1 ชิ้นกับคำค้น
     *
     * หลัก: วลีเต็ม > คำที่ลูกค้าพิมพ์เอง > คำพ้องที่ระบบเติมให้ · ชื่อ > ยี่ห้อ > คำอธิบาย
     *
     * รับเป็น "สตริงล้วน" ไม่ใช่โมเดล เพื่อให้ใช้ได้ทั้ง Product และ MarketplaceProduct
     *
     * @param  Collection<int,string>  $primary  คำที่ลูกค้าพิมพ์เอง
     * @param  array<int,string>  $synonyms  คำพ้องที่ระบบเติม
     */
    public function score(
        string $name,
        string $brand,
        string $description,
        string $query,
        Collection $primary,
        array $synonyms
    ): float {
        $name = mb_strtolower($name);
        $brand = mb_strtolower($brand);
        $desc = mb_strtolower($description);

        $score = 0.0;

        // วลีเต็มที่ลูกค้าพิมพ์อยู่ในชื่อ = ตรงที่สุด
        if ($query !== '' && mb_strpos($name, mb_strtolower($query)) !== false) {
            $score += 100;
        }

        foreach ($primary as $t) {
            $t = mb_strtolower($t);
            if (mb_strpos($name, $t) !== false) {
                $score += 40;
            } elseif ($brand !== '' && mb_strpos($brand, $t) !== false) {
                $score += 15;
            } elseif ($desc !== '' && mb_strpos($desc, $t) !== false) {
                $score += 8;
            }
        }

        foreach ($synonyms as $t) {
            if (mb_strpos($name, $t) !== false) {
                $score += 22;
            } elseif ($brand !== '' && mb_strpos($brand, $t) !== false) {
                $score += 8;
            } elseif ($desc !== '' && mb_strpos($desc, $t) !== false) {
                $score += 4;
            }
        }

        return $score;
    }
}
