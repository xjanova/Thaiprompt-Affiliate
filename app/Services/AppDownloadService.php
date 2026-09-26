<?php

namespace App\Services;

use App\Models\SiteSetting;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * แจกไฟล์ติดตั้งแอป Thai Prompt APP (Android APK) จากเซิร์ฟเวอร์ของเราเอง
 *
 * ที่เก็บไฟล์: storage/app/public/app-downloads/ (disk "public")
 *   - deploy ไม่ลบ เพราะ git clean ยกเว้น storage/app/public/* (ดู deploy.sh)
 *   - เว็บเสิร์ฟเป็นไฟล์นิ่งผ่าน public/storage → ไม่กิน PHP worker ระหว่างโหลดไฟล์ร้อยเมก
 *     และ Cloudflare แคชไฟล์ .apk ได้เอง
 * latest.json = ตัวชี้ไปไฟล์ล่าสุด เขียนแบบ temp + rename หลังคัดลอก APK เสร็จเสมอ
 *   → คนที่กดโหลดระหว่างกำลังอัปไฟล์ใหม่ ได้ไฟล์เก่าที่ครบ ไม่มีวันได้ไฟล์ครึ่งๆ
 *
 * อัปไฟล์ใหม่:  php artisan app:publish-apk /path/to/app.apk --app-version=3.384.0 --version-code=41
 *
 * @example
 * $apk = app(AppDownloadService::class)->available();
 * // ['file' => 'ThaiPrompt-APP-3.384.0.apk', 'version' => '3.384.0', 'size' => 81234567, ...] หรือ null
 */
class AppDownloadService
{
    /** โฟลเดอร์ใน disk public */
    public const DIR = 'app-downloads';

    private const MANIFEST = 'latest.json';

    /** ชื่อไฟล์ที่ยอมรับ — กันชื่อแปลก/path traversal จาก latest.json ที่ถูกแก้มือ */
    private const FILE_PATTERN = '/^ThaiPrompt-APP-[0-9]+(?:\.[0-9]+){1,3}\.apk$/';

    /** APK จริงของเรา ~70–150 MB — เล็กกว่า 1 MB แปลว่าไฟล์ผิดแน่นอน */
    private const MIN_BYTES = 1_000_000;

    private const MAX_BYTES = 400_000_000;

    /** อ่าน latest.json ครั้งเดียวต่อ request (หน้าแรกเรียกหลายจุด) */
    private ?array $latestCache = null;

    private bool $latestLoaded = false;

    /**
     * APK ล่าสุดบนเซิร์ฟเวอร์ (ไม่สนสวิตช์หลังบ้าน)
     *
     * @return array{file:string,version:string,version_code:int,size:int,sha256:string,published_at:string}|null
     */
    public function latest(): ?array
    {
        if ($this->latestLoaded) {
            return $this->latestCache;
        }
        $this->latestLoaded = true;

        try {
            $disk = Storage::disk('public');
            $manifest = self::DIR.'/'.self::MANIFEST;
            if (! $disk->exists($manifest)) {
                return $this->latestCache = null;
            }

            $meta = json_decode((string) $disk->get($manifest), true);
            $file = is_array($meta) ? ($meta['file'] ?? null) : null;
            if (! is_string($file) || ! preg_match(self::FILE_PATTERN, $file) || ! $disk->exists(self::DIR.'/'.$file)) {
                return $this->latestCache = null;
            }

            return $this->latestCache = [
                'file' => $file,
                'version' => (string) ($meta['version'] ?? ''),
                'version_code' => (int) ($meta['version_code'] ?? 0),
                'size' => (int) ($meta['size'] ?? 0),
                'sha256' => (string) ($meta['sha256'] ?? ''),
                'published_at' => (string) ($meta['published_at'] ?? ''),
            ];
        } catch (\Throwable $e) {
            // ดิสก์มีปัญหา = ถือว่ายังไม่มีไฟล์ (หน้าแรกต้องไม่ล่มเพราะปุ่มโหลดแอป)
            report($e);

            return $this->latestCache = null;
        }
    }

    /**
     * APK ที่เปิดให้โหลดได้จริงตอนนี้ — เคารพสวิตช์หลังบ้าน (ตั้งค่าเว็บไซต์ → ดาวน์โหลดแอป / APK)
     *
     * @return array{file:string,version:string,version_code:int,size:int,sha256:string,published_at:string}|null
     */
    public function available(): ?array
    {
        return $this->apkSwitchOn() ? $this->latest() : null;
    }

