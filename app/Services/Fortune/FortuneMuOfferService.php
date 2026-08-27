<?php

namespace App\Services\Fortune;

use App\Models\FortuneCustomerPersona;
use App\Models\FortuneProductOffer;
use App\Models\FortuneReading;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSetting;
use App\Services\FacebookWebhookService;
use App\Services\FortuneBanService;
use App\Services\LineFortuneService;
use App\Services\Marketplace\MuOfferCardBuilder;
use App\Services\Marketplace\MuPickContext;
use App\Services\Marketplace\MuProductPicker;
use App\Services\Marketplace\ProductQueryParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FortuneMuOfferService — ตัวคุมการเสนอสินค้าเสริมดวงของบอทแม่หมอ
 *
 * เป็นประตูเดียวที่ทุกจุดยิงต้องผ่าน — ด่านกันทั้งหมดอยู่ที่นี่ที่เดียว
 * ถ้าใครไปเรียก MuProductPicker + ส่งเองตรงๆ ด่านพวกนี้จะถูกข้าม
 *
 * 🚨 กฎเหล็ก 3 ข้อ
 *   1. **ห้ามแทรกก่อนคำทำนายถึงมือลูกค้า** — ผู้เรียกต้องมั่นใจว่าคำทำนายส่งไปแล้ว
 *      (กฎเดิมของระบบ: ห้ามมีอะไรคั่นระหว่างจ่ายเงิน → คำทำนาย)
 *   2. **ห้ามยิงในเลนคอมเมนต์เพจ** — service นี้ส่งผ่านช่องแชทเท่านั้น
 *      คอมเมนต์วิ่งผ่าน `replyToComment()` คนละเส้น จึงปลอดภัยโดยโครงสร้าง
 *   3. **ขายของพังห้ามทำให้ดูดวงพัง** — ทุกเมธอดสาธารณะห่อ try/catch คืน false เงียบๆ
 */
class FortuneMuOfferService
{
    /** สวิตช์ใหญ่ — ปิดไว้ก่อนจนกว่าจะ verify บนพร็อด */
    private const SETTING_ENABLED = 'fortune_mu_offer_enabled';

    /** จุดยิงที่เปิดอยู่ (คั่นด้วยจุลภาค) — ว่าง = เปิดทุกจุด */
    private const SETTING_TRIGGERS = 'fortune_mu_offer_triggers';

    /**
     * หน่วงกี่นาทีก่อนส่ง แยกรายจุดยิง — JSON `{"daily_free":60,"gesture_image":3}`
     *
     * 0 หรือไม่ระบุ = ส่งทันที
     */
    private const SETTING_DELAYS = 'fortune_mu_offer_delays';

    /**
     * คีย์เดิมของสายดวงฟรี (ก่อนมีตารางหน่วงเวลารายจุด)
     *
     * ⚠️ ห้ามลบ — พร็อดตั้งค่านี้ไว้แล้ว การถอดทิ้งจะทำให้ระยะห่าง 1 ชม.
     *    ที่ owner สั่งไว้ (2026-08-23) หายเงียบๆ กลับไปส่งติดกล่องดวงฟรีทันที
     *    ใช้เป็นค่าสำรองเฉพาะตอนตาราง JSON ไม่มีคีย์ daily_free
     */
    private const SETTING_DAILY_FREE_DELAY_LEGACY = 'fortune_mu_offer_daily_free_delay_minutes';

    /** บอทเสนอเองได้กี่ครั้งต่อคนต่อวัน (owner สั่ง: วันละครั้ง) */
    private const SETTING_DAILY_CAP = 'fortune_mu_offer_daily_cap';

    /**
     * โควตาแยกของ "ท้ายบิลที่จ่ายเงินแล้ว" (celtic_end / deep_end)
     *
     * คนละกระเป๋ากับ SETTING_DAILY_CAP โดยตั้งใจ — ดูเหตุผลที่ FortuneProductOffer::PAID_END_TRIGGERS
     *
     * ⚠️ 0 = ไม่จำกัด (ความหมายเดียวกับ SETTING_DAILY_CAP ห้ามให้ต่างกัน)
     *    ถ้าจะ "ปิด" การเสนอท้ายบิล ให้ถอด celtic_end,deep_end ออกจาก SETTING_TRIGGERS
     *    ซึ่งเป็นสวิตช์ปิดของจริงอยู่แล้ว — อย่าสร้างทางปิดที่สองที่ความหมายกลับด้านกัน
     */
    private const SETTING_PAID_END_DAILY_CAP = 'fortune_mu_offer_paid_end_daily_cap';

    /** ลูกค้าแสดงความรำคาญ → เงียบกี่วัน */
    private const SETTING_MUTE_DAYS = 'fortune_mu_offer_mute_days';

