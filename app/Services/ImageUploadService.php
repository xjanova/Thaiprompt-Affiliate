<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Exception;

class ImageUploadService
{
    /**
     * Upload and optimize image
     */
    public function uploadImage(
        UploadedFile $file,
        string $directory = 'products',
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 85
    ): string {
        try {
            // Generate unique filename
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = "{$directory}/{$filename}";

            // Get image dimensions
            $image = Image::make($file);

            // Resize if larger than max dimensions
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Optimize and save
            $image->encode($file->getClientOriginalExtension(), $quality);
            Storage::disk('public')->put($path, (string) $image->encode());

            return $path;
        } catch (Exception $e) {
            throw new Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultiple(
        array $files,
        string $directory = 'products',
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 85
    ): array {
        $uploadedPaths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedPaths[] = $this->uploadImage($file, $directory, $maxWidth, $maxHeight, $quality);
            }
        }

        return $uploadedPaths;
    }

    /**
     * Delete image
     */
    public function deleteImage(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete multiple images
     */
    public function deleteMultiple(array $paths): int
    {
        $deleted = 0;

        foreach ($paths as $path) {
            if ($this->deleteImage($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Create thumbnail
     */
    public function createThumbnail(
        string $sourcePath,
        int $width = 300,
        int $height = 300
    ): string {
        try {
            $image = Image::make(Storage::disk('public')->get($sourcePath));

            // Create thumbnail with crop
            $image->fit($width, $height);

            // Generate thumbnail path
            $pathInfo = pathinfo($sourcePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];

            // Save thumbnail
            Storage::disk('public')->put($thumbnailPath, (string) $image->encode());

            return $thumbnailPath;
        } catch (Exception $e) {
            throw new Exception('Failed to create thumbnail: ' . $e->getMessage());
        }
    }

    /**
     * Get image URL
     */
    public function getImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Validate image file
     */
    public function validateImage(UploadedFile $file, int $maxSizeKB = 5120): array
    {
        $errors = [];

        // Check file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'รองรับเฉพาะไฟล์ JPG, PNG, GIF และ WebP เท่านั้น';
        }

        // Check file size
        if ($file->getSize() > ($maxSizeKB * 1024)) {
            $errors[] = "ขนาดไฟล์ต้องไม่เกิน {$maxSizeKB} KB";
        }

        // Check image dimensions
        try {
            $image = Image::make($file);
            if ($image->width() < 100 || $image->height() < 100) {
                $errors[] = 'ขนาดรูปภาพต้องมีขนาดอย่างน้อย 100x100 พิกเซล';
            }
        } catch (Exception $e) {
            $errors[] = 'ไฟล์รูปภาพไม่ถูกต้อง';
        }

        return $errors;
    }

    /**
     * Convert to WebP format
     */
    public function convertToWebP(string $sourcePath): string
    {
        try {
            $image = Image::make(Storage::disk('public')->get($sourcePath));

            // Generate WebP path
            $pathInfo = pathinfo($sourcePath);
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

            // Convert and save as WebP
            $webp = $image->encode('webp', 85);
            Storage::disk('public')->put($webpPath, (string) $webp);

            return $webpPath;
        } catch (Exception $e) {
            throw new Exception('Failed to convert to WebP: ' . $e->getMessage());
        }
    }
}
