<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MobileBanner Model
 *
 * จัดการ Banner โฆษณาในแอพมือถือ
 * Admin สามารถเพิ่ม/แก้ไข/ลบ banner ได้
 *
 * @property int $id
 * @property string $title หัวข้อ banner
 * @property string $image URL รูปภาพ
 * @property string|null $link ลิงก์เมื่อคลิก
 * @property string $link_type ประเภทลิงก์ (internal, external, product, category)
 * @property string|null $link_target เป้าหมาย (product_id, category_id, etc.)
 * @property string $position ตำแหน่ง (home = หน้าหลัก, shop = หน้าช้อป)
 * @property int $sort_order ลำดับการแสดง
 * @property bool $is_active สถานะเปิด/ปิด
 * @property \Carbon\Carbon|null $start_date วันเริ่มแสดง
 * @property \Carbon\Carbon|null $end_date วันสิ้นสุดการแสดง
 * @property int $view_count จำนวนการแสดง
 * @property int $click_count จำนวนการคลิก
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MobileBanner extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mobile_banners';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'cta_label',
        'cta_type',
        'cta_value',
        'link',
        'link_type',
        'link_target',
        'position',
        'audience',
        'campaign_key',
        'sort_order',
        'is_active',
        'start_date',
        'end_date',
        'view_count',
        'click_count',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ตำแหน่งที่ใช้ได้
     */
    public const POSITION_HOME = 'home';

    public const POSITION_SHOP = 'shop';

    public const POSITIONS = [
        self::POSITION_HOME => 'หน้าหลัก',
        self::POSITION_SHOP => 'หน้าช้อป',
    ];

    // ===== แบนเนอร์แคมเปญ (2026-09-26) — position = placement ในแอป =====
    public const PLACEMENT_TALADSOD = 'taladsod';

    public const PLACEMENT_RIDER = 'rider';

    public const PLACEMENT_MERCHANT = 'merchant';

    /** ตำแหน่งแสดงทั้งหมด (placement) => ชื่อไทย — shop = ตำแหน่งเดิมของแอปรุ่นเก่า */
    public const PLACEMENTS = [
        self::POSITION_HOME => 'หน้าแรกแอป',
        self::PLACEMENT_TALADSOD => 'ตลาดสด',
        self::PLACEMENT_RIDER => 'ไรเดอร์',
        self::PLACEMENT_MERCHANT => 'ร้านค้า (ผู้ขาย)',
        self::POSITION_SHOP => 'หน้าช้อป (แอปรุ่นเก่า)',
    ];

    /** กลุ่มผู้ชม => ชื่อไทย */
    public const AUDIENCES = [
        'all' => 'ทุกคน',
        'buyer' => 'ผู้ซื้อ',
        'rider' => 'ไรเดอร์',
        'merchant' => 'ร้านค้า',
    ];

    /** ชนิดปุ่ม CTA => ชื่อไทย (null = ไม่มีปุ่ม) */
    public const CTA_TYPES = [
        'screen' => 'เปิดหน้าจอในแอป',
        'url' => 'เปิดลิงก์เว็บ',
    ];

    /**
     * หน้าจอในแอปที่เลือกได้ (ค่า cta_value เมื่อ cta_type = screen) — แอปเปิดด้วย router.push('/' + ค่า)
     * พิมพ์ชื่อหน้าจออื่นเองได้ (เช่น product/123) ตราบที่ตรงรูปแบบ SCREEN_PATTERN
     */
    public const APP_SCREENS = [
        'taladsod' => 'ตลาดสด',
        'rider' => 'ไรเดอร์',
        'rider-jobs' => 'งานไรเดอร์',
        'shopping' => 'ช้อปปิ้ง',
        'stores' => 'ร้านค้าทั้งหมด',
        'cart' => 'ตะกร้าสินค้า',
        'orders' => 'คำสั่งซื้อของฉัน',
        'wallet-topup' => 'เติมเงินกระเป๋า',
        'referral' => 'แนะนำเพื่อน',
        'services' => 'บริการ',
        'notifications' => 'การแจ้งเตือน',
    ];

    /** รูปแบบชื่อหน้าจอที่ยอมรับ (ตัวเล็ก/ตัวเลข/ขีด/ทับ) */
    public const SCREEN_PATTERN = '/^[a-z0-9][a-z0-9\-_\/]{0,99}$/';

    /**
     * The model's default values.
     *
     * @var array
     */
    protected $attributes = [
        'position' => 'home',  // home = หน้าหลัก, shop = หน้าช้อป
        'audience' => 'all',
        'link_type' => 'internal',
        'is_active' => true,
        'view_count' => 0,
        'click_count' => 0,
        'sort_order' => 0,
    ];

    /**
     * Scope: เฉพาะ Active banners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope: ตามตำแหน่ง
     */
    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope: เรียงตามลำดับ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * เพิ่ม view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * เพิ่ม click count
     */
    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    /**
     * คำนวณ CTR (Click-Through Rate)
     */
    public function getCtrAttribute(): float
    {
        if ($this->view_count === 0) {
            return 0;
        }

        return round(($this->click_count / $this->view_count) * 100, 2);
    }

    /**
     * ตรวจสอบว่า banner กำลังแสดงอยู่หรือไม่
     */
    public function getIsShowingAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $this->start_date > $now) {
            return false;
        }
        if ($this->end_date && $this->end_date < $now) {
            return false;
        }

        return true;
    }

    // =====================================================================
    // แบนเนอร์แคมเปญ (2026-09-26)
    // =====================================================================

    /**
     * สถานะตามตารางเวลา: showing (กำลังแสดง) | scheduled (ตั้งเวลาไว้) | expired (หมดเวลา) | inactive (ปิดอยู่)
     */
    public function scheduleState(?\DateTimeInterface $at = null): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $now = $at !== null ? \Illuminate\Support\Carbon::instance($at) : now();

        if ($this->start_date && $this->start_date->greaterThan($now)) {
            return 'scheduled';
        }
        if ($this->end_date && $this->end_date->lessThan($now)) {
            return 'expired';
        }

        return 'showing';
    }

    /**
     * ชื่อไทยของสถานะตามตารางเวลา
     */
    public function scheduleStateLabel(): string
    {
        return match ($this->scheduleState()) {
            'showing' => 'กำลังแสดง',
            'scheduled' => 'ตั้งเวลาไว้',
            'expired' => 'หมดเวลาแล้ว',
            default => 'ปิดอยู่',
        };
    }

    /**
     * ชื่อไทยของตำแหน่งแสดง
     */
    public function placementLabel(): string
    {
        return self::PLACEMENTS[$this->position] ?? (string) $this->position;
    }

    /**
     * URL รูปแบบเต็ม (แอปต้องใช้ absolute URL)
     */
    public function imageUrl(): ?string
    {
        $image = trim((string) $this->image);

        if ($image === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $image) === 1) {
            return $image;
        }

        return url('/'.ltrim($image, '/'));
    }

    /**
     * ลิงก์เต็มของปุ่ม CTA เมื่อเป็นชนิด url (ลิงก์ในเว็บเราเองเก็บแบบ /path → เติมโดเมน)
     */
    public function ctaUrl(): ?string
    {
        if ($this->cta_type !== 'url') {
            return null;
        }

        $value = trim((string) $this->cta_value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return $value;
        }

        return url('/'.ltrim($value, '/'));
    }

    /**
     * ตั้งค่า link/link_type เดิมให้ตรงกับ CTA — ให้ /api/v1/mobile/banners ของแอปรุ่นเก่ายังกดได้
     */
    public function syncLegacyLink(): void
    {
        if ($this->cta_type === 'url') {
            $this->link = $this->ctaUrl();
            $this->link_type = 'external';
            $this->link_target = null;

            return;
        }

        if ($this->cta_type === 'screen' && trim((string) $this->cta_value) !== '') {
            $this->link = '/'.ltrim((string) $this->cta_value, '/');
            $this->link_type = 'internal';
            $this->link_target = null;

            return;
        }

        $this->link = null;
        $this->link_type = 'internal';
        $this->link_target = null;
    }

    /**
     * ข้อมูลสำหรับแอป (GET /api/v1/banners) — ตัวเลขเป็น JSON number, วันที่ ISO8601
     *
     * @return array<string, mixed>
     */
    public function toAppApi(): array
    {
        [$ctaType, $ctaValue] = $this->resolvedCta();

        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'subtitle' => $this->subtitle !== null && $this->subtitle !== '' ? (string) $this->subtitle : null,
            'image_url' => $this->imageUrl(),
            'cta_label' => $this->cta_label !== null && $this->cta_label !== '' ? (string) $this->cta_label : null,
            'cta_type' => $ctaType,
            'cta_value' => $ctaValue,
            'cta_url' => $ctaType === 'url' ? $this->ctaUrlFor($ctaValue) : null,
            'placement' => (string) $this->position,
            'audience' => (string) ($this->audience ?: 'all'),
            'starts_at' => $this->start_date?->toIso8601String(),
            'ends_at' => $this->end_date?->toIso8601String(),
            'sort' => (int) $this->sort_order,
        ];
    }

    /**
     * CTA ที่ใช้จริง — แบนเนอร์เก่าที่ยังไม่มี cta_type จะแปลงจาก link/link_type เดิม
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function resolvedCta(): array
    {
        $type = $this->cta_type;
        $value = trim((string) $this->cta_value);

        if (in_array($type, ['screen', 'url'], true) && $value !== '') {
            return [$type, $value];
        }

        $link = trim((string) $this->link);

        if ($this->link_type === 'product') {
            return $this->link_target ? ['screen', 'product/'.$this->link_target] : [null, null];
        }
        if ($this->link_type === 'category') {
            return $this->link_target ? ['screen', 'shopping'] : [null, null];
        }
        if ($link === '') {
            return [null, null];
        }
        if ($this->link_type === 'external' || preg_match('#^https?://#i', $link) === 1) {
            return ['url', $link];
        }

        return ['screen', ltrim($link, '/')];
    }

    private function ctaUrlFor(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_match('#^https?://#i', $value) === 1 ? $value : url('/'.ltrim($value, '/'));
    }
}
