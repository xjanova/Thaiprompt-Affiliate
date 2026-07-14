<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🏦 KBank Slip Verification Service (2026-07-14)
 *
 * ตรวจสลิปโอนเงินผ่าน KBank Slip Verification API — provider ตัวที่ 2 คู่ขนานกับ SlipOK
 *
 * ต่างจาก SlipOK อย่างไร:
 *   - SlipOK: ส่ง "รูป/QR string" ให้ SlipOK แล้ว SlipOK ถอด QR + ถามธนาคารให้ (มีโควตาต่อใบ)
 *   - KBank : เราต้อง "ถอด QR จากสลิปเอง" → ได้ sendingBankId + transRef → ยิงถาม ledger ของ KBank ตรงๆ
 *             (OAuth2 + Two-Way SSL/mTLS ; เหมาะกับเงินเข้าบัญชี KBank ; ปกติถูก/ฟรีกว่าสำหรับลูกค้าธุรกิจ KBank)
 *
 * ⚠️ สถานะ (Phase 1): service + config admin พร้อมกรอกค่า — **ยังไม่ wire เข้า flow ตัดบิลจริง**
 *    ต้อง (1) กรอก Consumer Key/Secret + cert ในแอดมิน (2) ทดสอบ sandbox ผ่าน
 *    (3) ยืนยัน endpoint path + field mapping กับเอกสาร exercise จริง แล้วค่อย wire เข้า
 *    trySlipOkVerifyForReading() แบบ multi-provider
 *
 * 🔑 การตั้งค่า (fortune_telling_settings):
 *   - enable_kbank_verify, kbank_env (sandbox|production), kbank_base_url (override),
 *     kbank_verify_path, kbank_consumer_id, kbank_consumer_secret (encrypted),
 *     kbank_cert_path, kbank_cert_key_path, kbank_cert_password (encrypted), kbank_min_amount
 *
 * output ของ verify* ถูก normalize ให้ "รูปแบบเดียวกับ SlipOkService::normalize()" เป๊ะ
 * เพื่อให้เสียบเข้า SlipOkService::evaluateForReading() (5 ด่าน) ได้เลยตอน wire Phase 2
 *
 * @see \App\Services\Fortune\SlipOkService
 */
class KBankSlipService
{
    /** base URL production (Two-Way SSL) */
    public const BASE_PRODUCTION = 'https://openapi.kasikornbank.com';

    /** base URL sandbox (ทดสอบ) */
    public const BASE_SANDBOX = 'https://openapi-sandbox.kasikornbank.com';

    /** endpoint OAuth2 (stable across versions) */
    public const OAUTH_PATH = '/v2/oauth/token';

    /** endpoint ตรวจสลิป default (แอดมิน override ได้ผ่าน kbank_verify_path) */
    public const DEFAULT_VERIFY_PATH = '/v1/verslip/kbank/verify';

