<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceAffiliateLink;
use App\Models\MarketplaceLinkClick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ตัวกลางเปลี่ยนเส้นทางลิงก์ affiliate (/go/{shortCode})
 *
 * ทำไมต้องมี:
 * Lazada ไม่ยอมรับ subId/trackingId (ทดสอบแล้ว 6 พารามิเตอร์ ได้ tracking token เดียวกันหมด)
 * รายงาน conversion ที่ Lazada ส่งกลับมาจึงบอกไม่ได้ว่า "ลูกค้าคนไหนของเราเป็นคนพาไปซื้อ"
 * → เราต้องบันทึกการคลิกด้วยตัวเอง ก่อนส่งลูกค้าออกไป
 *   click log นี้คือหลักฐานชิ้นเดียวที่ผูก "คำสั่งซื้อบน Lazada" กลับมาหา "ผู้แนะนำ" ได้
 *
 * ⚠️ endpoint นี้เปิดสาธารณะ ไม่ต้องล็อกอิน → ต้องกัน open redirect อย่างเข้มงวด
 */
class AffiliateRedirectController extends Controller
{
    /**
     * โดเมนปลายทางที่อนุญาตให้ redirect ออกไปได้เท่านั้น
     *
     * ลิงก์ tracking ของ Lazada คือ s.lazada.co.th (และโดเมนย่อยอื่นของ lazada.co.th)
     */
    protected const ALLOWED_HOST = 'lazada.co.th';

