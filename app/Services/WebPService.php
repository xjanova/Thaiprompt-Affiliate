<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\WebPConversionStat;

class WebPService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        // Use GD driver (can switch to Imagick if available)
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Convert and save an uploaded image to WebP format
     *
     * @param UploadedFile $file The uploaded file
     * @param string $directory The storage directory (e.g., 'branding', 'avatars')
     * @param int $quality Quality of WebP conversion (0-100)
     * @return array ['path' => storage path, 'url' => public URL, 'original_extension' => original file extension]
     */
    public function convertAndStore(UploadedFile $file, string $directory, int $quality = 85): array
    {
        // Get original extension
        $originalExtension = $file->getClientOriginalExtension();

        // Skip conversion for SVG files
        if (strtolower($originalExtension) === 'svg') {
            $path = $file->store($directory, 'public');
            return [
                'path' => $path,
                'url' => '/storage/' . $path,
                'original_extension' => $originalExtension,
            ];
        }

        // Read the image
        $image = $this->imageManager->read($file->getPathname());

        // Generate unique filename with .webp extension
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = \Illuminate\Support\Str::slug($filename) . '-' . uniqid() . '.webp';

        // Define full path
        $fullPath = $directory . '/' . $filename;

        // Encode to WebP format
        $encoded = $image->toWebp($quality);

        // Store the WebP image
        Storage::disk('public')->put($fullPath, (string) $encoded);

        // Track automatic conversion
        try {
            WebPConversionStat::incrementUploadConversion(1);
        } catch (\Exception $e) {
            \Log::warning('Failed to track WebP conversion stat: ' . $e->getMessage());
        }

        return [
            'path' => $fullPath,
            'url' => '/storage/' . $fullPath,
            'original_extension' => $originalExtension,
        ];
    }

    /**
     * Convert an existing image file to WebP
     *
     * @param string $path The storage path of the existing image (relative to storage/app/public)
     * @param int $quality Quality of WebP conversion (0-100)
     * @param bool $deleteOriginal Whether to delete the original file after conversion
     * @return array|null ['path' => new storage path, 'url' => public URL] or null if conversion failed
     */
    public function convertExistingImage(string $path, int $quality = 85, bool $deleteOriginal = false): ?array
    {
        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        // Get file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Skip if already WebP or SVG
        if (in_array($extension, ['webp', 'svg'])) {
            return [
                'path' => $path,
                'url' => '/storage/' . $path,
            ];
        }

        // Skip non-image files
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return null;
        }

        try {
            // Get full file path
            $fullPath = Storage::disk('public')->path($path);

            // Read the image
            $image = $this->imageManager->read($fullPath);

            // Generate new filename with .webp extension
            $newPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $path);

            // Encode to WebP format
            $encoded = $image->toWebp($quality);

            // Store the WebP image
            Storage::disk('public')->put($newPath, (string) $encoded);

            // Delete original if requested
            if ($deleteOriginal && $newPath !== $path) {
                Storage::disk('public')->delete($path);
            }

            return [
                'path' => $newPath,
                'url' => '/storage/' . $newPath,
            ];
        } catch (\Exception $e) {
            \Log::error('WebP conversion failed for ' . $path . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get WebP version of an image path (if it exists)
     * This is useful for checking if a WebP version already exists
     *
     * @param string $path The original image path
     * @return string|null The WebP path if it exists, null otherwise
     */
    public function getWebPPath(string $path): ?string
    {
        // If already WebP, return as is
        if (pathinfo($path, PATHINFO_EXTENSION) === 'webp') {
            return $path;
        }

        // Generate WebP path
        $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $path);

        // Check if WebP version exists
        if (Storage::disk('public')->exists($webpPath)) {
            return $webpPath;
        }

        return null;
    }

    /**
     * Get the public URL for an image, preferring WebP if available
     *
     * @param string $path The storage path
     * @return string The public URL
     */
    public function getPublicUrl(string $path): string
    {
        if (empty($path)) {
            return '';
        }

        // If path already starts with /storage/, extract the relative path
        if (str_starts_with($path, '/storage/')) {
            $path = str_replace('/storage/', '', $path);
        }

        // Try to get WebP version
        $webpPath = $this->getWebPPath($path);

        return '/storage/' . ($webpPath ?? $path);
    }
}
