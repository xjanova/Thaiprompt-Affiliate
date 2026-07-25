<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneUserCredit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FortunePersonalDataTrait — "ศูนย์ข้อมูลของฉัน" ในแชท (สิทธิ์ PDPA: เข้าถึง + แก้ไข)
 *
 * 👤 (2026-07-25, owner) "ลูกค้าถามเกี่ยวกับข้อมูลตัวเองต้องตอบได้ เพราะเรามีบันทึกไว้
 *    จะเปลี่ยนแปลงอะไรบอทต้องเปลี่ยนให้ได้ เช่นข้อมูลส่วนตัว วันเดือนปีเกิด
 *    แต่พวกข้อมูลการเงิน เช่นจำนวนเงินในวอลเลต หรืออีเมล บอททำไม่ได้แน่ๆ"
 *
 * ครอบคลุม 3 สิทธิ์:
 *   1. เข้าถึง (Right to Access) — "ข้อมูลของฉัน" → สรุปสิ่งที่ระบบเก็บไว้
 *   2. แก้ไข (Right to Rectification) — ชื่อ (ที่นี่) + วันเกิด ([[BirthdateCorrectionTrait]])
 *   3. ลบ (Right to Erasure) — [[FortunePdpaDeletionTrait]] (มีอยู่แล้ว)
 *
 * 🔒 สิ่งที่บอท "ไม่ทำ" เด็ดขาด (ตอบปฏิเสธ + ชี้ทางที่ถูกต้อง):
 *   - ยอดเงิน/วอลเลต/ค่าคอมมิชชั่น — แก้ในแชทไม่ได้ (ต้องผ่านระบบที่ตรวจสอบได้)
 *   - อีเมล / เบอร์โทร / รหัสผ่าน — เป็นกุญแจเข้าบัญชี ต้องยืนยันตัวตนบนเว็บ
 *   เหตุผล: ใครก็ตามที่ยึดแชทได้ ไม่ควรเปลี่ยนช่องทางกู้บัญชี/เงินได้
 */
trait FortunePersonalDataTrait
{
    /** prefix cache สำหรับ flow เปลี่ยนชื่อ (รอรับชื่อใหม่) */
    protected const PERSONAL_NAME_PENDING_PREFIX = 'fortune:name_change_pending:';

    /** prefix cache เก็บชื่อที่ลูกค้าตั้งเอง — ต้องชนะชื่อโปรไฟล์ LINE/FB ในบิลถัดๆ ไป */
    protected const PERSONAL_CUSTOM_NAME_PREFIX = 'fortune:custom_name:';

    /** อายุคำขอเปลี่ยนชื่อ (วินาที) — สั้นไว้ กันกลืนข้อความสำคัญ เช่น "โอนแล้ว" */
    protected const PERSONAL_NAME_PENDING_TTL = 180;

