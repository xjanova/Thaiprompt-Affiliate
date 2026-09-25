<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone_number',
        'address_line_1',
        'address_line_2',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * ที่อยู่นี้มีพิกัดที่ใช้ได้หรือไม่ (จำเป็นเมื่อส่งด้วยไรเดอร์)
     */
    public function hasLocation(): bool
    {
        $lat = $this->latitude;
        $lng = $this->longitude;

        if ($lat === null || $lng === null) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        // 0,0 = ค่าว่างที่แอปส่งมา ไม่ใช่พิกัดจริง
        if (abs($lat) < 0.000001 && abs($lng) < 0.000001) {
            return false;
        }

        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * ข้อมูลที่อยู่ ณ เวลาสั่งซื้อ (เก็บลง orders.shipping_address_snapshot)
     *
     * มีทั้งคีย์ของตาราง shipping_addresses และคีย์แบบย่อที่หน้าจอ/บริการเดิมอ่าน (name, phone, address)
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'recipient_name' => $this->recipient_name,
            'phone_number' => $this->phone_number,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'sub_district' => $this->sub_district,
            'district' => $this->district,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'country' => $this->country ?: 'Thailand',
            'latitude' => $this->hasLocation() ? (float) $this->latitude : null,
            'longitude' => $this->hasLocation() ? (float) $this->longitude : null,
            'notes' => $this->notes,
            'full_address' => $this->full_address,
            // คีย์แบบย่อสำหรับโค้ดเดิม
            'name' => $this->recipient_name,
            'phone' => $this->phone_number,
            'address' => trim(implode(' ', array_filter([$this->address_line_1, $this->address_line_2]))),
            'subdistrict' => $this->sub_district,
        ];
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all orders using this address
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    /**
     * Scope: Default address
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get full address formatted
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->address_line_2,
            $this->sub_district,
            $this->district,
            $this->province,
            $this->postal_code,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Set as default address
     */
    public function setAsDefault(): void
    {
        // Remove default from other addresses
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();
    }
}
