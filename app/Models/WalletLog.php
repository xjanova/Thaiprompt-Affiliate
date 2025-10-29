<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only created_at is used

    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'severity',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->ip_address)) {
                $log->ip_address = request()->ip();
            }
            if (empty($log->user_agent)) {
                $log->user_agent = request()->userAgent();
            }
        });
    }

    /**
     * Get the wallet that owns the log
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user that owns the log
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction related to this log
     */
    public function transaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Get action icon
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'login' => '🔐',
            'logout' => '🚪',
            'transaction_attempt' => '🔄',
            'transaction_success' => '✅',
            'transaction_failed' => '❌',
            'pin_changed' => '🔑',
            'pin_failed' => '🚫',
            'two_factor_enabled' => '🛡️',
            'two_factor_disabled' => '🔓',
            'wallet_locked' => '🔒',
            'wallet_unlocked' => '🔓',
            'suspicious_activity' => '⚠️',
            'settings_changed' => '⚙️',
            default => '📝',
        };
    }

    /**
     * Get action label
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'login' => 'เข้าสู่ระบบ',
            'logout' => 'ออกจากระบบ',
            'transaction_attempt' => 'พยายามทำธุรกรรม',
            'transaction_success' => 'ธุรกรรมสำเร็จ',
            'transaction_failed' => 'ธุรกรรมล้มเหลว',
            'pin_changed' => 'เปลี่ยน PIN',
            'pin_failed' => 'PIN ไม่ถูกต้อง',
            'two_factor_enabled' => 'เปิด 2FA',
            'two_factor_disabled' => 'ปิด 2FA',
            'wallet_locked' => 'ล็อกกระเป๋าเงิน',
            'wallet_unlocked' => 'ปลดล็อกกระเป๋าเงิน',
            'suspicious_activity' => 'พบกิจกรรมน่าสงสัย',
            'settings_changed' => 'เปลี่ยนการตั้งค่า',
            default => 'อื่นๆ',
        };
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'info' => 'blue',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get severity label
     */
    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            'info' => 'ทั่วไป',
            'warning' => 'คำเตือน',
            'critical' => 'ร้ายแรง',
            default => 'ไม่ทราบ',
        };
    }
}
