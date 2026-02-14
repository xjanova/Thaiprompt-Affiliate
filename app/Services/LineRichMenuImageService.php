<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * LINE Rich Menu Image Service
 *
 * จัดการ:
 * - Resize/Crop ภาพให้ตรงตาม LINE Rich Menu specifications
 * - Convert เป็น WebP เพื่อลดขนาดไฟล์
 * - Optimize quality
 *
 * LINE Rich Menu Specifications:
 * - Full size: 2500 x 1686 px
 * - Half size: 2500 x 843 px
 * - File size: max 1 MB
 * - Format: JPG, PNG (แต่เราจะ convert เป็น WebP ภายใน)
 */
class LineRichMenuImageService
{
    /**
     * ขนาดมาตรฐานของ LINE Rich Menu
     *
     * @var array<string, array<string, int>>
     */
    protected const DIMENSIONS = [
        'full' => ['width' => 2500, 'height' => 1686],
        'half' => ['width' => 2500, 'height' => 843],
    ];

    /**
     * Process และ store ภาพ Rich Menu
     *
     * ใช้ Intervention Image v3 API
     *
     * @param  string  $size  'full' หรือ 'half'
     * @param  bool  $needsResize  ต้อง resize หรือไม่
     * @return array{path: string, url: string, width: int, height: int, size_kb: int}
     *
     * @throws \Exception
     */
    public function processAndStore(UploadedFile $file, string $size, bool $needsResize = false): array
    {
        if (! isset(self::DIMENSIONS[$size])) {
            throw new \InvalidArgumentException("ขนาด Rich Menu ไม่ถูกต้อง: {$size}");
        }

        $targetWidth = self::DIMENSIONS[$size]['width'];
        $targetHeight = self::DIMENSIONS[$size]['height'];

        // สร้าง ImageManager ด้วย GD driver (Intervention Image v3)
        $manager = new ImageManager(new Driver);

        // โหลดภาพด้วย Intervention Image v3
        $image = $manager->read($file->getPathname());

        // ตรวจสอบขนาดปัจจุบัน
        $currentWidth = $image->width();
        $currentHeight = $image->height();

        // ถ้าต้อง resize
        if ($needsResize || $currentWidth !== $targetWidth || $currentHeight !== $targetHeight) {
            // Resize แบบ cover (รักษา aspect ratio แล้ว crop)
            $image->cover($targetWidth, $targetHeight);
        }

        // Optimize quality - เพิ่มความคมชัด
        $image->sharpen(10);

        // สร้างชื่อไฟล์
        $filename = $this->generateFilename($size);

        // แปลงเป็น WebP และบันทึก (v3 API)
        $path = "rich-menus/{$filename}";
        $webpData = $image->toWebp(quality: 90);

        Storage::disk('public')->put($path, (string) $webpData);

        // ดึงข้อมูลไฟล์
        $sizeKb = round(Storage::disk('public')->size($path) / 1024, 2);

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'width' => $targetWidth,
            'height' => $targetHeight,
            'size_kb' => $sizeKb,
        ];
    }

    /**
     * สร้างชื่อไฟล์ที่ unique
     */
    protected function generateFilename(string $size): string
    {
        $timestamp = now()->format('YmdHis');
        $random = bin2hex(random_bytes(8));

        return "richmenu-{$size}-{$timestamp}-{$random}.webp";
    }

    /**
     * ลบไฟล์ภาพ Rich Menu
     */
    public function delete(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * ตรวจสอบว่าภาพมีขนาดถูกต้องหรือไม่
     *
     * @return array{valid: bool, message: string, dimensions: array}
     */
    public function validateDimensions(UploadedFile $file, string $size): array
    {
        if (! isset(self::DIMENSIONS[$size])) {
            return [
                'valid' => false,
                'message' => 'ขนาด Rich Menu ไม่ถูกต้อง',
                'dimensions' => [],
            ];
        }

        $targetWidth = self::DIMENSIONS[$size]['width'];
        $targetHeight = self::DIMENSIONS[$size]['height'];
        $targetRatio = $targetWidth / $targetHeight;

        $imageInfo = getimagesize($file->getPathname());

        if ($imageInfo === false) {
            return [
                'valid' => false,
                'message' => 'ไม่สามารถอ่านข้อมูลรูปภาพได้',
                'dimensions' => [],
            ];
        }

        [$width, $height] = $imageInfo;
        $actualRatio = $width / $height;

        // ตรวจสอบ aspect ratio (ยอมรับผิดพลาด 1%)
        $ratioDifference = abs($targetRatio - $actualRatio) / $targetRatio;

        if ($ratioDifference > 0.01) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Aspect Ratio ไม่ถูกต้อง! ต้องการ %d:%d แต่ได้ %d:%d',
                    $targetWidth,
                    $targetHeight,
                    $width,
                    $height
                ),
                'dimensions' => [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $actualRatio,
                ],
            ];
        }

        return [
            'valid' => true,
            'message' => 'ภาพมีขนาดและอัตราส่วนถูกต้อง',
            'dimensions' => [
                'width' => $width,
                'height' => $height,
                'ratio' => $actualRatio,
                'needs_resize' => ($width !== $targetWidth || $height !== $targetHeight),
            ],
        ];
    }
}
