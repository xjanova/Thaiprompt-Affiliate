<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LazadaPortalSearch — ค้นสินค้าด้วย "คำ" จากพอร์ทัล affiliate
 *
 * 🚨 ทำไมต้องมี ทั้งที่มี API ทางการอยู่แล้ว (พิสูจน์บนพร็อด 2026-08-23)
 *   API ทางการ `/marketing/product/feed` **ไม่มีการค้นด้วยคำ** และของในฟีดแพงเกินใช้:
 *     - สแกน 1,180 ชิ้น → อยู่ในช่วง 25-990฿ แค่ 4 ชิ้น
 *     - ราคากลาง 192,857฿ · หมวด beauty/pets/books ไม่มีของต่ำกว่า 990฿ เลยสักชิ้น
 *     - กวาดคลัง 7,631 ชิ้นหาของสายมูที่ยังไม่ติดป้าย → เจอ 25 ชิ้น ซึ่งเกือบทั้งหมด
 *       เป็นของหลอก (เลนส์ถ่ายพระเครื่อง · ไม้กอล์ฟ Zodiac · ถ้วยชา Zodiac)
 *   ⇒ **ของสายมูราคา 25-1,859฿ มีอยู่ที่เดียวคือพอร์ทัล**
 *
 * ⚠️ auth = session cookie ของพอร์ทัล ไม่ใช่ app_key/sign
 *   คุกกี้เป็น httpOnly (JS ในหน้าอ่านไม่ได้) แต่ **เซิร์ฟเวอร์ replay ด้วย header Cookie ได้**
 *   ถ้าเจ้าของก็อปมาให้ครั้งเดียว
 *
 * 🔒 คุกกี้นี้ = session ของบัญชี affiliate (เข้าถึงเงินได้) ⇒
 *   - เก็บเข้ารหัสใน `additional_credentials` เท่านั้น **ห้ามลง .env / marketplace_settings**
 *   - ห้าม log ค่าคุกกี้ แม้บางส่วน
 *   - `allow_redirects=false` — พอร์ทัลตอบ 302 ไปหน้า login เมื่อคุกกี้ตาย
 *     ถ้าตาม redirect จะได้ HTML หน้า login มาแล้วแปลผิดเป็น "ไม่มีสินค้า"
 *
 * คืนสถานะ 3 แบบ ไม่ใช่ bool — เพราะ "ค้นไม่เจอ" กับ "คุกกี้ตาย" ต้องทำคนละอย่าง
 *   ok            → ใช้ได้ (items อาจว่างจริงก็ได้)
 *   auth_dead     → คุกกี้หมดอายุ ต้องขอใหม่จากเจ้าของ
 *   shape_changed → พอร์ทัลเปลี่ยนโครงสร้าง ต้องมีคนมาดู
 */
class LazadaPortalSearch
{
    /** โฮสต์พอร์ทัล — hardcode ไว้ ห้ามรับจากภายนอก (กัน SSRF) */
    private const HOST = 'https://adsense.lazada.co.th';

    private const PATH = '/newOffer/searchSkuOffer.json';

    /** คีย์ที่เก็บคุกกี้ใน additional_credentials */
    public const CRED_KEY = 'portal_cookie';

    /** เวลาบันทึกว่าคุกกี้ใช้ได้ล่าสุดเมื่อไหร่ — ใช้วัดอายุจริง ไม่ต้องเดา TTL */
    public const CRED_LAST_OK = 'portal_cookie_last_ok_at';

    private const TIMEOUT = 15;

    public function __construct(private MarketplaceAccount $account) {}

