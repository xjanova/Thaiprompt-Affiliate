<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Core Usage Log Model
 *
 * เก็บ log การใช้งาน AI features
 * ใช้สำหรับติดตาม quota, analytics, และ billing
 *
 * @property int $id
 * @property int $tenant_id Tenant ที่ใช้งาน
 * @property int $feature_id Feature ที่ถูกใช้งาน
 * @property int|null $user_id ผู้ใช้งาน
 * @property string $action การกระทำ
 * @property string $status สถานะการใช้งาน
 * @property int $usage_amount จำนวนที่ใช้
 * @property string|null $usage_unit หน่วย
 * @property int|null $response_time เวลาตอบสนอง (ms)
 * @property int|null $tokens_used จำนวน tokens
 * @property float|null $cost ค่าใช้จ่าย
 * @property string|null $request_data ข้อมูล request
 * @property string|null $response_data ข้อมูล response
 * @property string|null $error_message ข้อความ error
 * @property string|null $ip_address IP address
 * @property string|null $user_agent User agent
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $created_at
 */
class AICoreUsageLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_usage_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // ใช้แค่ created_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'feature_id',
        'user_id',
        'action',
        'status',
        'usage_amount',
        'usage_unit',
        'response_time',
        'tokens_used',
        'cost',
        'request_data',
        'response_data',
        'error_message',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'usage_amount' => 'integer',
        'response_time' => 'integer',
        'tokens_used' => 'integer',
        'cost' => 'decimal:4',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Tenant
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(AICoreTenant::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Feature
     *
     * @return BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(AICoreFeature::class, 'feature_id');
    }

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: เฉพาะ logs ที่สำเร็จ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: เฉพาะ logs ที่ล้มเหลว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: กรองตามช่วงเวลา
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon|null $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $from, $to = null)
    {
        $query->where('created_at', '>=', $from);

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * ตรวจสอบว่าการใช้งานสำเร็จหรือไม่
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * ตรวจสอบว่าการใช้งานล้มเหลวหรือไม่
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
