<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MenuRoleSetting Model
 *
 * เก็บการตั้งค่าสิทธิ์เมนูสำหรับแต่ละ Role
 * ช่วยให้แอดมินสามารถ:
 * - เปิด/ปิดเมนูสำหรับ Role แต่ละตัว
 * - กำหนดลำดับเมนูเฉพาะสำหรับ Role
 *
 * @property int $id
 * @property int $menu_item_id
 * @property int $role_id
 * @property bool $is_visible แสดง/ซ่อนสำหรับ Role นี้
 * @property bool $is_enabled เปิด/ปิดใช้งานสำหรับ Role นี้
 * @property float|null $custom_order ลำดับเฉพาะสำหรับ Role นี้
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @version 1.0.0
 * @since 2025-12-04
 */
class MenuRoleSetting extends Model
{
    /**
     * ตารางที่ใช้เก็บข้อมูล
     *
     * @var string
     */
    protected $table = 'menu_role_settings';

    /**
     * Attributes ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'menu_item_id',
        'role_id',
        'is_visible',
        'is_enabled',
        'custom_order',
    ];

    /**
     * Attribute casting
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_visible' => 'boolean',
        'is_enabled' => 'boolean',
        'custom_order' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ MenuItem
     *
     * @return BelongsTo
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * ความสัมพันธ์กับ Role
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Scope: ดึงการตั้งค่าสำหรับ Role
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope: ดึงการตั้งค่าสำหรับ MenuItem
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $menuItemId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMenuItem($query, int $menuItemId)
    {
        return $query->where('menu_item_id', $menuItemId);
    }

    /**
     * Scope: ดึงเฉพาะที่ visible
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: ดึงเฉพาะที่ enabled
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * สร้างหรืออัพเดทการตั้งค่าสำหรับ Menu + Role
     *
     * @param int $menuItemId
     * @param int $roleId
     * @param array $attributes
     * @return self
     */
    public static function updateOrCreateSetting(int $menuItemId, int $roleId, array $attributes): self
    {
        return static::updateOrCreate(
            [
                'menu_item_id' => $menuItemId,
                'role_id' => $roleId,
            ],
            $attributes
        );
    }

    /**
     * ตรวจสอบว่าเมนูสามารถใช้งานได้หรือไม่
     *
     * @return bool
     */
    public function isAccessible(): bool
    {
        return $this->is_visible && $this->is_enabled;
    }

    /**
     * ดึงลำดับที่ใช้งานจริง (custom_order หรือ order จาก menu_item)
     *
     * @return float
     */
    public function getEffectiveOrder(): float
    {
        if ($this->custom_order !== null) {
            return $this->custom_order;
        }

        return $this->menuItem->order ?? 0;
    }
}
