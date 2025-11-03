<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Get permissions grouped by category.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getGroupedByCategory()
    {
        return static::all()->groupBy('category');
    }

    /**
     * Get all permission categories.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return static::distinct('category')->pluck('category')->toArray();
    }

    /**
     * Get category display name in Thai.
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryDisplayName(string $category): string
    {
        return match($category) {
            'system' => 'ระบบ',
            'users' => 'ผู้ใช้',
            'affiliate' => 'แอฟฟิลิเอท',
            'finance' => 'การเงิน',
            'ranks' => 'ระดับ',
            'kyc' => 'ยืนยันตัวตน',
            default => $category,
        };
    }
}
