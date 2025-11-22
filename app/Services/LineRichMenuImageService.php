<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

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
     * @param UploadedFile $file
     * @param string $size 'full' หรือ 'half'
     * @param bool $needsResize ต้อง resize หรือไม่
     * @return array{path: string, url: string, width: int, height: int, size_kb: int}
     *
     * @throws \Exception
     */
    public function processAndStore(UploadedFile $file, string $size, bool $needsResize = false): array
    {
        if (!isset(self::DIMENSIONS[$size])) {
            throw new \InvalidArgumentException("ขนาด Rich Menu ไม่ถูกต้อง: {$size}");
        }

        $targetWidth = self::DIMENSIONS[$size]['width'];
        $targetHeight = self::DIMENSIONS[$size]['height'];

        // โหลดภาพด้วย Intervention Image
        $image = Image::make($file);

        // ตรวจสอบขนาดปัจจุบัน
        $currentWidth = $image->width();
        $currentHeight = $image->height();

        // ถ้าต้อง resize
        if ($needsResize || $currentWidth !== $targetWidth || $currentHeight !== $targetHeight) {
            // Resize แบบ fit (รักษา aspect ratio แล้ว crop)
            $image = $this->resizeAndCrop($image, $targetWidth, $targetHeight);
        }

        // Optimize quality
        $image->sharpen(10); // เพิ่มความคมชัด

        // สร้างชื่อไฟล์
        $filename = $this->generateFilename($size);

        // แปลงเป็น WebP และบันทึก
        $path = "rich-menus/{$filename}";
        $webpData = $image->encode('webp', 90)->getEncoded(); // quality 90%

        Storage::disk('public')->put($path, $webpData);

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
     * Resize และ crop ภาพให้ตรงขนาดที่ต้องการ
     *
     * Strategy:
     * 1. Resize ให้ใหญ่กว่าหรือเท่ากับขนาดเป้าหมาย (รักษา aspect ratio)
     * 2. Crop ส่วนเกินออก (จากตรงกลาง)
     *
     * @param \Intervention\Image\Image $image
     * @param int $targetWidth
     * @param int $targetHeight
     * @return \Intervention\Image\Image
     */
    protected function resizeAndCrop($image, int $targetWidth, int $targetHeight)
    {
        $currentWidth = $image->width();
        $currentHeight = $image->height();

        // คำนวณ ratio
        $targetRatio = $targetWidth / $targetHeight;
        $currentRatio = $currentWidth / $currentHeight;

        // Resize ให้ใหญ่กว่าขนาดเป้าหมายเล็กน้อย (เพื่อเตรียม crop)
        if ($currentRatio > $targetRatio) {
            // ภาพกว้างกว่า → resize ตาม height แล้ว crop width
            $image->resize(null, $targetHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } else {
            // ภาพสูงกว่า → resize ตาม width แล้ว crop height
            $image->resize($targetWidth, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Crop ให้ตรงขนาดเป้าหมาย (จากตรงกลาง)
        $image->fit($targetWidth, $targetHeight, null, 'center');

        return $image;
    }

    /**
     * สร้างชื่อไฟล์ที่ unique
     *
     * @param string $size
     * @return string
     */
    protected function generateFilename(string $size): string
    {
        $timestamp = now()->format('YmdHis');
        $random = bin2hex(random_bytes(8));

        return "richmenu-{$size}-{$timestamp}-{$random}.webp";
    }

    /**
     * ลบไฟล์ภาพ Rich Menu
     *
     * @param string $path
     * @return bool
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
     * @param UploadedFile $file
     * @param string $size
     * @return array{valid: bool, message: string, dimensions: array}
     */
    public function validateDimensions(UploadedFile $file, string $size): array
    {
        if (!isset(self::DIMENSIONS[$size])) {
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
