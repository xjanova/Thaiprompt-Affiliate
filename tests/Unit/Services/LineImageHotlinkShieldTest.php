<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\LineFortuneService;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🛡️ เกราะกัน Hotlink Protection ของ Cloudflare สำหรับรูปที่ส่งเข้า LINE (2026-09-19)
 *
 * ที่มา (เคสจริง): เจ้าของแจ้ง "รูปไพ่ และอื่นๆ ใน LINE ไม่ขึ้นอีกแล้ว" แล้วตามด้วย
 * เบาะแสสองข้อที่ชี้ตัวการได้ทันที:
 *   • "รูปในแอพ LINE OA Business เห็น แต่บนเว็บไม่เห็น"
 *     ⇒ แอพดึงรูปโดยไม่มี Referer (200) · เว็บ OA ดึงผ่านเบราว์เซอร์ที่แนบ Referer (403)
 *   • "ส่วนรูปไพ่ของ 39 บนเว็บเห็นนะ"
 *     ⇒ ไพ่ 39 เป็น .webp ซึ่ง Cloudflare ไม่กิน ส่วน Celtic 99 วิ่งผ่านตัวแปลง
 *       WebP→JPEG (2026-07-25) เลยกลายเป็น .jpg แล้วโดนบล็อก
 * พิสูจน์ด้วย curl: .jpg + Referer = 403 (error code 1011) · .webp + Referer = 200
 *
 * เทสต์ชุดนี้ล็อก:
 *   1. jpg/png/gif ที่เราโฮสต์เอง → แปลงเป็น .webp (รอดด่าน)
 *   2. .webp ปล่อยผ่าน ไม่แปลงซ้ำ · เรียกซ้ำได้ผลเดิม (idempotent)
 *   3. URL ภายนอก / สวิตช์ปิด / ไฟล์ไม่มีจริง → คืน URL เดิมเสมอ (ห้ามทำให้แย่กว่าเดิม)
 *   4. query string เดิม (?v={mtime} ของการ์ดแพคเกจ) ต้องไม่หาย
 *   5. ตัวทาใน pushMessage/replyMessage แตะเฉพาะ field ที่เป็นรูปจริง
 *      ⚠️ ห้ามแตะ originalContentUrl ของ video/audio — แปลงแล้วไฟล์พัง
 *
 * ไม่แตะ DB (ยัด settings เปล่าเข้า constructor) · ต้องมี GD ที่เขียน WebP ได้
 */
class LineImageHotlinkShieldTest extends TestCase
{
    private LineFortuneService $service;

    /** โฟลเดอร์ชั่วคราวใต้ storage/app/public สำหรับรูปทดสอบ */
    private string $sandbox = 'hotlink-shield-test';