    /**
     * ค้นสินค้าด้วยคำ
     *
     * @param  string  $keyword  คำค้น เช่น "บ่วงนาคบาศ"
     * @param  int  $page  หน้า (เริ่ม 1)
     * @param  int  $pageSize  กี่ชิ้นต่อหน้า
     * @return array{status:string,items:array<int,array<string,mixed>>,error:?string}
     *
     * @example
     * $r = $portal->search('บ่วงนาคบาศ');
     * // ['status' => 'ok', 'items' => [['itemId' => '123', 'title' => '…', 'commission' => 12.0, …]], …]
     */
    public function search(string $keyword, int $page = 1, int $pageSize = 50): array
    {
        $cookie = $this->cookie();
        if ($cookie === '') {
            return ['status' => 'auth_dead', 'items' => [], 'error' => 'ยังไม่ได้ตั้งคุกกี้พอร์ทัล'];
        }

        try {
            $resp = Http::withHeaders([
                'Cookie' => $cookie,
                'Accept' => 'application/json, text/plain, */*',
                'Referer' => self::HOST.'/index.htm',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
                ->withOptions(['allow_redirects' => false])
                ->timeout(self::TIMEOUT)
                ->get(self::HOST.self::PATH, [
                    'pageNo' => max(1, $page),
                    'pageSize' => max(1, min(100, $pageSize)),
                    'keywordType' => 0,
                    'keyword' => $keyword,
                    'sortField' => 'sales_volume',
                    'sortOrder' => 'desc',
                ]);
        } catch (\Throwable $e) {
            // ⚠️ ห้าม log getMessage() ดิบ — Guzzle ต่อท้าย URI เต็มซึ่งอาจมีข้อมูลอ่อนไหว
            Log::warning('LazadaPortalSearch: ยิงไม่ถึง', ['keyword' => $keyword, 'class' => get_class($e)]);

            return ['status' => 'shape_changed', 'items' => [], 'error' => 'ยิงพอร์ทัลไม่สำเร็จ'];
        }

        // 302 = เด้งไปหน้า login ⇒ คุกกี้ตาย (นี่คือเหตุผลที่ปิด allow_redirects)
        if ($resp->status() >= 300 && $resp->status() < 400) {
            return ['status' => 'auth_dead', 'items' => [], 'error' => 'คุกกี้หมดอายุ (พอร์ทัลเด้งไปหน้า login)'];
        }

        if (! $resp->successful()) {
            return ['status' => 'shape_changed', 'items' => [], 'error' => 'พอร์ทัลตอบ HTTP '.$resp->status()];
        }

        // ได้ HTML แทน JSON = โดนหน้า login / หน้ากันบอท
        $contentType = strtolower((string) $resp->header('Content-Type'));
        if ($contentType !== '' && ! str_contains($contentType, 'json')) {
            return ['status' => 'auth_dead', 'items' => [], 'error' => 'พอร์ทัลตอบ HTML ไม่ใช่ JSON (คุกกี้ตาย/โดนกันบอท)'];
        }

        $data = $resp->json();
        if (! is_array($data)) {
            return ['status' => 'shape_changed', 'items' => [], 'error' => 'ตอบกลับไม่ใช่ JSON'];
        }

        // พอร์ทัลบอกเองว่าไม่ได้ล็อกอิน
        if (isset($data['success']) && $data['success'] === false) {
            $code = (string) ($data['resultCode'] ?? '');
            if (stripos($code, 'login') !== false || stripos($code, 'auth') !== false || stripos($code, 'session') !== false) {
                return ['status' => 'auth_dead', 'items' => [], 'error' => 'พอร์ทัลปฏิเสธ: '.$code];
            }
        }

        $rows = $this->extractRows($data);
        if ($rows === null) {
            return ['status' => 'shape_changed', 'items' => [], 'error' => 'ไม่พบ reportItem ในผลลัพธ์'];
        }

        $this->stampOk();

        return ['status' => 'ok', 'items' => array_values(array_filter(array_map(
            fn ($r) => $this->normalize($r), $rows
        ))), 'error' => null];
    }

    /**
     * หาแถวสินค้าในผลลัพธ์
     *
     * คืน null = หา path ไม่เจอเลย (โครงสร้างเปลี่ยน) — ต่างจาก [] ที่แปลว่า "ค้นแล้วไม่มีของ"
     *
     * @param  array<string,mixed>  $data
     * @return array<int,array<string,mixed>>|null
     */
    private function extractRows(array $data): ?array
    {
        foreach (['data.reportItem', 'data.list', 'data.items', 'result.reportItem', 'reportItem'] as $path) {
            $v = data_get($data, $path);
            if (is_array($v)) {
                return $v; // ว่างก็ถือว่าเจอ path แล้ว = ค้นไม่พบของจริง
            }
        }

        return null;
    }

    /**
     * แปลง 1 แถวจากพอร์ทัล → โครงที่ตัวนำเข้าใช้
     *
     * `formatCommission` มาเป็นสตริง "10%" ⇒ ต้องถอดเป็นตัวเลขเอง
     * ประโยชน์คือกรองค่าคอมได้ **ตั้งแต่ขั้นค้น** ไม่ต้องเสียคอล enrich กับของที่ตกเกณฑ์
     *
     * @param  array<string,mixed>  $r
     * @return array<string,mixed>|null
     */
    private function normalize(array $r): ?array
    {
        $itemId = (string) ($r['itemId'] ?? $r['productId'] ?? '');
        if ($itemId === '') {
            return null;
        }

        $commission = 0.0;
        if (! empty($r['formatCommission']) && preg_match('/([\d.]+)/', (string) $r['formatCommission'], $m)) {
            $commission = (float) $m[1];
        } elseif (isset($r['sellerCommissionRate'])) {
            $commission = (float) $r['sellerCommissionRate'];
        }

        return [
            'itemId' => $itemId,
            'title' => (string) ($r['title'] ?? ''),
            'price' => (float) preg_replace('/[^0-9.]/', '', (string) ($r['discountPrice'] ?? $r['originalPrice'] ?? '0')),
            'commission' => $commission,
            'soldCount' => (int) ($r['soldCount'] ?? 0),
            'canGetLink' => (bool) ($r['canGetLink'] ?? true),
        ];
    }

    /**
     * คุกกี้ที่เก็บไว้ (ถอดรหัสแล้ว)
     */
    private function cookie(): string
    {
        $stored = (string) $this->account->cred(self::CRED_KEY, '');
        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            // ค่าเก่าที่เก็บแบบ plaintext — ยังใช้ได้ ไม่ทิ้ง
            return $stored;
        }
    }

    /**
     * บันทึกว่าคุกกี้ใช้งานได้ล่าสุดเมื่อไหร่
     *
     * ใช้วัด **อายุจริง** ของคุกกี้จากข้อมูล ไม่ใช่ตั้ง TTL เดาเอง
     */
    private function stampOk(): void
    {
        try {
            $creds = (array) ($this->account->additional_credentials ?? []);
            $creds[self::CRED_LAST_OK] = now()->toIso8601String();
            $this->account->update(['additional_credentials' => $creds]);
        } catch (\Throwable $e) {
            Log::debug('LazadaPortalSearch: บันทึกเวลาใช้งานคุกกี้ไม่ได้', ['error' => $e->getMessage()]);
        }
    }
}
