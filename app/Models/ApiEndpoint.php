<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiEndpoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'path',
        'method',
        'category',
        'description',
        'parameters',
        'response_example',
        'is_active',
        'requires_auth',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'allowed_roles',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_auth' => 'boolean',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_per_hour' => 'integer',
        'rate_limit_per_day' => 'integer',
        'allowed_roles' => 'array',
        'priority' => 'integer',
    ];

    /**
     * Get usage logs for this endpoint
     */
    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    /**
     * Scope เฉพาะ endpoints ที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope เฉพาะหมวดหมู่
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope เฉพาะ method
     */
    public function scopeMethod($query, $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * ตรวจสอบว่าบทบาทสามารถเข้าถึงได้หรือไม่
     */
    public function canAccessByRole($role)
    {
        if (empty($this->allowed_roles)) {
            return true; // ไม่ได้จำกัดบทบาท
        }

        return in_array($role, $this->allowed_roles);
    }

    /**
     * Get endpoint signature (path + method)
     */
    public function getSignatureAttribute()
    {
        return strtoupper($this->method) . ' ' . $this->path;
    }

    /**
     * Get total requests count
     */
    public function getTotalRequestsAttribute()
    {
        return $this->usageLogs()->count();
    }

    /**
     * Get success rate
     */
    public function getSuccessRateAttribute()
    {
        $total = $this->usageLogs()->count();
        if ($total === 0) {
            return 0;
        }

        $successful = $this->usageLogs()
            ->whereBetween('response_code', [200, 299])
            ->count();

        return round(($successful / $total) * 100, 2);
    }
}