    /**
     * 🛍️ เห็นการ์ดสินค้าไปแล้วกี่ชั่วโมง ยังนับว่า "อยู่ในโหมดช้อป"
     *
     * ⚠️ 24 ชม. ไม่ได้ตั้งมั่ว — เคสจริงคือได้การ์ดตอน 05:37 แล้วกลับมาถามตอน 17:54
     *    (ห่าง 12 ชม.) ถ้าตั้งสั้นแบบ 30 นาที เคสที่ทำให้ต้องสร้างฟีเจอร์นี้จะยังหลุดเหมือนเดิม
     */
    private const SETTING_SHOP_MODE_HOURS = 'fortune_mu_offer_shop_mode_hours';

    /**
     * 😤 ลูกค้ารำคาญ/ไม่อยากให้ส่งอีก → ตัดจบสุภาพ + เงียบยาว
     *
     * ⚠️ ต้องแคบมาก — คำกว้างเกินไปจะทำให้ลูกค้าที่แค่พูดคำธรรมดาโดนตัดออกจากระบบถาวร
     *    เช่นห้ามใส่ "ไม่" เดี่ยวๆ ("ไม่แน่ใจ" "ไม่ทราบ" = คุยปกติ)
     *
     * 🚫 เส้นแบ่งสำคัญ (owner ตัดสิน 2026-08-22):
     *    "ไม่เอา" เฉยๆ = **ยังยิงได้** (แค่ไม่ซื้อ ไม่ได้รำคาญ)
     *    ต้องมีน้ำเสียงรำคาญ/สั่งห้าม ถึงจะเข้าเกณฑ์นี้
     */
    private const ANNOYED_PATTERN = '/(รำคาญ|น่ารำคาญ|เลิกส่ง|ไม่ต้องส่ง|หยุดส่ง|อย่าส่ง|ไม่ต้องเสนอ|อย่าเสนอ|'
        .'ไม่ต้องขาย|อย่าขายของ|เลิกขาย|พอแล้ว|พอเถอะ|หยุดเถอะ|อย่ามายุ่ง|อย่ามากวน|ไม่ต้องมายุ่ง|'
        .'สแปม|กวนตีน|กวนประสาท|เยอะ)/u';

    public function __construct(
        private MuProductPicker $picker,
        private MuOfferCardBuilder $cards,
        private ProductQueryParser $parser,
    ) {}

