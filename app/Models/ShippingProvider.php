<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shipping Provider Model - บริษัทขนส่ง
 *
 * @property int $id
 * @property string $code รหัสขนส่ง (THAIPOST, KERRY, etc.)
 * @property string $name ชื่อบริษัท
 * @property string|null $name_en ชื่อภาษาอังกฤษ
 * @property string|null $logo โลโก้
 * @property string|null $tracking_url URL ติดตามพัสดุ
 * @property string|null $website เว็บไซต์
 * @property string|null $hotline เบอร์โทร
 * @property bool $is_active สถานะใช้งาน
 * @property int $sort_order ลำดับการแสดง
 */
class ShippingProvider extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_providers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'name_en',
        'logo',
        'tracking_url',
        'website',
        'hotline',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * ความสัมพันธ์กับ Order
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_provider_id');
    }

    /**
     * Scope: Active providers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * สร้าง URL ติดตามพัสดุ
     *
     * @param string $trackingNumber เลขพัสดุ
     * @return string|null
     */
    public function getTrackingLink(string $trackingNumber): ?string
    {
        if (!$this->tracking_url || !$trackingNumber) {
            return null;
        }

        return str_replace('{tracking_number}', $trackingNumber, $this->tracking_url);
    }

    /**
     * Get tracking link attribute
     */
    public function getTrackingLinkAttributeAttribute(): ?string
    {
        return null; // Use getTrackingLink() method with tracking number
    }
}
