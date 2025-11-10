<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'key',
        'prefix',
        'user_id',
        'description',
        'is_active',
        'allowed_endpoints',
        'allowed_ips',
        'scopes',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'monthly_quota',
        'monthly_usage',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_endpoints' => 'array',
        'allowed_ips' => 'array',
        'scopes' => 'array',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_per_hour' => 'integer',
        'rate_limit_per_day' => 'integer',
        'monthly_quota' => 'integer',
        'monthly_usage' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'key', // ซ่อน API key ตัวเต็ม
    ];

    /**
     * Get the user that owns the API key
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get usage logs
     */
    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    /**
     * Get quotas
     */
    public function quotas()
    {
        return $this->hasMany(ApiQuota::class);
    }

    /**
     * Generate new API key
     */
    public static function generate($name, $userId = null, $attributes = [])
    {
        $prefix = 'sk_' . (app()->environment('production') ? 'live' : 'test') . '_';
        $randomKey = Str::random(48);
        $fullKey = $prefix . $randomKey;

        return static::create(array_merge([
            'name' => $name,
            'key' => hash('sha256', $fullKey),
            'prefix' => $prefix . substr($randomKey, 0, 8) . '...',
            'user_id' => $userId,
        ], $attributes));
    }

    /**
     * Verify API key
     */
    public static function verify($key)
    {
        $hashedKey = hash('sha256', $key);
        return static::where('key', $hashedKey)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Check if API key can access endpoint
     */
    public function canAccessEndpoint($endpointId)
    {
        if (empty($this->allowed_endpoints)) {
            return true; // ไม่ได้จำกัด endpoints
        }

        return in_array($endpointId, $this->allowed_endpoints);
    }

    /**
     * Check if request is from allowed IP
     */
    public function isIpAllowed($ip)
    {
        if (empty($this->allowed_ips)) {
            return true; // ไม่ได้จำกัด IP
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if has scope
     */
    public function hasScope($scope)
    {
        if (empty($this->scopes)) {
            return true; // ไม่ได้จำกัด scopes
        }

        return in_array($scope, $this->scopes);
    }

    /**
     * Increment usage
     */
    public function incrementUsage()
    {
        $this->increment('monthly_usage');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if quota exceeded
     */
    public function isQuotaExceeded()
    {
        if (!$this->monthly_quota) {
            return false;
        }

        return $this->monthly_usage >= $this->monthly_quota;
    }

    /**
     * Get masked key for display
     */
    public function getMaskedKeyAttribute()
    {
        return $this->prefix;
    }

    /**
     * Scope active keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Reset monthly usage (ใช้ใน scheduler)
     */
    public static function resetMonthlyUsage()
    {
        static::query()->update(['monthly_usage' => 0]);
    }
}
