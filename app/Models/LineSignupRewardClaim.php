<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * บันทึกการรับรางวัล
 *
 * เก็บประวัติการรับรางวัลของผู้ใช้แต่ละคน
 *
 * @property int $id
 * @property int $user_id ผู้รับรางวัล
 * @property int $reward_id รางวัลที่รับ
 * @property int|null $signup_session_id Session การสมัคร
 * @property string $reward_type ประเภทรางวัล
 * @property float $amount จำนวน
 * @property array|null $reward_data ข้อมูลรางวัล
 * @property string $status สถานะ
 * @property \Carbon\Carbon|null $claimed_at วันที่รับ
 * @property \Carbon\Carbon|null $expires_at วันหมดอายุ
 * @property string|null $notes หมายเหตุ
 * @property string|null $error_message ข้อความ error
 */
class LineSignupRewardClaim extends Model
{
    protected $fillable = [
        'user_id',
        'reward_id',
        'signup_session_id',
        'reward_type',
        'amount',
        'reward_data',
        'status',
        'claimed_at',
        'expires_at',
        'notes',
        'error_message',
    ];

    protected $casts = [
        'reward_data' => 'array',
        'claimed_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * ความสัมพันธ์กับผู้ใช้
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับรางวัล
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(LineSignupReward::class, 'reward_id');
    }

    /**
     * ความสัมพันธ์กับ Session
     */
    public function signupSession(): BelongsTo
    {
        return $this->belongsTo(LineSignupSession::class, 'signup_session_id');
    }
}
