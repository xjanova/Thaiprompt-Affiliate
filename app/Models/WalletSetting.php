<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WalletSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'group',
        'value',
        'type',
        'label',
        'description',
        'is_active',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are updated
        static::saved(function () {
            Cache::forget('wallet_settings');
        });

        static::deleted(function () {
            Cache::forget('wallet_settings');
        });
    }

    /**
     * Get value with proper type casting
     */
    public function getTypedValue()
    {
        return match($this->type) {
            'number' => (float) $this->value,
            'percentage' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $settings = Cache::remember('wallet_settings', 3600, function () {
            return static::where('is_active', true)->get()->keyBy('key');
        });

        $setting = $settings->get($key);

        if (!$setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        // Convert value to string for storage
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } else {
            $value = (string) $value;
        }

        return $setting->update(['value' => $value]);
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->getTypedValue()];
            })
            ->toArray();
    }

    /**
     * Get all public settings
     */
    public static function getPublicSettings(): array
    {
        return static::where('is_public', true)
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(function ($settings) {
                return $settings->mapWithKeys(function ($setting) {
                    return [$setting->key => [
                        'value' => $setting->getTypedValue(),
                        'label' => $setting->label,
                        'description' => $setting->description,
                    ]];
                });
            })
            ->toArray();
    }

    /**
     * Calculate withdrawal fee
     */
    public static function calculateWithdrawalFee(float $amount): float
    {
        $feeType = static::get('withdrawal_fee_type', 'percentage');
        $feeAmount = static::get('withdrawal_fee_amount', 0);
        $feeMin = static::get('withdrawal_fee_min', 0);
        $feeMax = static::get('withdrawal_fee_max', 999999);

        if ($feeType === 'percentage') {
            $fee = ($amount * $feeAmount) / 100;
        } else {
            $fee = $feeAmount;
        }

        // Apply min/max limits
        $fee = max($feeMin, min($feeMax, $fee));

        return round($fee, 2);
    }

    /**
     * Calculate withdrawal tax
     */
    public static function calculateWithdrawalTax(float $amount): float
    {
        $taxEnabled = static::get('tax_enabled', false);

        if (!$taxEnabled) {
            return 0;
        }

        $taxThreshold = static::get('tax_threshold', 0);

        if ($amount < $taxThreshold) {
            return 0;
        }

        $taxPercentage = static::get('tax_percentage', 0);
        $tax = ($amount * $taxPercentage) / 100;

        return round($tax, 2);
    }

    /**
     * Calculate transfer fee
     */
    public static function calculateTransferFee(float $amount): float
    {
        $fee = static::get('transfer_fee_amount', 0);
        return round($fee, 2);
    }

    /**
     * Check if withdrawal requires approval
     */
    public static function withdrawalRequiresApproval(float $amount): bool
    {
        $requiresApproval = static::get('withdrawal_requires_approval', true);

        if (!$requiresApproval) {
            return false;
        }

        $autoApproveThreshold = static::get('auto_approve_threshold', 0);

        if ($autoApproveThreshold > 0 && $amount < $autoApproveThreshold) {
            return false;
        }

        return true;
    }

    /**
     * Validate withdrawal amount
     */
    public static function validateWithdrawalAmount(float $amount): array
    {
        $minAmount = static::get('withdrawal_min_amount', 0);
        $maxAmount = static::get('withdrawal_max_amount', 999999999);

        $errors = [];

        if ($amount < $minAmount) {
            $errors[] = "ยอดถอนขั้นต่ำคือ " . number_format($minAmount, 2) . " บาท";
        }

        if ($amount > $maxAmount) {
            $errors[] = "ยอดถอนสูงสุดคือ " . number_format($maxAmount, 2) . " บาท";
        }

        return $errors;
    }

    /**
     * Validate transfer amount
     */
    public static function validateTransferAmount(float $amount): array
    {
        $minAmount = static::get('transfer_min_amount', 0);

        $errors = [];

        if ($amount < $minAmount) {
            $errors[] = "ยอดโอนขั้นต่ำคือ " . number_format($minAmount, 2) . " บาท";
        }

        return $errors;
    }

    /**
     * Validate deposit amount
     */
    public static function validateDepositAmount(float $amount): array
    {
        $minAmount = static::get('deposit_min_amount', 0);

        $errors = [];

        if ($amount < $minAmount) {
            $errors[] = "ยอดฝากขั้นต่ำคือ " . number_format($minAmount, 2) . " บาท";
        }

        return $errors;
    }

    /**
     * Check if payment method is enabled
     */
    public static function isPaymentMethodEnabled(string $method): bool
    {
        $key = $method . '_enabled';
        return static::get($key, false);
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public settings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope by group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