    /** prefix cache token */
    protected const TOKEN_CACHE_PREFIX = 'fortune:kbank:token:';

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * เปิดใช้งานไหม — ต้องเปิดสวิตช์ + มี Consumer ID/Secret ครบ
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->settings->enable_kbank_verify ?? false)
            && ! empty($this->settings->kbank_consumer_id)
            && ! empty($this->settings->kbank_consumer_secret);
    }

    /**
     * ใช้โหมด sandbox อยู่ไหม
     */
    public function isSandbox(): bool
    {
        return ($this->settings->kbank_env ?? 'sandbox') !== 'production';
    }

    /**
     * Base URL ที่จะยิง — ใช้ override ถ้ากรอกมา ไม่งั้นเลือกตาม env
     */
    public function baseUrl(): string
    {
        $override = trim((string) ($this->settings->kbank_base_url ?? ''));
        if ($override !== '') {
            return rtrim($override, '/');
        }

        return $this->isSandbox() ? self::BASE_SANDBOX : self::BASE_PRODUCTION;
    }

    /**
     * Path ของ endpoint ตรวจสลิป
     */
    protected function verifyPath(): string
    {
        $path = trim((string) ($this->settings->kbank_verify_path ?? ''));

        return $path !== '' ? '/'.ltrim($path, '/') : self::DEFAULT_VERIFY_PATH;
    }

    // ================================================================
    // 🔐 HTTP client + mTLS (Two-Way SSL)
    // ================================================================

    /**
     * สร้าง HTTP client พร้อม client certificate (ถ้ากรอก path ไว้)
     *   - production ของ KBank บังคับ Two-Way SSL → ต้องมี cert + key
     *   - sandbox บางที่ทดสอบ OAuth ได้โดยไม่ต้องมี cert → cert เป็น optional
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function httpClient(int $timeout = 25)
    {
        $client = Http::timeout($timeout)->connectTimeout(10);

        $certPath = trim((string) ($this->settings->kbank_cert_path ?? ''));
        $keyPath = trim((string) ($this->settings->kbank_cert_key_path ?? ''));
        $certPass = (string) ($this->settings->kbank_cert_password ?? '');

        $options = [];

        if ($certPath !== '' && is_file($certPath)) {
            $options['cert'] = $certPass !== '' ? [$certPath, $certPass] : $certPath;
        }
        if ($keyPath !== '' && is_file($keyPath)) {
            $options['ssl_key'] = $certPass !== '' ? [$keyPath, $certPass] : $keyPath;
        }

        return ! empty($options) ? $client->withOptions($options) : $client;
    }

    // ================================================================
    // 🎫 OAuth2 (client_credentials) + token cache
    // ================================================================

    /**
     * ขอ access token (cache ตาม expires_in — ลบ 60 วิ กัน race หมดอายุ)
     *
     * @return array{ok: bool, token?: string, http?: int, message?: string}
     */
    public function getAccessToken(bool $forceRefresh = false): array
    {
        $consumerId = trim((string) ($this->settings->kbank_consumer_id ?? ''));
        $consumerSecret = (string) ($this->settings->kbank_consumer_secret ?? '');

        if ($consumerId === '' || $consumerSecret === '') {
            return ['ok' => false, 'message' => 'ยังไม่ได้กรอก Consumer ID / Secret'];
        }

        // แยก cache ต่อ (env + consumer) — เปลี่ยน cred แล้วไม่ใช้ token เก่า
        $cacheKey = self::TOKEN_CACHE_PREFIX.($this->isSandbox() ? 'sb:' : 'pd:').sha1($consumerId.'|'.$consumerSecret);

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return ['ok' => true, 'token' => $cached, 'http' => 200];
            }
        }

        try {
            $resp = $this->httpClient(20)
                ->asForm()
                ->withBasicAuth($consumerId, $consumerSecret)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl().self::OAUTH_PATH, [
                    'grant_type' => 'client_credentials',
                ]);

            $json = $resp->json() ?? [];
            $token = $json['access_token'] ?? null;

            if ($resp->successful() && ! empty($token)) {
                $ttl = max(60, (int) ($json['expires_in'] ?? 1800) - 60);
                Cache::put($cacheKey, $token, $ttl);

                return ['ok' => true, 'token' => (string) $token, 'http' => $resp->status()];
            }

            return [
                'ok' => false,
                'http' => $resp->status(),
                'message' => $json['error_description'] ?? ($json['error'] ?? ($json['message'] ?? 'ขอ token ไม่สำเร็จ (HTTP '.$resp->status().')')),
            ];
        } catch (\Throwable $e) {
            Log::warning('KBankSlipService: getAccessToken exception', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'เชื่อมต่อ KBank ไม่ได้: '.$e->getMessage()];
        }
    }

    /**
     * 🔌 ทดสอบการเชื่อมต่อ (ใช้ในหน้าแอดมิน ปุ่มทดสอบ) — แค่ขอ token
     *
     * @return array{success: bool, message: string, env?: string}
     */
    public function checkConnection(): array
    {
        if (empty($this->settings->kbank_consumer_id) || empty($this->settings->kbank_consumer_secret)) {
            return ['success' => false, 'message' => 'ยังไม่ได้กรอก Consumer ID / Secret (กรอกแล้วกด 💾 บันทึกก่อนทดสอบ)'];
        }

        $env = $this->isSandbox() ? 'sandbox' : 'production';
        $res = $this->getAccessToken(true);

        if (! empty($res['ok'])) {
            return ['success' => true, 'message' => 'เชื่อมต่อ + ขอ token สำเร็จ', 'env' => $env];
        }

        return ['success' => false, 'message' => $res['message'] ?? 'เชื่อมต่อไม่สำเร็จ', 'env' => $env];
    }

    // ================================================================
    // 🔎 ตรวจสลิป
    // ================================================================

    /**
     * ตรวจสลิปจาก sendingBankId + transRef (core — ยิง KBank ตรง)
     *
     * @param  string  $sendingBankId  รหัสธนาคารต้นทาง (3 หลัก เช่น 004=KBANK, 014=SCB)
     * @param  string  $transRef  เลขอ้างอิงรายการจาก QR สลิป
     */
    public function verifyByTransRef(string $sendingBankId, string $transRef): array
    {
        $sendingBankId = trim($sendingBankId);
        $transRef = trim($transRef);

        if ($sendingBankId === '' || $transRef === '') {
            return $this->normalize(0, ['statusMessage' => 'ไม่มี sendingBankId / transRef'], null);
        }

        $tokenRes = $this->getAccessToken();
        if (empty($tokenRes['ok'])) {
            return $this->normalize((int) ($tokenRes['http'] ?? 0), ['statusMessage' => $tokenRes['message'] ?? 'ขอ token ไม่สำเร็จ'], null);
        }

        try {
            $headers = ['Accept' => 'application/json'];
            if ($this->isSandbox()) {
                // sandbox ของ KBank ใช้ header นี้บอกให้คืนข้อมูลจำลอง (ยืนยันกับเอกสาร exercise)
                $headers['x-test-mode'] = 'true';
            }

            $resp = $this->httpClient()
                ->withToken($tokenRes['token'])
                ->withHeaders($headers)
                ->asJson()
                ->post($this->baseUrl().$this->verifyPath(), [
                    // ⚠️ (2026-07-14) โครงสร้าง body ตามเอกสาร Slip Verification ทั่วไป —
                    //    ยืนยัน field name กับหน้า "Try it" ของ exercise จริงก่อน wire live
                    'rqUID' => (string) Str::uuid(),
                    'rqDt' => now()->toIso8601String(),
                    'sendingBankId' => $sendingBankId,
                    'transRef' => $transRef,
                ]);

            return $this->normalize($resp->status(), $resp->json() ?? [], $resp->json());
        } catch (\Throwable $e) {
            Log::warning('KBankSlipService: verifyByTransRef exception', [
                'error' => $e->getMessage(),
                'sendingBankId' => $sendingBankId,
            ]);

            return $this->normalize(0, ['statusMessage' => $e->getMessage()], null);
        }
    }

    /**
     * ตรวจสลิปจาก QR payload (EMVCo string) — ถอด sendingBankId + transRef แล้วยิง verify
     */
    public function verifyByQrPayload(string $emvcoPayload): array
    {
        $ref = $this->extractSlipRef($emvcoPayload);
        if ($ref === null) {
            return $this->normalize(0, ['statusMessage' => 'อ่าน sendingBankId/transRef จาก QR ไม่ได้'], null);
        }

        return $this->verifyByTransRef($ref['sendingBankId'], $ref['transRef']);
    }

    /**
     * ตรวจสลิปจากไฟล์รูปในเครื่อง — ถอด QR จากรูปก่อน แล้วค่อย verify
     *   ⚠️ ต้องมีตัวถอด QR (khanamiryan/qrcode-detector-decoder หรือ zbarimg) ติดตั้งบนเซิร์ฟเวอร์
     */
    public function verifyByFile(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            return $this->normalize(0, ['statusMessage' => 'ไฟล์สลิปไม่พบ'], null);
        }

        $payload = $this->decodeQrFromImage($absolutePath);
        if ($payload === null) {
            return $this->normalize(0, [
                'statusMessage' => 'ถอด QR จากรูปไม่ได้ (ยังไม่ได้ติดตั้งตัวถอด QR หรือรูปไม่มี QR)',
            ], null);
        }

        return $this->verifyByQrPayload($payload);
    }

    // ================================================================
    // 🧩 QR decode + EMVCo TLV parse
    // ================================================================

    /**
     * ถอด QR จากรูปสลิป → คืน payload string
     *   ลำดับ fallback (ไม่เพิ่ม hard dependency):
     *     1) khanamiryan/qrcode-detector-decoder ถ้า composer require ไว้
     *     2) zbarimg ถ้าติดตั้งบนเซิร์ฟเวอร์
     *     3) คืน null (แจ้งชัดเจนว่ายังถอดไม่ได้)
     *
     * @return string|null EMVCo payload หรือ null ถ้าถอดไม่ได้
     */
    public function decodeQrFromImage(string $absolutePath): ?string
    {
        // 1) khanamiryan (pure-PHP) — ถ้าติดตั้งไว้
        if (class_exists(\Zxing\QrReader::class)) {
            try {
                $reader = new \Zxing\QrReader($absolutePath);
                $text = $reader->text();
                if (is_string($text) && $text !== '') {
                    return $text;
                }
            } catch (\Throwable $e) {
                Log::info('KBankSlipService: khanamiryan decode ล้มเหลว', ['error' => $e->getMessage()]);
            }
        }

        // 2) zbarimg (ถ้ามี binary บนเซิร์ฟเวอร์)
        try {
            $bin = trim((string) @shell_exec('command -v zbarimg 2>/dev/null'));
            if ($bin !== '') {
                $out = (string) @shell_exec('zbarimg --raw -q '.escapeshellarg($absolutePath).' 2>/dev/null');
                $out = trim($out);
                if ($out !== '') {
                    return $out;
                }
            }
        } catch (\Throwable $e) {
            Log::info('KBankSlipService: zbarimg decode ล้มเหลว', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * แยก sendingBankId + transRef จาก EMVCo payload ของสลิป
     *
     * ⚠️ (2026-07-14) โครงสร้าง QR สลิปไทยเป็น TLV (tag 2 หลัก + len 2 หลัก + value) —
     *    parseTlv() ถอดโครงสร้างถูกต้องแน่ ; ส่วน "tag ไหน = bankId / transRef"
     *    ให้ยืนยันกับสลิปจริงตอนทดสอบ (เรา log map ทั้งหมดไว้ให้ปรับ) — heuristic นี้อิง
     *    รูปแบบที่พบบ่อย: field 00 บรรจุ sub-TLV { 00: version, 01: sendingBankId(3), 02: transRef }
     *
     * @return array{sendingBankId: string, transRef: string}|null
     */
    public function extractSlipRef(string $payload): ?array
    {
        $payload = trim($payload);
        if ($payload === '') {
            return null;
        }

        $top = $this->parseTlv($payload);
        if (empty($top)) {
            return null;
        }

        // รูปแบบที่พบบ่อย: tag "00" เป็น sub-TLV ที่มี bankId + transRef
        $bankId = null;
        $transRef = null;

        if (isset($top['00']) && strlen($top['00']) >= 6) {
            $sub = $this->parseTlv($top['00']);
            // เดา: 01=sendingBankId (3 หลัก) , 02/03=transRef (ยาวสุด)
            foreach ($sub as $tag => $val) {
                if ($bankId === null && preg_match('/^\d{3}$/', $val)) {
                    $bankId = $val;
                } elseif (strlen($val) >= 10) {
                    $transRef = $transRef ?? $val;
                }
            }
        }

        // fallback: หา 3 หลัก + ก้อนยาวสุด จาก top-level ถ้า sub ไม่เจอ
        if ($bankId === null || $transRef === null) {
            foreach ($top as $val) {
                if ($bankId === null && preg_match('/^\d{3}$/', $val)) {
                    $bankId = $val;
                }
                if (strlen($val) >= 10 && ($transRef === null || strlen($val) > strlen($transRef))) {
                    $transRef = $val;
                }
            }
        }

        // log ไว้ช่วยยืนยัน mapping ตอนทดสอบ (ไม่ใส่ค่าดิบเต็มกัน log บวม)
        Log::info('KBankSlipService: extractSlipRef', [
            'top_tags' => array_keys($top),
            'bankId' => $bankId,
            'transRef_len' => $transRef !== null ? strlen($transRef) : 0,
        ]);

        if (empty($bankId) || empty($transRef)) {
            return null;
        }

        return ['sendingBankId' => $bankId, 'transRef' => $transRef];
    }

    /**
     * ถอด EMVCo TLV (tag 2 หลัก + length 2 หลัก + value) → map tag => value
     *   ปลอดภัย: กัน length เกินความยาวจริง / loop ไม่รู้จบ
     *
     * @return array<string, string>
     */
    protected function parseTlv(string $s): array
    {
        $out = [];
        $i = 0;
        $n = strlen($s);

        while ($i + 4 <= $n) {
            $tag = substr($s, $i, 2);
            $len = substr($s, $i + 2, 2);

            if (! ctype_digit($len)) {
                break; // ไม่ใช่ TLV ที่ถูกต้อง
            }

            $len = (int) $len;
            $valStart = $i + 4;

            if ($valStart + $len > $n) {
                break; // length เกินความยาวจริง
            }

            $out[$tag] = substr($s, $valStart, $len);
            $i = $valStart + $len;
        }

        return $out;
    }

    // ================================================================
    // 🧹 normalize → รูปแบบเดียวกับ SlipOkService::normalize()
    // ================================================================

    /**
     * แปลง response ดิบของ KBank → รูปแบบมาตรฐาน (shape เดียวกับ SlipOK เป๊ะ)
     *   เพื่อให้เสียบเข้า SlipOkService::evaluateForReading() ได้เลยตอน wire Phase 2
     *
     * @return array{ok: bool, http: int, error_code: int|null, message: string,
     *   transRef: string|null, amount: float|null, receiver_account: string|null,
     *   receiver_name: string|null, receiving_bank: string|null, sending_bank: string|null,
     *   sender_name: string|null, trans_timestamp: string|null, raw: array}
     */
    protected function normalize(int $http, array $json, ?array $raw): array
    {
        $data = $json['data'] ?? [];
        if (! is_array($data)) {
            $data = [];
        }

        // statusCode "0000" = สำเร็จ (ยืนยันกับเอกสารจริง — เผื่อรูปแบบต่าง เก็บ raw ไว้)
        $statusCode = $json['statusCode'] ?? ($json['code'] ?? null);
        $message = (string) ($json['statusMessage'] ?? ($json['message'] ?? ($json['error_description'] ?? '')));

        $transRef = $data['transRef'] ?? ($json['transRef'] ?? null);
        $ok = $http === 200
            && ! empty($transRef)
            && ($statusCode === null || in_array((string) $statusCode, ['0000', '00', 'success'], true));

        // แปลง transDate + transTime → ISO timestamp (ถ้ามี)
        $transTs = null;
        $tDate = $data['transDate'] ?? null;
        $tTime = $data['transTime'] ?? null;
        if (! empty($tDate)) {
            $transTs = trim($tDate.' '.($tTime ?? ''));
        }

        return [
            'ok' => (bool) $ok,
            'http' => $http,
            'error_code' => is_numeric($statusCode) ? (int) $statusCode : null,
            'message' => $message,
            'transRef' => $transRef,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'receiver_account' => $data['receiver']['account']['value']
                ?? ($data['receiver']['proxy']['value'] ?? null),
            'receiver_name' => $data['receiver']['displayName'] ?? ($data['receiver']['name'] ?? null),
            'receiving_bank' => $data['receivingBank'] ?? null,
            'sending_bank' => $data['sendingBank'] ?? null,
            'sender_name' => $data['sender']['displayName'] ?? ($data['sender']['name'] ?? null),
            'trans_timestamp' => $transTs,
            'raw' => $raw ?? $json,
        ];
    }
}
