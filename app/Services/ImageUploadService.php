<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

class ImageUploadService
{
    protected ImageManager $manager;

    public function __construct()
    {
        // Initialize ImageManager with GD driver (Intervention Image v3)
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Upload and optimize image (saves as WebP for better performance)
     */
    public function uploadImage(
        UploadedFile $file,
        string $directory = 'products',
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 85
    ): string {
        try {
            // Generate unique filename with .webp extension
            $filename = Str::random(40) . '.webp';
            $path = "{$directory}/{$filename}";

            // Read and process image with Intervention Image v3
            $image = $this->manager->read($file->getPathname());

            // Resize if larger than max dimensions while maintaining aspect ratio
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                // Use scaleDown to resize proportionally
                $image->scaleDown(width: $maxWidth, height: $maxHeight);
            }

            // Encode as WebP and save
            $encoded = $image->toWebp(quality: $quality);
            Storage::disk('public')->put($path, (string) $encoded);

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
            $imageData = Storage::disk('public')->get($sourcePath);
            $image = $this->manager->read($imageData);

            // Create thumbnail with crop (cover maintains aspect ratio and crops)
            $image->cover($width, $height);

            // Generate thumbnail path
            $pathInfo = pathinfo($sourcePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];

            // Save thumbnail
            $encoded = $image->toWebp(quality: 85);
            Storage::disk('public')->put($thumbnailPath, (string) $encoded);

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
            $image = $this->manager->read($file->getPathname());
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
            $imageData = Storage::disk('public')->get($sourcePath);
            $image = $this->manager->read($imageData);

            // Generate WebP path
            $pathInfo = pathinfo($sourcePath);
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

            // Convert and save as WebP
            $encoded = $image->toWebp(quality: 85);
            Storage::disk('public')->put($webpPath, (string) $encoded);

            return $webpPath;
        } catch (Exception $e) {
            throw new Exception('Failed to convert to WebP: ' . $e->getMessage());
        }
    }
}
