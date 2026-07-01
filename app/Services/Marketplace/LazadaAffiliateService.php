<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lazada Affiliate (Open API ของ Lazada เอง — ไม่ใช่ Involve Asia)
 *
 * ใช้กับบัญชี program_type = 'affiliate_native'
 *   - app_key    = App Key จาก Lazada Affiliate (adsense.lazada.co.th → Integration → Open API)
 *   - app_secret = App Secret
 *   - access_token (ไม่บังคับ) = user token จากบัญชี affiliate ถ้า endpoint ต้องการ
 *   - additional_credentials.sub_id = tracking / sub-affiliate id (ไม่บังคับ)
 *
 * ทำไมแยกจาก LazadaApiService (Seller): Seller ยิง /seller/get, /products/get ซึ่งเป็น scope ของ
 * "ร้านตัวเอง" — affiliate เป็นคนละ scope (ค้นสินค้าใครก็ได้ + สร้างลิงก์ได้ค่าคอม) endpoint คนละชุด
 *
 * 🔑 ระบบเซ็นลายเซ็น = GOP เดียวกับ Lazada Open Platform (app_key + timestamp + sign_method=sha256
 *    + HMAC-SHA256 ของ path+พารามิเตอร์เรียงคีย์) → reuse ได้ ทำให้ทดสอบคีย์ได้ทันทีที่มี credential
 *
 * ⚠️ สถานะเฟสนี้ = DISCOVERY: ยืนยัน "คีย์+ลายเซ็นถูกต้อง" ได้แล้ว (testConnection)
 *    ส่วน endpoint จริงของ "ค้นสินค้า affiliate" + "สร้างลิงก์ได้ค่าคอม" ยังต้องยืนยันกับ API จริง
 *    (เอกสารเปิดเฉพาะผู้มีแอป affiliate) → ใช้ probe() ยิงจริงบน prod เพื่อค้น endpoint ที่มีอยู่
 *    แล้วค่อยเติม searchProducts()/generatePromotionLink() ตาม spec จริง (ไม่เดา)
 *
 * 🔒 ความปลอดภัย: ไม่ log app_key/app_secret/access_token/sign — log เฉพาะ path + code/message
 */
class LazadaAffiliateService
{
    /** Gateway หลักของ Lazada Open Platform (ไทย) — ใช้ร่วมกับ affiliate API */
    protected string $baseUrl = 'https://api.lazada.co.th/rest';

    /**
     * รายชื่อ endpoint ที่ "อาจจะใช่" สำหรับ affiliate (ค้นสินค้า / สร้างลิงก์)
     * — ใช้เฉพาะใน probe() เพื่อ "ค้นหา" ว่า Lazada เปิด API ตัวไหนให้บัญชีนี้จริง
     * — ไม่ใช่ค่าที่ยืนยันแล้ว (comment ชัดเจนกัน mistake) เมื่อ probe เจอตัวจริงจึงย้ายไปใช้จริง
     *
     * @var array<int,string>
     */
    protected const PROBE_ENDPOINTS = [
        '/marketing/getlink',
        '/marketing/getpromotionlink',
        '/product/link/get',
        '/affiliate/link/generate',
        '/affiliate/product/query',
        '/pmp/product/query',
    ];

    public function __construct(protected MarketplaceAccount $account) {}

    /**
     * สร้างลายเซ็น GOP (เหมือน Lazada Open Platform)
     *
     * @param  string  $path  API path เช่น /marketing/getlink
     * @param  array<string,string>  $params  พารามิเตอร์ทั้งหมดที่จะส่ง (รวม app_key/timestamp/sign_method)
     */
    protected function sign(string $path, array $params): string
    {
        unset($params['sign']);
        ksort($params);

        $stringToSign = $path;
        foreach ($params as $key => $value) {
            $stringToSign .= $key.$value;
        }

        return strtoupper(hash_hmac('sha256', $stringToSign, (string) $this->account->app_secret));
    }