    /**
     * บันทึกการคลิก แล้วส่งลูกค้าออกไปยังลิงก์ affiliate ปลายทาง
     *
     * @param  Request  $request  คำขอปัจจุบัน
     * @param  string  $shortCode  รหัสสั้นของลิงก์ (marketplace_affiliate_links.short_code)
     *
     * @example
     * GET /go/aB3xK9mQ2p  →  302  https://s.lazada.co.th/s.XXXX
     */
    public function go(Request $request, string $shortCode): RedirectResponse
    {
        // ── หาลิงก์ที่ยังใช้งานได้ (is_active + ยังไม่หมดอายุ) ──
        $link = MarketplaceAffiliateLink::query()
            ->active()
            ->where('short_code', $shortCode)
            ->first();

        if (! $link) {
            abort(404);
        }

        // 🔒 redirect ไปได้เฉพาะ URL ที่เก็บอยู่ในแถวของเราเท่านั้น
        //    ห้ามหยิบอะไรจาก request มาต่อท้าย/แทนที่เด็ดขาด (open redirect)
        $destination = $this->safeDestination($link->affiliate_url);

        if ($destination === null) {
            // URL ในฐานข้อมูลผิดรูป/ไม่ใช่โดเมนที่อนุญาต — ถือว่าลิงก์นี้ใช้ไม่ได้
            Log::warning('affiliate redirect: ปลายทางไม่ผ่านการตรวจสอบ', [
                'link_id' => $link->id,
                'short_code' => $link->short_code,
            ]);

            abort(404);
        }

        // ── บันทึกคลิก — ห้ามให้ล้มเหลวแล้วขวางทางลูกค้า ──
        $this->recordClick($request, $link);

        // no-store: กันเบราว์เซอร์/proxy แคช 302 ไว้ จนคลิกครั้งถัดไปไม่ถูกนับ
        return redirect()->away($destination, 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache');
    }

    /**
     * ตรวจว่า URL ปลายทางปลอดภัยพอจะ redirect ออกไปไหม
     *
     * เงื่อนไขครบทุกข้อจึงผ่าน:
     *  1. ไม่มีอักขระควบคุม (กัน header injection / CRLF)
     *  2. scheme เป็น http หรือ https เท่านั้น (กัน javascript:, data:, file:)
     *  3. host เป็น lazada.co.th หรือโดเมนย่อยของมันเท่านั้น
     *
     * ⚠️ ข้อ 3 ต้องเทียบแบบมีจุดคั่น — str_ends_with('evil-lazada.co.th', 'lazada.co.th')
     *    คืน true ซึ่งเปิดช่องให้โดเมนปลอมผ่านได้
     *
     * @param  string|null  $url  URL ที่เก็บไว้ในฐานข้อมูล
     * @return string|null URL ที่ผ่านการตรวจแล้ว หรือ null ถ้าไม่ผ่าน
     */
    protected function safeDestination(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        // 1) อักขระควบคุม (\r \n \t \0 ฯลฯ) ห้ามมีเด็ดขาด
        //    รวม backslash ด้วย! — PHP กับเบราว์เซอร์ "อ่าน URL ไม่ตรงกัน" ตรงตัวนี้
        //    parse_url('https://evil.com\@lazada.co.th/') → PHP เห็น host=lazada.co.th (ผ่านด่าน)
        //    แต่เบราว์เซอร์ตามมาตรฐาน WHATWG แปลง \ เป็น / ก่อน → วิ่งไป evil.com จริง
        //    ช่องว่าง/เครื่องหมายคำพูดก็กันไว้ด้วย กัน header injection และ parser differential อื่นๆ
        if (preg_match('/[\x00-\x1F\x7F\\\\ "\'<>]/', $url)) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        // 2) scheme
        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        // 3) host — ต้องเป็น lazada.co.th พอดี หรือลงท้ายด้วย ".lazada.co.th"
        //    parse_url แยก userinfo ออกให้แล้ว เช่น https://lazada.co.th@evil.com/
        //    จะได้ host = evil.com → ตกที่นี่ถูกต้อง
        $host = strtolower(rtrim($parts['host'], '.'));

        if ($host !== self::ALLOWED_HOST && ! str_ends_with($host, '.'.self::ALLOWED_HOST)) {
            return null;
        }

        return $url;
    }

    /**
     * บันทึกการคลิก 1 ครั้ง + เพิ่มตัวนับบนลิงก์
     *
     * ⚠️ ทุกอย่างในนี้ห้ามโยน exception ออกไป — ถ้าบันทึกไม่ได้ ลูกค้าต้องยังไปต่อได้อยู่ดี
     *    (เสียสถิติ 1 คลิก ดีกว่าเสียลูกค้าทั้งคน)
     *
     * @param  Request  $request  คำขอปัจจุบัน
     * @param  MarketplaceAffiliateLink  $link  ลิงก์ที่ถูกคลิก
     */
    protected function recordClick(Request $request, MarketplaceAffiliateLink $link): void
    {
        try {
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;

            // คลิกแรกของ session นี้บนลิงก์นี้ = unique
            // ไม่มี session id → นับเป็น unique (ประเมินสูงไว้ ดีกว่าทิ้งข้อมูล)
            $isUnique = true;
            if (! empty($sessionId)) {
                $isUnique = ! MarketplaceLinkClick::query()
                    ->where('affiliate_link_id', $link->id)
                    ->where('session_id', $sessionId)
                    ->exists();
            }

            MarketplaceLinkClick::create([
                'affiliate_link_id' => $link->id,
                'ip_address' => $request->ip(),
                // คอลัมน์เป็น string — ตัดกัน user agent ยาวเกินจน insert ล้ม
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'referer_url' => mb_substr((string) $request->headers->get('referer', ''), 0, 500) ?: null,
                'session_id' => $sessionId,
                'is_unique_click' => $isUnique,
                'converted' => false,
                'clicked_at' => now(),
            ]);

            // increment() = UPDATE ... SET click_count = click_count + 1 (atomic)
            // ห้ามอ่านมาบวกแล้วเซฟ — คลิกพร้อมกันจะนับหาย
            $link->incrementClicks($isUnique);
        } catch (\Throwable $e) {
            Log::warning('affiliate redirect: บันทึกคลิกไม่สำเร็จ', [
                'link_id' => $link->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