    /**
     * รายชื่อไฟล์ใน line-webp/ ก่อนเทสต์เริ่ม
     *
     * เก็บ snapshot แทนการเทียบ mtime — cache ของจริงตั้งใจให้อยู่ถาวร
     * ลบผิดใบ = ลูกค้าคนถัดไปต้องรอ GD แปลงใหม่
     *
     * @var array<string>
     */
    private array $cacheBefore = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD เขียน WebP ไม่ได้บนเครื่องนี้');
        }

        config(['line_media.webp_shield' => true]);
        config(['app.url' => 'https://main.thaiprompt.online']);

        $this->service = new LineFortuneService(new FortuneTellingSetting);

        File::ensureDirectoryExists(storage_path('app/public/'.$this->sandbox));
        $this->cacheBefore = File::glob(storage_path('app/public/line-webp/*.webp')) ?: [];
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/public/'.$this->sandbox));

        // ลบเฉพาะไฟล์ที่ "เทสต์นี้" ทำให้เกิดขึ้น
        foreach (File::glob(storage_path('app/public/line-webp/*.webp')) ?: [] as $file) {
            if (! in_array($file, $this->cacheBefore, true)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    /** สร้างรูปจริงในแซนด์บ็อกซ์ แล้วคืน URL แบบที่โค้ดจริงใช้ */
    private function makeImage(string $name, string $format = 'png'): string
    {
        $image = imagecreatetruecolor(8, 8);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 20, 30));

        $path = storage_path('app/public/'.$this->sandbox.'/'.$name);
        match ($format) {
            'png' => imagepng($image, $path),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path, 90),
            default => imagejpeg($image, $path, 90),
        };
        imagedestroy($image);

        return 'https://main.thaiprompt.online/storage/'.$this->sandbox.'/'.$name;
    }

    /** 1. jpg/png/gif ที่เราโฮสต์เอง ต้องกลายเป็น .webp */
    public function test_เปลี่ยนรูปที่โดน_hotlink_เป็น_webp(): void
    {
        foreach ([['card.png', 'png'], ['qr.jpg', 'jpg'], ['old.gif', 'gif']] as [$name, $format]) {
            $url = $this->makeImage($name, $format);
            $safe = $this->service->lineSafeImageUrl($url);

            $this->assertStringContainsString('/storage/line-webp/', $safe, "{$name} ต้องถูกแปลง");
            $this->assertStringEndsWith('.webp', $safe);
            $this->assertNotSame($url, $safe);
        }
    }

    /** 2. .webp รอดด่านอยู่แล้ว → ปล่อยผ่าน และเรียกซ้ำได้ผลเดิม */
    public function test_webp_ปล่อยผ่านและเรียกซ้ำได้ผลเดิม(): void
    {
        $url = $this->makeImage('already.webp', 'webp');

        $first = $this->service->lineSafeImageUrl($url);
        $this->assertSame($url, $first, '.webp ต้องไม่ถูกแตะ');

        // ทาซ้ำ (pushMessage เรียกทับของที่ helper ทาไว้แล้ว) ต้องได้ผลเดิม
        $png = $this->makeImage('twice.png', 'png');
        $once = $this->service->lineSafeImageUrl($png);
        $this->assertSame($once, $this->service->lineSafeImageUrl($once));
    }

    /** 3. ทุกทางที่แปลงไม่ได้ ต้องคืน URL เดิม — ห้ามทำให้แย่กว่าเดิม */
    public function test_คืน_url_เดิมเมื่อแปลงไม่ได้(): void
    {
        // รูปของโดเมนอื่น — ไม่ใช่ของเรา ห้ามแตะ
        $external = 'https://cdn.example.com/promo/banner.jpg';
        $this->assertSame($external, $this->service->lineSafeImageUrl($external));

        // ไฟล์ไม่มีอยู่จริง
        $missing = 'https://main.thaiprompt.online/storage/'.$this->sandbox.'/ghost.png';
        $this->assertSame($missing, $this->service->lineSafeImageUrl($missing));

        // กัน path traversal — ชี้ออกนอก public roots
        $escape = 'https://main.thaiprompt.online/storage/../../.env.png';
        $this->assertSame($escape, $this->service->lineSafeImageUrl($escape));

        // สวิตช์ปิด (Cloudflare แก้ต้นเหตุแล้ว)
        $real = $this->makeImage('switch.png', 'png');
        config(['line_media.webp_shield' => false]);
        $this->assertSame($real, $this->service->lineSafeImageUrl($real));
    }

    /** 4. ?v={mtime} ของการ์ดแพคเกจต้องติดไปด้วย (กลไก cache-bust เดิมห้ามพัง) */
    public function test_คง_query_string_เดิมไว้(): void
    {
        $url = $this->makeImage('pkg.jpg', 'jpg').'?v=1758240000';
        $safe = $this->service->lineSafeImageUrl($url);

        $this->assertStringContainsString('/storage/line-webp/', $safe);
        $this->assertStringEndsWith('?v=1758240000', $safe);
    }

    /** 5. ตัวทาต้องแตะเฉพาะ field ที่เป็นรูป — video/audio ห้ามโดน */
    public function test_ตัวทาแตะเฉพาะ_field_ที่เป็นรูป(): void
    {
        $png = $this->makeImage('hero.png', 'png');
        $video = 'https://main.thaiprompt.online/storage/'.$this->sandbox.'/clip.mp4';
        $audio = 'https://main.thaiprompt.online/storage/'.$this->sandbox.'/voice.m4a';

        $messages = [
            ['type' => 'image', 'originalContentUrl' => $png, 'previewImageUrl' => $png],
            ['type' => 'video', 'originalContentUrl' => $video, 'previewImageUrl' => $png],
            ['type' => 'audio', 'originalContentUrl' => $audio, 'duration' => 60000],
            [
                'type' => 'flex',
                'contents' => [
                    'type' => 'bubble',
                    'hero' => ['type' => 'image', 'url' => $png],
                    'body' => [
                        'type' => 'box',
                        'contents' => [
                            ['type' => 'text', 'text' => 'ดูดวงไพ่ยิปซี'],
                            ['type' => 'button', 'action' => ['type' => 'uri', 'uri' => $png]],
                        ],
                    ],
                ],
            ],
        ];

        $shield = new ReflectionMethod(LineFortuneService::class, 'shieldLineImageUrls');
        $shield->setAccessible(true);
        $out = $shield->invoke($this->service, $messages, 0);

        // รูปจริง → ทาแล้วทุกช่อง
        $this->assertStringContainsString('/storage/line-webp/', $out[0]['originalContentUrl']);
        $this->assertStringContainsString('/storage/line-webp/', $out[0]['previewImageUrl']);
        $this->assertStringContainsString('/storage/line-webp/', $out[1]['previewImageUrl']);
        $this->assertStringContainsString('/storage/line-webp/', $out[3]['contents']['hero']['url']);

        // ไฟล์ที่ไม่ใช่รูป → ห้ามแตะ (แปลงแล้วลูกค้าเปิดไม่ได้)
        $this->assertSame($video, $out[1]['originalContentUrl'], 'วิดีโอห้ามโดนแปลง');
        $this->assertSame($audio, $out[2]['originalContentUrl'], 'เสียงห้ามโดนแปลง');

        // ลิงก์ปลายทางของปุ่มไม่ใช่รูปที่ LINE โหลด — ห้ามเปลี่ยน
        $this->assertSame($png, $out[3]['contents']['body']['contents'][1]['action']['uri']);

        // ข้อความยังอยู่ครบ
        $this->assertSame('ดูดวงไพ่ยิปซี', $out[3]['contents']['body']['contents'][0]['text']);
    }
}