    /**
     * ยิง request ที่เซ็นแล้วไปยัง Lazada gateway
     *
     * @param  array<string,mixed>  $extra  พารามิเตอร์เฉพาะของ endpoint
     * @return array<string,mixed>|null  payload JSON หรือ null ถ้า transport ล้ม
     */
    protected function signedGet(string $path, array $extra = []): ?array
    {
        $params = array_merge($extra, [
            'app_key' => (string) $this->account->app_key,
            'timestamp' => (string) (time() * 1000), // จับ timestamp ครั้งเดียว ใช้ทั้งเซ็นและส่ง
            'sign_method' => 'sha256',
        ]);

        if ($this->account->access_token) {
            $params['access_token'] = (string) $this->account->access_token;
        }

        $params['sign'] = $this->sign($path, $params);

        try {
            $resp = Http::timeout(12)->get($this->baseUrl.$path, $params);
            $data = $resp->json();
        } catch (\Throwable $e) {
            Log::warning('Lazada Affiliate transport error', [
                'account_id' => $this->account->id,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * โค้ด error ที่บ่งชี้ว่า "คีย์/ลายเซ็น/โทเคนผิด" (auth ไม่ผ่าน)
     * — ถ้าเจอโค้ดกลุ่มนี้ = credential มีปัญหา
     * — ถ้าเป็นโค้ดอื่น (เช่น ApiName ไม่มี, พารามิเตอร์ขาด) = ลายเซ็นผ่านแล้ว แค่ endpoint/param ไม่ตรง
     */
    protected function isAuthError(string $code, string $message): bool
    {
        $needle = strtolower($code.' '.$message);
        foreach (['sign', 'app_key', 'appkey', 'api key', 'apikey', 'access_token', 'accesstoken', 'token', 'timestamp', 'illegalaccess', 'authoriz', 'permission'] as $kw) {
            if (str_contains($needle, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ทดสอบการเชื่อมต่อ — ยืนยันว่า "App Key + App Secret ถูกต้องและลายเซ็นผ่าน gateway ของ Lazada"
     *
     * หลักการ: Lazada ตรวจ app_key + sign + timestamp "ก่อน" route ไป API ที่ขอ
     *   → code '0'/ว่าง          = สำเร็จเต็ม (endpoint ทำงานด้วย)
     *   → code ที่ไม่เกี่ยว auth  = ลายเซ็นผ่านแล้ว (คีย์ถูก) แค่ endpoint/param ไม่ตรง = ถือว่า "เชื่อมต่อได้"
     *   → code เกี่ยว sign/appkey = คีย์/ลายเซ็นผิด = ยังไม่ผ่าน
     *
     * @return array{0: bool, 1: string} [ผ่านไหม, ข้อความสำหรับแอดมิน]
     */
    public function testConnection(): array
    {
        $key = (string) ($this->account->app_key ?? '');
        $secret = (string) ($this->account->app_secret ?? '');
        if ($key === '' || $secret === '') {
            return [false, 'ยังไม่ได้กรอก App Key / App Secret ของ Lazada Affiliate — กรอกในหน้าแก้ไขก่อน'];
        }

        $lastMessage = 'ไม่มีการตอบกลับจาก Lazada';

        // web check: ลองไม่เกิน 3 endpoint พอ (การตอบกลับ 1 ครั้งก็ตัดสิน "ลายเซ็นผ่าน" ได้แล้ว)
        // การกวาดครบทุก endpoint ไว้ให้ probe() ผ่าน CLI (ไม่มี web timeout)
        foreach (array_slice(self::PROBE_ENDPOINTS, 0, 3) as $path) {
            $data = $this->signedGet($path);
            if ($data === null) {
                continue; // transport ล้ม — ลอง endpoint ถัดไป
            }

            $code = (string) ($data['code'] ?? '');
            $message = (string) ($data['message'] ?? ($data['type'] ?? ''));

            // สำเร็จเต็ม — endpoint นี้ใช้ได้เลย
            if ($code === '' || $code === '0') {
                return [true, 'เชื่อมต่อ Lazada Affiliate สำเร็จ ✓ คีย์ถูกต้อง และ endpoint '.$path.' ตอบกลับปกติ'];
            }

            // ลายเซ็นผ่าน (คีย์ถูก) แค่ endpoint/param ยังไม่ตรง → ถือว่าเชื่อมต่อได้ พร้อมทำ discovery ต่อ
            if (! $this->isAuthError($code, $message)) {
                return [true, 'เชื่อมต่อสำเร็จ ✓ App Key/Secret ถูกต้อง (ลายเซ็นผ่าน gateway Lazada แล้ว) — ขั้นต่อไปยืนยัน endpoint ค้นสินค้า/สร้างลิงก์'];
            }

            $lastMessage = trim($code.' '.$message);
        }

        Log::warning('Lazada Affiliate testConnection: auth ไม่ผ่าน', [
            'account_id' => $this->account->id,
            'last' => $lastMessage,
        ]);

        return [false, 'Lazada ปฏิเสธคีย์ (ลายเซ็น/App Key ไม่ถูกต้อง): '.mb_substr($lastMessage, 0, 200)];
    }

    /**
     * (discovery) ยิงทุก endpoint ผู้สมัครแล้วรายงานผลดิบ — ใช้ค้นว่า Lazada เปิด API ตัวไหนให้บัญชีนี้
     *
     * รันผ่านคำสั่ง: php artisan lazada:affiliate-probe {accountId}
     * ผลลัพธ์ช่วยให้ยืนยัน endpoint จริงของ "ค้นสินค้า" + "สร้างลิงก์" โดยไม่ต้องเดา
     *
     * @return array<int,array{path:string,code:string,message:string,authOk:bool}>
     */
    public function probe(): array
    {
        $out = [];
        foreach (self::PROBE_ENDPOINTS as $path) {
            $data = $this->signedGet($path);
            if ($data === null) {
                $out[] = ['path' => $path, 'code' => 'TRANSPORT', 'message' => 'ยิงไม่ถึง/timeout', 'authOk' => false];

                continue;
            }
            $code = (string) ($data['code'] ?? '');
            $message = (string) ($data['message'] ?? ($data['type'] ?? ''));
            $out[] = [
                'path' => $path,
                'code' => $code === '' ? '0' : $code,
                'message' => mb_substr($message, 0, 160),
                'authOk' => ($code === '' || $code === '0' || ! $this->isAuthError($code, $message)),
            ];
        }

        return $out;
    }

    /**
     * (scaffold) ค้นสินค้า affiliate — เติม implementation หลัง probe ยืนยัน endpoint จริง
     *
     * @return array<int,array>
     */
    public function searchProducts(string $keyword, ?float $maxPrice = null, int $limit = 20): array
    {
        // TODO(after-probe): map endpoint + พารามิเตอร์จริง → normalize เป็นสินค้า (name/price/image/url)
        return [];
    }

    /**
     * (scaffold) สร้างลิงก์โปรโมชัน (tracking link ที่ได้ค่าคอม) จาก URL ปลายทาง
     *
     * @return string|null  ลิงก์ที่ได้ค่าคอม หรือ null ถ้ายังทำไม่ได้ (caller fallback เป็น URL เดิม)
     */
    public function generatePromotionLink(string $destinationUrl): ?string
    {
        // TODO(after-probe): เรียก endpoint สร้างลิงก์จริง + แนบ sub_id (tracking)
        return null;
    }
}
