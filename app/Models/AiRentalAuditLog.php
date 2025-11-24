<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

/**
 * Model สำหรับ AI Rental Audit Logs
 *
 * เก็บ audit trail ของทุกการกระทำในระบบ
 *
 * @property int $id
 * @property int|null $user_id ID ผู้ใช้
 * @property int|null $deployment_id ID deployment
 * @property int|null $cloud_config_id ID config
 * @property string $action การกระทำ
 * @property string|null $action_category หมวดหมู่
 * @property string $action_type ประเภท
 * @property string|null $resource_type ประเภท resource
 * @property string|null $resource_id ID resource
 * @property string|null $resource_name ชื่อ resource
 * @property string $description คำอธิบาย
 * @property array|null $changes_before ข้อมูลก่อน
 * @property array|null $changes_after ข้อมูลหลัง
 * @property array|null $request_data Request data
 * @property array|null $response_data Response data
 * @property string $status สถานะ
 * @property string|null $error_message Error message
 * @property int|null $http_status_code HTTP status
 * @property string|null $ip_address IP address
 * @property string|null $user_agent User agent
 * @property string|null $session_id Session ID
 * @property string|null $request_id Request ID
 * @property string|null $country_code Country code
 * @property string|null $city City
 * @property string|null $device_type Device type
 * @property string|null $browser Browser
 * @property string|null $platform Platform
 * @property string|null $http_method HTTP method
 * @property string|null $endpoint Endpoint
 * @property string|null $url URL
 * @property string|null $referrer Referrer
 * @property int|null $duration_ms Duration
 * @property int|null $memory_usage_mb Memory usage
 * @property int|null $query_count Query count
 * @property float|null $cost_impact Cost impact
 * @property float|null $cost_before Cost before
 * @property float|null $cost_after Cost after
 * @property string $severity Severity
 * @property bool $is_sensitive Sensitive data
 * @property bool $requires_review Requires review
 * @property bool $reviewed Reviewed
 * @property \Carbon\Carbon|null $reviewed_at Reviewed at
 * @property int|null $reviewed_by Reviewed by
 * @property array|null $tags Tags
 * @property bool $is_automated Automated
 * @property string|null $triggered_by Triggered by
 * @property int|null $parent_audit_log_id Parent log ID
 * @property array|null $metadata Metadata
 * @property string|null $notes Notes
 * @property \Carbon\Carbon $action_at Action time
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiRentalAuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_rental_audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'deployment_id',
        'cloud_config_id',
        'action',
        'action_category',
        'action_type',
        'resource_type',
        'resource_id',
        'resource_name',
        'description',
        'changes_before',
        'changes_after',
        'request_data',
        'response_data',
        'status',
        'error_message',
        'http_status_code',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'country_code',
        'city',
        'device_type',
        'browser',
        'platform',
        'http_method',
        'endpoint',
        'url',
        'referrer',
        'duration_ms',
        'memory_usage_mb',
        'query_count',
        'cost_impact',
        'cost_before',
        'cost_after',
        'severity',
        'is_sensitive',
        'requires_review',
        'reviewed',
        'reviewed_at',
        'reviewed_by',
        'tags',
        'is_automated',
        'triggered_by',
        'parent_audit_log_id',
        'metadata',
        'notes',
        'action_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changes_before' => 'array',
        'changes_after' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_sensitive' => 'boolean',
        'requires_review' => 'boolean',
        'reviewed' => 'boolean',
        'is_automated' => 'boolean',
        'http_status_code' => 'integer',
        'duration_ms' => 'integer',
        'memory_usage_mb' => 'integer',
        'query_count' => 'integer',
        'cost_impact' => 'decimal:2',
        'cost_before' => 'decimal:2',
        'cost_after' => 'decimal:2',
        'action_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * ความสัมพันธ์กับ Deployment
     *
     * @return BelongsTo
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(AiRentalDeployment::class, 'deployment_id');
    }

    /**
     * ความสัมพันธ์กับ Cloud Config
     *
     * @return BelongsTo
     */
    public function cloudConfig(): BelongsTo
    {
        return $this->belongsTo(AiRentalCloudConfig::class, 'cloud_config_id');
    }

    /**
     * ความสัมพันธ์กับ Reviewer
     *
     * @return BelongsTo
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * ความสัมพันธ์กับ Parent Audit Log
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AiRentalAuditLog::class, 'parent_audit_log_id');
    }

    /**
     * Scope: Logs สำหรับ user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Logs ตาม action category
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    /**
     * Scope: Logs ตาม action type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActionType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope: Logs ที่ล้มเหลว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Logs ที่ต้องการ review
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequiresReview($query)
    {
        return $query->where('requires_review', true)
            ->where('reviewed', false);
    }

    /**
     * Scope: Logs ที่เป็น sensitive
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope: Logs ล่าสุด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('action_at', 'desc');
    }

    /**
     * Scope: Search logs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhere('error_message', 'like', "%{$search}%")
                ->orWhere('action', 'like', "%{$search}%");
        });
    }

    /**
     * บันทึก audit log
     *
     * @param array $data
     * @return static
     */
    public static function log(array $data): static
    {
        // Auto-capture request info ถ้าไม่ได้ระบุ
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = Request::ip();
        }

        if (!isset($data['user_agent'])) {
            $data['user_agent'] = Request::userAgent();
        }

        if (!isset($data['http_method'])) {
            $data['http_method'] = Request::method();
        }

        if (!isset($data['url'])) {
            $data['url'] = Request::fullUrl();
        }

        if (!isset($data['action_at'])) {
            $data['action_at'] = now();
        }

        // Set default status
        if (!isset($data['status'])) {
            $data['status'] = 'success';
        }

        // Set default severity
        if (!isset($data['severity'])) {
            $data['severity'] = 'low';
        }

        return static::create($data);
    }

    /**
     * บันทึก deployment action
     *
     * @param string $action
     * @param AiRentalDeployment $deployment
     * @param array $additionalData
     * @return static
     */
    public static function logDeploymentAction(string $action, AiRentalDeployment $deployment, array $additionalData = []): static
    {
        return static::log(array_merge([
            'user_id' => $deployment->user_id,
            'deployment_id' => $deployment->id,
            'action' => $action,
            'action_category' => 'deployment',
            'resource_type' => 'deployment',
            'resource_id' => (string) $deployment->id,
            'resource_name' => $deployment->deployment_name,
            'description' => "Deployment '{$deployment->deployment_name}' {$action}",
        ], $additionalData));
    }

    /**
     * บันทึก config action
     *
     * @param string $action
     * @param AiRentalCloudConfig $config
     * @param array $additionalData
     * @return static
     */
    public static function logConfigAction(string $action, AiRentalCloudConfig $config, array $additionalData = []): static
    {
        return static::log(array_merge([
            'user_id' => $config->user_id,
            'cloud_config_id' => $config->id,
            'action' => $action,
            'action_category' => 'configuration',
            'resource_type' => 'config',
            'resource_id' => (string) $config->id,
            'resource_name' => $config->config_name,
            'description' => "Configuration '{$config->config_name}' {$action}",
        ], $additionalData));
    }

    /**
     * ทำเครื่องหมายว่า reviewed แล้ว
     *
     * @param int $reviewerId
     * @param string|null $notes
     * @return bool
     */
    public function markAsReviewed(int $reviewerId, ?string $notes = null): bool
    {
        $this->reviewed = true;
        $this->reviewed_at = now();
        $this->reviewed_by = $reviewerId;

        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * คำนวณ changes diff
     *
     * @return array
     */
    public function getChangesDiff(): array
    {
        if (!$this->changes_before || !$this->changes_after) {
            return [];
        }

        $diff = [];
        foreach ($this->changes_after as $key => $after) {
            $before = $this->changes_before[$key] ?? null;
            if ($before !== $after) {
                $diff[$key] = [
                    'before' => $before,
                    'after' => $after,
                ];
            }
        }

        return $diff;
    }

    /**
     * ตรวจสอบว่ามี cost impact หรือไม่
     *
     * @return bool
     */
    public function hasCostImpact(): bool
    {
        return !is_null($this->cost_impact) && $this->cost_impact != 0;
    }
}
