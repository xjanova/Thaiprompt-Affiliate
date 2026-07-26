<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Crypt;
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
    protected function signedGet(string $path, array $extra = [], bool $includeAccessToken = true): ?array
    {
        $params = array_merge($extra, [
            'app_key' => (string) $this->account->app_key,
            'timestamp' => (string) (time() * 1000), // จับ timestamp ครั้งเดียว ใช้ทั้งเซ็นและส่ง
            'sign_method' => 'sha256',
        ]);

        // ⚠️ บาง API (เช่น /marketing/product/feed) auth ด้วย userToken param ไม่ใช่ access_token
        //    และบัญชี native มักมี seller access_token เก่าค้าง → ถ้าแนบไปจะ IllegalAccessToken
        //    caller จึงสั่ง includeAccessToken=false ได้ เพื่อไม่ให้ token เก่ามาทำ auth พัง
        if ($includeAccessToken && $this->account->access_token) {
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
     * ดึงฟีดสินค้า affiliate จาก Open API ทางการ — endpoint จริง `/marketing/product/feed` (สถานะ IN USE)
     *
     * ยืนยัน spec จากเอกสารในพอร์ทัล (2026-07-04, อ่านหน้า Open API ด้วย Chrome จริง):
     *   Request (บังคับ): offerType(1=Regular/2=MM/3=DM), userToken, page(เริ่ม 1), limit
     *          (ตัวเลือก): categoryL1, productIds[list], mmCampaignId, dmInviteId
     *   Response fields: productId, productName, pictures, discountPrice, currency, stock, outOfStock,
     *          sellerName, brandName, sales7d, totalCommissionRate/Amount ฯลฯ — ⚠️ ไม่มีลิงก์ + ไม่มี keyword
     *
     * ⚠️ API ไม่มีพารามิเตอร์ค้นด้วยคำ (keyword) → sync ฟีดเข้า index ในบ้านแล้วค้นฝั่งเรา
     * ⚠️ ลิงก์ค่าคอมต้องเรียกแยก (Batch get link by productId) — เติมภายหลัง (generateProductLink)
     *
     * @param  int  $offerType  1=สินค้าปกติ (ใช้ทั่วไป), 2=MM, 3=DM
     * @param  int  $page  หน้า (เริ่มที่ 1)
     * @param  int  $limit  จำนวนต่อหน้า (1-100)
     * @param  int|null  $categoryL1  กรองหมวดระดับ 1 (ไม่ระบุ = ทุกหมวด)
     * @param  array<int,int|string>  $productIds  กรอง product id เจาะจง (ไม่บังคับ)
     * @return array{ok:bool, items:array<int,array>, error:?string, page:int, hasMore:bool, raw:mixed}
     */
    public function getProductFeed(int $offerType = 1, int $page = 1, int $limit = 50, ?int $categoryL1 = null, array $productIds = []): array
    {
        $userToken = $this->resolveUserToken();
        if ($userToken === '') {
            return ['ok' => false, 'items' => [], 'error' => 'ยังไม่มี User Token — กด "Acquire User Token" ในพอร์ทัล Lazada Affiliate → Open API แล้วกรอกในหน้าแก้ไขบัญชี', 'page' => $page, 'hasMore' => false, 'raw' => null];
        }

        $params = [
            'offerType' => (string) $offerType,
            'userToken' => $userToken,
            'page' => (string) max(1, $page),
            'limit' => (string) max(1, min(100, $limit)),
        ];
        if ($categoryL1 !== null && $categoryL1 > 0) {
            $params['categoryL1'] = (string) $categoryL1;
        }
        if (! empty($productIds)) {
            // เอกสารตัวอย่าง productIds = [1,2,3] → ส่งเป็น JSON array string
            $params['productIds'] = json_encode(array_values(array_map('strval', $productIds)));
        }

        // includeAccessToken:false — API นี้ auth ด้วย userToken (แนบ seller access_token เก่า = IllegalAccessToken)
        $data = $this->signedGet('/marketing/product/feed', $params, includeAccessToken: false);
        if ($data === null) {
            return ['ok' => false, 'items' => [], 'error' => 'ยิง API ไม่ถึง/timeout', 'page' => $page, 'hasMore' => false, 'raw' => null];
        }

        $code = (string) ($data['code'] ?? '');
        if ($code !== '' && $code !== '0') {
            return ['ok' => false, 'items' => [], 'error' => trim($code.' '.(string) ($data['message'] ?? $data['type'] ?? '')), 'page' => $page, 'hasMore' => false, 'raw' => $data];
        }

        $rows = $this->extractFeedRows($data);
        $items = array_values(array_filter(array_map(fn ($r) => $this->normalizeFeedItem($r), $rows)));

        return [
            'ok' => true,
            'items' => $items,
            'error' => null,
            'page' => $page,
            'hasMore' => count($rows) >= $limit, // เต็มหน้า = อาจมีต่อ (best-effort จนกว่ายืนยันฟิลด์ total)
            'raw' => $data,
        ];
    }

    /**
     * ถอด User Token (เก็บเข้ารหัสใน additional_credentials) — เผื่อค่าเก่า plaintext = fallback
     */
    protected function resolveUserToken(): string
    {
        $stored = (string) $this->account->cred('user_token', '');
        if ($stored === '') {
            return '';
        }
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            return $stored;
        }
    }

    /**
     * สร้างลิงก์ติดตาม (ได้ค่าคอม) จาก productId — endpoint จริง `/marketing/product/link`
     *
     * ยืนยัน spec (2026-07-04): Request userToken(required) + productId(required) + mmCampaignId?/dmInviteId?
     * ใช้ห่อสินค้าจาก feed ให้คลิกแล้วเราได้ค่าคอม (feed ไม่มีลิงก์มาด้วย)
     *
     * @return string|null ลิงก์ affiliate หรือ null ถ้าไม่สำเร็จ (caller fallback = PDP ปกติ, ไม่ได้ค่าคอม)
     */
    public function getProductLink(string|int $productId): ?string
    {
        $userToken = $this->resolveUserToken();
        if ($userToken === '' || (string) $productId === '') {
            return null;
        }

        $data = $this->signedGet('/marketing/product/link', [
            'userToken' => $userToken,
            'productId' => (string) $productId,
        ], includeAccessToken: false);
        if (! is_array($data)) {
            return null;
        }

        $code = (string) ($data['code'] ?? '');
        if ($code !== '' && $code !== '0') {
            Log::warning('Lazada Affiliate getProductLink ไม่สำเร็จ', [
                'account_id' => $this->account->id,
                'product_id' => (string) $productId,
                'code' => $code,
                'message' => (string) ($data['message'] ?? $data['type'] ?? ''),
            ]);

            return null;
        }

        // ✅ ยืนยันจาก response จริง (2026-07-04): result.data.trackingLink
        //    เผื่อชื่ออื่นไว้กัน Lazada ปรับ schema
        foreach (['result.data.trackingLink', 'result.trackingLink', 'result.data.shortLink', 'result.data.link', 'result.data.clickUrl', 'result.data.url', 'result.clickUrl', 'result.link', 'result.url', 'data.trackingLink', 'data.clickUrl', 'data.url'] as $p) {
            $u = data_get($data, $p);
            if (is_string($u) && str_starts_with($u, 'http')) {
                return $u;
            }
        }
        $r = $data['result'] ?? null;
        if (is_string($r) && str_starts_with($r, 'http')) {
            return $r;
        }

        return null;
    }

    /**
     * (debug) คืน raw response ของ `/marketing/product/link` — ไว้ยืนยันชื่อฟิลด์ url จริง
     * ก่อนล็อก mapping ใน getProductLink()
     *
     * @return array<string,mixed>|null
     */
    public function fetchProductLinkRaw(string|int $productId): ?array
    {
        $userToken = $this->resolveUserToken();
        if ($userToken === '') {
            return null;
        }

        return $this->signedGet('/marketing/product/link', [
            'userToken' => $userToken,
            'productId' => (string) $productId,
        ], includeAccessToken: false);
    }

    /**
     * ดึงรายงาน Conversion (ออเดอร์ที่เกิดจากลิงก์ affiliate ของเรา) — endpoint จริง `/marketing/conversion/report`
     *
     * ✅ ยืนยันแล้วจากการยิงจริง: endpoint มีอยู่จริง ตอบ code=0
     *    envelope = { result: { data: [...], success: true }, code, request_id }
     *    พารามิเตอร์บังคับ: userToken, dateStart (Y-m-d), dateEnd (Y-m-d) | ตัวเลือก: page, limit
     *
     * 🚨 ข้อจำกัดสำคัญ — ช่วงวันที่ต้องอยู่ "ภายในเดือนปฏิทินเดียวกัน" เท่านั้น
     *    (เอกสาร Lazada ระบุ "only support fetch single month data")
     *    → ผู้เรียกต้องหั่นช่วงเวลาเป็นรายเดือนเอง ห้ามส่งช่วงคร่อมเดือน
     *    (ตัวหั่นเดือนอยู่ที่ LazadaConversionSyncService::syncMonth)
     *
     * ⚠️ ชื่อฟิลด์ของ "แต่ละแถว" ยังไม่ยืนยัน — ทุกเดือนที่เคยยิงจริงคืน rows = 0
     *    (ยังไม่มีลูกค้าซื้อผ่านลิงก์เรา) → เมธอดนี้จึงคืน "แถวดิบ" ตามที่ Lazada ส่งมา ไม่ normalize ให้
     *    ห้ามผู้เรียกเชื่อชื่อฟิลด์ใด ๆ ว่ายืนยันแล้ว ต้อง map แบบเผื่อหลายชื่อ + เก็บแถวดิบไว้ตรวจย้อนหลังเสมอ
     *
     * @param  string  $dateStart  วันเริ่ม รูปแบบ Y-m-d
     * @param  string  $dateEnd  วันสิ้นสุด รูปแบบ Y-m-d (ต้องอยู่เดือนเดียวกับ $dateStart)
     * @param  int  $page  หน้า (เริ่มที่ 1)
     * @param  int  $limit  จำนวนต่อหน้า
     * @return array{ok:bool, rows:array<int,array<string,mixed>>, error:?string, raw:mixed}
     */
    public function getConversionReport(string $dateStart, string $dateEnd, int $page = 1, int $limit = 50): array
    {
        $userToken = $this->resolveUserToken();
        if ($userToken === '') {
            return ['ok' => false, 'rows' => [], 'error' => 'ยังไม่มี User Token — กด "Acquire User Token" ในพอร์ทัล Lazada Affiliate → Open API แล้วกรอกในหน้าแก้ไขบัญชี', 'raw' => null];
        }

        // includeAccessToken:false — API ชุด /marketing/* auth ด้วย userToken (แนบ seller access_token เก่า = IllegalAccessToken)
        $data = $this->signedGet('/marketing/conversion/report', [
            'userToken' => $userToken,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'page' => (string) max(1, $page),
            'limit' => (string) max(1, min(500, $limit)),
        ], includeAccessToken: false);

        if ($data === null) {
            return ['ok' => false, 'rows' => [], 'error' => 'ยิง API ไม่ถึง/timeout', 'raw' => null];
        }

        $code = (string) ($data['code'] ?? '');
        if ($code !== '' && $code !== '0') {
            return [
                'ok' => false,
                'rows' => [],
                'error' => trim($code.' '.(string) ($data['message'] ?? $data['type'] ?? '')),
                'raw' => $data,
            ];
        }

        // ✅ ยืนยันจาก response จริง: result.data — path อื่นใส่เผื่อ Lazada ปรับ schema (mirror extractFeedRows)
        $rows = [];
        foreach (['result.data', 'result.list', 'result.records', 'result.conversions', 'result.orders', 'data.data', 'data.list', 'data.records', 'data'] as $path) {
            $v = data_get($data, $path);
            if (is_array($v) && array_is_list($v) && ! empty($v) && is_array($v[0])) {
                $rows = $v;
                break;
            }
        }

        return ['ok' => true, 'rows' => $rows, 'error' => null, 'raw' => $data];
    }

    /**
     * หา "แถวสินค้า" ในผลลัพธ์ feed — เผื่อหลายรูปแบบ path (ยืนยัน exact จาก dump raw ครั้งแรก)
     *
     * @param  array<string,mixed>  $data
     * @return array<int,array<string,mixed>>
     */
    protected function extractFeedRows(array $data): array
    {
        // ✅ ยืนยันจาก response จริง (2026-07-04): envelope = { result: { data: [...] }, code, request_id }
        //    เผื่อ path อื่นไว้กัน Lazada ปรับ schema
        foreach (['result.data', 'result.products', 'result.list', 'result.records', 'data.products', 'data.result', 'data.list', 'data.items', 'data'] as $path) {
            $v = data_get($data, $path);
            if (is_array($v) && array_is_list($v) && ! empty($v) && is_array($v[0])) {
                return $v;
            }
        }

        return [];
    }

    /**
     * แปลง 1 แถวจาก feed → โครงสินค้ากลางของเรา (เก็บ product_id ไว้ขอลิงก์ค่าคอมทีหลัง)
     *
     * @param  array<string,mixed>  $r
     * @return array<string,mixed>|null  null = ข้อมูลไม่พอ (ข้าม)
     */
    protected function normalizeFeedItem(array $r): ?array
    {
        $productId = (string) ($r['productId'] ?? $r['itemId'] ?? '');
        $name = trim((string) ($r['productName'] ?? $r['title'] ?? ''));
        if ($productId === '' || $name === '') {
            return null;
        }

        $pics = $r['pictures'] ?? $r['imageList'] ?? $r['image'] ?? [];
        if (is_string($pics)) {
            $pics = [$pics];
        }
        $pics = is_array($pics) ? array_values(array_filter($pics, fn ($u) => is_string($u) && $u !== '')) : [];

        return [
            'product_id' => $productId,
            'name' => mb_substr($name, 0, 500),
            'image' => $pics[0] ?? '',
            'images' => $pics,
            'price' => (float) preg_replace('/[^0-9.]/', '', (string) ($r['discountPrice'] ?? $r['price'] ?? '0')),
            'currency' => (string) ($r['currency'] ?? 'THB'),
            'brand' => (string) ($r['brandName'] ?? ''),
            'brand_id' => (string) ($r['brandId'] ?? ''),
            'seller' => (string) ($r['sellerName'] ?? ''),
            'seller_id' => (string) ($r['sellerId'] ?? ''),
            'category_l1' => (string) ($r['categoryL1'] ?? ''),
            'sales7d' => (int) ($r['sales7d'] ?? 0),
            'stock' => (int) ($r['stock'] ?? 0),
            'out_of_stock' => (bool) ($r['outOfStock'] ?? false),
            'commission_rate' => (float) ($r['totalCommissionRate'] ?? 0),      // เศษส่วน 0.05 = 5%
            'commission_amount' => (float) ($r['totalCommissionAmount'] ?? 0),
            'hyper_commission_rate' => (float) ($r['hyperCommissionRate'] ?? 0),
            'cps_commission_rate' => (float) ($r['cpsCommissionRate'] ?? 0),
            'cps_commission_amount' => (float) ($r['cpsCommissionAmount'] ?? 0),
            'bonus_offer_flag' => (bool) ($r['bonusOfferFlag'] ?? false),
            'raw' => $r,
        ];
    }

    /**
     * (scaffold) ค้นสินค้า affiliate — สำหรับ affiliate API ที่ค้นด้วย keyword ได้ (เช่น Involve Asia)
     * Lazada native ไม่มี keyword-search → ใช้ getProductFeed() + ค้นฝั่งเราแทน
     *
     * @return array<int,array>
     */
    public function searchProducts(string $keyword, ?float $maxPrice = null, int $limit = 20): array
    {
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
