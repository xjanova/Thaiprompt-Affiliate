<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Models\SlipOkAccount;
use App\Models\SlipVerification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🧾 SlipOK Service (2026-05-31)
 *
 * ตรวจสลิปโอนเงินผ่าน SlipOK API — fallback ชั้นสองเมื่อ SMS checker ไม่พบยอด
 *
 * หลักการ:
 *   - SMS checker = ตัวหลัก (ฟรี) ตัดบิลก่อนเสมอ
 *   - SlipOK = เรียกเฉพาะตอน "ส่งสลิปแล้ว 1 นาทียังไม่ตัด" หรือ "ลูกค้าพิมพ์/ถาม" → ประหยัดโควตา
 *   - QR ในสลิป (EMVCo) ฝัง transRef → SlipOK ยิงถามธนาคารจริง → คืนยอด/บัญชีปลายทาง/ผู้โอน
 *   - ปลอมไม่ได้: ตัดต่อยอด→คืนยอดจริง / QR มั่ว→ไม่เจอ / สลิปซ้ำ→1012 / โอนผิดบัญชี→1014
 *
 * API (เอกสาร SlipOK):
 *   - POST https://api.slipok.com/api/line/apikey/{branchId}  header x-authorization: {apiKey}
 *   - body: data(QR string) | files(รูป) | url(ลิงก์รูป) + log:true (dedup + เช็คบัญชีผู้รับ)
 *   - GET  .../{branchId}/quota  → เช็คโควตาคงเหลือ
 *
 * @see FortuneConversationService::trySlipOkVerifyForReading
 */
class SlipOkService
{
    public const DECISION_APPROVE = 'approve';

    public const DECISION_REJECT_AMOUNT = 'reject_amount';

    public const DECISION_REJECT_RECEIVER = 'reject_receiver';

    public const DECISION_DUPLICATE = 'duplicate';

    public const DECISION_NO_QR = 'no_qr';

    public const DECISION_QUOTA = 'quota';

    public const DECISION_BANK_DELAY = 'bank_delay';

    public const DECISION_STALE = 'stale';

    public const DECISION_ERROR = 'error';

    public const DECISION_DISABLED = 'disabled';

    /**
     * 🛡️ (2026-06-04) เกินเพดานยิง SlipOK ต่อคน (flood guard) — ไม่ยิง API, ส่งให้แอดมินตรวจ
     */
    public const DECISION_RATE_LIMITED = 'rate_limited';

    /** prefix cache สำหรับ flood guard */
    protected const FLOOD_KEY = 'fortune:slipok:';

    /**
     * 📅 (2026-06-01, user directive) อนุโลมรับสลิปย้อนหลังได้ไม่เกินกี่วัน
     *   เดิม "วันนี้เท่านั้น" — ผ่อนเป็น 3 วัน (กันลูกค้าที่โอนเมื่อวาน/2-3 วันก่อนแล้วเพิ่งกลับมา)
     *   ความปลอดภัยยังอยู่: transRef dedup (slip_verifications unique) กันใช้สลิปซ้ำ + เช็คบัญชีผู้รับ + ยอดขั้นต่ำ
     */
    public const MAX_SLIP_AGE_DAYS = 3;

    protected FortuneTellingSetting $settings;

