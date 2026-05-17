<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 🎨 Payment Banner Service (2026-05-17)
 *
 * Composite ภาพ "banner ธนาคาร + Dynamic PromptPay QR + ยอดเงิน + bill ref" → 1 ภาพ
 *
 * จุดประสงค์:
 *   - หลบ FB detection (QR เปลือย → FB จัด classify เป็น payment)
 *   - ดู professional + ฝัง QR ใน design ที่ดูเหมือน promotional graphic
 *   - Dynamic QR ทุกครั้ง (มียอด+ทศนิยม) → SMS checker จับคู่
 *
 * ลำดับ template:
 *   1. Admin upload (settings.payment_banner_template) → ใช้ตามนั้น
 *   2. ระบบ generate default (programmatic ด้วย GD) → cache 30 วัน
 *
 * คำสั่งใช้:
 *   $banner = app(PaymentBannerService::class)->generateCompositeBanner(
 *       amount: 39.07,
 *       billRef: 'FTU-260517-X1234',
 *       qrPayload: $emvPayload,
 *       bankName: 'กสิกรไทย',
 *       accountNumber: '123-4-56789-0',
 *   );
 *   // → return URL ของภาพ composite หรือ null ถ้าสร้างไม่ได้
 */
class PaymentBannerService
{
    protected FortuneTellingSetting $settings;

    /** @var string banner output directory (storage/app/public) */
    protected string $outputDir = 'qrcodes/fortune-banners';

    /** @var string default banner storage location */
    protected string $defaultBannerPath = 'fortune/payment-banner-default.png';

    /** Banner dimensions (px) — 2x ของเดิม เพื่อความชัด */
    protected int $bannerWidth = 1200;
    protected int $bannerHeight = 1600;

    /** Font paths (TTF — รองรับ UTF-8 + Thai)
     *
     * 🎯 (2026-05-17 v2) Production DejaVuSans.ttf อ่านไม่ได้ — debug result:
     *   - NotoSansThai-Bold.ttf → bbox return ปกติ ✅
     *   - DejaVuSans.ttf → bbox = false ("Could not read font") ❌
     *   → ใช้ Noto Sans Thai ทั้งคู่ (รองรับ Latin chars + Thai)
     */
    protected function thaiFont(): string
    {
        return resource_path('fonts/NotoSansThai-Bold.ttf');
    }

