<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'store_display_name',
        'receipt_header',
        'receipt_footer',
        'receipt_logo',
        'print_logo_on_receipt',
        'tax_enabled',
        'tax_percentage',
        'tax_inclusive',
        'tax_id_number',
        'service_charge_enabled',
        'service_charge_percentage',
        'allow_discounts',
        'max_discount_percentage',
        'require_manager_approval',
        'manager_approval_threshold',
        'enabled_payment_methods',
        'allow_credit_sales',
        'allow_partial_payments',
        'auto_print_receipt',
        'receipt_size',
        'receipt_copies',
        'print_customer_copy',
        'dual_screen_enabled',
        'show_product_images',
        'show_stock_levels',
        'theme_color',
        'offline_mode_enabled',
        'max_offline_transactions',
        'auto_sync_when_online',
        'customer_display_ads_enabled',
        'ad_rotation_seconds',
        'show_promotions',
        'real_time_stock_sync',
        'low_stock_warning',
        'low_stock_threshold',
        'prevent_negative_stock',
        'require_cash_management',
        'require_opening_cash',
        'require_closing_cash',
        'capture_customer_info',
        'require_customer_phone',
        'loyalty_points_enabled',
        'require_pin_for_void',
        'require_pin_for_refund',
        'require_pin_for_discount',
        'custom_fields',
        'keyboard_shortcuts',
    ];

    protected $casts = [
        'print_logo_on_receipt' => 'boolean',
        'tax_enabled' => 'boolean',
        'tax_percentage' => 'decimal:2',
        'tax_inclusive' => 'boolean',
        'service_charge_enabled' => 'boolean',
        'service_charge_percentage' => 'decimal:2',
        'allow_discounts' => 'boolean',
        'max_discount_percentage' => 'decimal:2',
        'require_manager_approval' => 'boolean',
        'manager_approval_threshold' => 'decimal:2',
        'enabled_payment_methods' => 'array',
        'allow_credit_sales' => 'boolean',
        'allow_partial_payments' => 'boolean',
        'auto_print_receipt' => 'boolean',
        'print_customer_copy' => 'boolean',
        'dual_screen_enabled' => 'boolean',
        'show_product_images' => 'boolean',
        'show_stock_levels' => 'boolean',
        'offline_mode_enabled' => 'boolean',
        'auto_sync_when_online' => 'boolean',
        'customer_display_ads_enabled' => 'boolean',
        'show_promotions' => 'boolean',
        'real_time_stock_sync' => 'boolean',
        'low_stock_warning' => 'boolean',
        'prevent_negative_stock' => 'boolean',
        'require_cash_management' => 'boolean',
        'require_opening_cash' => 'boolean',
        'require_closing_cash' => 'boolean',
        'capture_customer_info' => 'boolean',
        'require_customer_phone' => 'boolean',
        'loyalty_points_enabled' => 'boolean',
        'require_pin_for_void' => 'boolean',
        'require_pin_for_refund' => 'boolean',
        'require_pin_for_discount' => 'boolean',
        'custom_fields' => 'array',
        'keyboard_shortcuts' => 'array',
    ];

    /**
     * Relationships
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Helper Methods
     */
    public function isPaymentMethodEnabled(string $method): bool
    {
        return in_array($method, $this->enabled_payment_methods ?? ['cash']);
    }

    public function getEnabledPaymentMethods(): array
    {
        return $this->enabled_payment_methods ?? ['cash'];
    }

    public function requiresManagerApproval(float $discountPercentage): bool
    {
        return $this->require_manager_approval
            && $discountPercentage > $this->manager_approval_threshold;
    }

    public function canApplyDiscount(float $discountPercentage): bool
    {
        return $this->allow_discounts
            && $discountPercentage <= $this->max_discount_percentage;
    }

    public function calculateTax(float $amount, bool $inclusive = null): array
    {
        $inclusive = $inclusive ?? $this->tax_inclusive;

        if (!$this->tax_enabled) {
            return [
                'amount' => $amount,
                'tax' => 0,
                'total' => $amount,
            ];
        }

        if ($inclusive) {
            // Extract tax from inclusive price
            $divisor = 1 + ($this->tax_percentage / 100);
            $baseAmount = $amount / $divisor;
            $tax = $amount - $baseAmount;
        } else {
            // Add tax to price
            $baseAmount = $amount;
            $tax = $amount * ($this->tax_percentage / 100);
        }

        return [
            'amount' => round($baseAmount, 2),
            'tax' => round($tax, 2),
            'total' => round($amount + ($inclusive ? 0 : $tax), 2),
            'tax_percentage' => $this->tax_percentage,
            'tax_inclusive' => $inclusive,
        ];
    }

    public function calculateServiceCharge(float $amount): float
    {
        if (!$this->service_charge_enabled) {
            return 0;
        }

        return round($amount * ($this->service_charge_percentage / 100), 2);
    }

    public function getDefaultSettings(): array
    {
        return [
            'tax_enabled' => true,
            'tax_percentage' => 7.00,
            'tax_inclusive' => true,
            'allow_discounts' => true,
            'max_discount_percentage' => 100,
            'require_manager_approval' => false,
            'manager_approval_threshold' => 20,
            'enabled_payment_methods' => ['cash', 'card', 'qr'],
            'auto_print_receipt' => true,
            'receipt_size' => '80mm',
            'receipt_copies' => 1,
            'dual_screen_enabled' => true,
            'show_product_images' => true,
            'show_stock_levels' => true,
            'theme_color' => '#4F46E5',
            'offline_mode_enabled' => true,
            'max_offline_transactions' => 1000,
            'auto_sync_when_online' => true,
            'real_time_stock_sync' => true,
            'low_stock_warning' => true,
            'low_stock_threshold' => 10,
            'prevent_negative_stock' => true,
            'require_cash_management' => true,
            'require_pin_for_void' => true,
            'require_pin_for_refund' => true,
        ];
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($settings) {
            // Set defaults for any null values
            $defaults = $settings->getDefaultSettings();
            foreach ($defaults as $key => $value) {
                if ($settings->$key === null) {
                    $settings->$key = $value;
                }
            }
        });
    }
}
