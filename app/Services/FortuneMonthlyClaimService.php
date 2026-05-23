<?php

namespace App\Services;

use App\Models\FortuneMonthlyFreeClaim;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FortuneMonthlyClaimService
 *
 * จัดการแคมเปญรับสิทธิ์ดูฟรีรายเดือน (จากโพสต์ในกลุ่ม Facebook)
 *
 * Flow:
 *   1. แอดมินโพสต์ในกลุ่ม + แนบลิงก์ landing page (`/fortune/monthly-claim`)
 *   2. ลูกค้าคลิก → landing page → กด "เปิด Messenger รับสิทธิ์"
 *   3. ลิงก์ m.me → ส่ง ref="MONTHLY_CLAIM_2026-05" ผ่าน Messenger referral
 *   4. Webhook detect referral → เรียก claimForUser($psid, 'facebook')
 *   5. ถ้ายังไม่ claim เดือนนี้ → +1 bonus_credits + ส่งข้อความขอบคุณ
 *   6. ถ้า claim แล้ว → ส่งข้อความ "ใช้สิทธิ์ไปแล้ว เจอกันเดือนหน้า"
 *
 * ทุก operation idempotent — กดซ้ำไม่ทำให้เครดิตเพิ่ม
 */
class FortuneMonthlyClaimService
{
    /**
     * prefix ที่ใช้ใน m.me ref param
     * รูปแบบเต็ม: MONTHLY_CLAIM_2026-05
     */
    public const REF_PREFIX = 'MONTHLY_CLAIM_';

    /**
     * source default ของการ claim ผ่านลิงก์โพสต์ในกลุ่ม
     */
    public const SOURCE_GROUP_POST = 'group_post';

    /**
     * source สำหรับ admin grant ตรง (ไม่ผ่านลิงก์)
     */
    public const SOURCE_ADMIN_GRANT = 'admin_grant';

    /**
     * คืน ref string ของเดือนปัจจุบัน
     * เช่น "MONTHLY_CLAIM_2026-05"
     */
    public function currentRef(): string
    {
        return self::REF_PREFIX . FortuneMonthlyFreeClaim::currentMonthKey();
    }

    /**
     * แยก month_key จาก ref string
     * คืน null ถ้า format ไม่ตรง หรือ month_key ไม่ใช่เดือนปัจจุบัน
     *
     * (กันคนเก็บ link เก่าเดือนก่อนๆ มากดในเดือนปัจจุบัน → invalidate ลิงก์เก่า)
     */
    public function parseRefForCurrentMonth(?string $ref): ?string
    {
        if (! $ref || ! str_starts_with($ref, self::REF_PREFIX)) {
            return null;
        }

        $monthKey = substr($ref, strlen(self::REF_PREFIX));
        $current = FortuneMonthlyFreeClaim::currentMonthKey();

        return $monthKey === $current ? $monthKey : null;
    }