    /**
     * 🔔 จุดเข้าหลัก — เรียกจาก processMessage (วางถัดจาก PDPA flow)
     *
     * @return array|null response หรือ null (ไม่เกี่ยว → flow ปกติ)
     */
    protected function handlePersonalDataFlow(string $uid, string $messageText): ?array
    {
        if (empty($uid)) {
            return null;
        }

        // 🛡️ (2026-07-25) ลูกค้าจ่ายเงินแล้ว/กำลังอยู่ในรอบทำนาย → ห้ามแทรกเด็ดขาด
        //   flow นี้อยู่ก่อน Pro Session guard ~570 บรรทัด และ COMPLETED ไม่อยู่ใน
        //   IN_PREDICTION_STATUSES → ถ้าไม่กันตรงนี้ คำถามในหน้าต่างถาม-ตอบที่จ่ายเงินแล้ว
        //   จะถูกดึงออกจากการทำนาย (ผิดกฎ "paid customer bypass all guards")
        try {
            if (method_exists($this, 'hasPaidActiveReading') && $this->hasPaidActiveReading($uid)) {
                return null;
            }
            if (method_exists($this, 'findActiveProSessionReading') && $this->findActiveProSessionReading($uid) !== null) {
                return null;
            }
        } catch (\Throwable $e) {
            // เช็คไม่ได้ → เลือกทางปลอดภัย: ไม่แทรก
            return null;
        }

        $platform = $this->currentPlatform ?? 'facebook';
        $pendingKey = self::PERSONAL_NAME_PENDING_PREFIX."{$platform}:{$uid}";

        // ── รอรับชื่อใหม่อยู่ ──────────────────────────────
        if (Cache::has($pendingKey)) {
            if ($this->looksLikePersonalDataCancel($messageText)) {
                Cache::forget($pendingKey);

                return [
                    'action' => 'personal_name_cancelled',
                    'message' => '🙏 รับทราบค่ะ — ใช้ชื่อเดิมต่อไปนะคะ',
                    'reading' => null,
                ];
            }

            // 🚨 ข้อความสำคัญกว่าการตั้งชื่อ (แจ้งโอน/ขอคุยกับคน/ลบข้อมูล/ดูดวง)
            //   → ทิ้ง flow ตั้งชื่อทันที ปล่อยให้ flow เดิมจัดการ
            //   (เดิม "โอนแล้วค่ะ" ถูกบันทึกเป็น "ชื่อ" — แจ้งโอนเงินหายทั้งข้อความ)
            if ($this->looksLikeHigherPriorityThanNaming($messageText)) {
                Cache::forget($pendingKey);

                return null;
            }

            $newName = $this->extractNewCustomerName($messageText);
            if ($newName !== null) {
                Cache::forget($pendingKey);

                return $this->applyCustomerNameChange($platform, $uid, $newName);
            }

            return [
                'action' => 'personal_name_ask',
                'message' => "✏️ ขอ*ชื่อที่อยากให้แม่หมอเรียก*อีกครั้งนะคะ\n\n"
                    ."📝 พิมพ์แค่ชื่อสั้นๆ ได้เลย เช่น \"น้ำ\" หรือ \"สมชาย\"\n\n"
                    .'_(พิมพ์ "ยกเลิก" ถ้าเปลี่ยนใจค่ะ)_',
                'reading' => null,
                'show_quick_replies' => true,
                'quick_replies' => [['title' => '❌ ยกเลิก', 'text' => 'ยกเลิก', 'payload' => 'ยกเลิก']],
            ];
        }

        // ── ขอเปลี่ยนสิ่งที่บอทแตะไม่ได้ (เงิน/อีเมล/รหัสผ่าน) ──
        if ($this->looksLikeRestrictedDataRequest($messageText)) {
            return $this->buildRestrictedDataResponse();
        }

        // ── ขอเปลี่ยนชื่อ ────────────────────────────────
        if ($this->looksLikeNameChangeRequest($messageText)) {
            Cache::put($pendingKey, now()->toIso8601String(), self::PERSONAL_NAME_PENDING_TTL);

            // 🛑 (2026-07-25) ห้ามบันทึกชื่อจากประโยคเดียวโดยไม่ถาม —
            //   "อยากเปลี่ยนชื่อเป็นมงคล ดีไหม" คือคำถามดูดวงยอดฮิต ไม่ใช่คำสั่งตั้งชื่อ
            //   จึงถามยืนยันเสมอ (ลูกค้าพิมพ์ชื่อซ้ำอีกทีถึงจะบันทึก)
            return [
                'action' => 'personal_name_ask',
                'message' => "✏️ ได้ค่ะ — อยากให้แม่หมอเรียกว่าอะไรดีคะ?\n\n"
                    ."📝 พิมพ์ *เฉพาะชื่อ* มาได้เลย เช่น \"น้ำ\" หรือ \"สมชาย\"\n\n"
                    .'_(พิมพ์ "ยกเลิก" ถ้าเปลี่ยนใจค่ะ)_',
                'reading' => null,
                'show_quick_replies' => true,
                'quick_replies' => [['title' => '❌ ยกเลิก', 'text' => 'ยกเลิก', 'payload' => 'ยกเลิก']],
            ];
        }

        // ── ขอดูข้อมูลของตัวเอง ──────────────────────────
        if ($this->looksLikeMyDataRequest($messageText)) {
            return $this->buildMyDataResponse($platform, $uid);
        }

        return null;
    }

