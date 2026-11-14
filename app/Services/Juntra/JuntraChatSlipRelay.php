<?php

namespace App\Services\Juntra;

use App\Models\JuntraChatSlip;
use App\Models\SlipVerification;
use App\Services\Fortune\SlipOkService;
use App\Services\Fortune\SlipUsageRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 🧾 สลิปที่ลูกค้าส่งในแชทเพจ → เติมเงินเว็บจันทรา (2026-11-10)
 *
 * เจ้าของ: "อยากให้ระบบเก่า ก็สามารถตรวจสลิปได้อยู่ด้วย แม้จะส่งลูกค้ามาเว็บ
 *          มันจะได้รู้ว่าขณะนั้นใครบิลใคร ชื่อไร"
 *
 * ลำดับ (สลิปที่ไม่มีบิลค้างฝั่งบอท = ระดับ C ของโหมด web-only):
 *   1. ตรวจด้วย SlipOK ก้อนเดียวกับบอท (flood guard + hash cache เดิม)
 *   2. ต้องเป็นรายการจริง · เข้าบัญชีร้านเรา · อายุไม่เกินเพดาน · ยังไม่เคยใช้ที่ไหน
 *   3. "พัก" สลิป: จองเลขอ้างอิงใน slip_verifications ทันที (unique ตัดสิน — ใช้ซ้ำข้ามระบบไม่ได้)
 *      + บันทึกแถว juntra_chat_slips ใน transaction เดียวกัน
 *   4. กดกริ่งบอกเว็บจันทรา (ส่งแค่เลขอ้างอิง) — เว็บดึงข้อมูลที่ตรวจแล้วกลับไปเองผ่านช่องทาง
 *      ที่ยืนยันตัวตนแล้ว (/api/v1/juntra/server/chat-slips/*) แล้วจับคู่บิล/เติมเครดิตเอง
 *      กริ่งพัง = สลิปยังพักอยู่ เว็บมีตัวกวาดทุกนาทีมารับไปเอง (ไม่หาย ไม่เติมซ้ำ)
 *
 * คลาสนี้ไม่เคยเติมเงินเอง — คนเติมคือเว็บจันทรา (ด่านจับคู่ยอด/สวิตช์อนุมัติอัตโนมัติอยู่ที่นั่น)
 */
class JuntraChatSlipRelay
{
    // ผลลัพธ์ที่ผู้เรียกใช้ตัดสินใจว่าจะตอบลูกค้าอย่างไร
    public const CREDITED = JuntraChatSlip::CREDITED;

    public const REVIEW = JuntraChatSlip::REVIEW;

    public const DUPLICATE = JuntraChatSlip::DUPLICATE;

    public const NO_USER = JuntraChatSlip::NO_USER;

    /** พักไว้แล้ว แต่เว็บยังไม่ตอบ (ตัวกวาดฝั่งเว็บจะมารับ) */
    public const PARKED = JuntraChatSlip::PARKED;

    public const WRONG_ACCOUNT = 'wrong_account';

    public const TOO_OLD = 'too_old';

    /** ตรวจไม่ได้/ไม่ใช่สลิป/ปิดอยู่ — ผู้เรียกถอยไปทางเดิม (เก็บหลักฐาน + แจ้งแอดมิน) */
    public const UNVERIFIED = 'unverified';

    /**
     * 🧾 (2026-11-11) รูปสอนเติมเครดิตบนเว็บ ส่งตามข้อความผลตรวจ (ไม่เกินครั้งละ GUIDE_COOLDOWN_DAYS ต่อคน)
     * ในรูปจงใจไม่มี QR และไม่มีคำเรื่องเงินที่ FB แบน — ต้นฉบับ HTML: resources/design/web-topup-guide/
     * (เปลี่ยนรูป = เปลี่ยน ?v= เพราะ FB แคชรูปตาม URL)
     */
    public const GUIDE_IMAGE_PATH = 'images/fortune/web-topup-guide.jpg?v=1';

    public const GUIDE_COOLDOWN_DAYS = 7;

    /** ผลที่สอนวิธีใช้เว็บต่อได้ (ผิดบัญชี/เก่าเกิน = ไม่ใช่เรื่องใช้เว็บผิด ไม่ส่งรูปสอน) */
    public const GUIDE_OUTCOMES = [self::CREDITED, self::REVIEW, self::PARKED, self::NO_USER, self::DUPLICATE];

    /** ⚠️ ข้อความทั้งหมดต้องไม่มีคำในด่าน FB_WEB_ONLY_MONEY_PATTERN (ล็อกด้วยเทสต์) */
    public const DO_NOT_REPEAT = '⚠️ ไม่ต้องทำรายการซ้ำ และไม่ต้องส่งรูปเดิมมาอีกนะคะ เครดิตจะเข้าเองเมื่อทีมงานยืนยันเสร็จ'
        .' (บนเว็บจะขึ้นว่า "ได้รับหลักฐานจากแชทเพจแล้ว")';

    public const NEXT_TIME_TIP = '💡 ครั้งหน้าไม่ต้องส่งรูปมาที่แชทนี้นะคะ เลือกไพ่ในห้องแม่หมอบนเว็บ แล้วใช้ยอดตามการ์ดให้ตรงทุกหลัก (รวมเศษสตางค์)'
        .' เครดิตเข้าเองภายในไม่กี่นาทีค่ะ';

    public const GUIDE_SUFFIX = ' — แม่หมอส่งรูปวิธีทำไว้ให้ด้านล่างแล้ว';

    public function __construct(
        protected SlipOkService $slipok,
        protected SlipUsageRegistry $registry,
    ) {}

    public static function enabled(): bool
    {
        return (bool) config('services.juntra.chat_slip_relay', true);
    }

    /**
     * @param  string  $relPath  ไฟล์สลิปบน disk local (เก็บไว้แล้วเป็นหลักฐาน)
     * @return array{outcome: string, amount?: float|null, credited?: float|null, topup_ref?: string|null, trans_ref?: string|null}
     */
    public function handle(string $platform, string $platformUserId, string $relPath): array
    {
        if (! self::enabled() || ! $this->slipok->isEnabled()) {
            return ['outcome' => self::UNVERIFIED];
        }

        $disk = Storage::disk('local');
        if ($relPath === '' || ! $disk->exists($relPath)) {
            return ['outcome' => self::UNVERIFIED];
        }

        // flood guard ตัวเดียวกับบอท — กันส่งรูปรัวจนโควตา SlipOK ที่ใช้ร่วมกันหมด
        if (! $this->slipok->canSpendForUser($platform, $platformUserId)) {
            $this->slipok->registerOverflowStrike($platform, $platformUserId);

            return ['outcome' => self::UNVERIFIED];
        }

        try {
            $verify = $this->slipok->verifyByFile($disk->path($relPath), $platform, $platformUserId);
        } catch (\Throwable $e) {
            Log::warning('JuntraChatSlipRelay: SlipOK threw', ['err' => $e->getMessage()]);

            return ['outcome' => self::UNVERIFIED];
        }

        $owner = JuntraChatSlip::ownerKey($platform, $platformUserId);
        $d = $this->registry->describe($verify);
        $ref = trim((string) ($d['trans_ref'] ?? ''));
        $amount = isset($d['amount']) ? (float) $d['amount'] : null;

        // ส่งรูปเดิมซ้ำ (SlipOK ตอบ "เคยตรวจแล้ว" หรือทะเบียนเจอเลขนี้) — ถ้าเป็นสลิปที่เราพักให้คนนี้ไว้เอง
        // ให้ไปต่อกับแถวเดิม (idempotent) ไม่นับว่าเป็นสลิปซ้ำ
        if ($ref !== '' && ($existing = JuntraChatSlip::where('trans_ref', $ref)->first())) {
            if ($existing->owner() === $owner) {
                return $this->continueWith($existing);
            }

            return ['outcome' => self::DUPLICATE, 'amount' => $amount, 'trans_ref' => $ref];
        }

        if (! empty($d['used'])) {
            return ['outcome' => self::DUPLICATE, 'amount' => $amount, 'trans_ref' => $ref ?: null];
        }

        if (empty($d['ok']) || $ref === '' || $amount === null || $amount <= 0) {
            // ตัวเลข 1014 = ปลายทางไม่ใช่บัญชีเรา (SlipOK ตัดสินเอง)
            if ((int) ($d['error_code'] ?? 0) === 1014) {
                return ['outcome' => self::WRONG_ACCOUNT, 'amount' => $amount];
            }

            return ['outcome' => self::UNVERIFIED, 'amount' => $amount];
        }

        if (empty($d['receiver_matches'])) {
            return ['outcome' => self::WRONG_ACCOUNT, 'amount' => $amount, 'trans_ref' => $ref];
        }

        if (array_key_exists('slip_age_ok', $d) && $d['slip_age_ok'] === false) {
            return ['outcome' => self::TOO_OLD, 'amount' => $amount, 'trans_ref' => $ref];
        }

        $row = $this->park($platform, $platformUserId, $owner, $d, $relPath);
        if ($row === null) {
            // แพ้การจอง — มีระบบอื่นใช้เลขนี้ไปก่อนเสี้ยววินาที
            return ['outcome' => self::DUPLICATE, 'amount' => $amount, 'trans_ref' => $ref];
        }

        return $this->continueWith($row);
    }

    /**
     * จองเลขอ้างอิง + บันทึกแถวพัก ใน transaction เดียว — unique ของ trans_ref ตัดสิน (ไม่ check-then-insert)
     */
    protected function park(string $platform, string $platformUserId, string $owner, array $d, string $relPath): ?JuntraChatSlip
    {
        $ref = trim((string) $d['trans_ref']);

        try {
            return DB::transaction(function () use ($platform, $platformUserId, $owner, $d, $relPath, $ref) {
                SlipVerification::create([
                    'trans_ref' => $ref,
                    'fortune_reading_id' => null,
                    'consumed_by_platform' => 'juntraweb',
                    'consumed_by_user_id' => $owner,
                    'amount' => $d['amount'],
                    'sender_name' => $d['sender_name'] ?? null,
                    'receiver_account' => $d['receiver_account'] ?? null,
                    'sending_bank' => $d['sending_bank'] ?? null,
                    'receiving_bank' => $d['receiving_bank'] ?? null,
                    'status' => 'verified',
                    'flagged_review' => false,
                    'raw' => [
                        'source' => 'chat_slip',
                        'platform' => $platform,
                        'topup_ref' => null,
                        'trans_timestamp' => $d['trans_timestamp'] ?? null,
                    ],
                    'verified_at' => now(),
                ]);

                return JuntraChatSlip::create([
                    'trans_ref' => $ref,
                    'platform' => $platform,
                    'platform_user_id' => $platformUserId,
                    'amount' => $d['amount'],
                    'sender_name' => isset($d['sender_name']) ? mb_substr((string) $d['sender_name'], 0, 120) : null,
                    'receiver_account' => $d['receiver_account'] ?? null,
                    'sending_bank' => $d['sending_bank'] ?? null,
                    'receiving_bank' => $d['receiving_bank'] ?? null,
                    'trans_timestamp' => isset($d['trans_timestamp']) ? (string) $d['trans_timestamp'] : null,
                    'slip_path' => $relPath,
                    'status' => JuntraChatSlip::PARKED,
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            return $this->parkedForSameOwner($ref, $owner);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return $this->parkedForSameOwner($ref, $owner);
            }

            throw $e;
        }
    }

    /** ชน unique — คนเดิมส่งสองรอบพร้อมกัน = แถวเดิม · คนอื่น/ระบบอื่น = null (ซ้ำ) */
    protected function parkedForSameOwner(string $ref, string $owner): ?JuntraChatSlip
    {
        $row = JuntraChatSlip::where('trans_ref', $ref)->first();

        return ($row && $row->owner() === $owner) ? $row : null;
    }

    /**
     * แถวที่พักแล้ว: ถ้ายังไม่มีผล → กดกริ่งเว็บ แล้วอ่านผลจากแถว (เว็บรายงานผ่าน /resolve ก่อนตอบกริ่ง)
     */
    protected function continueWith(JuntraChatSlip $row): array
    {
        if ($row->status === JuntraChatSlip::PARKED) {
            $this->ring($row);
            $row->refresh();
        }

        return [
            'outcome' => $row->status,
            'amount' => (float) $row->amount,
            'credited' => $row->credited_amount !== null ? (float) $row->credited_amount : null,
            'topup_ref' => $row->topup_ref,
            'trans_ref' => $row->trans_ref,
        ];
    }

    /**
     * กดกริ่งเว็บจันทรา — ส่งแค่เลขอ้างอิง (เว็บดึงข้อมูลจริงกลับมาเองด้วย token ของมัน)
     * ล้มเหลว = เงียบ ๆ (ตัวกวาดฝั่งเว็บมารับทีหลัง)
     */
    public function ring(JuntraChatSlip $row): bool
    {
        $base = rtrim((string) config('services.juntra.url'), '/');
        if ($base === '') {
            return false;
        }

        $row->increment('ping_attempts');

        try {
            // ⏱️ webhook เพจทำงานแบบ sync และ Facebook รอได้ ~20 วิ (รวม SlipOK แล้ว)
            //    กริ่งไม่ทัน = สลิปยังพัก → ลูกค้าได้ข้อความ "รอตรวจ" แล้วตัวกวาดฝั่งเว็บเติมให้ภายในนาที
            $resp = Http::connectTimeout(3)
                ->timeout(8)
                ->acceptJson()
                ->post($base.'/api/server/chat-slips/ping', ['trans_ref' => $row->trans_ref]);

            if (! $resp->successful()) {
                Log::warning('JuntraChatSlipRelay: เว็บจันทราตอบกริ่งไม่สำเร็จ (สลิปยังพักอยู่)', [
                    'trans_ref' => $row->trans_ref,
                    'http' => $resp->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('JuntraChatSlipRelay: กดกริ่งเว็บจันทราไม่ติด (สลิปยังพักอยู่)', [
                'trans_ref' => $row->trans_ref,
                'err' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ข้อความตอบลูกค้าในแชทเพจ
     *
     * ⚠️ แชท FB โหมด web-only มีด่านเงินชั้นส่ง (FacebookWebhookService::FB_WEB_ONLY_MONEY_PATTERN)
     *    บล็อกทั้งข้อความที่มีคำอย่าง "บาท/฿/สลิป/โอนแล้ว" → ข้อความทุกอันที่นี่ต้องไม่มีคำเหล่านั้น
     *    (ล็อกด้วยเทสต์ JuntraChatSlipRelayTest)
     *
     * @param  bool  $withGuide  ผู้เรียกจะส่งรูปสอน (GUIDE_IMAGE_PATH) ตามหลังข้อความนี้
     * @return string|null null = ใช้ทางเดิมของผู้เรียก
     */
    public static function customerMessage(array $result, bool $withGuide = false): ?string
    {
        $text = self::outcomeMessage($result);
        if ($text !== null && $withGuide && str_contains($text, self::NEXT_TIME_TIP)) {
            $text = str_replace(self::NEXT_TIME_TIP, self::NEXT_TIME_TIP.self::GUIDE_SUFFIX, $text);
        }

        return $text;
    }

    private static function outcomeMessage(array $result): ?string
    {
        $fmt = fn ($v) => number_format((float) $v, 2);

        switch ($result['outcome'] ?? null) {
            case self::CREDITED:
                $credited = $result['credited'] ?? $result['amount'] ?? null;
                $text = 'แม่หมอเช็คให้แล้วนะคะ ✨ เติมเครดิต '.$fmt($credited).' เข้ากระเป๋าบนเว็บของคุณเรียบร้อยแล้ว'
                    .(! empty($result['topup_ref']) ? ' (รายการ '.$result['topup_ref'].')' : '');
                $over = round((float) ($result['amount'] ?? 0) - (float) ($credited ?? 0), 2);
                if ($credited !== null && $over >= 1) {
                    $text .= ' · ส่วนที่เกินมา '.$fmt($over).' ทีมงานจะเติมให้หรือคืนให้นะคะ';
                }

                return $text.' แตะปุ่มด้านล่างเข้าห้องดูดวงได้เลยค่ะ 🌙'
                    ."\n\n".self::NEXT_TIME_TIP;

                // 🧾 (2026-11-11) เคสจริง: ข้อความเดิมบอกแค่ "ทีมงานกำลังเติมให้" → ลูกค้าคิดว่ายังไม่เข้า
                //    เปิดเว็บเห็นรายการค้างแล้วจ่ายซ้ำอีกรอบ — ต้องบอกให้ชัดว่า "ไม่ต้องทำซ้ำ" และรออะไรอยู่
            case self::REVIEW:
            case self::PARKED:
                return 'แม่หมอได้รับแล้วนะคะ 🙏 เช็คแล้วเป็นรายการจริง ทีมงานกำลังยืนยันและเติมเครดิตเข้ากระเป๋าบนเว็บให้ค่ะ'
                    ."\n\n".self::DO_NOT_REPEAT
                    ."\n\n".self::NEXT_TIME_TIP;

            case self::NO_USER:
                return 'แม่หมอได้รับแล้วนะคะ 🙏 แต่ยังหาบัญชีบนเว็บที่ผูกกับเฟซบุ๊กนี้ไม่เจอ ทีมงานจะเติมเครดิตให้เองค่ะ'
                    ."\n\n".self::DO_NOT_REPEAT
                    ."\n\nครั้งหน้าเข้าเว็บจากปุ่มด้านล่างนะคะ บัญชีบนเว็บจะผูกกับเฟซบุ๊กนี้ให้เอง"
                    ."\n\n".self::NEXT_TIME_TIP;

            case self::DUPLICATE:
                return 'รายการนี้เคยใช้เติมเครดิตไปแล้วค่ะ 🙏 ลองเปิดดูยอดในกระเป๋าบนเว็บก่อนนะคะ'
                    .' ถ้ายอดยังไม่ขึ้น แม่หมอแจ้งทีมงานให้เช็คแล้วค่ะ · ถ้าเป็นรายการใหม่อีกครั้ง ส่งรูปใบใหม่มาได้เลยนะคะ'
                    ."\n\n".self::NEXT_TIME_TIP;

            case self::WRONG_ACCOUNT:
                return 'รายการนี้ไม่ได้เข้าบัญชีของแม่หมอค่ะ 🙏 แม่หมอส่งให้ทีมงานดูอีกครั้งแล้วนะคะ';

            case self::TOO_OLD:
                return 'รายการนี้เก่าเกินกว่าที่ระบบเช็คเองได้ค่ะ 🙏 แม่หมอส่งให้ทีมงานดูให้แล้วนะคะ';
        }

        return null;
    }
}
