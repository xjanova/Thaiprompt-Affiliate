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
            // 🐛 (2026-09-25) SELLER-19: คอลัมน์เป็น enum('individual','company') — เดิมรับข้อความอิสระแล้ว 500
            'business_type' => 'required|in:individual,company',
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
            // 🛵 (2026-09-25) ส่งด้วยไรเดอร์ของแพลตฟอร์ม: ต้องปักหมุดจุดรับของก่อนเปิด
            'rider_delivery_enabled' => 'nullable|boolean',
            'pickup_address' => 'nullable|string|max:500',
            'pickup_latitude' => 'nullable|numeric|between:-90,90|required_with:pickup_longitude|required_if:rider_delivery_enabled,1',
            'pickup_longitude' => 'nullable|numeric|between:-180,180|required_with:pickup_latitude|required_if:rider_delivery_enabled,1',
            // 🧾 (2026-09-25) ร้านแจ้งเองว่าจดทะเบียน VAT (ระบบถอด VAT 7/107 ออกจากยอดขายก่อนโอน) — ต้องมีเลขผู้เสียภาษี
            'vat_registered' => 'nullable|boolean',
        ], [
            'business_type.required' => 'กรุณาเลือกประเภทธุรกิจ',
            'business_type.in' => 'ประเภทธุรกิจไม่ถูกต้อง',
            'pickup_latitude.required_if' => 'เปิดส่งด้วยไรเดอร์ต้องปักหมุดจุดรับของก่อน',
            'pickup_longitude.required_if' => 'เปิดส่งด้วยไรเดอร์ต้องปักหมุดจุดรับของก่อน',
            'pickup_latitude.required_with' => 'กรุณาปักหมุดจุดรับของให้ครบ',
            'pickup_longitude.required_with' => 'กรุณาปักหมุดจุดรับของให้ครบ',
            'pickup_latitude.between' => 'พิกัดจุดรับของไม่ถูกต้อง',
            'pickup_longitude.between' => 'พิกัดจุดรับของไม่ถูกต้อง',
        ]);

        // สวิตช์: ฟอร์ม V4 ส่ง hidden 0 + checkbox 1 (ไม่ติ๊ก = "0") → ต้องอ่านด้วย boolean() ไม่ใช่ has()
        // (has() จะเป็น true เสมอเพราะมี hidden 0 → ปิด COD/รีวิวไม่ได้) · ไม่ส่งช่องมาเลย = ปิด เหมือนเดิม
        $validated['enable_cod'] = $request->boolean('enable_cod');
        $validated['enable_reviews'] = $request->boolean('enable_reviews');
        $validated['rider_delivery_enabled'] = $request->boolean('rider_delivery_enabled');
        $validated['pickup_latitude'] = is_numeric($request->input('pickup_latitude')) ? round((float) $request->input('pickup_latitude'), 7) : null;
        $validated['pickup_longitude'] = is_numeric($request->input('pickup_longitude')) ? round((float) $request->input('pickup_longitude'), 7) : null;

        // VAT: เปลี่ยนเฉพาะเมื่อฟอร์มส่งช่องนี้มา (ฟอร์มเว็บส่ง hidden 0 + checkbox 1) — ไคลเอนต์อื่นที่ไม่ส่งจะไม่ถูกรีเซ็ต
        $vatChanged = false;
        if ($request->has('vat_registered')) {
            $vatRegistered = $request->boolean('vat_registered');
            if ($vatRegistered) {
                $taxDigits = preg_replace('/\D/', '', (string) $request->input('tax_id'));
                if (strlen($taxDigits) !== 13) {
                    return back()->withInput()->withErrors([
                        'tax_id' => 'ร้านที่จดทะเบียน VAT ต้องกรอกเลขประจำตัวผู้เสียภาษี 13 หลัก',
                    ]);
                }
            }
            $vatChanged = (bool) $store->vat_registered !== $vatRegistered;
            $validated['vat_registered'] = $vatRegistered;
        } else {
            unset($validated['vat_registered']);
        }

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
        // 🔗 (2026-09-25) SELLER-18: ชื่อไทยได้ slug ว่าง/ชนร้านอื่น → ตัวสร้าง slug กลางที่ไม่ซ้ำ
        if ($validated['store_name'] !== $store->store_name) {
            $validated['store_slug'] = VendorStore::generateUniqueSlug($validated['store_name'], $store->id);
        }

        $oldVat = (bool) $store->vat_registered;

        $store->update($validated);

        // บันทึกประวัติการเปลี่ยนสถานะ VAT (กระทบเงินที่ร้านได้รับทุกออเดอร์หลังจากนี้)
        if ($vatChanged) {
            try {
                \App\Models\AccountingActivityLog::create([
                    'user_id' => $user->id,
                    'loggable_type' => VendorStore::class,
                    'loggable_id' => $store->id,
                    'action' => 'store.vat_registered_changed',
                    'description' => 'ร้านเปลี่ยนสถานะจดทะเบียน VAT: '.($oldVat ? 'จด' : 'ไม่จด').' → '.($validated['vat_registered'] ? 'จด' : 'ไม่จด'),
                    'old_values' => ['vat_registered' => $oldVat],
                    'new_values' => ['vat_registered' => (bool) $validated['vat_registered'], 'tax_id' => $store->tax_id],
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Store VAT change audit log failed', ['store_id' => $store->id, 'error' => $e->getMessage()]);
            }
        }

        // ซิงค์สีกับ StoreLayoutSetting (สร้างอัตโนมัติถ้ายังไม่มี)
        $colorFields = array_filter([
            'primary_color' => $validated['primary_color'] ?? null,
            'secondary_color' => $validated['secondary_color'] ?? null,
        ]);

        if (! empty($colorFields)) {
            $layoutSettings = \App\Models\StoreLayoutSetting::getOrCreateForUser($user->id);
            $layoutSettings->update($colorFields);
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
