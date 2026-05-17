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

    /** Banner dimensions (px) */
    protected int $bannerWidth = 600;
    protected int $bannerHeight = 800;

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
            $this->renderText($banner, $amount, $billRef, $bankName, $accountNumber, $qrY + $qrSize);

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
        $W = $this->bannerWidth;
        $H = $this->bannerHeight;

        $img = imagecreatetruecolor($W, $H);

        // Palette — Mystical Purple Gradient
        $bgTop = imagecolorallocate($img, 0x1A, 0x1A, 0x3E);    // #1a1a3e dark blue
        $bgBot = imagecolorallocate($img, 0x4A, 0x14, 0x8C);    // #4a148c deep purple
        $gold = imagecolorallocate($img, 0xFF, 0xD7, 0x00);     // #ffd700 gold
        $white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
        $silver = imagecolorallocate($img, 0xC0, 0xC0, 0xC0);
        $qrBg = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);     // white box for QR

        // Vertical gradient fill
        for ($y = 0; $y < $H; $y++) {
            $ratio = $y / $H;
            $r = (int) (0x1A + ($ratio * (0x4A - 0x1A)));
            $g = (int) (0x1A + ($ratio * (0x14 - 0x1A)));
            $b = (int) (0x3E + ($ratio * (0x8C - 0x3E)));
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $W, $y, $color);
        }

        // Decorative stars (random sprinkle)
        mt_srand(42); // deterministic
        for ($i = 0; $i < 80; $i++) {
            $x = mt_rand(0, $W);
            $y = mt_rand(0, $H);
            $size = mt_rand(1, 3);
            $brightness = mt_rand(160, 255);
            $star = imagecolorallocate($img, $brightness, $brightness, $brightness);
            imagefilledellipse($img, $x, $y, $size, $size, $star);
        }

        // Top border (gold line)
        imagefilledrectangle($img, 0, 0, $W, 4, $gold);
        imagefilledrectangle($img, 0, $H - 4, $W, $H, $gold);

        // QR background white box (กลาง — 400x400 + 20px padding)
        $qrSize = (int) ($this->settings->payment_banner_qr_size ?? 400);
        $qrX = (int) (($W - $qrSize) / 2);
        $qrY = (int) (($H - $qrSize) / 2) - 50;
        $pad = 20;
        imagefilledrectangle(
            $img,
            $qrX - $pad,
            $qrY - $pad,
            $qrX + $qrSize + $pad,
            $qrY + $qrSize + $pad,
            $qrBg
        );

        // Gold border รอบ QR box
        for ($i = 0; $i < 3; $i++) {
            imagerectangle(
                $img,
                $qrX - $pad - $i - 1,
                $qrY - $pad - $i - 1,
                $qrX + $qrSize + $pad + $i,
                $qrY + $qrSize + $pad + $i,
                $gold
            );
        }

        // Header text (ใช้ imagestring เพราะไม่มี Thai font แน่นอน — ใช้ภาษาอังกฤษ + emoji เป็น text)
        // ใช้ font built-in (ภาษาอังกฤษ)
        $headerText1 = 'PromptPay QR Payment';
        $headerText2 = 'Fortune by Mae Mor Janta';
        $textColor = $white;
        $titleY = 40;

        // Built-in font 5 = largest (15px high, 9px wide)
        $text1W = imagefontwidth(5) * strlen($headerText1);
        $text2W = imagefontwidth(4) * strlen($headerText2);
        imagestring($img, 5, (int) (($W - $text1W) / 2), $titleY, $headerText1, $gold);
        imagestring($img, 4, (int) (($W - $text2W) / 2), $titleY + 25, $headerText2, $silver);

        // Footer placeholder text — จะถูก overwrite ด้วย renderText() ตอน composite จริง
        $footerY = $qrY + $qrSize + $pad + 30;
        $footerText = 'Scan QR to pay (amount embedded)';
        $footerW = imagefontwidth(3) * strlen($footerText);
        imagestring($img, 3, (int) (($W - $footerW) / 2), $footerY, $footerText, $silver);

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
        $green = imagecolorallocate($banner, 0x4C, 0xAF, 0x50);

        // ทับ footer placeholder ด้วย dark rectangle (ลบของเดิม)
        $blackOut = imagecolorallocate($banner, 0x2A, 0x10, 0x60); // ม่วงเข้ม
        imagefilledrectangle($banner, 20, $startY - 5, $W - 20, $startY + 100, $blackOut);

        // ยอดเงิน (font 5 = ใหญ่สุด)
        $amountStr = '฿ '.number_format($amount, 2);
        $amountW = imagefontwidth(5) * strlen($amountStr);
        imagestring($banner, 5, (int) (($W - $amountW) / 2), $startY + 5, $amountStr, $gold);

        // Bill ref
        $billStr = 'Bill: '.$billRef;
        $billW = imagefontwidth(3) * strlen($billStr);
        imagestring($banner, 3, (int) (($W - $billW) / 2), $startY + 30, $billStr, $white);

        // Bank info (ถ้ามี)
        if ($bankName && $accountNumber) {
            $bankStr = $bankName.' '.$accountNumber;
            // strlen ของ Thai chars แตกต่างจาก English — ใช้ mb_strlen ไม่ได้กับ imagefontwidth
            // ใช้ approximation
            $bankW = imagefontwidth(2) * (int) (strlen($bankStr) * 0.6);
            imagestring($banner, 2, (int) (($W - $bankW) / 2), $startY + 55, $bankStr, $green);
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
