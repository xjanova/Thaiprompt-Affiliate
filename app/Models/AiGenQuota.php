<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGenQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'free_image_daily',
        'free_image_monthly',
        'free_video_daily',
        'free_video_monthly',
        'role',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'free_image_daily' => 'integer',
        'free_image_monthly' => 'integer',
        'free_video_daily' => 'integer',
        'free_video_monthly' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Scope for active quotas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default quota.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for role-based quotas.
     */
    public function scopeForRole($query, ?string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('role', $role)
                ->orWhereNull('role');
        });
    }

    /**
     * Get quota for a user.
     */
    public static function getQuotaForUser(?User $user = null): ?self
    {
        if (!$user) {
            return self::active()->default()->first();
        }

        // Check for role-specific quota
        $role = null;
        if ($user->is_super_admin || $user->is_admin) {
            $role = 'admin';
        } else {
            $role = 'user';
        }

        $quota = self::active()->forRole($role)->first();

        if (!$quota) {
            $quota = self::active()->default()->first();
        }

        return $quota;
    }
}