    /** 🪪 (2026-06-09) Account Pool — หมุนเวียนหลายบัญชี SlipOK กัน quota ตัน */
    protected SlipOkAccountPool $pool;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->pool = new SlipOkAccountPool($this->settings);
    }

    public function isEnabled(): bool
    {
        if (! (bool) ($this->settings->enable_slipok_verify ?? false)) {
            return false;
        }

        // เปิดได้ถ้า: มีบัญชีใน pool พร้อมใช้ หรือ มี key เดี่ยวเดิม (backward-compat)
        return $this->pool->isEnabled()
            || (! empty($this->settings->slipok_branch_id) && ! empty($this->settings->slipok_api_key));
    }

    /**
     * 🪪 (2026-06-09) เลือก credential (branch + key) สำหรับยิงครั้งนี้
     *   - pool เปิด → เลือกบัญชีตามโหมด (pick / nextAfter สำหรับ failover)
     *   - pool ปิด/ไม่มีบัญชี → key เดี่ยวเดิม (legacy)
     *
     * @param  array<int>  $triedAccountIds  id บัญชีที่ลองแล้วในรอบ failover นี้
     * @return array{branch: string, key: string, account: ?SlipOkAccount}|null
     */
    protected function resolveCreds(array $triedAccountIds = []): ?array
    {
        if ($this->pool->isEnabled()) {
            $account = empty($triedAccountIds)
                ? $this->pool->pick()
                : $this->pool->nextAfter($triedAccountIds);

            if ($account) {
                return [
                    'branch' => trim((string) $account->branch_id),
                    'key' => (string) $account->api_key,
                    'account' => $account,
                ];
            }

            // pool เปิด = แหล่งความจริง — บัญชีหมดทั้ง pool → ไม่ fallback legacy (กันยิงคีย์หมดซ้ำ)
            return null;
        }

        // pool ปิด → key เดี่ยวเดิม
        if (! empty($this->settings->slipok_branch_id) && ! empty($this->settings->slipok_api_key)) {
            return [
                'branch' => trim((string) $this->settings->slipok_branch_id),
                'key' => (string) $this->settings->slipok_api_key,
                'account' => null,
            ];
        }

        return null;
    }

    protected function baseUrl(string $branch): string
    {
        return 'https://api.slipok.com/api/line/apikey/'.rawurlencode(trim($branch));
    }

    /**
     * ❌ ผลลัพธ์เป็น "บัญชีหมดโควต้า" หรือไม่ (codes 1003/1004/1015) → ต้องสลับบัญชี
     */
    protected function isAccountQuotaFailure(array $result): bool
    {
        return in_array((int) ($result['error_code'] ?? 0), [1003, 1004, 1015], true);
    }

    /**
     * ❌ ผลลัพธ์เป็น "key พัง / auth ล้ม" หรือไม่ (HTTP 401/403) → สลับบัญชี + พักบัญชีนี้
     */
    protected function isAccountAuthFailure(array $result): bool
    {
        return in_array((int) ($result['http'] ?? 0), [401, 403], true);
    }

    // ================================================================
    // 🛡️ (2026-06-04) Flood Guard — กันส่งสลิป/บิลปลอมรัวๆ ดูดโควต้า SlipOK ฟรี
    //   หลักการ: 1 คนยิง SlipOK ได้ไม่เกิน N ครั้ง/หน้าต่างเวลา (default 2/24ชม.)
    //   เกินแล้ว → หยุดยิง ส่งให้แอดมินตรวจ ; ก่อกวนซ้ำหลายรอบ → แบนอัตโนมัติ
    // ================================================================

    /** เพดานยิง SlipOK ต่อคน/หน้าต่าง (0 = ไม่จำกัด) */
    public function maxChecksPerUser(): int
    {
        return (int) ($this->settings->slipok_max_checks_per_user ?? 2);
    }

    /** ความยาวหน้าต่างเวลานับเพดาน (วินาที) */
    public function checkWindowSeconds(): int
    {
        return max(1, (int) ($this->settings->slipok_check_window_hours ?? 24)) * 3600;
    }

    /** ก่อกวนเกินเพดานกี่รอบแล้วแบน (0 = ไม่แบนอัตโนมัติ) */
    public function banAfterRounds(): int
    {
        return (int) ($this->settings->slipok_ban_after_rounds ?? 2);
    }

    /** สร้าง key ระบุตัวผู้ใช้ (platform:userId) */
    public function userKey(?string $platform, ?string $userId): string
    {
        return ($platform ?: 'x').':'.($userId ?: 'x');
    }

    /** จำนวนครั้งที่ยิง SlipOK ไปแล้วในหน้าต่างปัจจุบัน */
    public function checksUsed(?string $platform, ?string $userId): int
    {
        if (empty($userId)) {
            return 0;
        }

        return (int) Cache::get(self::FLOOD_KEY.'spend:'.$this->userKey($platform, $userId), 0);
    }

    /**
     * 🚦 ยังยิง SlipOK ให้ผู้ใช้คนนี้ได้อีกไหม (ยังไม่เกินเพดาน)
     *   - max <= 0 → ไม่จำกัด (true เสมอ)
     *   - ไม่มี userId → ปล่อยผ่าน (กันบล็อกเคสที่ระบุตัวไม่ได้)
     */
    public function canSpendForUser(?string $platform, ?string $userId): bool
    {
        $max = $this->maxChecksPerUser();
        if ($max <= 0 || empty($userId)) {
            return true;
        }

        return $this->checksUsed($platform, $userId) < $max;
    }

    /**
     * บันทึกว่ายิง SlipOK จริงไป 1 ครั้ง (เลื่อนหน้าต่าง TTL — driver-agnostic)
     *   เรียกเฉพาะตอนยิง API จริง (hash-hit ไม่นับ)
     */
    protected function noteSpend(?string $platform, ?string $userId): void
    {
        if (empty($userId)) {
            return;
        }
        $key = self::FLOOD_KEY.'spend:'.$this->userKey($platform, $userId);
        $cur = (int) Cache::get($key, 0);
        Cache::put($key, $cur + 1, $this->checkWindowSeconds());
    }

    /**
     * 🚨 บันทึก "รอบ" การก่อกวน (overflow) เมื่อผู้ใช้ยิงเกินเพดาน
     *   - นับเป็น strike แค่ 1 ครั้งต่อหน้าต่าง (flag struck TTL = window) — กันนับรัวทุกใบ
     *   - strike สะสม 7 วัน → ถึง banAfterRounds → ควรแบน
     *
     * @return array{strikes: int, should_ban: bool, overflow: int}
     */
    public function registerOverflowStrike(?string $platform, ?string $userId): array
    {
        $key = $this->userKey($platform, $userId);

        // นับจำนวนรูป overflow ในหน้าต่างนี้ (ไว้ดู severity)
        $ovKey = self::FLOOD_KEY.'overflow:'.$key;
        $overflow = (int) Cache::get($ovKey, 0) + 1;
        Cache::put($ovKey, $overflow, $this->checkWindowSeconds());

        // strike — 1 ครั้ง/หน้าต่าง
        $struckKey = self::FLOOD_KEY.'struck:'.$key;
        $strikesKey = self::FLOOD_KEY.'strikes:'.$key;
        $strikes = (int) Cache::get($strikesKey, 0);

        if (! Cache::get($struckKey, false)) {
            $strikes++;
            Cache::put($strikesKey, $strikes, 7 * 86400); // สะสม 7 วัน
            Cache::put($struckKey, true, $this->checkWindowSeconds());
        }

        $banAfter = $this->banAfterRounds();
        $shouldBan = $banAfter > 0 && $strikes >= $banAfter;

        return ['strikes' => $strikes, 'should_ban' => $shouldBan, 'overflow' => $overflow];
    }

    /** เคลียร์ตัวนับ flood ของผู้ใช้ (เช่นเมื่อ verify ผ่าน/แอดมินอนุมัติ) */
    public function clearFloodCounters(?string $platform, ?string $userId): void
    {
        $key = $this->userKey($platform, $userId);
        foreach (['spend:', 'overflow:', 'struck:', 'strikes:'] as $p) {
            Cache::forget(self::FLOOD_KEY.$p.$key);
        }
    }

    /** ดึงผล verify ที่ cache ไว้ตาม hash รูป (กันยิงซ้ำรูปเดิม) */
    protected function cachedVerifyByHash(?string $platform, ?string $userId, string $sha): ?array
    {
        if (empty($userId)) {
            return null;
        }
        $cached = Cache::get(self::FLOOD_KEY.'hash:'.$this->userKey($platform, $userId).':'.$sha);

        return is_array($cached) ? $cached : null;
    }

    /**
     * 🚦 ผลนี้ "นับเป็น spend (เก็บเพดาน) + cache" ได้ไหม
     *   นับเฉพาะผลที่ SlipOK ประมวลผลจริง + เป็นความรับผิดชอบของผู้ส่ง:
     *     ✅ verify สำเร็จ / สลิปซ้ำ (1012) / บัญชีผิด (1014) / ไม่มี QR (1007/1008/1011)
     *   ไม่นับ (transient — ไม่ใช่ความผิดลูกค้า, ให้ retry ได้ฟรี กัน false-block):
     *     ❌ network error (http != 200) / ธนาคารยังไม่อัปเดต (1009/1010) / โควต้าเราหมด (1003/1004/1015)
     */
    protected function isCountableSpend(array $result): bool
    {
        if ((int) ($result['http'] ?? 0) !== 200) {
            return false; // network/timeout/5xx → transient
        }
        if (! empty($result['ok'])) {
            return true; // verify สำเร็จ (มี transRef)
        }

        $code = $result['error_code'] ?? null;

        // ธนาคารยังไม่อัปเดต / โควต้าเราเองหมด → transient ไม่ลงโทษลูกค้า
        if (in_array($code, [1009, 1010, 1003, 1004, 1015], true)) {
            return false;
        }
        // ปฏิเสธชัดเจน (ซ้ำ/บัญชีผิด/ไม่มี QR) → SlipOK ประมวลผลจริง = นับ
        if (in_array($code, [1012, 1014, 1007, 1008, 1011], true)) {
            return true;
        }

        // 200 แต่ error code แปลก/ไม่รู้จัก → ไม่นับ (ปลอดภัยไว้ก่อน ไม่ลงโทษลูกค้า)
        return false;
    }

    /**
     * หลังยิง SlipOK จริง 1 ครั้ง: นับ spend + cache ผลตาม hash (กันยิงซ้ำรูปเดิม)
     *   ⚠️ นับ + cache เฉพาะผล "definitive" (isCountableSpend) — transient (bank delay/quota/network)
     *      ไม่นับ + ไม่ cache → ลูกค้าจริงที่ธนาคารช้า retry ได้ ไม่เผาเพดาน + ไม่ติด cache ค้าง
     */
    protected function afterRealSpend(?string $platform, ?string $userId, string $sha, array $result): void
    {
        if (! $this->isCountableSpend($result)) {
            return; // transient → ไม่นับ spend + ไม่ cache (retry แล้วยิงจริงได้)
        }

        $this->noteSpend($platform, $userId);

        if (empty($userId)) {
            return;
        }
        // cache แบบเบา (ตัด raw ออก) TTL 24 ชม. — รูปเดิมส่งซ้ำ → คืนผลเดิม ไม่ยิง API
        $light = $result;
        unset($light['raw']);
        Cache::put(self::FLOOD_KEY.'hash:'.$this->userKey($platform, $userId).':'.$sha, $light, 86400);
    }

    /**
     * 📊 เช็คโควตาคงเหลือ (ใช้ในหน้า admin ปุ่มทดสอบ)
     *
     * @return array{success: bool, quota?: int, overQuota?: int, message?: string}
     */
    public function checkQuota(): array
    {
        $creds = $this->resolveCreds();
        if ($creds === null) {
            return ['success' => false, 'message' => 'ยังไม่ได้กรอก Branch ID / API Key (หรือ pool ว่าง)'];
        }

        return $this->checkQuotaWithCreds($creds['branch'], $creds['key']);
    }

    /**
     * 📊 (2026-06-09) เช็คโควตาของบัญชี pool ที่ระบุ + sync ค่าจริงกลับ DB
     */
    public function checkQuotaForAccount(SlipOkAccount $account): array
    {
        $result = $this->checkQuotaWithCreds(trim((string) $account->branch_id), (string) $account->api_key);

        if ($result['success']) {
            // โควต้าคงเหลือจริง = quota (+ specialQuota ถ้ามี) — ใช้ sync เกณฑ์ near_empty/balance
            $remaining = (int) ($result['quota'] ?? 0) + (int) ($result['specialQuota'] ?? 0);
            $account->syncQuota($remaining, $result['endDate'] ?? null);
        }

        return $result;
    }

    /**
     * เช็คโควตาด้วย credential ที่ระบุ (ใช้ร่วมทั้ง key เดี่ยว + บัญชี pool)
     *
     * @return array{success: bool, quota?: int, overQuota?: int, message?: string}
     */
    protected function checkQuotaWithCreds(string $branch, string $key): array
    {
        if (empty($branch) || empty($key)) {
            return ['success' => false, 'message' => 'ยังไม่ได้กรอก Branch ID / API Key'];
        }

        try {
            $resp = Http::timeout(15)
                ->withHeaders(['x-authorization' => $key])
                ->get($this->baseUrl($branch).'/quota');

            $json = $resp->json() ?? [];

            if ($resp->successful() && ($json['success'] ?? false)) {
                $data = $json['data'] ?? [];

                return [
                    'success' => true,
                    'quota' => (int) ($data['quota'] ?? 0),
                    'overQuota' => (int) ($data['overQuota'] ?? 0),
                    'specialQuota' => (int) ($data['specialQuota'] ?? 0),
                    'endDate' => $data['endDate'] ?? null,
                    'message' => 'เชื่อมต่อสำเร็จ',
                ];
            }

            return [
                'success' => false,
                'message' => $json['message'] ?? ('เช็คโควตาไม่สำเร็จ (HTTP '.$resp->status().')'),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อ SlipOK ไม่ได้: '.$e->getMessage()];
        }
    }

    /**
     * 🔎 ตรวจสลิปจากไฟล์ในเครื่อง (multipart) — ใช้กับสลิปที่ดาวน์โหลด/เก็บไว้
     *
     * @param  string|null  $platform  'facebook'|'line' — ใช้นับ flood guard + hash dedup (ส่งมาเสมอถ้ามี)
     * @param  string|null  $userId  PSID/LINE id — ใช้นับ flood guard + hash dedup
     */
    public function verifyByFile(string $absolutePath, ?string $platform = null, ?string $userId = null): array
    {
        if (! is_file($absolutePath)) {
            return $this->normalize(0, ['message' => 'ไฟล์สลิปไม่พบ'], null);
        }

        $bytes = @file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            return $this->normalize(0, ['message' => 'อ่านไฟล์สลิปไม่ได้'], null);
        }

        // 🛡️ (2026-06-04) hash dedup — รูปไบต์เดิมที่เคยส่ง → คืนผลเดิม ไม่ยิง SlipOK ซ้ำ (กันเปลืองโควต้า)
        $sha = sha1($bytes);
        $cached = $this->cachedVerifyByHash($platform, $userId, $sha);
        if ($cached !== null) {
            return $cached;
        }

        // 🪪 (2026-06-09) ยิงผ่าน pool + auto-failover (สลับบัญชีถ้าโควต้าหมด/key พัง)
        return $this->runVerifyWithFailover('file', ['bytes' => $bytes], $platform, $userId, $sha);
    }

    /**
     * 🔎 ตรวจสลิปจาก URL รูป (FB CDN เป็น public URL)
     *
     * @param  string|null  $platform  ใช้นับ flood guard (ส่งมาเสมอถ้ามี)
     * @param  string|null  $userId  ใช้นับ flood guard
     */
    public function verifyByUrl(string $url, ?string $platform = null, ?string $userId = null): array
    {
        // hash dedup ตาม URL (FB CDN URL ต่างกันทุกอัปโหลด → ช่วยได้น้อย แต่กัน retry ลิงก์เดิม)
        $sha = sha1($url);
        $cached = $this->cachedVerifyByHash($platform, $userId, $sha);
        if ($cached !== null) {
            return $cached;
        }

        // 🪪 (2026-06-09) ยิงผ่าน pool + auto-failover
        return $this->runVerifyWithFailover('url', ['url' => $url], $platform, $userId, $sha);
    }

    /**
     * 🔁 (2026-06-09) ยิง SlipOK พร้อม auto-failover ข้ามบัญชีใน pool
     *
     * - เลือกบัญชีตามโหมด → ยิง → ถ้าโควต้าหมด (1003/1004/1015) หรือ key พัง (401/403)
     *   → พักบัญชีนั้น + ลองบัญชีถัดไป (วนได้สูงสุด = จำนวนบัญชี active)
     * - ผลชัดเจน (definitive spend) → นับ used + เคลียร์ error counter ของบัญชี
     * - afterRealSpend (flood guard ต่อคน + hash cache) ยิงครั้งเดียวหลังจบ
     *
     * @param  'file'|'url'  $kind
     * @param  array{bytes?: string, url?: string}  $payload
     */
    protected function runVerifyWithFailover(string $kind, array $payload, ?string $platform, ?string $userId, string $sha): array
    {
        $tried = [];
        $maxAttempts = max(1, $this->pool->isEnabled() ? $this->pool->activeCount() : 1);
        $result = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $creds = $this->resolveCreds($tried);
            if ($creds === null) {
                // ไม่มีบัญชีเหลือใน pool → ถ้าเคยได้ผล quota มาแล้วก็คืนผลนั้น ไม่งั้นแจ้ง quota
                $result ??= $this->normalize(0, ['code' => 1003, 'message' => 'บัญชี SlipOK หมดโควตาทุกตัวในรอบนี้'], null);
                break;
            }

            $account = $creds['account'] ?? null;

            try {
                $result = $this->attemptVerify($kind, $payload, $creds);
            } catch (\Throwable $e) {
                Log::warning('SlipOkService: attemptVerify exception', [
                    'kind' => $kind,
                    'account_id' => $account?->id,
                    'error' => $e->getMessage(),
                ]);
                $result = $this->normalize(0, ['message' => $e->getMessage()], null);
            }

            // ไม่มีบัญชี (legacy key เดี่ยว) → ไม่ต้องหมุน จบเลย
            if ($account === null) {
                break;
            }

            // โควต้าหมด → พักบัญชีถึงสิ้นเดือน + ลองตัวถัดไป
            if ($this->isAccountQuotaFailure($result)) {
                $account->markQuotaExhausted('SlipOK code '.($result['error_code'] ?? '?'));
                $tried[] = $account->id;

                continue;
            }

            // key พัง / auth ล้ม → พักสั้นๆ + ลองตัวถัดไป
            if ($this->isAccountAuthFailure($result)) {
                $account->markError('HTTP '.($result['http'] ?? '?'));
                $tried[] = $account->id;

                continue;
            }

            // ผลชัดเจน (เจอสลิป/ปฏิเสธยอด/ซ้ำ/บัญชีผิด ฯลฯ) → นับโควต้าที่เสียจริง + เคลียร์ error
            if ($this->isCountableSpend($result)) {
                $account->markUsed();
                $account->markSuccess();
            }

            break;
        }

        if ($result === null) {
            $result = $this->normalize(0, ['message' => 'ไม่มีบัญชี SlipOK พร้อมใช้งาน'], null);
        }

        // นับ spend ต่อคน (flood guard) + cache ผลตาม hash — ครั้งเดียวต่อการตรวจ
        $this->afterRealSpend($platform, $userId, $sha, $result);

        return $result;
    }

    /**
     * ยิง SlipOK 1 ครั้งด้วย credential ที่ระบุ (ไม่มี failover — runner จัดการ)
     *
     * @param  'file'|'url'  $kind
     * @param  array{branch: string, key: string}  $creds
     */
    protected function attemptVerify(string $kind, array $payload, array $creds): array
    {
        $url = $this->baseUrl($creds['branch']);

        if ($kind === 'file') {
            $resp = Http::timeout(25)
                ->withHeaders(['x-authorization' => $creds['key']])
                ->attach('files', $payload['bytes'], 'slip.jpg')
                ->post($url, [
                    'log' => $this->settings->slipok_use_log ? 'true' : 'false',
                ]);
        } else {
            $resp = Http::timeout(25)
                ->withHeaders(['x-authorization' => $creds['key']])
                ->asJson()
                ->post($url, [
                    'url' => $payload['url'],
                    'log' => (bool) $this->settings->slipok_use_log,
                ]);
        }

        return $this->normalize($resp->status(), $resp->json() ?? [], $resp->json());
    }

    /**
     * 🧹 แปลง response ดิบ → รูปแบบมาตรฐาน
     *
     * @return array{ok: bool, http: int, error_code: int|null, message: string,
     *   transRef: string|null, amount: float|null, receiver_account: string|null,
     *   receiving_bank: string|null, sending_bank: string|null, sender_name: string|null,
     *   trans_timestamp: string|null, raw: array}
     */
    protected function normalize(int $http, array $json, ?array $raw): array
    {
        $data = $json['data'] ?? [];
        $dataSuccess = is_array($data) ? ($data['success'] ?? null) : null;
        $topSuccess = $json['success'] ?? null;

        // error code อาจอยู่ที่ top-level หรือใน data
        $errorCode = $json['code'] ?? ($data['code'] ?? null);
        $errorCode = $errorCode !== null ? (int) $errorCode : null;

        $ok = $http === 200 && ($topSuccess === true) && ($dataSuccess !== false) && ! empty($data['transRef']);

        return [
            'ok' => $ok,
            'http' => $http,
            'error_code' => $errorCode,
            'message' => (string) ($json['message'] ?? ($data['message'] ?? '')),
            'transRef' => $data['transRef'] ?? null,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'receiver_account' => $data['receiver']['account']['value']
                ?? ($data['receiver']['proxy']['value'] ?? null),
            'receiver_name' => $data['receiver']['displayName'] ?? ($data['receiver']['name'] ?? null),
            'receiving_bank' => $data['receivingBank'] ?? null,
            'sending_bank' => $data['sendingBank'] ?? null,
            'sender_name' => $data['sender']['displayName'] ?? ($data['sender']['name'] ?? null),
            'trans_timestamp' => $data['transTimestamp'] ?? null,
            'raw' => $raw ?? $json,
        ];
    }

    /**
     * 🚦 ตัดสินใจจากผล verify เทียบกับบิล — 4 ด่าน
     *
     * @return array{decision: string, reason: string, verify: array}
     */
    public function evaluateForReading(FortuneReading $reading, array $verify): array
    {
        // ── ด่าน 1: verify จริงไหม (map error code) ──────────────
        if (! $verify['ok']) {
            $decision = match ($verify['error_code']) {
                1012 => self::DECISION_DUPLICATE,
                1014 => self::DECISION_REJECT_RECEIVER,
                1007, 1008, 1011 => self::DECISION_NO_QR,
                1004, 1003, 1015 => self::DECISION_QUOTA,
                1010, 1009 => self::DECISION_BANK_DELAY,
                default => self::DECISION_ERROR,
            };

            return ['decision' => $decision, 'reason' => $verify['message'] ?: ('error '.$verify['error_code']), 'verify' => $verify];
        }

        // ── ด่าน 4 (ทำก่อนเพราะถูกสุด): transRef ซ้ำไหม ──────────
        $transRef = (string) $verify['transRef'];
        $existing = SlipVerification::where('trans_ref', $transRef)->first();
        if ($existing) {
            return ['decision' => self::DECISION_DUPLICATE, 'reason' => 'transRef ซ้ำ (เคยใช้กับบิลอื่นแล้ว)', 'verify' => $verify];
        }

        // ── ด่าน 4.5 (2026-06-01, user): สลิปนี้เคยตัดบิลด้วย SMS checker ไปแล้วไหม ──────────
        //   ปิดช่อง cross-platform reuse: บิลที่ตัดด้วย SMS ไม่ได้ record transRef → ด่าน 4 จับไม่ได้
        //   user idea: SMS มี "ยอด+เวลา" → match สลิป (ยอดตรง + เวลา ±2 นาที) กับ SMS ที่ matched บิลอื่นแล้ว
        //   (ระบบใช้ยอดทศนิยม unique ต่อบิล → ยอด+เวลา แม่นพอ + ไม่เปลือง SlipOK quota)
        if ($this->slipMatchesUsedSmsPayment($verify, $reading->id)) {
            return ['decision' => self::DECISION_DUPLICATE, 'reason' => 'สลิปนี้เคยใช้ตัดบิลด้วย SMS ไปแล้ว (ยอด+เวลาตรง)', 'verify' => $verify];
        }

        // ── ด่าน 2: บัญชีปลายทาง = ของเราไหม ─────────────────────
        if (! $this->receiverMatchesOurAccounts($verify['receiver_account'])
            && ! $this->receiverMatchesOurAccounts($verify['receiver_name'])) {
            return ['decision' => self::DECISION_REJECT_RECEIVER, 'reason' => 'บัญชีปลายทางไม่ตรงกับบัญชีร้าน', 'verify' => $verify];
        }

        // ── ด่าน 3: ยอด ≥ ขั้นต่ำ (ไม่ต่ำกว่า 99 / ราคาบิล) ──────
        $minAmount = $this->minAmountForReading($reading);
        if (($verify['amount'] ?? 0) + 0.001 < $minAmount) {
            return [
                'decision' => self::DECISION_REJECT_AMOUNT,
                'reason' => 'ยอดโอน ฿'.number_format((float) $verify['amount'], 2).' ต่ำกว่าขั้นต่ำ ฿'.number_format($minAmount, 2),
                'verify' => $verify,
            ];
        }

        // ── ด่าน 5: สลิปย้อนหลังไม่เกิน MAX_SLIP_AGE_DAYS วัน (user directive 2026-06-01) ─────
        if (! $this->isTransWithinAllowedWindow($verify)) {
            return [
                'decision' => self::DECISION_STALE,
                'reason' => 'สลิปเก่าเกิน '.self::MAX_SLIP_AGE_DAYS.' วัน ('.($verify['trans_timestamp'] ?? '-').')',
                'verify' => $verify,
            ];
        }

        return ['decision' => self::DECISION_APPROVE, 'reason' => 'ผ่านทุกด่าน', 'verify' => $verify];
    }

    /**
     * 🛡️ (2026-06-01, user) สลิปนี้เคยถูกใช้ตัดบิลผ่าน SMS checker ไปแล้วไหม
     *   ปิดช่อง cross-platform reuse: บิลที่ตัดด้วย SMS ไม่ได้ record transRef → ด่าน 4 (transRef) จับไม่ได้
     *   วิธี: match การโอนของสลิป (ยอด + เวลา ±2 นาที) กับ sms_payment_notifications ที่ matched บิลอื่นแล้ว
     *   - amount: ตรงเป๊ะ (ระบบใช้ยอดทศนิยม unique ต่อบิล → ยอด+เวลา = แม่น)
     *   - เวลา: ±2 นาที (user: SMS กับเวลาในสลิปอาจคลาดเคลื่อนได้)
     *
     * @param  array  $verify  ผล verify จาก SlipOK (ต้องมี amount + trans_timestamp)
     * @param  int|null  $excludeReadingId  บิลปัจจุบัน — ไม่นับ (เคส SMS ตัดบิลนี้ + ลูกค้าส่งสลิปบิลเดียวกัน)
     * @return bool true = สลิปเคยตัดบิลด้วย SMS ไปแล้ว (= ซ้ำ)
     */
    public function slipMatchesUsedSmsPayment(array $verify, ?int $excludeReadingId = null): bool
    {
        $amount = $verify['amount'] ?? null;
        $ts = $verify['trans_timestamp'] ?? null;
        if ($amount === null || empty($ts)) {
            return false;
        }

        try {
            // สลิป transTimestamp = UTC (Z) → แปลงเป็นเวลาไทยให้ตรงกับ sms_timestamp (เก็บเป็นเวลาไทย)
            $slipTime = \Carbon\Carbon::parse($ts)->setTimezone('Asia/Bangkok');
            $amt = (float) $amount;

            $q = \Illuminate\Support\Facades\DB::table('sms_payment_notifications')
                ->whereNotNull('matched_transaction_id')               // ตัดบิลไปแล้ว
                ->whereBetween('amount', [$amt - 0.001, $amt + 0.001])  // ยอดตรงเป๊ะ
                ->whereBetween('sms_timestamp', [
                    $slipTime->copy()->subMinutes(2)->toDateTimeString(),
                    $slipTime->copy()->addMinutes(2)->toDateTimeString(),
                ]);

            // ไม่นับบิลปัจจุบัน (SMS ตัดบิลนี้ + ลูกค้าส่งสลิปบิลเดียวกัน = ไม่ใช่ reuse)
            if ($excludeReadingId !== null) {
                $q->where('matched_transaction_id', '!=', $excludeReadingId);
            }

            return $q->exists();
        } catch (\Throwable $e) {
            Log::warning('SlipOkService: slipMatchesUsedSmsPayment ล้มเหลว (non-blocking)', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 📅 สลิปอยู่ในช่วงที่อนุโลมไหม (โซนเวลาไทย) — user directive 2026-06-01: รับย้อนหลังไม่เกิน 3 วัน
     *   เดิมรับเฉพาะวันนี้ — ผ่อนเป็น MAX_SLIP_AGE_DAYS วัน (กันลูกค้าโอนเมื่อวาน/2-3 วันก่อนเพิ่งกลับมา)
     *   ไม่มี timestamp → ไม่บล็อก (เชื่อว่า SlipOK verify แล้ว)
     */
    protected function isTransWithinAllowedWindow(array $verify): bool
    {
        $ts = $verify['trans_timestamp'] ?? null;
        if (empty($ts)) {
            return true;
        }

        try {
            $slipDate = \Carbon\Carbon::parse($ts)->setTimezone('Asia/Bangkok')->startOfDay();
            $earliest = \Carbon\Carbon::now('Asia/Bangkok')->startOfDay()->subDays(self::MAX_SLIP_AGE_DAYS);

            // อนุโลมสลิปย้อนหลังได้ไม่เกิน MAX_SLIP_AGE_DAYS วัน (อายุ 0-3 วัน)
            return $slipDate->greaterThanOrEqualTo($earliest);
        } catch (\Throwable $e) {
            return true; // parse ไม่ได้ → ไม่บล็อก
        }
    }

    /**
     * ยอดขั้นต่ำที่อนุมัติได้ = max(slipok_min_amount, ราคาบิลจริง)
     *   - กันโอนต่ำกว่าราคาสินค้า (Celtic 99 / Deep 39)
     *   - user spec: "ไม่ต่ำกว่า 99"
     */
    protected function minAmountForReading(FortuneReading $reading): float
    {
        // ราคาบิลจริง (Celtic 99 / Deep 39) — ลูกค้าต้องโอน ≥ ราคาสินค้านั้น
        $basePrice = (float) ($reading->uniquePaymentAmount?->base_amount ?? 0);
        if ($basePrice > 0) {
            return $basePrice;
        }

        // ไม่มี UPA (เช่นเคสสร้างบิลจากสลิป) → ใช้ floor ขั้นต่ำ (default 99 — user spec "ไม่ต่ำกว่า 99")
        return (float) ($this->settings->slipok_min_amount ?? 99.00);
    }

    /**
     * 📛 บัญชีปลายทางตรงกับบัญชีร้านไหม
     *   SlipOK mask เลขบัญชี (เช่น "xxx-x-x5514-x") → เทียบ 4 หลักท้ายของบัญชี active
     */
    public function receiverMatchesOurAccounts(?string $receiverValue): bool
    {
        if (empty($receiverValue)) {
            return false;
        }

        // ดึงเฉพาะตัวเลขจาก masked value
        $recvDigits = preg_replace('/\D/', '', $receiverValue);
        if (mb_strlen($recvDigits) < 4) {
            // ไม่มีเลขให้เทียบ (proxy เป็นชื่อ) → เชื่อได้เฉพาะตอน log:true (SlipOK บังคับบัญชีผู้รับ = 1014)
            //   ถ้า log ปิด = ไม่มีใครเช็คบัญชีปลายทาง → ปฏิเสธไว้ก่อน (กันอนุมัติสลิปที่โอนเข้าบัญชีคนอื่น)
            return (bool) ($this->settings->slipok_use_log ?? false);
        }
        $recvLast4 = mb_substr($recvDigits, -4);

        $accounts = PaymentBankAccount::active()->get();
        foreach ($accounts as $acc) {
            foreach ([$acc->account_number, $acc->promptpay_id] as $mine) {
                $mineDigits = preg_replace('/\D/', '', (string) $mine);
                if (mb_strlen($mineDigits) >= 4 && mb_substr($mineDigits, -4) === $recvLast4) {
                    return true;
                }
            }
        }

        return false;
    }
}
