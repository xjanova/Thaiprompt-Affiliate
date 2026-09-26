<?php

namespace App\Services\Seller;

use App\Models\AccountingActivityLog;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\Shop\ShopPresenter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * ตั้งค่าร้านจากแอป (/api/v1/seller/store) — กติกาเดียวกับหน้าเว็บ /seller/store/settings (Seller\StoreController)
 *
 * ต่างจากเว็บโดยตั้งใจ:
 *   - แก้เฉพาะช่องที่ส่งมา (partial) — ฟอร์มเว็บส่งทุกช่องทุกครั้ง แต่แอปแยกเป็นส่วนๆ
 *     (ถ้าใช้กติกาเว็บตรงๆ ช่องที่ไม่ส่ง เช่น enable_cod จะถูกปิดเองโดยไม่ตั้งใจ)
 *   - ลิงก์โซเชียลรับเฉพาะ http/https · คอลัมน์ NOT NULL (ค่าส่ง/ยอดขั้นต่ำ) ส่งว่าง = 0
 *   - ลบไฟล์โลโก้/แบนเนอร์เดิมได้จริง (เว็บเช็คผิด disk ไฟล์เก่าจึงค้าง)
 */
class SellerStoreSettingsService
{
    /** ยอดเงินสูงสุดที่รับ (คอลัมน์ decimal(10,2)) */
    private const MAX_AMOUNT = 10000000;

    /** ช่องที่แก้จากแอปได้ (อื่นๆ เช่น สีร้าน/เลย์เอาต์ อยู่บนเว็บ) */
    private const EDITABLE = [
        'store_name', 'store_description', 'store_email', 'store_phone',
        'store_address', 'store_city', 'store_state', 'store_postal_code',
        'business_type', 'tax_id', 'company_name',
        'facebook_url', 'line_oa_id', 'instagram_url', 'tiktok_url',
        'minimum_order_amount', 'shipping_fee', 'free_shipping_threshold',
        'enable_cod', 'enable_reviews',
        'rider_delivery_enabled', 'pickup_address', 'pickup_latitude', 'pickup_longitude',
        'vat_registered',
    ];

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $amount = 'sometimes|nullable|numeric|min:0|max:'.self::MAX_AMOUNT;

