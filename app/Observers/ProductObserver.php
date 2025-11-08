<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "deleting" event.
     * This runs before the product is deleted from the database.
     */
    public function deleting(Product $product): void
    {
        try {
            // Delete main image if exists
            if ($product->main_image_url) {
                $this->deleteImageFile($product->main_image_url);
            }

            // Delete all product images
            foreach ($product->images as $image) {
                // Delete the main image file
                if ($image->image_url) {
                    $this->deleteImageFile($image->image_url);
                }

                // Delete thumbnail if exists
                if ($image->thumbnail_url) {
                    $this->deleteImageFile($image->thumbnail_url);
                }
            }

            Log::info('Product images deleted', [
                'product_id' => $product->id,
                'product_name' => $product->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete product images', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw exception - allow product deletion to proceed
        }
    }

    /**
     * Delete an image file from storage
     */
    protected function deleteImageFile(string $imageUrl): void
    {
        // Extract path from URL
        // URLs are typically like: http://domain.com/storage/products/filename.webp
        // We need to convert this to: products/filename.webp for Storage facade

        $path = $this->extractStoragePath($imageUrl);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            Log::debug('Image file deleted', ['path' => $path]);
        }
    }

    /**
     * Extract storage path from image URL
     */
    protected function extractStoragePath(string $imageUrl): ?string
    {
        // Handle full URLs
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            // Extract path after /storage/
            if (preg_match('/\/storage\/(.+)$/', $imageUrl, $matches)) {
                return $matches[1];
            }
        }

        // Handle relative paths that start with /storage/
        if (str_starts_with($imageUrl, '/storage/')) {
            return substr($imageUrl, strlen('/storage/'));
        }

        // Handle paths that already are storage paths (e.g., "products/image.webp")
        if (str_starts_with($imageUrl, 'products/') || str_starts_with($imageUrl, 'categories/')) {
            return $imageUrl;
        }

        return null;
    }

    /**
     * Handle the Product "forceDeleted" event.
     * This is called when a soft-deleted product is permanently deleted.
     */
    public function forceDeleted(Product $product): void
    {
        // Images should already be deleted by the deleting event
        // But we can add additional cleanup here if needed
        Log::info('Product force deleted', [
            'product_id' => $product->id,
        ]);
    }
}