    /**
     * ข้อความที่ "สำคัญกว่า" การตั้งชื่อ — ต้องหลุดจาก flow ตั้งชื่อทันที
     *
     * เคสจริงที่อันตราย: ลูกค้าขอเปลี่ยนชื่อ แล้วเปลี่ยนใจไปโอนเงิน พิมพ์ "โอนแล้วค่ะ"
     * → เดิมกลายเป็น "ชื่อ" = แจ้งโอนหาย (ผิดกฎ never interrupt payment-to-prediction flow)
     */
    protected function looksLikeHigherPriorityThanNaming(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return true;
        }

        $signals = [
            'โอนแล้ว', 'จ่ายแล้ว', 'ชำระแล้ว', 'สลิป', 'โอนเงิน', 'เลขบัญชี', 'บัญชี',
            'ดูดวง', 'ทำนาย', 'ยกเลิก', 'ลบข้อมูล', 'คุยกับคน', 'แอดมิน', 'บิล',
            '39', '99', 'ราคา', 'ค่าครู',
        ];
        foreach ($signals as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจว่าลูกค้าขอ "ดูข้อมูลของตัวเอง"
     *
     * ⚠️ ต้อง strict — ไม่ให้คำถามดูดวงที่มีคำว่า "ฉัน/ข้อมูล" มา trigger
     *    (เทียบบทเรียนจาก looksLikeBirthdateCorrectionRequest)
     */
    protected function looksLikeMyDataRequest(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) > 60) {
            return false;
        }

        $me = '(?:ของ)?(?:ฉัน|ผม|หนู|เรา|ดิฉัน|ข้าพเจ้า)';

