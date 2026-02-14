<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SoftwareDownload Model
 *
 * Tracking การดาวน์โหลดซอฟต์แวร์
 *
 * @property int $id
 * @property int $software_product_id
 * @property int|null $software_license_id
 * @property int $user_id
 * @property string $download_token
 * @property \Carbon\Carbon|null $token_expires_at
 * @property bool $token_used
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $os
 * @property int|null $file_size
 * @property int|null $download_duration
 * @property string $status
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $error_message
 */
class SoftwareDownload extends Model
{
    use HasFactory;

    protected $table = 'software_downloads';

    protected $fillable = [
        'software_product_id',
        'software_license_id',
        'user_id',
        'download_token',
        'token_expires_at',
        'token_used',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'file_size',
        'download_duration',
        'status',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'token_used' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
        'download_duration' => 'integer',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';

    const STATUS_DOWNLOADING = 'downloading';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * ความสัมพันธ์กับ SoftwareProduct
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class, 'software_product_id');
    }

    /**
     * ความสัมพันธ์กับ SoftwareLicense
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(SoftwareLicense::class, 'software_license_id');
    }

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Completed downloads
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Today's downloads
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * ตรวจสอบว่า token ยังใช้งานได้หรือไม่
     */
    public function isTokenValid(): bool
    {
        if ($this->token_used) {
            return false;
        }

        if ($this->token_expires_at && $this->token_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Mark token as used
     */
    public function markTokenUsed(): void
    {
        $this->update([
            'token_used' => true,
            'status' => self::STATUS_DOWNLOADING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark download as completed
     */
    public function markCompleted(?int $duration = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'download_duration' => $duration,
        ]);
    }

    /**
     * Mark download as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }
}
