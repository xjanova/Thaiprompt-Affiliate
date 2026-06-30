<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Involve Asia — เครือข่าย affiliate สำหรับ Lazada (ไทย)
 *
 * ใช้กับบัญชี program_type = 'affiliate':
 *   - app_key    = Involve Asia API Key   (เข้ารหัส Crypt ใน MarketplaceAccount)
 *   - app_secret = Involve Asia API Secret (เข้ารหัส Crypt)
 *   - additional_credentials.sub_id / offer_id / aff_id = ค่าติดตาม + deeplink
 *
 * Auth: POST /api/authenticate ด้วย key+secret → คืน token (อายุ ~2 ชม.) → cache ไว้ใช้ซ้ำ
 *
 * ⚠️ ขอบเขตเฟสนี้: authenticate + testConnection + generateDeeplink (best-effort)
 *    ส่วน "ค้นสินค้า datafeed" ต้องดู endpoint จริงหลังมีบัญชี+API key (api.involve.asia)
 *    → ทำเป็น scaffold (searchProducts) ไว้ ต่อยอดเมื่อยืนยัน spec จริงได้
 *
 * 🔒 ความปลอดภัย: ไม่ log key/secret/token เต็ม — log เฉพาะสถานะ/ข้อความ
 */
class InvolveAsiaService
{
    /** Base URL ของ Involve Asia Publisher API */
    protected string $baseUrl = 'https://api.involve.asia/api';

    public function __construct(protected MarketplaceAccount $account) {}

    /**
     * ขอ API token (อายุ ~2 ชม. ฝั่ง Involve) — cache 110 นาที กันขอซ้ำถี่
     *
     * @param  bool  $fresh  true = ข้าม cache ขอใหม่
     * @return string|null token หรือ null ถ้าไม่สำเร็จ/ไม่มีคีย์
     */
    public function authenticate(bool $fresh = false): ?string
    {
        $key = (string) ($this->account->app_key ?? '');
        $secret = (string) ($this->account->app_secret ?? '');
        if ($key === '' || $secret === '') {
            return null;
        }

        $cacheKey = 'involve_asia_token:'.$this->account->id;
        if (! $fresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        try {
            $resp = Http::asForm()->timeout(20)->post($this->baseUrl.'/authenticate', [
                'key' => $key,
                'secret' => $secret,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Involve Asia authenticate transport error', ['account_id' => $this->account->id, 'error' => $e->getMessage()]);

            return null;
        }

        $data = $resp->json();
        $token = is_array($data) ? ($data['data']['token'] ?? null) : null;

        if (! is_string($token) || $token === '') {
            Log::warning('Involve Asia authenticate ไม่สำเร็จ', [
                'account_id' => $this->account->id,
                'http' => $resp->status(),
                'message' => is_array($data) ? ($data['message'] ?? null) : null,
            ]);

            return null;
        }

        Cache::put($cacheKey, $token, now()->addMinutes(110));

        return $token;
    }

    /**
     * ทดสอบการเชื่อมต่อ — สำเร็จ = authenticate ได้ token
     *
     * @return array{0: bool, 1: string} [ผ่านไหม, ข้อความสำหรับแอดมิน]
     */
    public function testConnection(): array
    {
        $key = (string) ($this->account->app_key ?? '');
        $secret = (string) ($this->account->app_secret ?? '');
        if ($key === '' || $secret === '') {
            return [false, 'ยังไม่ได้กรอก API Key / Secret ของ Involve Asia — กรอกในหน้าแก้ไขก่อน'];
        }

        try {
            $resp = Http::asForm()->timeout(20)->post($this->baseUrl.'/authenticate', [
                'key' => $key,
                'secret' => $secret,
            ]);
            $data = $resp->json();
            $token = is_array($data) ? ($data['data']['token'] ?? null) : null;

            if (is_string($token) && $token !== '') {
                Cache::put('involve_asia_token:'.$this->account->id, $token, now()->addMinutes(110));

                return [true, 'เชื่อมต่อ Involve Asia สำเร็จ ✓ ได้ API token แล้ว'];
            }

            $msg = is_array($data) ? ((string) ($data['message'] ?? 'คีย์ไม่ถูกต้อง')) : 'ไม่มีการตอบกลับ';

            return [false, 'Involve Asia ปฏิเสธคีย์: '.mb_substr($msg, 0, 200)];
        } catch (\Throwable $e) {
            Log::warning('Involve Asia testConnection error', ['account_id' => $this->account->id, 'error' => $e->getMessage()]);

            return [false, 'เชื่อมต่อ Involve Asia ไม่สำเร็จ (ระบบขัดข้องหรือคีย์ผิด) ลองใหม่อีกครั้ง'];
        }
    }

    /**
     * สร้าง deeplink affiliate จาก URL ปลายทาง (เช่น หน้าสินค้า Lazada)
     *
     * รูปแบบ Involve Asia deeplink (invol.co) ต้องมี offer_id (ของ Lazada บน Involve) + aff_id (publisher)
     * เก็บใน additional_credentials. ถ้าไม่ครบ → คืน null ให้ caller fallback เป็น URL เดิม (ไม่พังหน้าร้าน)
     *
     * ⚠️ offer_id/aff_id ที่ถูกต้องดูได้จาก dashboard Involve ของผู้ใช้ — ต้องยืนยันกับลิงก์จริง 1 ครั้ง
     *
     * @param  string  $destinationUrl  URL ปลายทาง (https ของ lazada)
     * @return string|null deeplink หรือ null ถ้าข้อมูลไม่พอ
     */
    public function generateDeeplink(string $destinationUrl): ?string
    {
        $destinationUrl = trim($destinationUrl);
        $offerId = (string) $this->account->cred('offer_id', '');
        $affId = (string) $this->account->cred('aff_id', '');

        if ($offerId === '' || $affId === '' || ! preg_match('#^https?://#i', $destinationUrl)) {
            return null;
        }

        $params = [
            'offer_id' => $offerId,
            'aff_id' => $affId,
            'source' => 'deeplink',
            'url' => $destinationUrl,
        ];
        $subId = (string) $this->account->cred('sub_id', '');
        if ($subId !== '') {
            $params['sub1'] = $subId;
        }

        return 'https://invol.co/aff_m?'.http_build_query($params);
    }

    /**
     * (scaffold) ค้นสินค้าจาก Involve Asia datafeed
     *
     * ⚠️ ยังไม่เปิดใช้ — endpoint/รูปแบบ datafeed ต้องยืนยันกับ API จริงหลังมีบัญชี+API key
     *    (api.involve.asia เปิดเอกสารเฉพาะผู้มีคีย์). เมื่อยืนยัน spec แล้วค่อยเติม implementation
     *    + นำเข้าผลลัพธ์เป็นสินค้าให้ Eve ค้นเจอ + แทรก deeplink
     *
     * @return array<int,array>
     */
    public function searchProducts(string $keyword, ?float $maxPrice = null, int $limit = 10): array
    {
        return [];
    }
}
