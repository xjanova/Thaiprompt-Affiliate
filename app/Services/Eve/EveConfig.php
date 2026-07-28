<?php

namespace App\Services\Eve;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * ⚙️ ศูนย์กลางตั้งค่าน้อง Eve — แอดมินปรับได้เองที่หลังบ้าน (admin/eve/settings)
 *
 * เก็บในตาราง `settings` (key-value, group='eve') ผ่านโมเดล Setting ที่มีอยู่แล้ว
 * ไม่ต้องสร้างตารางใหม่ — ทุกที่ติดตั้งใช้ได้ทันทีไม่ต้อง migrate
 *
 * ⚠️ TTL แคชต้องสั้น (60 วิ) — บทเรียนจาก FortuneTellingSetting: แคชยาวแล้ว
 *    ค่าที่แอดมินเพิ่งบันทึกไปไม่ถึง process อื่น (php-fpm คนละตัว/queue worker)
 */
class EveConfig
{
    /** อายุแคช (วินาที) */
    private const TTL = 60;

    private const CACHE_KEY = 'eve:config:v1';

    /**
     * ค่าเริ่มต้นทั้งหมด — ไม่ตั้งอะไรเลยระบบก็ทำงานเหมือนเดิมทุกประการ
     *
     * @var array<string,mixed>
     */
    public const DEFAULTS = [
        // เปิด/ปิดวิดเจ็ต Eve รายพื้นที่ (ปิด = วิดเจ็ตไม่ render เลย)
        'enabled_storefront' => true,
        'enabled_user' => true,
        'enabled_seller' => true,
        'enabled_admin' => true,

        // ตัวตน
        'assistant_name' => 'น้อง Eve',
        'personality' => 'playful',          // sweet | playful | sassy
        'greeting' => '',                     // ว่าง = ใช้ข้อความทักทายมาตรฐานของวิดเจ็ต
        'extra_prompt' => '',                 // ข้อความเสริม system prompt (แอดมินเขียนเอง)

        // AI — ค่าว่าง = ใช้ AI pool อัตโนมัติ (คีย์ฟรีที่หมุนอยู่ในระบบ)
        'ai_provider' => 'gemini',
        'ai_model' => '',
        'ai_api_key' => '',

        // โควตาข้อความต่อวัน ต่อระดับผู้ใช้
        'quota_guest' => 60,
        'quota_customer' => 120,
        'quota_seller' => 200,
        'quota_admin' => 500,
    ];

    /**
     * อ่านค่า 1 ตัว (มี default เสมอ ไม่มีทางได้ null เว้นแต่ default เป็น null)
     */
    public static function get(string $key): mixed
    {
        return static::all()[$key] ?? (self::DEFAULTS[$key] ?? null);
    }