    /**
     * ลิงก์ APK นอกเซิร์ฟเวอร์ที่หลังบ้านตั้งไว้ (สำรอง เมื่อยังไม่มีไฟล์บนเซิร์ฟเวอร์) — รับเฉพาะ https
     */
    public function externalUrl(): ?string
    {
        if (! $this->apkSwitchOn()) {
            return null;
        }

        try {
            $url = trim((string) (SiteSetting::getSetting()->app_apk_url ?? ''));
        } catch (\Throwable $e) {
            return null;
        }

        return preg_match('#^https://#i', $url) ? $url : null;
    }

    /**
     * สวิตช์หลังบ้าน "แสดง Section ดาวน์โหลดแอปในหน้า Home" (ค่าเริ่มต้น = เปิด)
     */
    public function sectionOn(): bool
    {
        try {
            return (bool) (SiteSetting::getSetting()->app_download_enabled ?? true);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * ลิงก์ Google Play — ต้องเปิดทั้งส่วนดาวน์โหลดแอปและสวิตช์ Play Store และเป็น https เท่านั้น
     */
    public function playStoreUrl(): ?string
    {
        try {
            $setting = SiteSetting::getSetting();
            if (! (bool) ($setting->app_download_enabled ?? true) || ! (bool) ($setting->app_playstore_enabled ?? false)) {
                return null;
            }
            $url = trim((string) ($setting->app_playstore_url ?? ''));
        } catch (\Throwable $e) {
            return null;
        }

        return preg_match('#^https://#i', $url) ? $url : null;
    }

    /**
     * ลิงก์ปุ่มโหลดแอปแบบเต็ม (ใช้ทำ QR) — ยึด APP_URL ไม่ใช้ host จาก request
     * (แอปเชื่อ X-Forwarded-Host จากทุกที่ → ถ้าใช้ host จาก request คนยิง host สุ่มจะสร้างแคช QR ไม่รู้จบ)
     */
    public function downloadPageUrl(): string
    {
        return $this->baseUrl().'/app/download';
    }

    /**
     * ลิงก์ไฟล์นิ่งของ APK — ต่อท้ายด้วย hash ไฟล์ เพื่อให้ Cloudflare ไม่แจกไฟล์เก่า
     * เมื่ออัปเวอร์ชันเดิมซ้ำ (build ใหม่แต่เลขเวอร์ชันเท่าเดิม) · ยึด APP_URL เหมือน downloadPageUrl()
     */
    public function fileUrl(array $apk): string
    {
        return $this->baseUrl().'/storage/'.self::DIR.'/'.$apk['file'].'?h='.substr($apk['sha256'], 0, 12);
    }

    /**
     * QR (SVG กรมท่าบนขาว) ให้คนที่เปิดเว็บบนคอมสแกนไปโหลดบนมือถือ — แคช 1 วัน
     * ส่ง downloadPageUrl() เข้ามาเท่านั้น (ห้ามส่ง URL ที่มาจาก request — key แคชจะงอกตาม host)
     *
     * @return string|null SVG พร้อมฝังในหน้า (ตัด <?xml ?> ออกแล้ว) หรือ null ถ้าสร้างไม่ได้
     */
    public function qrSvg(string $url): ?string
    {
        try {
            return Cache::remember('app_download_qr:'.md5($url), now()->addDay(), function () use ($url) {
                $renderer = new ImageRenderer(
                    new RendererStyle(168, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(13, 27, 61))),
                    new SvgImageBackEnd
                );

                return trim((string) preg_replace('/^<\?xml[^>]*>\s*/', '', (new Writer($renderer))->writeString($url)));
            });
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * นำ APK ขึ้นเซิร์ฟเวอร์ แล้วชี้ latest.json ไปที่ไฟล์ใหม่
     *
     * @param  string  $sourcePath  ไฟล์ APK บนเครื่องนี้
     * @param  string  $version  เวอร์ชันแอป เช่น 3.384.0
     * @param  int|null  $versionCode  versionCode ของ Android
     * @return array ข้อมูลไฟล์ที่เผยแพร่ (รูปแบบเดียวกับ latest())
     *
     * @throws RuntimeException ไฟล์ไม่ใช่ APK / ขนาดผิด / คัดลอกไม่สำเร็จ
     */
    public function publish(string $sourcePath, string $version, ?int $versionCode = null): array
    {
        if (! preg_match('/^[0-9]+(?:\.[0-9]+){1,3}$/', $version)) {
            throw new RuntimeException('เลขเวอร์ชันต้องเป็นรูปแบบ 3.384.0');
        }
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('ไม่พบไฟล์ APK: '.$sourcePath);
        }

        $size = (int) filesize($sourcePath);
        if ($size < self::MIN_BYTES || $size > self::MAX_BYTES) {
            throw new RuntimeException('ขนาดไฟล์ผิดปกติ ('.number_format($size).' ไบต์) — ไม่ใช่ APK ของแอป');
        }

        $fh = fopen($sourcePath, 'rb');
        $magic = $fh ? fread($fh, 4) : '';
        if ($fh) {
            fclose($fh);
        }
        if ($magic !== "PK\x03\x04") {
            throw new RuntimeException('ไฟล์นี้ไม่ใช่ APK (ไม่ใช่ไฟล์ zip)');
        }

        $sha256 = (string) hash_file('sha256', $sourcePath);
        $file = 'ThaiPrompt-APP-'.$version.'.apk';
        $previous = $this->latest();

        $disk = Storage::disk('public');
        $disk->makeDirectory(self::DIR);
        $dir = $disk->path(self::DIR);

        // คัดลอกเป็นไฟล์ชั่วคราวก่อน ตรวจ hash แล้วค่อย rename เข้าที่ (rename ในโฟลเดอร์เดียวกัน = atomic)
        $tmp = $dir.DIRECTORY_SEPARATOR.'.'.$file.'.'.bin2hex(random_bytes(4)).'.part';
        if (! @copy($sourcePath, $tmp)) {
            throw new RuntimeException('คัดลอกไฟล์ไม่สำเร็จ (พื้นที่ดิสก์หรือสิทธิ์โฟลเดอร์)');
        }
        if (! hash_equals($sha256, (string) hash_file('sha256', $tmp))) {
            @unlink($tmp);
            throw new RuntimeException('ไฟล์ที่คัดลอกไม่ครบ (hash ไม่ตรง) — ลองใหม่อีกครั้ง');
        }
        @chmod($tmp, 0644);
        if (! @rename($tmp, $dir.DIRECTORY_SEPARATOR.$file)) {
            @unlink($tmp);
            throw new RuntimeException('ย้ายไฟล์เข้าที่ไม่สำเร็จ');
        }

        $meta = [
            'file' => $file,
            'version' => $version,
            'version_code' => (int) ($versionCode ?? 0),
            'size' => $size,
            'sha256' => $sha256,
            'published_at' => now()->toIso8601String(),
        ];

        $tmpJson = $dir.DIRECTORY_SEPARATOR.'.'.self::MANIFEST.'.'.bin2hex(random_bytes(4)).'.part';
        $json = json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (@file_put_contents($tmpJson, $json) === false || ! @rename($tmpJson, $dir.DIRECTORY_SEPARATOR.self::MANIFEST)) {
            @unlink($tmpJson);
            throw new RuntimeException('บันทึก latest.json ไม่สำเร็จ');
        }

        // เก็บตัวก่อนหน้าไว้ 1 ตัว (คนที่กำลังโหลดค้างอยู่ยังได้ไฟล์ครบ) ที่เหลือลบทิ้ง
        foreach (glob($dir.DIRECTORY_SEPARATOR.'ThaiPrompt-APP-*.apk') ?: [] as $old) {
            $name = basename($old);
            if ($name !== $file && $name !== ($previous['file'] ?? null)) {
                @unlink($old);
            }
        }

        $this->latestCache = $meta;
        $this->latestLoaded = true;

        return $meta;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * สวิตช์หลังบ้าน: เปิดส่วนดาวน์โหลดแอป และเปิดดาวน์โหลด APK (ค่าเริ่มต้นของทั้งคู่ = เปิด)
     */
    private function apkSwitchOn(): bool
    {
        try {
            $setting = SiteSetting::getSetting();

            return (bool) ($setting->app_download_enabled ?? true) && (bool) ($setting->app_apk_enabled ?? true);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
