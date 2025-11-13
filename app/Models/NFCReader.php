<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NFCReader extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nfc_readers';

    protected $fillable = [
        'name',
        'reader_id',
        'location',
        'serial_number',
        'description',
        'ip_address',
        'mac_address',
        'status',
        'settings',
        'last_heartbeat',
        'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_heartbeat' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * สถานะของเครื่องอ่านบัตร
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';

    /**
     * Get the user who created this reader
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all transactions from this reader
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(NFCTransaction::class, 'nfc_reader_id');
    }

    /**
     * Get recent transactions
     */
    public function recentTransactions($limit = 10)
    {
        return $this->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if reader is online (heartbeat within last 5 minutes)
     */
    public function isOnline(): bool
    {
        if (!$this->last_heartbeat) {
            return false;
        }

        return $this->last_heartbeat->diffInMinutes(now()) <= 5;
    }

    /**
     * Check if reader is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Update heartbeat
     */
    public function updateHeartbeat(): void
    {
        $this->update(['last_heartbeat' => now()]);
    }

    /**
     * Activate reader
     */
    public function activate(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Deactivate reader
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    /**
     * Set to maintenance mode
     */
    public function setMaintenance(): bool
    {
        return $this->update(['status' => self::STATUS_MAINTENANCE]);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_MAINTENANCE => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'ใช้งานได้',
            self::STATUS_INACTIVE => 'ไม่ได้ใช้งาน',
            self::STATUS_MAINTENANCE => 'ปรับปรุง',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * Get online status label
     */
    public function getOnlineStatusAttribute(): string
    {
        return $this->isOnline() ? 'ออนไลน์' : 'ออฟไลน์';
    }

    /**
     * Scope for active readers
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for online readers
     */
    public function scopeOnline($query)
    {
        return $query->where('last_heartbeat', '>=', now()->subMinutes(5));
    }

    /**
     * Scope for specific location
     */
    public function scopeAtLocation($query, string $location)
    {
        return $query->where('location', $location);
    }
}