    /**
     * เสนอสินค้าให้ลูกค้า 1 รอบ (ปกติได้ 2 ชิ้น — ราคาต่ำ + ราคาสูง)
     *
     * @param  string  $platform  facebook | line
     * @param  string  $platformUserId  FB PSID / LINE userId
     * @param  string  $trigger  ดู FortuneProductOffer::TRIGGER_*
     * @param  FortuneReading|null  $reading  ผูกกับการดูดวงใบไหน (ถ้ามี)
     * @param  array<string,mixed>  $options  topicText · cardsText · birthYear · searchQuery · budget · replyToken
     * @return bool true = ส่งถึงลูกค้าแล้วจริง
     *
     * @example
     * $service->offer('facebook', $psid, FortuneProductOffer::TRIGGER_CELTIC_END, $reading, [
     *     'topicText' => 'ปีนี้ชงเรื่องเงิน',
     *     'cardsText' => 'The Tower, Ten of Pentacles',
     * ]);
     */
    /**
     * 🕐 ประตูหน้าสุด — เสนอสินค้า "ตามระยะหน่วงที่ตั้งไว้ของจุดยิงนั้น"
     *
     * ทุก call site ควรเรียกตัวนี้ ไม่ใช่ `offer()` ตรงๆ
     *   - หน่วง 0 นาที → ส่งทันที (เหมือนเดิมทุกประการ)
     *   - หน่วง > 0    → เข้าคิว `SendMuOfferJob` แล้วค่อยส่ง
     *
     * 🚨 ด่านที่เช็ค **ตอนนี้** มีแค่ "จุดยิงนี้เปิดอยู่ไหม" เท่านั้น
     *    ด่านที่เหลือ (เพดานรายวัน · คนสั่งเงียบ · คนถูกแบน · โควตา LINE)
     *    ต้องเช็คตอน job ทำงานจริง — สถานะลูกค้าเปลี่ยนได้ระหว่างที่รอ
     *    เช่นลูกค้าพิมพ์ "รำคาญ" ในนาทีที่ 20 ของการหน่วง 60 นาที
     *
     * @return bool true = **ส่งถึงลูกค้าแล้วจริง** (เข้าคิวสำเร็จยังคืน false —
     *              ผู้เรียกที่ใช้ค่านี้ตัดสินใจต่อ ต้องแปลว่า "ยังไม่ถึงมือลูกค้า")
     */
    public function send(
        string $platform,
        string $platformUserId,
        string $trigger,
        ?FortuneReading $reading = null,
        array $options = []
    ): bool {
        try {
            // ปิดอยู่ = ไม่ต้องเปลืองที่ในคิว
            if (! $this->isEnabled($trigger)) {
                return false;
            }

            $minutes = $this->delayMinutesFor($trigger);
            if ($minutes <= 0) {
                return $this->offer($platform, $platformUserId, $trigger, $reading, $options);
            }

            // 🚧 กันคิวบวม — ลูกค้าคนเดียวยิงรูปรัว 10 ใบ = 10 งานรอส่งพร้อมกัน
            //    เพดานรายวันตัดตอนงานทำงานก็จริง แต่ถ้างานหลายตัวตื่นพร้อมกันคนละ worker
            //    จะแย่งกันเช็คแล้วหลุดส่งซ้ำได้ ⇒ กันตั้งแต่ตอนเข้าคิวเลย
            //    แยกกุญแจตามจุดยิง เพื่อไม่ให้การ์ดท้ายบิลที่จ่ายเงินแล้วโดนดวงฟรีบังคิว
            $lockKey = "mu_offer_q:{$platform}:{$platformUserId}:{$trigger}";
            if (! Cache::add($lockKey, 1, ($minutes * 60) + 120)) {
                return false;
            }

            // ⚠️ ตั้ง delay บนตัว job ให้เสร็จ "ก่อน" dispatch
            //    `Job::dispatch(...)->delay(...)` คืน PendingDispatch ซึ่งยิงจริงตอน __destruct
            //    มีข้อยกเว้นแทรกกลางทางเมื่อไหร่ งานจะหลุดออกไปแบบไม่มี delay
            $job = new \App\Jobs\SendMuOfferJob(
                $platform,
                $platformUserId,
                $trigger,
                $reading?->id,
                $options
            );
            $job->delay(now()->addMinutes($minutes));

            // \dispatch() = ฟังก์ชัน global — คลาสนี้มีเมธอด private ชื่อ dispatch() อยู่ด้วย
            // ใส่ backslash ไว้ให้ชัดว่าเรียกคนละตัว
            \dispatch($job);

            Log::debug('MuOffer: เข้าคิวเสนอสินค้าแบบหน่วงเวลา', [
                'platform' => $platform,
                'trigger' => $trigger,
                'delay_minutes' => $minutes,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('MuOffer: ตั้งงานเสนอสินค้าไม่สำเร็จ (ไม่กระทบ flow หลัก)', [
                'platform' => $platform,
                'user_id' => $platformUserId,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * หน่วงกี่นาทีก่อนส่งการ์ดของจุดยิงนี้
     *
     * ลำดับที่อ่าน: ตาราง JSON รายจุด → คีย์เดิมของสายดวงฟรี → 0 (ส่งทันที)
     *
     * @return int นาที (0 = ส่งทันที)
     */
    public function delayMinutesFor(string $trigger): int
    {
        // 🔓 ลูกค้าถามเอง = ต้องตอบเดี๋ยวนั้น หน่วงไม่ได้ไม่ว่าตารางจะเขียนไว้ว่าอะไร
        //    (หน้าแอดมินล็อกช่องนี้ไว้แล้ว บังคับซ้ำที่นี่กันค่าเก่าใน DB หลุดมามีผล)
        if (in_array($trigger, FortuneProductOffer::ALWAYS_ON_TRIGGERS, true)) {
            return 0;
        }

        $map = MarketplaceSetting::get(self::SETTING_DELAYS, null);
        if (is_string($map)) {
            $map = json_decode($map, true);
        }

        if (is_array($map) && array_key_exists($trigger, $map)) {
            return max(0, (int) $map[$trigger]);
        }

        // ค่าสำรองของสายดวงฟรี — ระยะห่าง 1 ชม. ที่ owner สั่งไว้ ต้องไม่หายไปตอนอัปโค้ด
        if ($trigger === FortuneProductOffer::TRIGGER_DAILY_FREE) {
            return max(0, (int) MarketplaceSetting::get(self::SETTING_DAILY_FREE_DELAY_LEGACY, 60));
        }

        return 0;
    }

    public function offer(
        string $platform,
        string $platformUserId,
        string $trigger,
        ?FortuneReading $reading = null,
        array $options = []
    ): bool {
        try {
            if (! $this->canOffer($platform, $platformUserId, $trigger)) {
                return false;
            }

            $ctx = $this->buildContext($platform, $platformUserId, $trigger, $reading, $options);
            $picked = $this->picker->pick($ctx);

            if (empty($picked['items'])) {
                Log::debug('MuOffer: ไม่มีของที่ผ่านเกณฑ์ — ข้ามเงียบๆ', [
                    'platform' => $platform,
                    'trigger' => $trigger,
                    'reason' => $picked['reason'] ?? '',
                ]);

                return false;
            }

            $sent = $this->dispatch($platform, $platformUserId, $trigger, $picked['items'], $options);
            if (! $sent) {
                return false;
            }

            $this->record($platform, $platformUserId, $trigger, $reading, $picked, $options);

            return true;
        } catch (\Throwable $e) {
            // ⛔ ขายของพังห้ามทำให้ดูดวงพัง
            Log::warning('MuOffer: เสนอสินค้าล้มเหลว (ไม่กระทบ flow หลัก)', [
                'platform' => $platform,
                'user_id' => $platformUserId,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ผ่านด่านทั้งหมดไหม
     */
    public function canOffer(string $platform, string $platformUserId, string $trigger): bool
    {
        if (! $this->isEnabled($trigger)) {
            return false;
        }

        if ($this->isMuted($platform, $platformUserId)) {
            return false;
        }

        // 💸 (2026-08-27) การ์ดขายของ = push ที่ไม่วิกฤต — โควตา LINE ใกล้หมดให้ข้าม
        //   ย้ายมาจาก FortuneChannelManager::offerProductsAfterReading() เพราะเดิมกันแค่
        //   ท้ายบิล ⇒ สายดวงฟรี (1,841 ใบ/7 วัน) กินโควตาไปโดยไม่ผ่านด่านนี้เลย
        //   และตั้งแต่มีการหน่วงเวลา การเช็คตอน dispatch ก็เป็นข้อมูลเก่าไปแล้ว
        //   แพลน LINE = 300 push/เดือน · โควตาหมด = 429 ปิดปาก webhook ทั้งเส้น
        if ($platform === 'line') {
            try {
                if (! app(LineFortuneService::class)->canSpendNonCriticalPush()) {
                    return false;
                }
            } catch (\Throwable $e) {
                Log::debug('MuOffer: เช็คโควตา LINE ไม่ได้ — ปล่อยผ่าน', ['error' => $e->getMessage()]);
            }
        }

        // ⛔ (2026-08-23) คนที่ถูกแบนอยู่ ห้ามได้การ์ดขายของ
        //   ด่านนี้อยู่ในแผนตั้งแต่แรกแต่ตกหล่นไป — สำคัญมากกับเส้น "ส่งรูป/ลิงก์"
        //   เพราะคนยิงลิงก์สแปมคือกลุ่มที่โดนแบนบ่อยที่สุด (เคสอุดม ศรีโปฎก ยิงลิงก์ 13 ครั้ง)
        //   ยิงการ์ดกลับไป = ตอบโต้บัญชีสแปมโดยอัตโนมัติ ซึ่งเป็นสัญญาณแย่ต่อเพจ
        try {
            if (app(FortuneBanService::class)->isBanned($platform, $platformUserId)) {
                return false;
            }
        } catch (\Throwable $e) {
            // เช็คแบนไม่ได้ = ไม่เสี่ยง ปล่อยผ่านไม่ได้ ⇒ ถือว่าห้ามส่ง (fail-closed)
            Log::warning('MuOffer: เช็คสถานะแบนไม่ได้ — ไม่ส่งไว้ก่อน', [
                'platform' => $platform,
                'user_id' => $platformUserId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        // เพดานรายวันนับเฉพาะ "บอทเสนอเอง" — ลูกค้าถามเองต้องได้คำตอบเสมอ
        //
        // 💰 (2026-08-23) แยกเป็น 2 กระเป๋า: ท้ายบิลที่จ่ายเงินแล้ว vs ที่เหลือ
        //    เดิมรวมกระเป๋าเดียว ⇒ การ์ดจากดวงฟรีตอนเช้ากินโควตา แล้วคนที่จ่าย 99
        //    ดูจบตอนบ่ายไม่ได้การ์ดเลย (เคส Zurich Mock: ฟรี 13:20 → จ่าย 15:05 → ดูจบ 15:35 เงียบ)
        //    นาทีที่ลูกค้าเพิ่งจ่ายและพอใจ = นาทีที่ขายของได้ดีที่สุด ห้ามให้ของฟรีมาแย่งคิว
        if (in_array($trigger, FortuneProductOffer::PAID_END_TRIGGERS, true)) {
            $cap = max(0, (int) MarketplaceSetting::get(self::SETTING_PAID_END_DAILY_CAP, 1));
            $used = FortuneProductOffer::proactiveCountToday(
                $platform,
                $platformUserId,
                FortuneProductOffer::PAID_END_TRIGGERS
            );

            if ($cap > 0 && $used >= $cap) {
                return false;
            }
        } elseif (in_array($trigger, FortuneProductOffer::PROACTIVE_TRIGGERS, true)) {
            $cap = max(0, (int) MarketplaceSetting::get(self::SETTING_DAILY_CAP, 1));
            $used = FortuneProductOffer::proactiveCountToday(
                $platform,
                $platformUserId,
                FortuneProductOffer::unpaidProactiveTriggers()
            );

            if ($cap > 0 && $used >= $cap) {
                return false;
            }
        }

        return true;
    }

    /**
     * ระบบเปิดอยู่ไหม + จุดยิงนี้เปิดไหม
     */
    public function isEnabled(string $trigger): bool
    {
        if (! filter_var(MarketplaceSetting::get(self::SETTING_ENABLED, false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        // 🔓 จุดยิงที่ปิดรายจุดไม่ได้ — ลูกค้าถามหาของเอง ต้องได้คำตอบเสมอ
        //    (การ์ดทุกใบสัญญาไว้เองว่า "อยากได้อะไรบอกมา แม่หมอหาให้" — ดู ALWAYS_ON_TRIGGERS)
        if (in_array($trigger, FortuneProductOffer::ALWAYS_ON_TRIGGERS, true)) {
            return true;
        }

        $raw = trim((string) MarketplaceSetting::get(self::SETTING_TRIGGERS, ''));
        if ($raw === '') {
            return true; // ไม่ระบุ = เปิดทุกจุด
        }

        $allowed = array_filter(array_map('trim', explode(',', $raw)));

        if (in_array($trigger, $allowed, true)) {
            return true;
        }

        // 🕰️ ค่าเดิมในพร็อดเก็บคำว่า `gesture` รวมทุกท่า (รูป/ลิงก์/สติกเกอร์)
        //    ถ้าไม่แปลให้ ช่วงระหว่าง deploy กับตอนแอดมินกดบันทึกครั้งแรก
        //    การ์ดจากเส้น "ส่งรูป/ส่งลิงก์" จะดับไปเงียบๆ ทั้งที่ไม่มีใครสั่งปิด
        if (in_array($trigger, FortuneProductOffer::GESTURE_TRIGGERS, true)) {
            return in_array(FortuneProductOffer::TRIGGER_GESTURE, $allowed, true);
        }

        return false;
    }

    /**
     * ลูกค้าคนนี้ถูกสั่งห้ามเสนอสินค้าอยู่ไหม
     */
    public function isMuted(string $platform, string $platformUserId): bool
    {
        $persona = FortuneCustomerPersona::findByPlatformUser($platform, $platformUserId);

        return $persona?->product_offer_muted_until !== null
            && $persona->product_offer_muted_until->isFuture();
    }

    /**
     * ข้อความนี้แสดงความรำคาญ/สั่งห้ามเสนอของไหม
     *
     * 🚫 "ไม่เอา" เฉยๆ **ไม่เข้าเกณฑ์นี้** — owner ตัดสินแล้วว่าคนที่แค่ไม่ซื้อ ยังเสนอได้
     */
    public function detectAnnoyance(string $message): bool
    {
        $message = trim($message);

        return $message !== '' && (bool) preg_match(self::ANNOYED_PATTERN, $message);
    }

    /**
     * 🛍️ ลูกค้าคนนี้ "อยู่ในโหมดช้อป" ไหม — เพิ่งเห็นการ์ดสินค้าไปหมาดๆ
     *
     * ใช้ตัดสินว่าจะอ่านข้อความด้วยเกณฑ์เข้ม (STRICT) หรือเกณฑ์ผ่อน (RELAXED)
     *
     * 🚨 ทำไมต้องมี 2 เกณฑ์ (อ่าน docblock ของ ProductQueryParser ประกอบ):
     *    นอกโหมดช้อป คำว่า "ราคา/เอา/สนใจ/แนะนำ" คือคำที่คนดูดวงพิมพ์ตลอดเวลา
     *    ⇒ ใช้เกณฑ์ผ่อนกับทุกคน = บอทเด้งไปโหมดขายของกลางวงดูดวง พังทั้งบทสนทนา
     *    แต่คนที่เพิ่งได้การ์ดสินค้าไป คำพวกนี้แปลว่า "ถามถึงของที่เพิ่งเห็น" จริงๆ
     *
     * @param  string  $platform  facebook | line
     */
    public function shopModeActive(string $platform, string $platformUserId): bool
    {
        try {
            $hours = (int) MarketplaceSetting::get(self::SETTING_SHOP_MODE_HOURS, 24);
            if ($hours <= 0) {
                return false; // แอดมินตั้ง 0 = ปิดโหมดช้อป ใช้เกณฑ์เข้มกับทุกคน
            }

            $last = FortuneProductOffer::lastSentAt($platform, $platformUserId);

            return $last !== null && $last->gte(now()->subHours($hours));
        } catch (\Throwable $e) {
            // เช็คไม่ได้ = ใช้เกณฑ์เข้มไว้ก่อน (fail-safe ฝั่งไม่ขายดีกว่าฝั่งขายมั่ว)
            Log::debug('MuOffer: เช็คโหมดช้อปไม่ได้ — ใช้เกณฑ์เข้ม', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * 🔎 ข้อความนี้คือ "ลูกค้าถามหาของ" ไหม — ถ้าใช่ คืนคำค้น + งบที่แกะได้
     *
     * ⚠️ ตัวนี้ **ไม่ส่งอะไรทั้งนั้น** เป็นแค่ตัวอ่านเจตนา ผู้เรียกตัดสินใจเอง
     *    ด่านของจริง (สวิตช์ · คนสั่งเงียบ · คนถูกแบน) อยู่ใน `canOffer()` เหมือนเดิม
     *
     * @return array{query:string,budget:?float}|null null = ไม่ใช่การถามหาของ
     *
     * @example
     * $ask = $svc->detectCustomerAsk('facebook', $psid, 'อยากได้ปี่เซี้ยะ งบไม่เกิน 500');
     * // ['query' => 'ปี่เซี้ยะ', 'budget' => 500.0]
     */
    public function detectCustomerAsk(string $platform, string $platformUserId, string $message): ?array
    {
        try {
            $message = trim($message);
            if ($message === '') {
                return null;
            }

            $mode = $this->shopModeActive($platform, $platformUserId)
                ? ProductQueryParser::MODE_RELAXED
                : ProductQueryParser::MODE_STRICT;

            [$query, $budget] = $this->parser->parse($message, $mode);

            if ($query === null || $query === '') {
                return null;
            }

            return ['query' => $query, 'budget' => $budget];
        } catch (\Throwable $e) {
            Log::debug('MuOffer: อ่านเจตนาถามหาของไม่ได้ (non-blocking)', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * สั่งเงียบ — ไม่เสนอสินค้ากับลูกค้าคนนี้อีกจนกว่าจะครบกำหนด
     *
     * @param  string  $reason  annoyed | customer_optout | admin
     * @return bool true = ตั้งธงสำเร็จ
     */
    public function mute(string $platform, string $platformUserId, string $reason = 'annoyed'): bool
    {
        try {
            $days = max(1, (int) MarketplaceSetting::get(self::SETTING_MUTE_DAYS, 7));

            FortuneCustomerPersona::getOrCreate($platform, $platformUserId)->update([
                'product_offer_muted_until' => now()->addDays($days),
                'product_offer_mute_reason' => mb_substr($reason, 0, 100),
            ]);

            Log::info('MuOffer: สั่งเงียบไม่เสนอสินค้า', [
                'platform' => $platform,
                'user_id' => $platformUserId,
                'reason' => $reason,
                'days' => $days,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('MuOffer: ตั้งธงเงียบล้มเหลว', [
                'user_id' => $platformUserId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ประกอบบริบทให้ picker
     *
     * ⚠️ ต้องรับ `$reading` เข้ามาเป็นพารามิเตอร์ — ห้ามพึ่งตัวแปรจากขอบเขตอื่น
     *    (2026-08-23) เคยลืมใส่พารามิเตอร์นี้แล้วอ้าง `$reading` ลอยๆ ในเมธอด
     *    ⇒ `Undefined variable $reading` ทุกครั้งที่เรียก แต่ถูก try/catch ชั้นนอกกลืนไว้
     *      ⇒ ลูกค้าไม่พัง แต่ **ไม่มีใครได้การ์ดเลยสักคน** และไม่มีอะไรดังขึ้นเลย
     *      เห็นได้จาก log อย่างเดียว — บทเรียน: ฟีเจอร์ที่ห่อ try/catch ทั้งก้อน
     *      ต้องดู log จริงหลัง deploy เสมอ ไม่ใช่ดูแค่ว่า "ไม่มี error ให้ลูกค้าเห็น"
     *
     * @param  array<string,mixed>  $options
     */
    private function buildContext(
        string $platform,
        string $platformUserId,
        string $trigger,
        ?FortuneReading $reading,
        array $options
    ): MuPickContext {
        if ($trigger === FortuneProductOffer::TRIGGER_CUSTOMER_ASK) {
            $ctx = MuPickContext::customerAsk(
                $platform,
                $platformUserId,
                isset($options['searchQuery']) ? (string) $options['searchQuery'] : null,
                isset($options['budget']) ? (float) $options['budget'] : null,
            );
        } else {
            $ctx = MuPickContext::proactive($platform, $platformUserId);
        }

        // ผู้เรียกไม่ส่งบริบทมา → ดึงจาก reading ให้เอง
        // (ทุกจุดยิงมี reading อยู่แล้ว — ไม่ควรบังคับให้ทุก call site แกะเองแล้วเขียนซ้ำ 5 ที่)
        $topic = (string) ($options['topicText'] ?? '');
        if ($topic === '' && $reading !== null) {
            $topic = self::topicTextOf($reading);
        }
        if ($topic !== '') {
            $ctx = $ctx->withTopic($topic);
        }

        if (! empty($options['cardsText'])) {
            $ctx = $ctx->withCards((string) $options['cardsText']);
        }

        $birthYear = isset($options['birthYear']) ? (int) $options['birthYear'] : null;
        if (! $birthYear && $reading !== null) {
            $birthYear = self::birthYearOf($reading);
        }
        if ($birthYear) {
            $ctx = $ctx->withBirthYear($birthYear);
        }

        if (! empty($options['group'])) {
            $ctx = $ctx->withGroup((string) $options['group']);
        }

        return $ctx;
    }

    /**
     * รวมคำถามที่ลูกค้าเคยพิมพ์ในการดูดวงใบนี้ เป็นสตริงเดียว
     *
     * ⚠️ คอลัมน์ชื่อ `questions` (พหูพจน์) และ cast เป็น array —
     *    เขียน `$reading->question` จะได้ null เงียบๆ ไม่มี error ให้เห็น
     *    (attribute ที่ไม่มีคอลัมน์จริง อ่านได้ null เหมือนคอลัมน์ว่าง)
     *
     * รูปร่างข้างในไม่คงที่ (บางแถวเป็นลิสต์สตริง บางแถวเป็นลิสต์ออบเจ็กต์)
     * ⇒ ต้องแบนแบบทนทุกรูปร่าง ไม่ใช่ implode ตรงๆ (จะได้คำว่า "Array")
     */
    public static function topicTextOf(FortuneReading $reading): string
    {
        $raw = $reading->questions;

        if (is_string($raw)) {
            return mb_substr($raw, 0, 500);
        }
        if (! is_array($raw)) {
            return '';
        }

        $parts = [];
        array_walk_recursive($raw, function ($v) use (&$parts) {
            if (is_string($v) && trim($v) !== '') {
                $parts[] = trim($v);
            }
        });

        return mb_substr(implode(' ', $parts), 0, 500);
    }

    /**
     * ปีเกิด (ค.ศ.) ของลูกค้าจากการดูดวงใบนี้ — ใช้เลือกเครื่องรางประจำปีนักษัตร
     *
     * `fortune_readings.birth_date` เก็บเป็นวันที่ ค.ศ. อยู่แล้ว (ผ่าน ThaiBirthYear
     * ตอนแปลง พ.ศ. → ค.ศ. ตั้งแต่ตอนรับข้อมูล) จึงอ่าน year ตรงๆ ได้
     *
     * 🚫 ไม่มีคอลัมน์ไพ่ให้อ่าน — `tarot_reading_cards` เป็นของเก่า (25 แถว หยุดตั้งแต่ เม.ย. 2026)
     *    ไพ่ของเส้น Celtic ปัจจุบันอยู่ใน conversation_state ⇒ ผู้เรียกที่มีไพ่อยู่ในมือ
     *    ส่ง `cardsText` เข้ามาเองได้ แต่ระบบไม่ไปขุดให้
     */
    public static function birthYearOf(FortuneReading $reading): ?int
    {
        try {
            $birth = $reading->birth_date;
            if (empty($birth)) {
                return null;
            }

            $year = $birth instanceof \DateTimeInterface
                ? (int) $birth->format('Y')
                : (int) \Illuminate\Support\Carbon::parse((string) $birth)->year;

            // กันปีเพี้ยน (พ.ศ. หลุดมา / ค่าขยะ) — ปีนักษัตรผิดแย่กว่าไม่รู้ปีเกิด
            return ($year >= 1900 && $year <= (int) now()->year) ? $year : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * ส่งจริงตามแพลตฟอร์ม
     *
     * ลำดับกล่อง (จงใจให้เหลือ 2 กล่อง — owner เคยติงว่ากล่องเยอะเกิน):
     *   บอทเสนอเอง   → ข้อความนำ (มีป้ายพันธมิตร) → การ์ด
     *   ลูกค้าถามเอง → การ์ด → ข้อความปิดการขาย (มีป้ายพันธมิตร)
     *
     * @param  array<int,array{product:MarketplaceProduct,slot:string}>  $items
     * @param  array<string,mixed>  $options
     */
    private function dispatch(string $platform, string $userId, string $trigger, array $items, array $options): bool
    {
        $isAsk = $trigger === FortuneProductOffer::TRIGGER_CUSTOMER_ASK;
        $lead = $isAsk ? '' : $this->cards->intro()."\n\n※ ".$this->cards->disclosure();
        $tail = $isAsk ? $this->cards->followUp()."\n\n※ ".$this->cards->disclosure() : '';

        return $platform === 'line'
            ? $this->dispatchLine($userId, $items, $lead, $tail, $options)
            : $this->dispatchFacebook($userId, $items, $lead, $tail);
    }

    /**
     * ส่งฝั่ง Facebook Messenger
     *
     * @param  array<int,array{product:MarketplaceProduct,slot:string}>  $items
     */
    private function dispatchFacebook(string $userId, array $items, string $lead, string $tail): bool
    {
        $fb = new FacebookWebhookService;

        if ($lead !== '') {
            $fb->sendMessage($userId, $lead);
            usleep(400000); // 0.4s — กันสองกล่องชนกัน/โดน rate limit
        }

        $template = $this->cards->facebookTemplate($items);
        $sent = false;

        if ($template !== null) {
            // ⚠️ catch เป็น \Throwable ไม่ใช่ \Exception — TypeError จาก payload ผิดรูปไม่ใช่ Exception
            try {
                $sent = $fb->sendButtonTemplate($userId, $template);
            } catch (\Throwable $e) {
                Log::warning('MuOffer FB: template ถูกปฏิเสธ → ตกไปข้อความล้วน', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $sent = false;
            }
        }

        // 🛟 นอกกรอบ 24 ชม. template ล้มแน่นอน (MESSAGE_TAG_USABLE ปิดอยู่)
        //    ⇒ ต้องมีทางลงเสมอ ไม่งั้นลูกค้าไม่ได้อะไรเลยและเราไม่รู้ตัว
        if (! $sent) {
            $fallback = $this->cards->plainTextFallback($items);
            if ($fallback === '') {
                return false;
            }
            $sent = $fb->sendMessage($userId, $fallback);
        }

        if ($sent && $tail !== '') {
            usleep(400000);
            $fb->sendMessage($userId, $tail);
        }

        return $sent;
    }

    /**
     * ส่งฝั่ง LINE
     *
     * @param  array<int,array{product:MarketplaceProduct,slot:string}>  $items
     * @param  array<string,mixed>  $options
     */
    private function dispatchLine(string $userId, array $items, string $lead, string $tail, array $options): bool
    {
        $line = new LineFortuneService;
        $replyToken = isset($options['replyToken']) ? (string) $options['replyToken'] : null;

        if ($lead !== '') {
            $line->sendMessage($userId, $lead);
            usleep(400000);
        }

        $flex = $this->cards->lineFlex($items);
        $sent = false;

        if ($flex !== null) {
            try {
                // replyToken ใช้ไปกับข้อความนำแล้ว → การ์ดต้องไปทาง push
                $sent = $line->sendFlexWithReplyFallback(
                    $userId,
                    $flex,
                    'แม่หมอมีของเสริมดวงฝากไว้ให้ดู',
                    $lead === '' ? $replyToken : null
                );
            } catch (\Throwable $e) {
                Log::warning('MuOffer LINE: Flex ล้ม → ตกไปข้อความล้วน', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $sent = false;
            }
        }

        if (! $sent) {
            $fallback = $this->cards->plainTextFallback($items);
            if ($fallback === '') {
                return false;
            }
            $sent = $line->sendMessage($userId, $fallback);
        }

        if ($sent && $tail !== '') {
            usleep(400000);
            $line->sendMessage($userId, $tail);
        }

        return $sent;
    }

    /**
     * บันทึกประวัติการเสนอ — 1 แถวต่อ 1 ชิ้น, `sent_at` เดียวกันทั้งรอบ
     *
     * ⚠️ `sent_at` ต้องเป็นค่าเดียวกันทุกแถวในรอบเดียว เพราะตัวนับเพดานรายวัน
     *    นับ `DISTINCT sent_at` เพื่อให้ "เสนอ 1 ครั้งได้ 2 ชิ้น" นับเป็น 1 ไม่ใช่ 2
     *
     * @param  array{items:array<int,array{product:MarketplaceProduct,slot:string}>,group:?string,reason:string}  $picked
     * @param  array<string,mixed>  $options
     */
    private function record(
        string $platform,
        string $userId,
        string $trigger,
        ?FortuneReading $reading,
        array $picked,
        array $options
    ): void {
        $sentAt = now();

        foreach ($picked['items'] as $item) {
            /** @var MarketplaceProduct $p */
            $p = $item['product'];

            FortuneProductOffer::create([
                'platform' => $platform,
                'platform_user_id' => $userId,
                'marketplace_product_id' => $p->id,
                'reading_id' => $reading?->id,
                'trigger' => $trigger,
                'mu_group' => $picked['group'] ?? $p->mu_group,
                'slot' => $item['slot'],
                'context_reason' => mb_substr((string) ($picked['reason'] ?? ''), 0, 100),
                'price_at_send' => $p->price,
                'commission_rate_at_send' => $p->commission_rate,
                'sent_at' => $sentAt,
            ]);
        }
    }
}
