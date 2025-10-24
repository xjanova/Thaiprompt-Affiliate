<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class LineOaConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'channel_id',
        'channel_secret',
        'channel_access_token',
        'webhook_url',
        'is_active',
        'require_kyc_for_withdrawal',
        'min_withdrawal_without_kyc',
        'welcome_message',
        'kyc_success_message',
        'rich_menu_id',
        'settings',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'require_kyc_for_withdrawal' => 'boolean',
        'min_withdrawal_without_kyc' => 'decimal:2',
        'welcome_message' => 'array',
        'kyc_success_message' => 'array',
        'rich_menu_id' => 'array',
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'channel_secret',
        'channel_access_token',
    ];

    /**
     * Get webhooks for this config
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(LineOaWebhook::class);
    }

    /**
     * Get messages for this config
     */
    public function messages(): HasMany
    {
        return $this->hasMany(LineOaMessage::class);
    }

    /**
     * Set encrypted channel secret
     */
    public function setChannelSecretAttribute($value)
    {
        $this->attributes['channel_secret'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted channel secret
     */
    public function getChannelSecretAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Set encrypted channel access token
     */
    public function setChannelAccessTokenAttribute($value)
    {
        $this->attributes['channel_access_token'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted channel access token
     */
    public function getChannelAccessTokenAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Check if KYC is required for withdrawal
     */
    public function requiresKycForWithdrawal(float $amount): bool
    {
        if (!$this->require_kyc_for_withdrawal) {
            return false;
        }

        return $amount >= $this->min_withdrawal_without_kyc;
    }

    /**
     * Sync with LINE API
     */
    public function syncWithLine(): bool
    {
        $this->last_synced_at = now();
        return $this->save();
    }

    /**
     * Get welcome message for new followers
     */
    public function getWelcomeMessage(): array
    {
        return $this->welcome_message ?? [
            'type' => 'text',
            'text' => 'ยินดีต้อนรับสู่ระบบของเรา! กรุณายืนยันตัวตนเพื่อใช้งานฟีเจอร์ทั้งหมด',
        ];
    }

    /**
     * Get KYC success message
     */
    public function getKycSuccessMessage(): array
    {
        return $this->kyc_success_message ?? [
            'type' => 'text',
            'text' => 'ยืนยันตัวตนสำเร็จ! คุณสามารถใช้งานระบบได้เต็มรูปแบบแล้ว',
        ];
    }

    /**
     * Scope for active configs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the active LINE OA config
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
