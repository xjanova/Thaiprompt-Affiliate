<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MenuItem Model
 *
 * เก็บรายการเมนูทั้งหมดในระบบ
 * รองรับการจัดการเมนูสำหรับ Dashboard ต่างๆ:
 * - Admin Dashboard
 * - Seller Dashboard
 * - User Dashboard
 * - Provider/Instructor Dashboard
 *
 * @property int $id
 * @property string $menu_key รหัสเมนู (unique)
 * @property string $dashboard_type ประเภท dashboard
 * @property int|null $parent_id Parent menu ID
 * @property string $label ชื่อเมนู
 * @property string|null $icon ไอคอน
 * @property string|null $route Laravel route name
 * @property string|null $url URL fallback
 * @property float $order ลำดับการแสดง
 * @property bool $is_active เปิด/ปิดเมนู
 * @property bool $is_visible แสดง/ซ่อนเมนู
 * @property array|null $permissions สิทธิ์ที่ต้องการ
 * @property string|null $condition เงื่อนไขพิเศษ
 * @property bool $hide_if_kyc_verified ซ่อนเมื่อ KYC verified
 * @property string|null $badge Badge text
 * @property string|null $badge_color Badge color class
 * @property string|null $description คำอธิบาย
 * @property bool $is_divider เป็นเส้นแบ่งหรือไม่
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @version 1.0.0
 *
 * @since 2025-12-04
 */
class MenuItem extends Model
{
    /**
     * ตารางที่ใช้เก็บข้อมูล
     *
     * @var string
     */
    protected $table = 'menu_items';

    /**
     * Attributes ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'menu_key',
        'dashboard_type',
        'parent_id',
        'label',
        'icon',
        'route',
        'url',
        'order',
        'is_active',
        'is_visible',
        'permissions',
        'condition',
        'hide_if_kyc_verified',
        'badge',
        'badge_color',
        'description',
        'is_divider',
    ];

    /**
     * Attribute casting
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'decimal:2',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'permissions' => 'array',
        'hide_if_kyc_verified' => 'boolean',
        'is_divider' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Dashboard Types ที่รองรับ
     */
    public const DASHBOARD_ADMIN = 'admin';

    public const DASHBOARD_SELLER = 'seller';

    public const DASHBOARD_USER = 'user';

    public const DASHBOARD_PROVIDER = 'provider';

    public const DASHBOARD_INSTRUCTOR = 'instructor';

    /**
     * ดึง Dashboard types ทั้งหมด
     */
    public static function getDashboardTypes(): array
    {
        return [
            self::DASHBOARD_ADMIN => 'ผู้ดูแลระบบ (Admin)',
            self::DASHBOARD_SELLER => 'ผู้ขาย (Seller)',
            self::DASHBOARD_USER => 'ผู้ใช้งาน (User)',
            self::DASHBOARD_PROVIDER => 'ผู้ให้บริการ (Provider)',
            self::DASHBOARD_INSTRUCTOR => 'ผู้สอน (Instructor)',
        ];
    }

    /**
     * ความสัมพันธ์กับเมนูแม่ (Parent)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * ความสัมพันธ์กับเมนูลูก (Children/Submenu)
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('order');
    }

    /**
     * ความสัมพันธ์กับ Roles ผ่านตาราง menu_role_settings
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'menu_role_settings')
            ->withPivot('is_visible', 'is_enabled', 'custom_order')
            ->withTimestamps();
    }

    /**
     * ความสัมพันธ์กับ MenuRoleSetting
     */
    public function roleSettings(): HasMany
    {
        return $this->hasMany(MenuRoleSetting::class);
    }

    /**
     * Scope: ดึงเมนูตาม Dashboard Type
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDashboard($query, string $type)
    {
        return $query->where('dashboard_type', $type);
    }

    /**
     * Scope: ดึงเฉพาะเมนูที่ active
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ดึงเฉพาะเมนูที่แสดง (visible)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: ดึงเฉพาะเมนูหลัก (ไม่มี parent)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: ดึงเมนูพร้อม children (Eager loading)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSubmenu($query)
    {
        return $query->with(['children' => function ($q) {
            $q->active()->visible()->orderBy('order');
        }]);
    }

    /**
     * ตรวจสอบว่าเป็นเมนูหลักหรือไม่
     */
    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * ตรวจสอบว่ามี submenu หรือไม่
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * ตรวจสอบว่าเป็น divider หรือไม่
     */
    public function isDivider(): bool
    {
        return $this->is_divider || $this->label === '---';
    }

    /**
     * ดึง URL ของเมนู (แปลง route เป็น URL)
     */
    public function getResolvedUrl(): string
    {
        if ($this->route && \Route::has($this->route)) {
            return route($this->route);
        }

        return $this->url ?? '#';
    }

    /**
     * ตรวจสอบว่า User มีสิทธิ์เข้าถึงเมนูนี้หรือไม่
     */
    public function userCanAccess(?User $user = null): bool
    {
        // ถ้าไม่ active หรือไม่ visible ให้ซ่อน
        if (! $this->is_active || ! $this->is_visible) {
            return false;
        }

        // ถ้าไม่มี user ให้ดูได้เฉพาะเมนูที่ไม่ต้องการ permission
        if (! $user) {
            return empty($this->permissions);
        }

        // ตรวจสอบ permissions
        if (! empty($this->permissions)) {
            foreach ($this->permissions as $permission) {
                if (! $user->hasPermission($permission)) {
                    return false;
                }
            }
        }

        // ตรวจสอบ hide_if_kyc_verified
        if ($this->hide_if_kyc_verified && method_exists($user, 'isKycVerified') && $user->isKycVerified()) {
            return false;
        }

        return true;
    }

    /**
     * ดึงการตั้งค่าสำหรับ Role
     */
    public function getSettingForRole(int $roleId): ?MenuRoleSetting
    {
        return $this->roleSettings()
            ->where('role_id', $roleId)
            ->first();
    }

    /**
     * ตรวจสอบว่า Role สามารถเห็นเมนูนี้ได้หรือไม่
     */
    public function isVisibleForRole(int $roleId): bool
    {
        $setting = $this->getSettingForRole($roleId);

        // ถ้าไม่มีการตั้งค่า ให้ใช้ค่าเริ่มต้น (แสดง)
        if (! $setting) {
            return $this->is_visible;
        }

        return $setting->is_visible && $setting->is_enabled;
    }

    /**
     * ดึงลำดับสำหรับ Role (ใช้ custom_order ถ้ามี)
     */
    public function getOrderForRole(int $roleId): float
    {
        $setting = $this->getSettingForRole($roleId);

        if ($setting && $setting->custom_order !== null) {
            return $setting->custom_order;
        }

        return $this->order;
    }

    /**
     * แปลง Model เป็น Array สำหรับ Frontend
     */
    public function toMenuArray(): array
    {
        return [
            'id' => $this->menu_key,
            'label' => $this->label,
            'icon' => $this->icon,
            'route' => $this->route,
            'url' => $this->getResolvedUrl(),
            'order' => $this->order,
            'permissions' => $this->permissions ?? [],
            'condition' => $this->condition,
            'badge' => $this->badge,
            'badge_color' => $this->badge_color,
            'description' => $this->description,
            'hide_if_kyc_verified' => $this->hide_if_kyc_verified,
            'submenu' => $this->children->map(fn ($child) => $child->toMenuArray())->toArray(),
        ];
    }
}
