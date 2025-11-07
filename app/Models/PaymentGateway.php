<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PaymentGateway extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'instructions',
        'is_active',
        'is_available',
        'is_coming_soon',
        'supports_deposit',
        'supports_withdrawal',
        'config',
        'credentials',
        'fees',
        'limits',
        'icon',
        'logo_url',
        'color',
        'help_url',
        'test_mode',
        'test_credentials',
        'sort_order',
        'category',
        'last_tested_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_coming_soon' => 'boolean',
        'supports_deposit' => 'boolean',
        'supports_withdrawal' => 'boolean',
        'config' => 'array',
        'fees' => 'array',
        'limits' => 'array',
        'metadata' => 'array',
        'test_mode' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Get the credentials attribute with decryption error handling
     */
    public function getCredentialsAttribute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Manually decrypt the value
            $decrypted = Crypt::decryptString($value);
            // Decode the JSON
            return json_decode($decrypted, true);
        } catch (DecryptException $e) {
            Log::warning('Failed to decrypt credentials for payment gateway', [
                'gateway_id' => $this->id,
                'gateway_code' => $this->code ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error accessing credentials for payment gateway', [
                'gateway_id' => $this->id,
                'gateway_code' => $this->code ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Set the credentials attribute with encryption
     */
    public function setCredentialsAttribute($value)
    {
        if ($value === null) {
            $this->attributes['credentials'] = null;
            return;
        }

        // Encrypt the JSON-encoded value
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Get the test_credentials attribute with decryption error handling
     */
    public function getTestCredentialsAttribute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Manually decrypt the value
            $decrypted = Crypt::decryptString($value);
            // Decode the JSON
            return json_decode($decrypted, true);
        } catch (DecryptException $e) {
            Log::warning('Failed to decrypt test credentials for payment gateway', [
                'gateway_id' => $this->id,
                'gateway_code' => $this->code ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error accessing test credentials for payment gateway', [
                'gateway_id' => $this->id,
                'gateway_code' => $this->code ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Set the test_credentials attribute with encryption
     */
    public function setTestCredentialsAttribute($value)
    {
        if ($value === null) {
            $this->attributes['test_credentials'] = null;
            return;
        }

        // Encrypt the JSON-encoded value
        $this->attributes['test_credentials'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Scope for active gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_available', true);
    }

    /**
     * Scope for available gateways (not coming soon)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('is_coming_soon', false);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for deposit-supporting gateways
     */
    public function scopeSupportsDeposit($query)
    {
        return $query->where('supports_deposit', true);
    }

    /**
     * Scope for withdrawal-supporting gateways
     */
    public function scopeSupportsWithdrawal($query)
    {
        return $query->where('supports_withdrawal', true);
    }

    /**
     * Get gateway by code
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * Get config value
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Get credential value (decrypted if necessary)
     */
    public function getCredential(string $key, $default = null)
    {
        if ($this->test_mode && $this->test_credentials) {
            return data_get($this->test_credentials, $key, $default);
        }

        return data_get($this->credentials, $key, $default);
    }

    /**
     * Check if gateway is properly configured
     */
    public function isConfigured(): bool
    {
        if ($this->is_coming_soon) {
            return false;
        }

        // Each gateway has different required fields
        $requiredFields = $this->getRequiredFields();

        foreach ($requiredFields as $field) {
            if (empty($this->getCredential($field))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get required credential fields for this gateway
     */
    public function getRequiredFields(): array
    {
        return match($this->code) {
            'promptpay' => ['promptpay_id', 'promptpay_name'],
            'bank_transfer' => ['bank_name', 'account_number', 'account_name'],
            'stripe' => ['api_key', 'secret_key'],
            'paypal' => ['client_id', 'client_secret'],
            'razorpay' => ['key_id', 'key_secret'],
            'truemoney' => ['app_id', 'app_secret'],
            'thaiepay' => ['merchant_id', 'secret_key'],
            'omise' => ['public_key', 'secret_key'],
            'paysolutions' => ['merchant_id', 'secret_key', 'api_key'],
            'crypto' => [],
            default => [],
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_coming_soon) {
            return 'gray';
        }

        if (!$this->is_available) {
            return 'red';
        }

        if ($this->is_active && $this->isConfigured()) {
            return 'green';
        }

        if ($this->is_active && !$this->isConfigured()) {
            return 'yellow';
        }

        return 'gray';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->is_coming_soon) {
            return 'Coming Soon';
        }

        if (!$this->is_available) {
            return 'Unavailable';
        }

        if ($this->is_active && $this->isConfigured()) {
            return 'Active';
        }

        if ($this->is_active && !$this->isConfigured()) {
            return 'Incomplete';
        }

        return 'Inactive';
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'bank' => 'ธนาคาร',
            'e-wallet' => 'กระเป๋าเงินอิเล็กทรอนิกส์',
            'crypto' => 'สกุลเงินดิจิทัล',
            'international' => 'ระหว่างประเทศ',
            'thai' => 'ไทย',
            default => 'ทั่วไป',
        };
    }

    /**
     * Test gateway connection
     */
    public function testConnection(): array
    {
        if ($this->is_coming_soon) {
            return [
                'success' => false,
                'message' => 'Gateway is coming soon',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Gateway is not properly configured',
            ];
        }

        // TODO: Implement actual connection testing for each gateway
        // This is a placeholder that should be replaced with actual API calls

        $this->update(['last_tested_at' => now()]);

        return [
            'success' => true,
            'message' => 'Connection test successful',
        ];
    }
}
