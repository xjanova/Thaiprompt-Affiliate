<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * EmailCampaignRecipient Model
 *
 * Model สำหรับจัดการผู้รับในแคมเปญอีเมล
 *
 * @property int $id
 * @property int $campaign_id แคมเปญที่เกี่ยวข้อง
 * @property int $user_id ผู้รับอีเมล
 * @property string $email อีเมลที่ส่งไป
 * @property string $status สถานะการส่ง
 * @property \Carbon\Carbon|null $sent_at ส่งเมื่อ
 * @property \Carbon\Carbon|null $delivered_at ส่งถึงเมื่อ
 * @property \Carbon\Carbon|null $opened_at เปิดอ่านเมื่อ
 * @property \Carbon\Carbon|null $clicked_at คลิกเมื่อ
 * @property \Carbon\Carbon|null $bounced_at ตีกลับเมื่อ
 * @property int $open_count จำนวนครั้งที่เปิด
 * @property int $click_count จำนวนครั้งที่คลิก
 * @property string|null $error_message ข้อความ Error
 * @property string|null $provider_message_id Message ID จาก Provider
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EmailCampaignRecipient extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'email_campaign_recipients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'campaign_id',
        'user_id',
        'email',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'open_count',
        'click_count',
        'error_message',
        'provider_message_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ EmailCampaign
     *
     * @return BelongsTo
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
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
     * เช็คว่าอีเมลถูกส่งแล้วหรือไม่
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    /**
     * เช็คว่าอีเมลส่งถึงแล้วหรือไม่
     *
     * @return bool
     */
    public function isDelivered(): bool
    {
        return !is_null($this->delivered_at);
    }

    /**
     * เช็คว่าอีเมลถูกเปิดอ่านแล้วหรือไม่
     *
     * @return bool
     */
    public function isOpened(): bool
    {
        return !is_null($this->opened_at);
    }

    /**
     * เช็คว่าอีเมลถูกคลิกแล้วหรือไม่
     *
     * @return bool
     */
    public function isClicked(): bool
    {
        return !is_null($this->clicked_at);
    }

    /**
     * เช็คว่าอีเมลตีกลับหรือไม่
     *
     * @return bool
     */
    public function isBounced(): bool
    {
        return !is_null($this->bounced_at);
    }

    /**
     * เช็คว่าอีเมลล้มเหลวหรือไม่
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope: ผู้รับที่ยังไม่ได้ส่ง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: ผู้รับที่ส่งแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'opened', 'clicked']);
    }

    /**
     * Scope: ผู้รับที่เปิดอ่านแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpened($query)
    {
        return $query->whereIn('status', ['opened', 'clicked'])
            ->whereNotNull('opened_at');
    }

    /**
     * Scope: ผู้รับที่คลิกแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClicked($query)
    {
        return $query->where('status', 'clicked')
            ->whereNotNull('clicked_at');
    }

    /**
     * Scope: ผู้รับที่ตีกลับ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBounced($query)
    {
        return $query->where('status', 'bounced');
    }

    /**
     * Scope: ผู้รับที่ล้มเหลว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