    /**
     * ดำเนินการ claim สำหรับ user
     *
     * คืน array:
     *   - status: 'granted' | 'already_claimed' | 'disabled' | 'error'
     *   - message: ข้อความสำหรับส่งหา user
     *   - claim: model FortuneMonthlyFreeClaim (ถ้า granted)
     */
    public function claimForUser(
        string $psid,
        string $platform = 'facebook',
        string $source = self::SOURCE_GROUP_POST,
        ?array $metadata = null
    ): array {
        $settings = FortuneTellingSetting::getSettings();

        // ตรวจ toggle
        if (! ($settings->monthly_free_claim_enabled ?? false)) {
            return [
                'status' => 'disabled',
                'message' => 'ขออภัย แคมเปญดูดวงฟรีรายเดือนยังไม่เปิดให้บริการ',
                'claim' => null,
            ];
        }

        $monthKey = FortuneMonthlyFreeClaim::currentMonthKey();

        // 1) Fast path: ตรวจซ้ำก่อนเข้า transaction (ไม่ต้องล็อก)
        $existing = FortuneMonthlyFreeClaim::where('psid', $psid)
            ->where('platform', $platform)
            ->where('month_key', $monthKey)
            ->first();

        if ($existing) {
            return [
                'status' => 'already_claimed',
                'message' => $this->resolveAlreadyMessage($settings),
                'claim' => $existing,
            ];
        }

        // 2) Slow path: สร้างใหม่ใน transaction (UNIQUE constraint กัน race อีกชั้น)
        try {
            return DB::transaction(function () use ($psid, $platform, $source, $metadata, $settings, $monthKey) {
                // ดึง/สร้าง FortuneUserCredit แล้ว +1 credit (ใช้ canonical method)
                $credit = FortuneUserCredit::getOrCreate($psid, $platform);

                $note = sprintf(
                    'แคมเปญดูฟรีรายเดือน %s (source: %s)',
                    $monthKey,
                    $source
                );
                $credit->addCredits(1, null, $note);

                // บันทึกการ claim
                $claim = FortuneMonthlyFreeClaim::create([
                    'psid' => $psid,
                    'platform' => $platform,
                    'month_key' => $monthKey,
                    'credits_granted' => 1,
                    'source' => $source,
                    'ip' => $metadata['ip'] ?? null,
                    'user_agent' => $metadata['user_agent'] ?? null,
                    'referrer' => $metadata['referrer'] ?? null,
                    'fortune_user_credit_id' => $credit->id,
                    'claimed_at' => Carbon::now(),
                ]);

                Log::info('🎁 Fortune monthly free claim granted', [
                    'psid' => $psid,
                    'platform' => $platform,
                    'month_key' => $monthKey,
                    'source' => $source,
                ]);

                return [
                    'status' => 'granted',
                    'message' => $this->resolveSuccessMessage($settings),
                    'claim' => $claim,
                ];
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // 3) ชน UNIQUE constraint = มี request อีกตัวกด claim พร้อมกันก่อนเรา
            //    → treat as already_claimed (idempotent)
            //    SQLSTATE 23000 = integrity constraint violation
            if (str_contains($e->getMessage(), 'fmfc_user_month_unique')
                || $e->getCode() === '23000') {
                $existing = FortuneMonthlyFreeClaim::where('psid', $psid)
                    ->where('platform', $platform)
                    ->where('month_key', $monthKey)
                    ->first();

                Log::info('🎁 Race-loss on monthly claim — treating as already_claimed', [
                    'psid' => $psid,
                    'month_key' => $monthKey,
                ]);

                return [
                    'status' => 'already_claimed',
                    'message' => $this->resolveAlreadyMessage($settings),
                    'claim' => $existing,
                ];
            }

            Log::error('🎁 Fortune monthly claim DB error', [
                'psid' => $psid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'ขออภัย เกิดข้อผิดพลาด ลองใหม่อีกครั้งภายหลังนะคะ',
                'claim' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('🎁 Fortune monthly claim failed', [
                'psid' => $psid,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'ขออภัย เกิดข้อผิดพลาด ลองใหม่อีกครั้งภายหลังนะคะ',
                'claim' => null,
            ];
        }
    }

    /**
     * ดึงข้อความเมื่อ claim สำเร็จ (custom จาก settings → fallback default)
     */
    protected function resolveSuccessMessage(FortuneTellingSetting $settings): string
    {
        $custom = $settings->monthly_free_claim_success_message ?? null;
        if (! empty($custom)) {
            return trim($custom);
        }

        return "🎁 รับสิทธิ์ดูไพ่ฟรี 1 ใบประจำเดือนเรียบร้อย ✨\n\n"
            . "พิมพ์ \"ดูดวง\" หรือกดปุ่มด้านล่างเพื่อใช้สิทธิ์ได้เลยค่ะ\n"
            . "(สิทธิ์นี้ใช้ได้ภายในเดือนนี้นะคะ)";
    }

    /**
     * ดึงข้อความเมื่อ claim ซ้ำในเดือนเดียวกัน
     */
    protected function resolveAlreadyMessage(FortuneTellingSetting $settings): string
    {
        $custom = $settings->monthly_free_claim_already_message ?? null;
        if (! empty($custom)) {
            return trim($custom);
        }

        $next = Carbon::now('Asia/Bangkok')->startOfMonth()->addMonth();

        return "🌙 คุณได้รับสิทธิ์ดูฟรีของเดือนนี้ไปแล้วค่ะ\n\n"
            . "พบกันใหม่วันที่ 1 " . $this->thaiMonthName($next->month) . " นะคะ ✨";
    }

    /**
     * ชื่อเดือนภาษาไทย
     */
    protected function thaiMonthName(int $month): string
    {
        $months = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        return $months[$month] ?? '';
    }

    /**
     * สร้างลิงก์ landing page (ใส่ในโพสต์กลุ่ม)
     */
    public function landingUrl(): string
    {
        return rtrim(config('app.url', 'https://main.thaiprompt.online'), '/')
            . '/fortune/monthly-claim';
    }

    /**
     * สร้าง m.me URL ที่มี ref ของเดือนปัจจุบัน (ใช้ใน landing page)
     */
    public function messengerUrl(): string
    {
        $settings = FortuneTellingSetting::getSettings();
        $pageId = $settings->facebook_page_id ?? '';

        if (empty($pageId)) {
            return '';
        }

        return "https://m.me/{$pageId}?ref=" . urlencode($this->currentRef());
    }

    /**
     * สร้างเนื้อหาโพสต์ "ล่อเป้า" สำหรับโพสต์ในกลุ่ม
     *
     * แอดมิน copy → ไปแปะในกลุ่มเอง (Meta บล็อก auto-post API ของ Page เข้ากลุ่ม)
     */
    public function generateBaitPostCopy(): string
    {
        $month = $this->thaiMonthName(Carbon::now('Asia/Bangkok')->month);
        $url = $this->landingUrl();

        // 🌙 (2026-05-23) Closing line dynamic — ไม่ pitch Deep ถ้า toggle ปิด
        $settings = \App\Models\FortuneTellingSetting::getSettings();
        $deepOnClaim = $settings->isDeepReadingEnabled();
        $celticOnClaim = (bool) ($settings->enable_celtic_cross ?? false);
        $closingLine = $deepOnClaim
            ? "💎 อยากดูดวงเชิงลึก? ทักแม่หมอใน Messenger ได้เลยค่ะ\n\n"
            : ($celticOnClaim
                ? "🔮 อยากดูดวงไพ่ Celtic Cross 10 ใบ? ทักแม่หมอใน Messenger ได้เลยค่ะ\n\n"
                : "🔮 อยากดูดวงเพิ่มเติม? ทักแม่หมอใน Messenger ได้เลยค่ะ\n\n");

        return "🌙✨ สิทธิพิเศษเดือน{$month} ✨🌙\n\n"
            . "🎁 รับสิทธิ์ดูไพ่ฟรี 1 ใบ ประจำเดือน\n"
            . "🔮 สุ่มดูดวงส่วนตัวกับแม่หมอจันทรา (สำหรับสมาชิกในกลุ่ม)\n\n"
            . "📌 กดลิงก์ด้านล่างเพื่อรับสิทธิ์\n"
            . "👉 {$url}\n\n"
            . "⏰ กดได้ครั้งเดียวต่อเดือน\n"
            . "🗓️ รีเซ็ตอัตโนมัติทุกวันที่ 1 ของเดือน\n\n"
            . $closingLine
            . "#แม่หมอจันทรา #ดูไพ่ฟรี #ดูดวง{$month}";
    }
}
