<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'created_by',
        'employee_code',
        'department',
        'position',
        'employment_status',
        'can_manage_users',
        'can_manage_vendors',
        'can_manage_products',
        'can_manage_orders',
        'can_manage_commissions',
        'can_manage_withdrawals',
        'can_manage_settings',
        'can_manage_security',
        'can_manage_employees',
        'can_view_reports',
        'can_view_analytics',
        'can_manage_content',
        'can_manage_coupons',
        'can_manage_categories',
        'allowed_ip_addresses',
        'work_start_time',
        'work_end_time',
        'allowed_days',
        'notes',
        'hired_at',
        'terminated_at',
        'last_active_at',
    ];

    protected $casts = [
        'can_manage_users' => 'boolean',
        'can_manage_vendors' => 'boolean',
        'can_manage_products' => 'boolean',
        'can_manage_orders' => 'boolean',
        'can_manage_commissions' => 'boolean',
        'can_manage_withdrawals' => 'boolean',
        'can_manage_settings' => 'boolean',
        'can_manage_security' => 'boolean',
        'can_manage_employees' => 'boolean',
        'can_view_reports' => 'boolean',
        'can_view_analytics' => 'boolean',
        'can_manage_content' => 'boolean',
        'can_manage_coupons' => 'boolean',
        'can_manage_categories' => 'boolean',
        'allowed_ip_addresses' => 'array',
        'allowed_days' => 'array',
        'hired_at' => 'datetime',
        'terminated_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the user associated with this employee
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this employee record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if employee can perform an action
     *
     * @param string $action
     * @return bool
     */
    public function can(string $action): bool
    {
        if ($this->employment_status !== 'active') {
            return false;
        }

        $permission = 'can_' . $action;
        return $this->$permission ?? false;
    }

    /**
     * Check if employee is working now (based on work hours and days)
     *
     * @return bool
     */
    public function isWorkingNow(): bool
    {
        if ($this->employment_status !== 'active') {
            return false;
        }

        $now = now();

        // Check allowed days
        if (!empty($this->allowed_days)) {
            $currentDay = strtolower($now->format('l'));
            if (!in_array($currentDay, $this->allowed_days)) {
                return false;
            }
        }

        // Check work hours
        if ($this->work_start_time && $this->work_end_time) {
            $currentTime = $now->format('H:i:s');
            if ($currentTime < $this->work_start_time || $currentTime > $this->work_end_time) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if IP address is allowed
     *
     * @param string $ip
     * @return bool
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ip_addresses)) {
            return true; // No restriction
        }

        return in_array($ip, $this->allowed_ip_addresses);
    }

    /**
     * Update last active timestamp
     *
     * @return void
     */
    public function updateLastActive(): void
    {
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * Get all permissions as array
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return [
            'manage_users' => $this->can_manage_users,
            'manage_vendors' => $this->can_manage_vendors,
            'manage_products' => $this->can_manage_products,
            'manage_orders' => $this->can_manage_orders,
            'manage_commissions' => $this->can_manage_commissions,
            'manage_withdrawals' => $this->can_manage_withdrawals,
            'manage_settings' => $this->can_manage_settings,
            'manage_security' => $this->can_manage_security,
            'manage_employees' => $this->can_manage_employees,
            'view_reports' => $this->can_view_reports,
            'view_analytics' => $this->can_view_analytics,
            'manage_content' => $this->can_manage_content,
            'manage_coupons' => $this->can_manage_coupons,
            'manage_categories' => $this->can_manage_categories,
        ];
    }
}