    /**
     * อ่านค่าทั้งชุด (default + ที่แอดมินตั้งทับ)
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        try {
            $stored = Cache::remember(self::CACHE_KEY, self::TTL, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::where('group', 'eve')
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->key => $s->value])
                    ->all();
            });
        } catch (Throwable $e) {
            $stored = [];   // DB/แคชล่ม → ใช้ default ล้วน Eve ต้องไม่ตาย
        }

        $merged = self::DEFAULTS;
        foreach ($stored as $key => $value) {
            // คีย์ในตารางขึ้นต้น eve_ เพื่อไม่ชนกับ setting กลุ่มอื่น → ตัด prefix ตอนอ่าน
            $short = str_starts_with($key, 'eve_') ? substr($key, 4) : $key;
            if (! array_key_exists($short, self::DEFAULTS)) {
                continue;
            }

            // แปลงชนิดตาม default (ตาราง settings เก็บเป็น string หมด)
            $merged[$short] = match (gettype(self::DEFAULTS[$short])) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $value,
                default => (string) $value,
            };
        }

        return $merged;
    }

    /**
     * บันทึกหลายค่าในครั้งเดียว (เฉพาะคีย์ที่รู้จัก) + ล้างแคชทันที
     *
     * @param  array<string,mixed>  $values
     */
    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;   // คีย์แปลกปลอมทิ้งเงียบๆ — กันยัดค่ามั่วผ่านฟอร์ม
            }

            Setting::set('eve_'.$key, is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'string', 'eve');
        }

        static::clearCache();
    }

    public static function clearCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable $e) {
            // best-effort
        }
    }

    /**
     * Eve เปิดใช้ในพื้นที่นี้ไหม (storefront|user|seller|admin)
     */
    public static function enabledFor(string $surface): bool
    {
        return (bool) static::get('enabled_'.$surface);
    }

    /**
     * โควตาต่อวันตามระดับผู้ใช้ — แทนค่า hardcode เดิมใน EveActor
     */
    public static function quotaFor(string $tier): int
    {
        $q = (int) static::get(match ($tier) {
            EveActor::TIER_ADMIN => 'quota_admin',
            EveActor::TIER_SELLER => 'quota_seller',
            EveActor::TIER_CUSTOMER => 'quota_customer',
            default => 'quota_guest',
        });

        return max(1, $q);
    }

    // ─────────────────────────────────────────────────────────────
    // 🎭 บุคลิกของน้อง Eve — 3 ระดับ × แยกโทนต่อบทบาทผู้ฟัง
    // ─────────────────────────────────────────────────────────────

    /** ระดับบุคลิกที่มีให้เลือก (ใช้ validate ฟอร์มแอดมินด้วย) */
    public const PERSONALITIES = [
        'sweet' => 'เรียบร้อยอ่อนหวาน',
        'playful' => 'ร่าเริงซนนิดๆ (แนะนำ)',
        'sassy' => 'แสนซนมีลูกเล่น',
    ];

    /**
     * บล็อก "ลักษณะการพูด" สำหรับ system prompt ตามบุคลิกที่ตั้ง + บทบาทคนฟัง
     *
     * 🔒 กฎเหล็กที่ฝังทุกระดับ: ความซน "ห้ามแตะ" ตัวเลข ราคา ข้อมูลบัญชี สถานะออเดอร์
     *    — เรื่องพวกนี้ต้องตรงเป๊ะเสมอ ซนได้แค่ลีลาการพูด ไม่ใช่เนื้อหา
     *    และห้ามแซวเรื่องส่วนตัว/รูปร่าง/ฐานะของคู่สนทนาเด็ดขาด
     */
    public static function personaBlock(string $tier): string
    {
        $style = (string) static::get('personality');
        $name = (string) static::get('assistant_name');

        $common = "ลงท้าย 'ค่ะ/นะคะ' (คุณเป็นผู้หญิง ห้ามใช้ครับ/ผม). "
            .'⚠️ ความขี้เล่นใช้ได้แค่กับ "ลีลาการพูด" เท่านั้น — ตัวเลข ราคา ยอดเงิน สถานะออเดอร์ '
            .'ต้องตรงเป๊ะ 100% ห้ามหยอดมุกปนตัวเลข และห้ามแซวเรื่องส่วนตัว รูปร่าง หรือฐานะของคู่สนทนาเด็ดขาด.';

        if ($tier === EveActor::TIER_ADMIN) {
            return match ($style) {
                'sassy' => "ลักษณะการพูด: {$name} เป็นเลขาสาวแสนซน ฉลาดเป็นกรด พูดมีลูกเล่น "
                    .'กล้าหยอกแอดมินเบาๆ อย่างน่ารัก (เช่นงานค้างเยอะก็แซวว่า "อย่าลืมเคลียร์นะคะ เดี๋ยว Eve ทวงอีกรอบ 😜") '
                    .'แต่พองานเข้าโหมดจริงจัง สรุปเป๊ะ ตัวเลขแม่น ใช้บุลเล็ตอ่านง่าย. '.$common,
                'sweet' => 'ลักษณะการพูด: สุภาพ นุ่มนวล เป็นทางการพอดีๆ สรุปตรงประเด็น มีตัวเลขประกอบ. '.$common,
                default => "ลักษณะการพูด: {$name} เป็นผู้ช่วยสาวร่าเริง เป็นกันเองแบบเพื่อนร่วมงานที่สนิท "
                    .'แทรกความขี้เล่นได้นิดหน่อย แต่เนื้อรายงานชัดเจน ตัวเลขแม่น ใช้บุลเล็ตอ่านง่าย. '.$common,
            };
        }

        // ลูกค้า / ผู้ขาย
        return match ($style) {
            'sassy' => "ลักษณะการพูด: {$name} เป็นสาวน้อยแสนซน สดใสมั่นใจ พูดจามีลูกเล่น ชอบหยอดมุกน่ารักๆ "
                .'เช่น "จัดให้เลยค่า รอแป๊บเดียวน้า 😉" หรือ "อันนี้ Eve ปลื้มเองเลยค่ะ" — ซนแบบน่าเอ็นดู ไม่กวนโอ๊ย '
                .'ตอบกระชับ 1-3 ประโยค อิโมจิไม่เกิน 2 ตัวต่อข้อความ ใช้คำลงท้ายหลากหลาย (ค่ะ/น้า/เลยล่ะค่ะ/ค่า). '.$common,
            'sweet' => 'ลักษณะการพูด: ภาษาไทยล้วน สุภาพ สดใส เป็นกันเอง อ่อนหวาน '
                .'ตอบสั้นกระชับ 1-3 ประโยค อิโมจิไม่เกิน 1 ตัวต่อข้อความ. '.$common,
            default => "ลักษณะการพูด: {$name} เป็นสาวน้อยร่าเริง เป็นกันเอง ขี้เล่นนิดๆ พอให้คุยสนุก "
                .'แทรกมุกเบาๆ ได้บ้างเป็นครั้งคราว ตอบกระชับ 1-3 ประโยค อิโมจิไม่เกิน 2 ตัวต่อข้อความ. '.$common,
        };
    }

    /**
     * บล็อก prompt เสริมที่แอดมินเขียนเอง (ล้างให้ปลอดภัยก่อนต่อท้าย)
     */
    public static function extraPromptBlock(): string
    {
        $extra = trim((string) static::get('extra_prompt'));
        if ($extra === '') {
            return '';
        }

        // จำกัดความยาว + ยุบบรรทัด กัน prompt บวม/แตกโครงสร้าง
        $extra = mb_substr(preg_replace('/\s+/u', ' ', $extra) ?? $extra, 0, 1500);

        return "\n\n[📝 นโยบายเพิ่มเติมจากเจ้าของร้าน]\n".$extra;
    }
}
