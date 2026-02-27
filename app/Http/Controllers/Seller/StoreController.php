<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class StoreController extends Controller
{
    /**
     * แสดงหน้าตั้งค่าร้านค้า
     *
     * หมายเหตุ: Route นี้ถูกป้องกันด้วย middleware kyc.verified และ has.vendor.store
     * ดังนั้น user จะมี store ที่ active แน่นอนเมื่อเข้ามาถึงตรงนี้
     */
    public function settings()
    {
        $user = auth()->user();
        $store = VendorStore::where('user_id', $user->id)->first();

        // ถ้าไม่มี store (ไม่ควรเกิดขึ้นเพราะมี middleware) ให้ redirect ไป onboarding
        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('info', 'กรุณาตั้งค่าร้านค้าของคุณก่อน');
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
            $validated['store_logo'] = 'storage/'.$logoPath;
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
            $validated['store_banner'] = 'storage/'.$bannerPath;
        }

        // Update store slug if store name changed
        if ($validated['store_name'] !== $store->store_name) {
            $validated['store_slug'] = Str::slug($validated['store_name']);
        }

        $store->update($validated);

        // ซิงค์สีกับ StoreLayoutSetting (ถ้ามี)
        $colorFields = array_filter([
            'primary_color' => $validated['primary_color'] ?? null,
            'secondary_color' => $validated['secondary_color'] ?? null,
        ]);

        if (! empty($colorFields)) {
            $layoutSettings = \App\Models\StoreLayoutSetting::where('user_id', $user->id)->first();
            if ($layoutSettings) {
                $layoutSettings->update($colorFields);
            }
        }

        return redirect()->route('seller.store.settings')
            ->with('success', 'ตั้งค่าร้านค้าสำเร็จแล้ว');
    }

    /**
     * แปลงและบันทึกรูปภาพเป็นรูปแบบ WebP
     *
     * ใช้ Intervention Image v3 API สำหรับการแปลงรูปภาพ
     *
     * @param  \Illuminate\Http\UploadedFile  $file  ไฟล์รูปภาพที่อัปโหลด
     * @param  string  $directory  โฟลเดอร์ที่จะบันทึก
     * @param  int  $maxWidth  ความกว้างสูงสุด
     * @param  int  $maxHeight  ความสูงสูงสุด
     * @return string เส้นทางไฟล์ที่บันทึก
     */
    private function convertAndSaveAsWebP($file, $directory, $maxWidth = 1920, $maxHeight = 1080)
    {
        try {
            // สร้างชื่อไฟล์ unique
            $filename = Str::random(40).'.webp';
            $fullPath = $directory.'/'.$filename;

            // สร้าง ImageManager ด้วย GD driver (Intervention Image v3)
            $manager = new ImageManager(new Driver);

            // โหลดรูปภาพโดยใช้ Intervention Image v3 API
            $image = $manager->read($file->getPathname());

            // ปรับขนาดรูปภาพโดยรักษาสัดส่วน (ไม่ขยายถ้าเล็กกว่า)
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scaleDown(width: $maxWidth, height: $maxHeight);
            }

            // เข้ารหัสเป็น WebP ที่คุณภาพ 85
            $encodedImage = $image->toWebp(quality: 85);

            // บันทึกลง storage
            Storage::disk('public')->put($fullPath, (string) $encodedImage);

            return $fullPath;
        } catch (\Exception $e) {
            // Fallback: ถ้าแปลง WebP ไม่สำเร็จ ให้บันทึกไฟล์ต้นฉบับ
            \Log::error('WebP conversion failed: '.$e->getMessage());

            // บันทึกไฟล์ต้นฉบับโดยไม่แปลง
            $originalPath = $file->store($directory, 'public');

            return $originalPath;
        }
    }
}
