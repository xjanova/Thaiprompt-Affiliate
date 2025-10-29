<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HomeSection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'title',
        'subtitle',
        'content',
        'type',
        'settings',
        'allowed_roles',
        'sort_order',
        'is_active',
        'schedule_start',
        'schedule_end',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'allowed_roles' => 'array',
            'is_active' => 'boolean',
            'schedule_start' => 'datetime',
            'schedule_end' => 'datetime',
        ];
    }

    /**
     * Scope for active sections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered sections by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Scope for sections visible now (considering schedule)
     */
    public function scopeVisibleNow($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('schedule_start')
                  ->orWhere('schedule_start', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('schedule_end')
                  ->orWhere('schedule_end', '>=', $now);
            });
    }

    /**
     * Scope for sections visible to a user role
     */
    public function scopeForRole($query, $role = null)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('allowed_roles')
              ->orWhereJsonContains('allowed_roles', $role);
        });
    }

    /**
     * Check if section is visible for a user
     */
    public function isVisibleFor($user = null): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check schedule
        $now = Carbon::now();
        if ($this->schedule_start && $this->schedule_start->gt($now)) {
            return false;
        }
        if ($this->schedule_end && $this->schedule_end->lt($now)) {
            return false;
        }

        // Check role permission
        if ($this->allowed_roles && !empty($this->allowed_roles)) {
            if (!$user) {
                return in_array('guest', $this->allowed_roles);
            }
            return in_array($user->role, $this->allowed_roles);
        }

        return true;
    }

    /**
     * Get section types
     */
    public static function getTypes(): array
    {
        return [
            'hero' => 'Hero Section (ส่วนหัว)',
            'features' => 'Features (คุณสมบัติ)',
            'statistics' => 'Statistics (สถิติ)',
            'testimonials' => 'Testimonials (รีวิว)',
            'cta' => 'Call to Action (ปุ่มเรียกใช้งาน)',
            'custom' => 'Custom (กำหนดเอง)',
        ];
    }
}