    protected function latinFont(): string
    {
        // ใช้ Noto Sans Thai เพราะ DejaVu เสีย — Noto รองรับ Latin/digits ปกติ
        return resource_path('fonts/NotoSansThai-Bold.ttf');
    }

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * Composite banner + QR + text → save PNG → return public URL
     *
     * @param  float  $amount  ยอดเงิน (มีทศนิยม เช่น 39.07)
     * @param  string  $billRef  bill reference (FTU-...)
     * @param  string  $qrPayload  EMVCo PromptPay payload (จาก PromptPayProvider::buildPromptPayPayload)
     * @param  string|null  $bankName  ชื่อธนาคาร (optional — แสดงใน banner)
     * @param  string|null  $accountNumber  เลขบัญชี (optional)
     * @return string|null  public URL หรือ null ถ้า fail
     */
    public function generateCompositeBanner(
        float $amount,
        string $billRef,
        string $qrPayload,
        ?string $bankName = null,
        ?string $accountNumber = null,
    ): ?string {
        if (! function_exists('imagecreatetruecolor')) {
            Log::warning('PaymentBanner: GD ไม่ได้ติดตั้ง — fallback QR เปลือย');

            return null;
        }

        if (! class_exists(\BaconQrCode\Encoder\Encoder::class)) {
            Log::warning('PaymentBanner: BaconQrCode ไม่ได้ติดตั้ง — fallback');

            return null;
        }

        try {
            // 1. Load หรือ generate banner template
            $templatePath = $this->getOrCreateBannerTemplate();
            if (! $templatePath || ! file_exists($templatePath)) {
                Log::warning('PaymentBanner: ไม่มี template — fallback');

                return null;
            }

            // 2. Load banner image
            $banner = $this->loadImage($templatePath);
            if (! $banner) {
                Log::warning('PaymentBanner: load banner ไม่ได้');

                return null;
            }

            $bannerW = imagesx($banner);
            $bannerH = imagesy($banner);

            // 3. Generate QR image (in-memory)
            $qrSize = (int) ($this->settings->payment_banner_qr_size ?? 400);
            $qrX = (int) ($this->settings->payment_banner_qr_x ?? 100);
            $qrY = (int) ($this->settings->payment_banner_qr_y ?? 150);

            // ถ้า admin ไม่ตั้งตำแหน่ง → คำนวณ default = กลาง banner
            if ($qrX <= 0 || $qrY <= 0) {
                $qrX = (int) (($bannerW - $qrSize) / 2);
                $qrY = (int) (($bannerH - $qrSize) / 2) - 50; // ขยับขึ้นเล็กน้อยให้มีที่ใส่ text
            }

            $qrImg = $this->createQrImage($qrPayload, $qrSize);
            if (! $qrImg) {
                imagedestroy($banner);
                Log::warning('PaymentBanner: สร้าง QR image ไม่ได้');

                return null;
            }

            // 4. Composite QR onto banner
            imagecopy($banner, $qrImg, $qrX, $qrY, 0, 0, $qrSize, $qrSize);
            imagedestroy($qrImg);

            // 5. Render text (ยอด + bill ref + bank info)
            // 🛡️ (2026-05-17 v3) +80 padding ใต้ QR — กัน text บัง QR (imagettftext y = baseline)
            //   เคสจริง: font size 56 → text กินขึ้นไป ~50px จาก baseline → ทับ QR ถ้า startY = qrEnd
            $textStartY = $qrY + $qrSize + 80;
            $this->renderText($banner, $amount, $billRef, $bankName, $accountNumber, $textStartY);

            // 6. Save PNG
            $filename = 'banner-'.substr(md5($billRef.$amount.microtime(true)), 0, 12).'.png';
            $relPath = $this->outputDir.'/'.$filename;
            $fullPath = storage_path('app/public/'.$relPath);

            $dir = dirname($fullPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            imagepng($banner, $fullPath, 7); // compression level 7 (smaller file)
            imagedestroy($banner);

            // 7. Ensure storage symlink
            $this->ensureStorageSymlink();

            $publicUrl = asset('storage/'.$relPath);

            Log::info('PaymentBanner: composite banner สำเร็จ', [
                'amount' => $amount,
                'bill_ref' => $billRef,
                'url' => $publicUrl,
            ]);

            return $publicUrl;
        } catch (\Throwable $e) {
            Log::error('PaymentBanner: composite ล้มเหลว', [
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return null;
        }
    }

    /**
     * ดึง path ของ banner template
     * - 1. admin upload (settings.payment_banner_template)
     * - 2. default ที่ระบบ generate
     */
    protected function getOrCreateBannerTemplate(): ?string
    {
        // 1. Admin upload
        $customPath = $this->settings->payment_banner_template;
        if (! empty($customPath)) {
            $fullPath = storage_path('app/public/'.ltrim($customPath, '/'));
            if (file_exists($fullPath)) {
                return $fullPath;
            }
            Log::warning('PaymentBanner: admin template หาย → fallback default', [
                'path' => $customPath,
            ]);
        }

        // 2. Default — generate ถ้ายังไม่มี (cache 30 วัน)
        return $this->getOrGenerateDefaultBanner();
    }

    /**
     * Generate default banner programmatic (ครั้งเดียว) + cache
     */
    public function getOrGenerateDefaultBanner(): ?string
    {
        $fullPath = storage_path('app/public/'.$this->defaultBannerPath);

        // ถ้ามีไฟล์อยู่แล้ว → return
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        // Generate ใหม่
        try {
            $dir = dirname($fullPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $img = $this->createDefaultBannerImage();
            if (! $img) {
                return null;
            }

            imagepng($img, $fullPath, 7);
            imagedestroy($img);

            Log::info('PaymentBanner: generate default banner สำเร็จ', [
                'path' => $fullPath,
            ]);

            return $fullPath;
        } catch (\Throwable $e) {
            Log::error('PaymentBanner: generate default ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🎨 สร้าง default banner image (mystical theme — สีม่วงเข้ม + ดาว)
     *
     * Layout:
     *   ┌────────────────────────────┐
     *   │  🌙 หมอจันทรา               │  ← header
     *   │  PromptPay Payment         │
     *   │                            │
     *   │   [    QR slot 400x400  ]  │  ← QR วาดทับตรงนี้
     *   │                            │
     *   │  ✨ มหัศจรรย์ดวงดาว         │  ← text + bill render ตรงนี้
     *   └────────────────────────────┘
     *
     * @return \GdImage|null
     */
    protected function createDefaultBannerImage()
    {
        $W = $this->bannerWidth;     // 1200
        $H = $this->bannerHeight;    // 1600

        $img = imagecreatetruecolor($W, $H);

        // 🎨 Mystical Purple Gradient palette
        $gold = imagecolorallocate($img, 0xFF, 0xD7, 0x00);     // gold
        $white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
        $silver = imagecolorallocate($img, 0xD0, 0xD0, 0xE8);   // silver-blue
        $qrBg = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);

        // Smooth vertical gradient: dark blue → deep purple → magenta hint
        for ($y = 0; $y < $H; $y++) {
            $ratio = $y / $H;
            // 3-stop gradient
            if ($ratio < 0.5) {
                $t = $ratio * 2;
                $r = (int) (0x1A + $t * (0x3A - 0x1A));
                $g = (int) (0x1A + $t * (0x0F - 0x1A));
                $b = (int) (0x3E + $t * (0x6B - 0x3E));
            } else {
                $t = ($ratio - 0.5) * 2;
                $r = (int) (0x3A + $t * (0x5C - 0x3A));
                $g = (int) (0x0F + $t * (0x12 - 0x0F));
                $b = (int) (0x6B + $t * (0x8E - 0x6B));
            }
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $W, $y, $color);
        }

        // ✨ Decorative stars + sparkles
        mt_srand(42);
        for ($i = 0; $i < 200; $i++) {
            $x = mt_rand(0, $W);
            $y = mt_rand(0, $H);
            $size = mt_rand(1, 4);
            $brightness = mt_rand(160, 255);
            $star = imagecolorallocate($img, $brightness, $brightness, $brightness);
            imagefilledellipse($img, $x, $y, $size, $size, $star);
        }
        // Large sparkles (4-point star shape)
        for ($i = 0; $i < 12; $i++) {
            $x = mt_rand(40, $W - 40);
            $y = mt_rand(40, $H - 40);
            $len = mt_rand(8, 16);
            imageline($img, $x - $len, $y, $x + $len, $y, $gold);
            imageline($img, $x, $y - $len, $x, $y + $len, $gold);
        }

        // Top + Bottom gold borders
        imagefilledrectangle($img, 0, 0, $W, 8, $gold);
        imagefilledrectangle($img, 0, $H - 8, $W, $H, $gold);

        // QR slot (กลาง banner — สูง)
        $qrSize = (int) ($this->settings->payment_banner_qr_size ?? 800);
        $qrX = (int) (($W - $qrSize) / 2);
        $qrY = (int) (($H - $qrSize) / 2) - 100;
        $pad = 40;

        // White box behind QR
        imagefilledrectangle(
            $img,
            $qrX - $pad,
            $qrY - $pad,
            $qrX + $qrSize + $pad,
            $qrY + $qrSize + $pad,
            $qrBg
        );

        // Gold border 6px thick รอบ QR
        for ($i = 0; $i < 6; $i++) {
            imagerectangle(
                $img,
                $qrX - $pad - $i - 1,
                $qrY - $pad - $i - 1,
                $qrX + $qrSize + $pad + $i,
                $qrY + $qrSize + $pad + $i,
                $gold
            );
        }

        // 🌙 Header text — ใช้ TTF font Thai (รองรับทั้งไทย + อังกฤษ)
        $thaiFont = $this->thaiFont();
        $latinFont = $this->latinFont();
        $titleY = 110;

        if (file_exists($thaiFont)) {
            // หัวเรื่องไทย
            $this->drawCenteredText($img, '🌙 หมอจันทรา', $thaiFont, 56, $titleY, $gold, $W);
            $this->drawCenteredText($img, 'PromptPay Payment', $latinFont, 32, $titleY + 70, $silver, $W);
        } else {
            // Fallback: built-in font
            $h1 = 'Mae Mor Janta';
            $h2 = 'PromptPay Payment';
            imagestring($img, 5, (int) (($W - imagefontwidth(5) * strlen($h1)) / 2), $titleY, $h1, $gold);
            imagestring($img, 4, (int) (($W - imagefontwidth(4) * strlen($h2)) / 2), $titleY + 30, $h2, $silver);
        }

        // Footer placeholder — จะถูก overwrite โดย renderText() ตอน composite จริง
        $footerY = $qrY + $qrSize + $pad + 60;
        if (file_exists($thaiFont)) {
            $this->drawCenteredText($img, 'สแกน QR เพื่อชำระเงิน (ฝังยอดในนั้น)', $thaiFont, 28, $footerY, $silver, $W);
        } else {
            $ft = 'Scan QR to pay';
            imagestring($img, 3, (int) (($W - imagefontwidth(3) * strlen($ft)) / 2), $footerY, $ft, $silver);
        }

        return $img;
    }

    /**
     * สร้าง QR image จาก EMVCo payload (in-memory GdImage)
     *
     * @param  string  $payload  EMVCo PromptPay payload
     * @param  int  $size  ขนาด QR (px)
     * @return \GdImage|null
     */
    protected function createQrImage(string $payload, int $size)
    {
        $qrCode = \BaconQrCode\Encoder\Encoder::encode(
            $payload,
            \BaconQrCode\Common\ErrorCorrectionLevel::H(),
            \BaconQrCode\Encoder\Encoder::DEFAULT_BYTE_MODE_ECODING
        );

        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();

        $moduleSize = max(1, (int) floor($size / ($matrixSize + 4)));
        $margin = $moduleSize * 2;
        $realSize = ($moduleSize * $matrixSize) + ($margin * 2);

        $img = imagecreatetruecolor($realSize, $realSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $img,
                        $margin + ($x * $moduleSize),
                        $margin + ($y * $moduleSize),
                        $margin + (($x + 1) * $moduleSize) - 1,
                        $margin + (($y + 1) * $moduleSize) - 1,
                        $black
                    );
                }
            }
        }

        // Resize ให้ตรง $size (ถ้าจำเป็น)
        if ($realSize !== $size) {
            $resized = imagecreatetruecolor($size, $size);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $size, $size, $realSize, $realSize);
            imagedestroy($img);

            return $resized;
        }

        return $img;
    }

    /**
     * Render text บน banner (ยอดเงิน + bill ref + bank info)
     *
     * 🎯 (2026-05-17) ใช้ TTF + imagettftext แทน built-in font
     *   เดิม: imagestring() ไม่รองรับ UTF-8 → text ไทย "เพี้ยน"
     *   ใหม่: imagettftext() + NotoSansThai → render ภาษาไทย/อังกฤษได้ชัดเจน
     */
    protected function renderText(
        $banner,
        float $amount,
        string $billRef,
        ?string $bankName,
        ?string $accountNumber,
        int $startY,
    ): void {
        $W = imagesx($banner);
        $gold = imagecolorallocate($banner, 0xFF, 0xD7, 0x00);
        $white = imagecolorallocate($banner, 0xFF, 0xFF, 0xFF);
        $green = imagecolorallocate($banner, 0x6A, 0xE6, 0x8A);

        // 🛡️ (2026-05-17 v4) Bottom-anchor positions — ปลอดภัยทุกขนาด banner
        //   เดิม: render จากใต้ QR (top-down) → text หลุดล่าง ถ้า banner สั้น
        //   ใหม่: anchor จาก bottom — "ธนาคาร" ห่างขอบล่าง 30px เสมอ
        //   + blackOut ครอบเต็มพื้นที่ text → ทับ "Scan QR to pay" placeholder
        $H = imagesy($banner);
        $bottomMargin = 30;

        $bankY = $H - $bottomMargin - 8;        // baseline ของ ธนาคาร (size 28)
        $billY = $bankY - 55;                    // Bill (size 28) — ห่าง 55px
        $amountY = $billY - 60;                  // ฿ ยอด (size 56) — ห่าง 60px
        $blackOutTop = $amountY - 70;            // blackOut เริ่มที่นี่ (ครอบหัว "฿")

        // ถ้า blackOut ทับ QR → ขยับลง (สถานการณ์ banner สั้นมาก)
        $qrBottomY = $startY; // (caller ส่ง $startY = qrY + qrSize + 80)
        if ($blackOutTop < $qrBottomY - 80) {
            $blackOutTop = $qrBottomY - 80;
        }

        $blackOut = imagecolorallocate($banner, 0x2A, 0x10, 0x60);
        imagefilledrectangle($banner, 30, $blackOutTop, $W - 30, $H - 8, $blackOut);

        $thaiFont = $this->thaiFont();
        $latinFont = $this->latinFont();
        $hasFont = file_exists($thaiFont) && function_exists('imagettftext');

        if ($hasFont) {
            // ยอดเงิน — ตัวใหญ่ + สีทอง (size 56)
            $this->drawCenteredText($banner, '฿ '.number_format($amount, 2), $latinFont, 56, $amountY, $gold, $W);

            // Bill ref — ตัวกลาง สีขาว (size 28)
            $this->drawCenteredText($banner, 'Bill: '.$billRef, $latinFont, 28, $billY, $white, $W);

            // Bank info — Thai font (size 26)
            if ($bankName && $accountNumber) {
                $this->drawCenteredText($banner, "{$bankName}  {$accountNumber}", $thaiFont, 26, $bankY, $green, $W);
            }
        } else {
            // Fallback: built-in font (ไม่รองรับไทย)
            $amountStr = '฿ '.number_format($amount, 2);
            imagestring($banner, 5, (int) (($W - imagefontwidth(5) * strlen($amountStr)) / 2), $startY + 5, $amountStr, $gold);
            $billStr = 'Bill: '.$billRef;
            imagestring($banner, 3, (int) (($W - imagefontwidth(3) * strlen($billStr)) / 2), $startY + 50, $billStr, $white);
        }
    }

    /**
     * 🎨 Helper: วาด text กลางแนวนอน ด้วย TTF font
     *
     * @param  \GdImage  $img
     * @param  string  $text
     * @param  string  $fontPath  Full path ของ TTF font
     * @param  int  $size  font size (px)
     * @param  int  $y  Y coordinate (baseline)
     * @param  int  $color  GD color resource
     * @param  int  $canvasW  width of canvas (สำหรับ center calculation)
     */
    protected function drawCenteredText($img, string $text, string $fontPath, int $size, int $y, int $color, int $canvasW): void
    {
        if (! function_exists('imagettftext') || ! file_exists($fontPath) || ! is_readable($fontPath)) {
            return;
        }

        try {
            // คำนวณความกว้างของ text — suppress warnings (FreeType บางเวอร์ชันมี edge case)
            $bbox = @imagettfbbox($size, 0, $fontPath, $text);

            if (is_array($bbox) && isset($bbox[0], $bbox[2])) {
                $textW = abs($bbox[2] - $bbox[0]);
            } else {
                // Fallback: approximate width (Thai+English mixed)
                //   ค่าเฉลี่ย: 1 char ≈ 0.55 × size px
                $textW = (int) (mb_strlen($text) * $size * 0.55);
            }

            $x = (int) (($canvasW - $textW) / 2);

            @imagettftext($img, $size, 0, $x, $y, $color, $fontPath, $text);
        } catch (\Throwable $e) {
            \Log::debug('PaymentBanner: drawCenteredText fail (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Load image (PNG/JPG) → GdImage
     *
     * @return \GdImage|null
     */
    protected function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        return match ($info['mime']) {
            'image/png' => @imagecreatefrompng($path),
            'image/jpeg' => @imagecreatefromjpeg($path),
            default => null,
        };
    }

    /**
     * Ensure public/storage symlink exists (สำหรับ asset() helper)
     */
    protected function ensureStorageSymlink(): void
    {
        $symlinkPath = public_path('storage');
        if (! file_exists($symlinkPath)) {
            try {
                \Artisan::call('storage:link');
            } catch (\Throwable $e) {
                Log::warning('PaymentBanner: storage:link ล้มเหลว', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