        return (bool) (
            // "ข้อมูลของฉัน" / "ข้อมูลส่วนตัวฉัน" / "ดูข้อมูลของฉัน"
            preg_match('/(?:ดู|เช็ค|ขอดู|ขอ)?\s*ข้อมูล(?:ส่วนตัว)?\s*'.$me.'/u', $t)
            // "เก็บข้อมูลอะไรของฉันไว้บ้าง" / "มีข้อมูลอะไรของฉัน"
            || preg_match('/(?:เก็บ|มี|บันทึก)\s*ข้อมูล(?:อะไร)?.{0,12}'.$me.'/u', $t)
            // "รู้ข้อมูลอะไรเกี่ยวกับฉันบ้าง" — ต้องมีคำว่า "ข้อมูล" ประกอบ
            //   (ตัด "รู้จักฉันไหม"/"จำหนูได้ไหม" ออก — เป็นคำทักทาย ตอบด้วยการ์ด PDPA แข็งเกินไป)
            || preg_match('/รู้\s*ข้อมูล(?:อะไร)?.{0,12}'.$me.'/u', $t)
            // "วันเกิดฉันคือ/เท่าไร" / "ชื่อฉันคืออะไร" (ถามข้อมูลที่ระบบเก็บ)
            || preg_match('/(?:วันเกิด|ชื่อ)\s*'.$me.'\s*(?:คือ|เป็น|ว่า)?\s*(?:อะไร|ไร|เท่าไ?ร่?)/u', $t)
            // "จำวันเกิดฉันได้ไหม" / "จำชื่อฉันได้ไหม" (ถามถึงข้อมูลเฉพาะ ไม่ใช่ "จำฉันได้ไหม" ลอยๆ)
            || preg_match('/จำ\s*(?:วันเกิด|ชื่อ)\s*'.$me.'?\s*ได้(?:ไหม|มั้ย|รึ|หรือ)/u', $t)
        );
    }

    /**
     * ตรวจว่าลูกค้าขอเปลี่ยน "ชื่อ" ที่บอทใช้เรียก
     */
    protected function looksLikeNameChangeRequest(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) > 60) {
            return false;
        }

        // 🛑 (2026-07-25) "อยากเปลี่ยนชื่อเป็นมงคลดีไหม" / "ควรแก้ชื่อไหมคะ" = คำถามดูดวง (ยอดฮิต)
        //   ไม่ใช่คำสั่งเปลี่ยนชื่อที่บอทใช้เรียก — ต้องปล่อยให้ไปถึงคำทำนาย
        if (preg_match('/(?:ไหม|มั้ย|รึเปล่า|หรือเปล่า|ควร|มงคล|โฉลก|เสริมดวง|ทำนาย|ดูดวง|ดวง)/u', $t)) {
            return false;
        }

        return (bool) (
            preg_match('/(?:เปลี่ยน|แก้ไข|แก้|ขอเปลี่ยน|ขอแก้)\s*ชื่อ/u', $t)
            || preg_match('/ชื่อ\s*(?:ของ)?(?:ฉัน|ผม|หนู|เรา)?\s*(?:มัน)?\s*(?:ผิด|ไม่ถูก|ไม่ใช่|สะกดผิด)/u', $t)
            || preg_match('/(?:เรียก|เรียกฉัน|เรียกผม|เรียกหนู|เรียกเรา)\s*(?:ฉัน|ผม|หนู|เรา)?\s*ว่า/u', $t)
        );
    }

    /**
     * ตรวจว่าลูกค้าขอให้บอทแก้ "ข้อมูลการเงิน/บัญชี" ที่บอททำไม่ได้
     *
     * เงิน/อีเมล/เบอร์/รหัสผ่าน = ต้องผ่านระบบที่ยืนยันตัวตนได้ ไม่ใช่แชท
     * ⚠️ ต้องแยกจาก "คำถามดูดวงเรื่องเงิน" ("เดือนนี้จะได้เงินไหม") ให้ขาด
     */
    protected function looksLikeRestrictedDataRequest(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) > 80) {
            return false;
        }

        // ⚠️ ห้ามใส่ "ถอน|โอน|เพิ่ม|ปรับ" ใน verb ของสายเงิน — คำถามดูดวงยอดฮิตชนหมด:
        //    "จะถอนเงินได้ตอนไหน" / "ปีนี้จะได้ปรับเงินเดือนไหม" / "เปลี่ยนงานเพิ่มเงินได้ไหม"
        //    (ลูกค้าจ่ายเงินถามดวงการเงิน — ห้ามตอบ lecture ความปลอดภัยบัญชีแทนคำทำนาย)
        $verb = '(?:เปลี่ยน|แก้ไข|แก้|อัพเดท|อัปเดต|ขอเปลี่ยน|ขอแก้|ตั้ง|รีเซ็ต)';

        // คำถามดูดวง → ไม่ใช่คำสั่งแก้ข้อมูล
        if (preg_match('/(?:ไหม|มั้ย|รึเปล่า|หรือเปล่า|ดวง|ทำนาย|ปีนี้|เดือนนี้|เงินเดือน)/u', $t)) {
            return false;
        }

        return (bool) (
            // เปลี่ยนอีเมล / เบอร์ / รหัสผ่าน / เลขบัญชี
            preg_match('/'.$verb.'\s*(?:อี?เมล|email|เบอร์(?:โทร)?|รหัสผ่าน|พาสเวิร์ด|password|เลขบัญชี|บัญชีธนาคาร)/u', $t)
            // แก้ยอดเงิน · วอลเลต · เครดิต · ค่าคอม (เฉพาะกริยาที่เป็นคำสั่งแก้ข้อมูลชัดๆ)
            || preg_match('/'.$verb.'\s*(?:ยอด|เงิน|วอลเล็?ท|wallet|เครดิต|credit|ค่าคอม(?:มิชชั่น)?)/u', $t)
            // "เติมเงินให้หน่อย" / "โอนเงินเข้าวอลเลตให้" — ต้องมี "ให้/เข้า" = สั่งบอททำ
            || preg_match('/(?:เติม|โอน|ใส่|เพิ่ม)\s*(?:เงิน|เครดิต|ยอด).{0,12}(?:ให้|เข้า)/u', $t)
        );
    }

    /**
     * คำยกเลิกกลาง flow เปลี่ยนชื่อ
     */
    protected function looksLikePersonalDataCancel(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        $t = (string) preg_replace('/[\s.!?]+$/u', '', $t);
        $t = (string) preg_replace('/\s*(ครับผม|ครับ|ค่ะ|คะ|จ้า|จ้ะ|นะคะ|นะครับ|นะ)+\s*$/u', '', $t);

        return in_array(trim($t), ['ยกเลิก', 'ไม่เปลี่ยน', 'ไม่แก้', 'ไม่แก้แล้ว', 'ไม่ต้อง', 'พอแล้ว', 'no'], true);
    }

    /**
     * 📋 สรุปข้อมูลที่ระบบเก็บไว้ของลูกค้าคนนี้
     *
     * 🔒 แสดงเฉพาะข้อมูลดูดวง — ไม่แตะอีเมล/เบอร์/ยอดเงิน (ดูบนเว็บที่ล็อกอินแล้วเท่านั้น)
     */
    protected function buildMyDataResponse(string $platform, string $uid): array
    {
        try {
            $scope = fn ($q) => $q->where('facebook_user_id', $uid)->orWhere('platform_user_id', $uid);

            // เลือกเฉพาะคอลัมน์ที่ใช้ — ไม่ดึง deep_response/ai_response (หลาย KB ต่อแถว)
            $readings = FortuneReading::where($scope)
                ->select(['id', 'created_at', 'paid_at', 'birth_date', 'is_paid', 'reading_type',
                    'facebook_user_name', 'user_profile', 'platform', 'platform_user_id', 'facebook_user_id'])
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            if ($readings->isEmpty()) {
                return [
                    'action' => 'personal_data_empty',
                    'message' => "📋 ตอนนี้แม่หมอยังไม่มีข้อมูลของเจ้าชะตาเก็บไว้เลยค่ะ\n\n"
                        .'ถ้าอยากเริ่มดูดวง พิมพ์ "ดูดวง" ได้เลยนะคะ 🔮',
                    'reading' => null,
                ];
            }

            $latest = $readings->first();
            $name = $latest->resolveCustomerName();
            $birth = $readings->firstWhere(fn ($r) => $r->birth_date !== null)?->birth_date?->format('Y-m-d');
            $lastPaid = $readings->where('is_paid', true)->first();

            // นับ/หาวันแรกจาก DB ตรงๆ — ถูกต้องเสมอแม้ลูกค้ามีบิลเกิน 100 ใบ
            $paidCount = FortuneReading::where($scope)->where('is_paid', true)->count();
            $firstSeenRaw = FortuneReading::where($scope)->min('created_at');
            $firstSeen = ! empty($firstSeenRaw) ? \Carbon\Carbon::parse($firstSeenRaw) : null;

            $lines = ["📋 *ข้อมูลที่แม่หมอเก็บไว้ของเจ้าชะตา*\n"];
            // resolveCustomerName คืน 'คุณ' เมื่อยังไม่มีชื่อจริง
            $lines[] = '👤 ชื่อที่ใช้เรียก: *'.($name !== '' && $name !== 'คุณ' ? $name : 'ยังไม่ได้ตั้ง').'*';
            $lines[] = '🎂 วันเกิด: *'.(! empty($birth) ? $this->formatThaiDate($birth) : 'ยังไม่ได้ให้ไว้').'*';
            $lines[] = '🔮 ดูดวงกับแม่หมอแล้ว: *'.$paidCount.' ครั้ง*';
            if ($lastPaid) {
                $lines[] = '📅 ครั้งล่าสุด: '.($lastPaid->paid_at ?? $lastPaid->created_at)->format('d/m/Y');
            }
            if ($firstSeen) {
                $lines[] = '🌙 รู้จักกันตั้งแต่: '.$firstSeen->format('d/m/Y');
            }
            $lines[] = '📱 ช่องทาง: '.($platform === 'line' ? 'LINE' : 'Facebook');

            $lines[] = "\n_ข้อมูลบัญชีและการเงิน (ยอดเงิน ค่าแนะนำ อีเมล) ดูได้บนเว็บที่ล็อกอินแล้วเท่านั้น เพื่อความปลอดภัยค่ะ_";
            $lines[] = "\n👇 แก้ไขหรือลบข้อมูลได้จากปุ่มด้านล่างเลยค่ะ";

            Log::info('👤 PersonalData: ลูกค้าขอดูข้อมูลตัวเอง', [
                'platform' => $platform,
                'user_id' => $uid,
                'readings' => $readings->count(),
            ]);

            // 🎂 ปุ่มแก้วันเกิดโชว์เฉพาะคนที่ใช้ได้จริง (บิล Deep ที่ทำนายแล้วภายในกรอบเวลา
            //    + ยังไม่ใช้สิทธิ์) — ไม่งั้นกดแล้วบอทตอบมั่วเพราะไม่มีบิลให้แก้
            $canFixBirthdate = false;
            try {
                $target = $this->findRecentDeepReadingForCorrection($uid);
                $canFixBirthdate = $target !== null && $this->canCorrectBirthdateAgain($target);
            } catch (\Throwable $e) {
                $canFixBirthdate = false;
            }

            $buttons = [];
            if ($canFixBirthdate) {
                $buttons[] = ['title' => '🎂 เปลี่ยนวันเกิด', 'text' => 'เปลี่ยนวันเกิด', 'payload' => 'เปลี่ยนวันเกิด'];
            }
            $buttons[] = ['title' => '✏️ เปลี่ยนชื่อ', 'text' => 'เปลี่ยนชื่อ', 'payload' => 'เปลี่ยนชื่อ'];
            $buttons[] = ['title' => '📚 คำทำนายย้อนหลัง', 'text' => 'บิลของฉัน', 'payload' => 'บิลของฉัน'];
            $buttons[] = ['title' => '🗑️ ลบข้อมูลของฉัน', 'text' => 'ลบข้อมูลของฉัน', 'payload' => 'ลบข้อมูลของฉัน'];

            if (! $canFixBirthdate && ! empty($birth)) {
                $lines[] = '_(วันเกิดแก้ได้ภายใน 20 ชั่วโมงหลังทำนาย 1 ครั้งต่อบิล — ถ้าเลยแล้วพิมพ์ "ขอคุยกับคน" ให้แอดมินช่วยได้ค่ะ)_';
            }

            return [
                'action' => 'personal_data_summary',
                'message' => implode("\n", $lines),
                'reading' => null,
                'show_quick_replies' => true,
                'quick_replies' => $buttons,
            ];
        } catch (\Throwable $e) {
            Log::error('👤 PersonalData: สรุปข้อมูลล้มเหลว', [
                'platform' => $platform,
                'user_id' => $uid,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'personal_data_error',
                'message' => '🙏 ขออภัยค่ะ ระบบดึงข้อมูลไม่สำเร็จ ลองใหม่อีกครั้งนะคะ',
                'reading' => null,
            ];
        }
    }

    /**
     * ดึงชื่อใหม่จากข้อความที่ลูกค้าพิมพ์ตอบ (ตอนบอทถามว่า "เรียกว่าอะไรดี")
     */
    protected function extractNewCustomerName(string $text): ?string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        // ตัดคำนำที่ลูกค้ามักพิมพ์ติดมา
        $name = (string) preg_replace('/^(?:ชื่อ|เรียกฉันว่า|เรียกผมว่า|เรียกหนูว่า|เรียกว่า|ฉันชื่อ|ผมชื่อ|หนูชื่อ|ดิฉันชื่อ)\s*/u', '', $name);
        $name = (string) preg_replace('/\s*(ค่ะ|คะ|ครับ|จ้า|จ้ะ|นะคะ|นะครับ|นะ)+\s*$/u', '', $name);
        $name = trim($name);

        // 🔒 (2026-07-25) whitelist charset + สั้น — ชื่อนี้ถูก inject เข้า prompt AI
        //   ถ้าปล่อยอิสระ ลูกค้าตั้งชื่อว่า "ignore all previous instructions" ได้ (prompt injection)
        //   อนุญาต: อักษรไทย/อังกฤษ/เว้นวรรค/จุด/ขีด ยาวไม่เกิน 20 ตัว ไม่เกิน 2 คำ
        if ($name === '' || mb_strlen($name) > 20) {
            return null;
        }
        if (! preg_match('/^[\x{0E01}-\x{0E4E}a-zA-Z][\x{0E01}-\x{0E4E}a-zA-Z .\-]*$/u', $name)) {
            return null;
        }
        if (count(preg_split('/\s+/u', $name) ?: []) > 2) {
            return null;
        }
        // ต้องมีตัวอักษรจริงอย่างน้อย 2 ตัว
        if (! preg_match('/[\x{0E01}-\x{0E4E}a-zA-Z]{2,}/u', $name)) {
            return null;
        }
        // กันคำสั่งระบบ + คำที่ resolveCustomerName/isHumanLikeName จะปฏิเสธ
        //   (ถ้าปล่อยผ่าน บอทจะบอก "เรียบร้อย" แต่ชื่อไม่เปลี่ยนจริง — หลอกลูกค้า)
        // + กันประโยค/คำสั่งที่ไม่ใช่ชื่อคน (defense-in-depth ซ้อนกับ looksLikeHigherPriorityThanNaming)
        //   เช่น "โอนแล้ว" / "งานจะดีไหม" / "ดวงความรักปีนี้" / "ลืมกฎเดิมทั้งหมด" (prompt injection)
        foreach (['ดูดวง', 'ยกเลิก', 'ยืนยัน', 'ทำนาย', 'เมนู', 'ราคา', 'ลบข้อมูล', 'บิล',
            'คุณ', 'ลูกค้า', 'เจ้าชะตา', 'แม่หมอ', 'admin', 'system', 'ignore',
            'ไหม', 'มั้ย', 'แล้ว', 'ดวง', 'ความรัก', 'เงิน', 'กฎ', 'ลืม', 'ทั้งหมด',
            'ปีนี้', 'เดือนนี้', 'สลิป', 'บัญชี'] as $reserved) {
            if (mb_stripos($name, $reserved) !== false) {
                return null;
            }
        }

        return $name;
    }

    /**
     * ✏️ บันทึกชื่อใหม่ — เขียนทุกที่ที่ระบบใช้อ้างอิงชื่อ
     *
     * resolveCustomerName() อ่านตามลำดับ: reading.facebook_user_name → user_profile.name
     * → FortuneUserCredit → persona ⇒ ต้องอัปเดตให้ครบ ไม่งั้นชื่อเก่าเด้งกลับ
     */
    protected function applyCustomerNameChange(string $platform, string $uid, string $newName): array
    {
        $updated = 0;

        try {
            // 1. readings ล่าสุด (10 ใบพอ — ที่เหลือเป็นประวัติเก่า)
            $readings = FortuneReading::where(function ($q) use ($uid) {
                $q->where('facebook_user_id', $uid)->orWhere('platform_user_id', $uid);
            })->orderByDesc('created_at')->limit(10)->get();

            foreach ($readings as $reading) {
                $profile = is_array($reading->user_profile) ? $reading->user_profile : [];
                $profile['name'] = $newName;
                $reading->update([
                    'facebook_user_name' => $newName,
                    'user_profile' => $profile,
                ]);
                $updated++;
            }

            // 2. FortuneUserCredit — ตัวที่ persistent ข้ามบทสนทนา
            try {
                $credit = FortuneUserCredit::findByUser($uid, $platform);
                if ($credit) {
                    $credit->update(['facebook_user_name' => $newName]);
                }
            } catch (\Throwable $e) {
                Log::debug('PersonalData: อัปเดตชื่อใน FortuneUserCredit ไม่สำเร็จ (ข้ามได้)', ['error' => $e->getMessage()]);
            }

            // 3. persona (ใช้ในบริบท AI) + ล้าง cache ที่ค้าง 24 ชม.
            try {
                \App\Models\FortuneCustomerPersona::where('platform', $platform)
                    ->where('platform_user_id', $uid)
                    ->update(['display_name' => $newName]);
                app(\App\Services\Fortune\CustomerPersonaService::class)->invalidateCache($platform, $uid);
            } catch (\Throwable $e) {
                Log::debug('PersonalData: อัปเดตชื่อใน persona ไม่สำเร็จ (ข้ามได้)', ['error' => $e->getMessage()]);
            }

            // 4. 🔑 เก็บชื่อที่ลูกค้าตั้งเองไว้ถาวร — บิลใหม่สร้างด้วยชื่อโปรไฟล์ LINE/FB เสมอ
            //    ถ้าไม่เก็บไว้ ชื่อที่แก้จะหายทันทีที่เปิดบิลถัดไป (resolveCustomerName อ่าน
            //    reading.facebook_user_name เป็นอันดับแรก) — ดูการ apply ที่ FortuneChannelManager
            Cache::forever(self::PERSONAL_CUSTOM_NAME_PREFIX."{$platform}:{$uid}", $newName);

            Log::info('👤 PersonalData: เปลี่ยนชื่อลูกค้าสำเร็จ', [
                'platform' => $platform,
                'user_id' => $uid,
                'new_name' => $newName,
                'readings_updated' => $updated,
            ]);

            return [
                'action' => 'personal_name_updated',
                'message' => "✅ เรียบร้อยค่ะ ต่อไปแม่หมอจะเรียกว่า *{$newName}* นะคะ ✨",
                'reading' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('👤 PersonalData: เปลี่ยนชื่อล้มเหลว', [
                'platform' => $platform,
                'user_id' => $uid,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'personal_name_failed',
                'message' => '🙏 ขออภัยค่ะ ระบบบันทึกชื่อไม่สำเร็จ ลองใหม่อีกครั้งนะคะ',
                'reading' => null,
            ];
        }
    }

    /**
     * 🔒 ตอบเรื่องที่บอทแก้ให้ไม่ได้ (เงิน/อีเมล/รหัสผ่าน) — ชี้ทางที่ปลอดภัยแทน
     */
    protected function buildRestrictedDataResponse(): array
    {
        return [
            'action' => 'personal_data_restricted',
            'message' => "🔒 ขอโทษนะคะ — เรื่อง*บัญชีและการเงิน* แม่หมอแก้ให้ทางแชทไม่ได้ค่ะ\n\n"
                ."(ยอดเงิน · ค่าแนะนำ · อีเมล · เบอร์โทร · รหัสผ่าน)\n"
                ."เพราะเป็นกุญแจของบัญชี ต้องยืนยันตัวตนบนเว็บก่อนถึงแก้ได้ เพื่อความปลอดภัยของเจ้าชะตาเองนะคะ 🙏\n\n"
                ."🌐 แก้ไขได้ที่เว็บ จันทรา.online (เข้าสู่ระบบแล้วไปที่บัญชีของฉัน)\n"
                ."💬 หรือพิมพ์ \"ขอคุยกับคน\" ให้แอดมินช่วยตรวจสอบให้ค่ะ\n\n"
                .'✅ ส่วนข้อมูลดูดวง (ชื่อ · วันเกิด) แม่หมอแก้ให้ได้ทันที — พิมพ์ "ข้อมูลของฉัน" ได้เลยค่ะ',
            'reading' => null,
        ];
    }
}
