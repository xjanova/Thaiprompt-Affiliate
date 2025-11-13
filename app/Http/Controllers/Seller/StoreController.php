<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class StoreController extends Controller
{
    /**
     * Display the store settings page
     */
    public function settings()
    {
        $user = auth()->user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            // Create default store for seller if it doesn't exist
            $store = VendorStore::create([
                'user_id' => $user->id,
                'store_name' => $user->name . "'s Store",
                'store_slug' => Str::slug($user->name . '-store-' . $user->id),
                'is_active' => true,
                'status' => 'active',
            ]);
        }

        return view('seller.store.settings', compact('store'));
    }

    /**
     * Update the store settings
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string',
            'store_email' => 'nullable|email|max:255',
            'store_phone' => 'nullable|string|max:20',
            'store_address' => 'nullable|string',
            'store_city' => 'nullable|string|max:100',
            'store_state' => 'nullable|string|max:100',
            'store_postal_code' => 'nullable|string|max:20',
            'store_country' => 'nullable|string|max:100',
            'business_type' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'line_oa_id' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'store_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'store_banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'banner_position_y' => 'nullable|numeric',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'shipping_fee' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
        ]);

        // Handle boolean fields (checkboxes send '1' when checked, nothing when unchecked)
        $validated['enable_cod'] = $request->has('enable_cod');
        $validated['enable_reviews'] = $request->has('enable_reviews');

        // Handle logo upload with WebP conversion
        if ($request->hasFile('store_logo')) {
            // Delete old logo if exists
            if ($store->store_logo && Storage::exists($store->store_logo)) {
                Storage::delete($store->store_logo);
            }

            $logoPath = $this->convertAndSaveAsWebP(
                $request->file('store_logo'),
                'stores/logos',
                400,  // max width
                400   // max height
            );
            $validated['store_logo'] = 'storage/' . $logoPath;
        }

        // Handle banner upload with WebP conversion
        if ($request->hasFile('store_banner')) {
            // Delete old banner if exists
            if ($store->store_banner && Storage::exists($store->store_banner)) {
                Storage::delete($store->store_banner);
            }

            $bannerPath = $this->convertAndSaveAsWebP(
                $request->file('store_banner'),
                'stores/banners',
                1920,  // max width
                600    // max height
            );
            $validated['store_banner'] = 'storage/' . $bannerPath;
        }

        // Update store slug if store name changed
        if ($validated['store_name'] !== $store->store_name) {
            $validated['store_slug'] = Str::slug($validated['store_name']);
        }

        $store->update($validated);

        return redirect()->route('seller.store.settings')
            ->with('success', 'ตั้งค่าร้านค้าสำเร็จแล้ว');
    }

    /**
     * Convert and save image as WebP format
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param int $maxWidth
     * @param int $maxHeight
     * @return string The saved file path
     */
    private function convertAndSaveAsWebP($file, $directory, $maxWidth = 1920, $maxHeight = 1080)
    {
        try {
            // Generate unique filename
            $filename = Str::random(40) . '.webp';
            $fullPath = $directory . '/' . $filename;

            // Load the image using Intervention Image
            $image = Image::make($file);

            // Resize image while maintaining aspect ratio
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Encode to WebP with quality of 85
            $encodedImage = $image->encode('webp', 85);

            // Save to storage
            Storage::disk('public')->put($fullPath, $encodedImage);

            return $fullPath;
        } catch (\Exception $e) {
            // Fallback: If WebP conversion fails, save the original file
            \Log::error('WebP conversion failed: ' . $e->getMessage());

            // Save original file without conversion
            $originalPath = $file->store($directory, 'public');
            return $originalPath;
        }
    }
}
