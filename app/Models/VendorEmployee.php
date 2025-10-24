<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'created_by',
        'employee_code',
        'position',
        'employment_status',
        'can_manage_products',
        'can_manage_orders',
        'can_manage_inventory',
        'can_view_sales_reports',
        'can_manage_coupons',
        'can_manage_reviews',
        'can_use_pos',
        'can_process_refunds',
        'can_manage_shipping',
        'can_manage_employees',
        'can_open_pos_session',
        'can_close_pos_session',
        'can_void_transactions',
        'can_apply_discounts',
        'max_discount_percentage',
        'max_transaction_amount',
        'allowed_ip_addresses',
        'work_start_time',
        'work_end_time',
        'allowed_days',
        'commission_percentage',
        'notes',
        'hired_at',
        'terminated_at',
        'last_active_at',
    ];

    protected $casts = [
        'can_manage_products' => 'boolean',
        'can_manage_orders' => 'boolean',
        'can_manage_inventory' => 'boolean',
        'can_view_sales_reports' => 'boolean',
        'can_manage_coupons' => 'boolean',
        'can_manage_reviews' => 'boolean',
        'can_use_pos' => 'boolean',
        'can_process_refunds' => 'boolean',
        'can_manage_shipping' => 'boolean',
        'can_manage_employees' => 'boolean',
        'can_open_pos_session' => 'boolean',
        'can_close_pos_session' => 'boolean',
        'can_void_transactions' => 'boolean',
        'can_apply_discounts' => 'boolean',
        'max_discount_percentage' => 'decimal:2',
        'max_transaction_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'allowed_ip_addresses' => 'array',
        'allowed_days' => 'array',
        'hired_at' => 'datetime',
        'terminated_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the vendor this employee belongs to
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

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
     * Check if employee can apply discount
     *
     * @param float $discountPercentage
     * @return bool
     */
    public function canApplyDiscount(float $discountPercentage): bool
    {
        if (!$this->can_apply_discounts) {
            return false;
        }

        if ($this->max_discount_percentage === null) {
            return true;
        }

        return $discountPercentage <= $this->max_discount_percentage;
    }

    /**
     * Check if employee can process transaction
     *
     * @param float $amount
     * @return bool
     */
    public function canProcessTransaction(float $amount): bool
    {
        if ($this->max_transaction_amount === null) {
            return true;
        }

        return $amount <= $this->max_transaction_amount;
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
            'manage_products' => $this->can_manage_products,
            'manage_orders' => $this->can_manage_orders,
            'manage_inventory' => $this->can_manage_inventory,
            'view_sales_reports' => $this->can_view_sales_reports,
            'manage_coupons' => $this->can_manage_coupons,
            'manage_reviews' => $this->can_manage_reviews,
            'use_pos' => $this->can_use_pos,
            'process_refunds' => $this->can_process_refunds,
            'manage_shipping' => $this->can_manage_shipping,
            'manage_employees' => $this->can_manage_employees,
            'open_pos_session' => $this->can_open_pos_session,
            'close_pos_session' => $this->can_close_pos_session,
            'void_transactions' => $this->can_void_transactions,
            'apply_discounts' => $this->can_apply_discounts,
        ];
    }
}