        return [
            'store_name' => 'sometimes|required|string|max:255',
            'store_description' => 'sometimes|nullable|string|max:5000',
            'store_email' => 'sometimes|nullable|email|max:255',
            'store_phone' => 'sometimes|nullable|string|max:20',
            'store_address' => 'sometimes|nullable|string|max:1000',
            'store_city' => 'sometimes|nullable|string|max:100',
            'store_state' => 'sometimes|nullable|string|max:100',
            'store_postal_code' => 'sometimes|nullable|string|max:20',
            // คอลัมน์เป็น enum('individual','company') (SELLER-19)
            'business_type' => 'sometimes|required|in:individual,company',
            'tax_id' => 'sometimes|nullable|string|max:50',
            'company_name' => 'sometimes|nullable|string|max:255',
            'facebook_url' => 'sometimes|nullable|url:http,https|max:255',
            'line_oa_id' => 'sometimes|nullable|string|max:255',
            'instagram_url' => 'sometimes|nullable|url:http,https|max:255',
            'tiktok_url' => 'sometimes|nullable|url:http,https|max:255',
            'minimum_order_amount' => $amount,
            'shipping_fee' => $amount,
            'free_shipping_threshold' => $amount,
            'enable_cod' => 'sometimes|boolean',
            'enable_reviews' => 'sometimes|boolean',
            'rider_delivery_enabled' => 'sometimes|boolean',
            'pickup_address' => 'sometimes|nullable|string|max:500',
            'pickup_latitude' => 'sometimes|nullable|numeric|between:-90,90|required_with:pickup_longitude',
            'pickup_longitude' => 'sometimes|nullable|numeric|between:-180,180|required_with:pickup_latitude',
            'vat_registered' => 'sometimes|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_name.required' => 'กรุณากรอกชื่อร้าน',
            'store_name.max' => 'ชื่อร้านต้องไม่เกิน 255 ตัวอักษร',
            'store_description.max' => 'รายละเอียดร้านยาวเกินไป',
            'store_email.email' => 'อีเมลร้านไม่ถูกต้อง',
            'store_phone.max' => 'เบอร์โทรร้านต้องไม่เกิน 20 ตัวอักษร',
            'store_address.max' => 'ที่อยู่ร้านยาวเกินไป',
            'store_city.max' => 'อำเภอ/เขตต้องไม่เกิน 100 ตัวอักษร',
            'store_state.max' => 'จังหวัดต้องไม่เกิน 100 ตัวอักษร',
            'store_postal_code.max' => 'รหัสไปรษณีย์ไม่ถูกต้อง',
            'business_type.required' => 'กรุณาเลือกประเภทธุรกิจ',
            'business_type.in' => 'ประเภทธุรกิจไม่ถูกต้อง',
            'tax_id.max' => 'เลขประจำตัวผู้เสียภาษีไม่ถูกต้อง',
            'company_name.max' => 'ชื่อบริษัทต้องไม่เกิน 255 ตัวอักษร',
            'facebook_url.url' => 'ลิงก์ Facebook ต้องขึ้นต้นด้วย https://',
            'instagram_url.url' => 'ลิงก์ Instagram ต้องขึ้นต้นด้วย https://',
            'tiktok_url.url' => 'ลิงก์ TikTok ต้องขึ้นต้นด้วย https://',
            'minimum_order_amount.numeric' => 'ยอดสั่งซื้อขั้นต่ำต้องเป็นตัวเลข',
            'minimum_order_amount.min' => 'ยอดสั่งซื้อขั้นต่ำต้องไม่ติดลบ',
            'minimum_order_amount.max' => 'ยอดสั่งซื้อขั้นต่ำสูงเกินไป',
            'shipping_fee.numeric' => 'ค่าจัดส่งต้องเป็นตัวเลข',
            'shipping_fee.min' => 'ค่าจัดส่งต้องไม่ติดลบ',
            'shipping_fee.max' => 'ค่าจัดส่งสูงเกินไป',
            'free_shipping_threshold.numeric' => 'ยอดส่งฟรีต้องเป็นตัวเลข',
            'free_shipping_threshold.min' => 'ยอดส่งฟรีต้องไม่ติดลบ',
            'free_shipping_threshold.max' => 'ยอดส่งฟรีสูงเกินไป',
            'pickup_latitude.required_with' => 'กรุณาปักหมุดจุดรับของให้ครบ',
            'pickup_longitude.required_with' => 'กรุณาปักหมุดจุดรับของให้ครบ',
            'pickup_latitude.between' => 'พิกัดจุดรับของไม่ถูกต้อง',
            'pickup_longitude.between' => 'พิกัดจุดรับของไม่ถูกต้อง',
            'pickup_latitude.numeric' => 'พิกัดจุดรับของไม่ถูกต้อง',
            'pickup_longitude.numeric' => 'พิกัดจุดรับของไม่ถูกต้อง',
            'pickup_address.max' => 'รายละเอียดจุดรับของต้องไม่เกิน 500 ตัวอักษร',
            '*.boolean' => 'ค่าสวิตช์ไม่ถูกต้อง',
            'store_logo.required' => 'กรุณาเลือกรูปโลโก้',
            'store_logo.image' => 'โลโก้ต้องเป็นไฟล์รูปภาพ',
            'store_logo.mimes' => 'โลโก้ต้องเป็น JPG, PNG, WebP หรือ GIF',
            'store_logo.max' => 'โลโก้ต้องไม่เกิน 2MB',
            'store_banner.required' => 'กรุณาเลือกรูปแบนเนอร์',
            'store_banner.image' => 'แบนเนอร์ต้องเป็นไฟล์รูปภาพ',
            'store_banner.mimes' => 'แบนเนอร์ต้องเป็น JPG, PNG, WebP หรือ GIF',
            'store_banner.max' => 'แบนเนอร์ต้องไม่เกิน 4MB',
        ];
    }

    /**
     * ตรวจเงื่อนไขข้ามช่อง (ใช้ค่าที่ส่งมา ถ้าไม่ส่งใช้ค่าเดิมของร้าน)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string> ข้อความผิดพลาดรายช่อง (ว่าง = ผ่าน)
     */
    public function crossFieldErrors(VendorStore $store, array $data): array
    {
        $errors = [];

        // พิกัดต้องส่งมาคู่กันเสมอ (ส่งมาช่องเดียว = หมุดครึ่งๆ กลางๆ)
        if (array_key_exists('pickup_latitude', $data) !== array_key_exists('pickup_longitude', $data)) {
            $errors['pickup_latitude'] = 'กรุณาปักหมุดจุดรับของให้ครบ';
        }

        $riderOn = array_key_exists('rider_delivery_enabled', $data)
            ? (bool) $data['rider_delivery_enabled']
            : (bool) $store->rider_delivery_enabled;
        $lat = array_key_exists('pickup_latitude', $data) ? $data['pickup_latitude'] : $store->pickup_latitude;
        $lng = array_key_exists('pickup_longitude', $data) ? $data['pickup_longitude'] : $store->pickup_longitude;

        if ($riderOn && (! is_numeric($lat) || ! is_numeric($lng))) {
            $errors['pickup_latitude'] = 'เปิดส่งด้วยไรเดอร์ต้องปักหมุดจุดรับของก่อน';
        }

        $vatOn = array_key_exists('vat_registered', $data) ? (bool) $data['vat_registered'] : (bool) $store->vat_registered;
        // ตรวจเฉพาะเมื่อส่งสถานะ VAT หรือเลขภาษีมา (ไม่บล็อกการแก้ช่องอื่นของร้านที่ข้อมูลเก่าไม่ครบ)
        if ($vatOn && (array_key_exists('vat_registered', $data) || array_key_exists('tax_id', $data))) {
            $taxId = array_key_exists('tax_id', $data) ? $data['tax_id'] : $store->tax_id;
            if (strlen((string) preg_replace('/\D/', '', (string) $taxId)) !== 13) {
                $errors['tax_id'] = 'ร้านที่จดทะเบียน VAT ต้องกรอกเลขประจำตัวผู้เสียภาษี 13 หลัก';
            }
        }

        return $errors;
    }

    /**
     * บันทึกการตั้งค่า (เฉพาะช่องที่ส่งมา)
     *
     * @param  array<string, mixed>  $data  ค่าที่ผ่าน rules() + crossFieldErrors() แล้ว
     */
    public function update(User $user, VendorStore $store, array $data, ?string $ip = null): VendorStore
    {
        $data = array_intersect_key($data, array_flip(self::EDITABLE));

        foreach (['enable_cod', 'enable_reviews', 'rider_delivery_enabled', 'vat_registered'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = (bool) $data[$flag];
            }
        }
        foreach (['pickup_latitude', 'pickup_longitude'] as $coord) {
            if (array_key_exists($coord, $data)) {
                $data[$coord] = is_numeric($data[$coord]) ? round((float) $data[$coord], 7) : null;
            }
        }
        // คอลัมน์ NOT NULL → ส่งว่าง = 0
        foreach (['minimum_order_amount', 'shipping_fee'] as $amount) {
            if (array_key_exists($amount, $data)) {
                $data[$amount] = $data[$amount] === null ? 0 : round((float) $data[$amount], 2);
            }
        }
        if (array_key_exists('free_shipping_threshold', $data) && $data['free_shipping_threshold'] !== null) {
            $data['free_shipping_threshold'] = round((float) $data['free_shipping_threshold'], 2);
        }
        foreach (['store_name', 'store_phone', 'store_email', 'tax_id', 'company_name', 'line_oa_id'] as $text) {
            if (array_key_exists($text, $data) && is_string($data[$text])) {
                $data[$text] = trim($data[$text]) === '' ? null : trim($data[$text]);
            }
        }

        return DB::transaction(function () use ($user, $store, $data, $ip) {
            $locked = VendorStore::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $oldVat = (bool) $locked->vat_registered;

            // เปลี่ยนชื่อร้าน → slug ใหม่ที่ไม่ซ้ำ (SELLER-18)
            if (array_key_exists('store_name', $data) && $data['store_name'] !== $locked->store_name) {
                $data['store_slug'] = VendorStore::generateUniqueSlug((string) $data['store_name'], $locked->id);
            }

            $locked->update($data);

            // บันทึกประวัติการเปลี่ยนสถานะ VAT (กระทบเงินที่ร้านได้รับทุกออเดอร์หลังจากนี้) — ตรงกับเว็บ
            if (array_key_exists('vat_registered', $data) && $oldVat !== (bool) $data['vat_registered']) {
                try {
                    AccountingActivityLog::create([
                        'user_id' => $user->id,
                        'loggable_type' => VendorStore::class,
                        'loggable_id' => $locked->id,
                        'action' => 'store.vat_registered_changed',
                        'description' => 'ร้านเปลี่ยนสถานะจดทะเบียน VAT (แอป): '.($oldVat ? 'จด' : 'ไม่จด').' → '.($data['vat_registered'] ? 'จด' : 'ไม่จด'),
                        'old_values' => ['vat_registered' => $oldVat],
                        'new_values' => ['vat_registered' => (bool) $data['vat_registered'], 'tax_id' => $locked->tax_id],
                        'ip_address' => $ip,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Store VAT change audit log failed (app)', ['store_id' => $locked->id, 'error' => $e->getMessage()]);
                }
            }

            return $locked->fresh();
        });
    }

    /**
     * อัปโหลดโลโก้/แบนเนอร์ (แปลง WebP ขนาดเดียวกับเว็บ) แล้วลบไฟล์เดิม
     *
     * @param  'logo'|'banner'  $kind
     */
    public function uploadImage(VendorStore $store, UploadedFile $file, string $kind): VendorStore
    {
        $column = $kind === 'logo' ? 'store_logo' : 'store_banner';
        $directory = $kind === 'logo' ? 'stores/logos' : 'stores/banners';
        [$maxW, $maxH] = $kind === 'logo' ? [400, 400] : [1920, 600];

        $path = $this->convertAndSaveAsWebP($file, $directory, $maxW, $maxH);
        $old = $store->getRawOriginal($column);

        try {
            $store->forceFill([$column => 'storage/'.$path])->save();
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);

            throw $e;
        }

        $this->deleteOldFile($old, $directory);

        return $store->fresh();
    }

    /**
     * ข้อมูลร้านสำหรับหน้าตั้งค่าในแอป
     *
     * @return array<string, mixed>
     */
    public function present(VendorStore $store): array
    {
        return [
            'id' => (int) $store->id,
            'store_name' => (string) $store->store_name,
            'store_slug' => $store->store_slug,
            'store_description' => $store->store_description,
            'store_email' => $store->store_email,
            'store_phone' => $store->store_phone,
            'store_address' => $store->store_address,
            'store_city' => $store->store_city,
            'store_state' => $store->store_state,
            'store_postal_code' => $store->store_postal_code,
            'business_type' => $store->business_type ?: 'individual',
            'tax_id' => $store->tax_id,
            'company_name' => $store->company_name,
            'facebook_url' => $store->facebook_url,
            'line_oa_id' => $store->line_oa_id,
            'instagram_url' => $store->instagram_url,
            'tiktok_url' => $store->tiktok_url,
            'minimum_order_amount' => (float) ($store->minimum_order_amount ?? 0),
            'shipping_fee' => (float) ($store->shipping_fee ?? 0),
            'free_shipping_threshold' => $store->free_shipping_threshold !== null ? (float) $store->free_shipping_threshold : null,
            'enable_cod' => (bool) $store->enable_cod,
            'enable_reviews' => (bool) $store->enable_reviews,
            'rider_delivery_enabled' => (bool) $store->rider_delivery_enabled,
            'pickup_address' => $store->pickup_address,
            'pickup_latitude' => $store->pickup_latitude !== null ? (float) $store->pickup_latitude : null,
            'pickup_longitude' => $store->pickup_longitude !== null ? (float) $store->pickup_longitude : null,
            'has_pickup_location' => $store->hasPickupLocation(),
            'vat_registered' => (bool) $store->vat_registered,
            'logo_url' => ShopPresenter::imageUrl($store->getRawOriginal('store_logo')),
            'banner_url' => ShopPresenter::imageUrl($store->getRawOriginal('store_banner')),
            'status' => $store->status,
            'is_active' => (bool) $store->is_active,
            'is_verified' => (bool) $store->is_verified,
            'updated_at' => $store->updated_at?->toIso8601String(),
        ];
    }

    /**
     * แปลงรูปเป็น WebP แล้วเก็บใน public disk (แปลงไม่ได้ = เก็บไฟล์เดิม) — ตรงกับ StoreController เว็บ
     */
    private function convertAndSaveAsWebP(UploadedFile $file, string $directory, int $maxWidth, int $maxHeight): string
    {
        try {
            $fullPath = $directory.'/'.Str::random(40).'.webp';
            $image = (new ImageManager(new Driver))->read($file->getPathname());

            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scaleDown(width: $maxWidth, height: $maxHeight);
            }

            Storage::disk('public')->put($fullPath, (string) $image->toWebp(quality: 85));

            return $fullPath;
        } catch (\Throwable $e) {
            Log::warning('Store image WebP conversion failed (app)', ['error' => $e->getMessage()]);

            return (string) $file->store($directory, 'public');
        }
    }

    /**
     * ลบไฟล์โลโก้/แบนเนอร์เดิม — เฉพาะไฟล์ในโฟลเดอร์ของร้านเท่านั้น (กันลบไฟล์อื่นจากค่าที่ถูกแก้ใน DB)
     */
    private function deleteOldFile(?string $old, string $directory): void
    {
        if ($old === null || $old === '' || filter_var($old, FILTER_VALIDATE_URL)) {
            return;
        }

        $relative = ltrim(Str::after($old, 'storage/'), '/');
        if (! str_starts_with($relative, $directory.'/') || str_contains($relative, '..')) {
            return;
        }

        try {
            // ร้านอื่นใช้ไฟล์เดียวกันอยู่ (ข้อมูลที่คัดลอกมา) → ไม่ลบ
            $inUse = VendorStore::withTrashed()
                ->where(fn ($q) => $q->where('store_logo', $old)->orWhere('store_banner', $old))
                ->exists();

            if (! $inUse) {
                Storage::disk('public')->delete($relative);
            }
        } catch (\Throwable $e) {
            Log::warning('Store image cleanup failed (app)', ['error' => $e->getMessage()]);
        }
    }
}
