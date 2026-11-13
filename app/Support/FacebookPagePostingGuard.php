<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * 🛑 ด่านกันโพสต์ลงเพจ Facebook ที่เจ้าของสั่งหยุด
 *
 * เจ้าของสั่ง (2026-11-13): "หยุดโพสต์คอนเทนต์ทั้งหมดไปยังเพจจันทราเมืองเลยก่อน"
 * (= สาขา 5 "จันทราพยากรณ์ จ.เลย - 5" ที่เคยรับสำเนาคอนเทนต์จากเพจหลัก)
 *
 * ทำงาน 2 ชั้น:
 *   1. เป็น middleware กลางของ Http client (ลงทะเบียนใน AppServiceProvider)
 *      ทุก POST ที่ "สร้างโพสต์ใหม่บนเพจ" (/feed /photos /videos ...) ถูกเช็คก่อนออกจากเครื่อง
 *      รวมรอบ retry และ worker ที่ถือ object เพจ/ตั้งค่าเก่าค้างในหน่วยความจำ
 *   2. service เรียก assertAllowed() เองตั้งแต่ต้นทาง ก่อนสร้างหรือลบคอนเทนต์เดิม (กรณี --force)
 *
 * ธงเก็บที่ fortune_pages.settings_override.facebook_posting_paused — ไม่ต้อง migration
 * และ FortuneTellingSetting::applyPageOverrides() ข้ามคีย์ที่ไม่ใช่คอลัมน์จริง จึงไม่กระทบบอทแชท
 * ตั้ง/ปลดด้วยคำสั่ง `php artisan fortune:facebook-posting-pause`
 *
 * ⚠️ ไม่แตะแชท คอมเมนต์ การลบโพสต์ และเพจอื่น — กันเฉพาะการสร้างโพสต์ใหม่ของเพจที่ถูกหยุด
 * ⚠️ อ่าน DB ไม่ได้ = โยน exception ไม่ปล่อยผ่าน — ด่านนิรภัยต้องปิดไว้ก่อนเมื่อไม่แน่ใจ
 * ⚠️ รู้จักเฉพาะปลายทางที่ระบุไอดีเพจเป็นตัวเลข — โค้ดใหม่ที่โพสต์ผ่าน /me, batch
 *    หรือ HTTP client ตัวอื่นที่ไม่ใช่ของ Laravel ต้องเรียก assertAllowed() เอง
 */
final class FacebookPagePostingGuard
{
    /** คีย์ใน settings_override ที่บอกว่าเพจนี้ถูกสั่งหยุดโพสต์ */
    public const PAUSED_KEY = 'facebook_posting_paused';

    /** คีย์เก็บค่าสวิตช์เดิมก่อนถูกหยุด (ไว้ดูตอนเปิดกลับ) */
    public const SNAPSHOT_KEY = 'facebook_posting_pause_snapshot';

    /** คีย์เก็บประวัติการหยุดครั้งล่าสุด หลังปลดแล้ว */
    public const LAST_PAUSE_KEY = 'facebook_posting_last_pause';

    /** ปลายทางของ Graph ที่สร้างโพสต์ใหม่บนเพจ */
    private const PUBLISH_EDGES = 'feed|photos|videos|video_reels|photo_stories|video_stories';

    /** โฮสต์ของ Graph ที่รับโพสต์ (วิดีโอใช้โฮสต์แยก) */
    private const GRAPH_HOSTS = ['graph.facebook.com', 'graph-video.facebook.com'];

    /**
     * middleware ของ Http client — เช็คทุกคำขอก่อนส่งออก (รวมรอบ retry)
     *
     * @param  RequestInterface  $request  คำขอที่กำลังจะส่ง
     * @return RequestInterface คำขอเดิม ไม่แก้ไขอะไร
     *
     * @throws RuntimeException เมื่อเป็นการสร้างโพสต์ลงเพจที่ถูกสั่งหยุด
     */
    public function __invoke(RequestInterface $request): RequestInterface
    {
        if (strtoupper($request->getMethod()) !== 'POST'
            || ! in_array(strtolower($request->getUri()->getHost()), self::GRAPH_HOSTS, true)) {
            return $request;
        }

        // ตัดเลขเวอร์ชันหน้า path ออกก่อน เช่น v27.0/123/feed → 123/feed
        $path = trim($request->getUri()->getPath(), '/');
        $path = (string) preg_replace('~^v\d+\.\d+/~', '', $path);

        if (preg_match('~^(\d+)/(?:'.self::PUBLISH_EDGES.')/?$~', $path, $matches)) {
            $this->assertAllowed($matches[1]);
        }

        return $request;
    }

    /**
     * เพจนี้ถูกสั่งหยุดโพสต์อยู่ไหม
     *
     * อ่านจาก DB (ตัวเขียน) ทุกครั้ง — ห้ามเชื่อ object เพจ/ตั้งค่าที่ worker ถือค้างไว้
     *
     * @param  string  $pageId  ไอดีเพจ Facebook (ตัวเลข)
     * @return bool true = ถูกหยุดอยู่
     */
    public function isPaused(string $pageId): bool
    {
        // โค้ดขึ้นก่อน migration ได้เสมอ — ตารางยังไม่มี = ยังไม่มีระบบสาขา = ไม่มีอะไรให้หยุด
        if ($pageId === '' || ! Schema::hasTable('fortune_pages')) {
            return false;
        }

        $overrides = DB::table('fortune_pages')->useWritePdo()
            ->where('platform', 'facebook')
            ->where('external_page_id', $pageId)
            ->pluck('settings_override');

        foreach ($overrides as $raw) {
            $settings = is_array($raw) ? $raw : json_decode((string) $raw, true);

            if (is_array($settings) && filter_var($settings[self::PAUSED_KEY] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ยืนยันว่าโพสต์ลงเพจนี้ได้ — ไม่ได้ให้โยน exception ทันที
     *
     * @param  string  $pageId  ไอดีเพจ Facebook (ตัวเลข)
     *
     * @throws RuntimeException เมื่อเพจถูกสั่งหยุดโพสต์
     *
     * @tip ข้อความลงท้ายด้วย "(Facebook publishing paused)" ไว้ให้ค้นใน log ได้ง่าย
     */
    public function assertAllowed(string $pageId): void
    {
        if ($this->isPaused($pageId)) {
            throw new RuntimeException(
                "เพจ Facebook {$pageId} ถูกสั่งหยุดโพสต์ไว้ — ปลดด้วยคำสั่ง fortune:facebook-posting-pause --resume ก่อนจึงจะโพสต์ได้ (Facebook publishing paused)"
            );
        }
    }
}
