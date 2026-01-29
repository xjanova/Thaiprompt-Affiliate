<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * FortuneReading Model
 *
 * จัดการบันทึกการทำนายแต่ละครั้ง
 * รองรับทั้งผู้ใช้ที่สมัครสมาชิกและไม่สมัครสมาชิก
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $facebook_user_id
 * @property string|null $facebook_user_name
 * @property string|null $facebook_comment_id
 * @property string|null $facebook_post_id
 * @property array $questions
 * @property array|null $categories
 * @property string $ai_response
 * @property array|null $user_profile
 * @property array|null $user_posts_context
 * @property string $ai_provider
 * @property string|null $ai_model
 * @property int|null $tokens_used
 * @property bool $is_paid
 * @property float $amount_paid
 * @property \Carbon\Carbon|null $paid_at
 * @property string $response_type
 * @property \Carbon\Carbon|null $responded_at
 * @property int $view_count
 * @property int|null $rating
 * @property string|null $feedback
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FortuneReading extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'fortune_readings';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'facebook_user_id',
        'facebook_user_name',
        'facebook_comment_id',
        'facebook_post_id',
        'questions',
        'categories',
        'ai_response',
        'user_profile',
        'user_posts_context',
        'ai_provider',
        'ai_model',
        'tokens_used',
        'is_paid',
        'amount_paid',
        'paid_at',
        'response_type',
        'responded_at',
        'view_count',
        'rating',
        'feedback',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'questions' => 'array',
        'categories' => 'array',
        'user_profile' => 'array',
        'user_posts_context' => 'array',
        'is_paid' => 'boolean',
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
        'responded_at' => 'datetime',
        'view_count' => 'integer',
        'tokens_used' => 'integer',
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้นของ attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_paid' => false,
        'amount_paid' => 0,
        'view_count' => 0,
        'response_type' => 'private_message',
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
     * Scope: เฉพาะการทำนายที่ชำระเงินแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope: เฉพาะการทำนายฟรี
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFree($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope: เฉพาะของผู้ใช้ Facebook คนใดคนหนึ่ง
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $facebookUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFacebookUser($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId);
    }

    /**
     * Scope: เฉพาะการทำนายวันนี้
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope: เฉพาะการทำนายที่ได้รับการตอบกลับแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResponded($query)
    {
        return $query->whereNotNull('responded_at');
    }

    /**
     * นับจำนวนการทำนายของผู้ใช้ Facebook ในวันนี้
     *
     * @param string $facebookUserId
     * @return int
     */
    public static function countTodayReadings(string $facebookUserId): int
    {
        return self::byFacebookUser($facebookUserId)
            ->today()
            ->count();
    }

    /**
     * ตรวจสอบว่าผู้ใช้ใช้งานครบจำนวนฟรีแล้วหรือยัง
     *
     * @param string $facebookUserId
     * @param int $maxFreeReadings
     * @return bool
     */
    public static function hasReachedFreeLimit(string $facebookUserId, int $maxFreeReadings): bool
    {
        $todayCount = self::countTodayReadings($facebookUserId);
        return $todayCount >= $maxFreeReadings;
    }

    /**
     * เพิ่มจำนวนการดู
     *
     * @return void
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * บันทึกเวลาที่ตอบกลับ
     *
     * @return void
     */
    public function markAsResponded(): void
    {
        $this->update(['responded_at' => now()]);
    }

    /**
     * บันทึกการชำระเงิน
     *
     * @param float $amount
     * @return void
     */
    public function markAsPaid(float $amount = 0): void
    {
        $this->update([
            'is_paid' => true,
            'amount_paid' => $amount,
            'paid_at' => now(),
        ]);
    }

    /**
     * ดึงคำถามทั้งหมดเป็น string
     *
     * @return string
     */
    public function getQuestionsText(): string
    {
        if (empty($this->questions)) {
            return '';
        }

        return implode("\n", $this->questions);
    }

    /**
     * ดึงข้อมูลผู้ใช้จากโปรไฟล์ Facebook
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getProfileData(string $key, $default = null)
    {
        return $this->user_profile[$key] ?? $default;
    }

    /**
     * ตรวจสอบว่ามีคะแนนรีวิวหรือยัง
     *
     * @return bool
     */
    public function hasRating(): bool
    {
        return !is_null($this->rating);
    }

    /**
     * ดึงคะแนนรีวิวเป็นดาว (สำหรับแสดงผล)
     *
     * @return string
     */
    public function getRatingStars(): string
    {
        if (!$this->hasRating()) {
            return '';
        }

        return str_repeat('⭐', $this->rating);
    }
}
