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
 * @property string $reading_type ประเภทคำทำนาย: basic = พื้นฐาน, deep = เชิงลึก
 * @property string|null $reading_image_url URL รูปคำทำนายที่สร้างส่งให้ผู้ใช้
 * @property string|null $user_image_url URL รูปที่ผู้ใช้ส่งมาผ่าน Messenger
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
     * สถานะ conversation ที่เป็นไปได้
     */
    public const STATUS_NEW = 'new';
    public const STATUS_BASIC_DONE = 'basic_done';
    public const STATUS_COLLECTING_BIRTHDATE = 'collecting_birthdate';
    public const STATUS_COLLECTING_QUESTIONS = 'collecting_questions';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';

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
        'basic_response',
        'deep_response',
        'user_profile',
        'user_posts_context',
        'birth_date',
        'ai_provider',
        'ai_model',
        'tokens_used',
        'is_paid',
        'amount_paid',
        'paid_at',
        'sms_notification_id',
        'unique_payment_amount_id',
        'sender_info',
        'sender_bank',
        'is_floating',
        'response_type',
        'responded_at',
        'reading_type',
        'conversation_status',
        'conversation_state',
        'reading_image_url',
        'user_image_url',
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
        'conversation_state' => 'array',
        'birth_date' => 'date',
        'is_paid' => 'boolean',
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
        'is_floating' => 'boolean',
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
        'reading_type' => 'basic',
        'conversation_status' => 'new',
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
     * ความสัมพันธ์กับ SMS Payment Notification
     *
     * @return BelongsTo
     */
    public function smsNotification(): BelongsTo
    {
        return $this->belongsTo(SmsPaymentNotification::class, 'sms_notification_id');
    }

    /**
     * Scope: เฉพาะบิลลอย (ยังไม่ระบุตัวตนลูกค้า)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFloating($query)
    {
        return $query->where('is_floating', true);
    }

    /**
     * Scope: เฉพาะที่ชำระผ่าน SMS
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeViaSms($query)
    {
        return $query->whereNotNull('sms_notification_id');
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
     * Scope: เฉพาะการทำนายเชิงลึก
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeep($query)
    {
        return $query->where('reading_type', 'deep');
    }

    /**
     * Scope: เฉพาะการทำนายพื้นฐาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBasic($query)
    {
        return $query->where('reading_type', 'basic');
    }

    /**
     * นับจำนวนการทำนายเชิงลึกฟรีของผู้ใช้ Facebook ในวันนี้
     *
     * @param string $facebookUserId
     * @return int
     */
    public static function countTodayDeepReadings(string $facebookUserId): int
    {
        return self::byFacebookUser($facebookUserId)
            ->today()
            ->deep()
            ->free()
            ->count();
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

    /**
     * ตรวจสอบว่าเป็นคำทำนายเชิงลึกหรือไม่
     *
     * @return bool
     */
    public function isDeep(): bool
    {
        return $this->reading_type === 'deep';
    }

    /**
     * ตรวจสอบว่ามีรูปคำทำนายหรือไม่
     *
     * @return bool
     */
    public function hasReadingImage(): bool
    {
        return !empty($this->reading_image_url);
    }

    /**
     * ตรวจสอบว่าผู้ใช้ส่งรูปมาหรือไม่
     *
     * @return bool
     */
    public function hasUserImage(): bool
    {
        return !empty($this->user_image_url);
    }

    /**
     * ดึงข้อความสรุปประเภทคำทำนาย (สำหรับแสดงผล)
     *
     * @return string
     */
    public function getReadingTypeLabel(): string
    {
        return match ($this->reading_type) {
            'deep' => '🌟 เชิงลึก',
            default => '🔮 พื้นฐาน',
        };
    }

    // ============================================================
    // Conversation State Management
    // ============================================================

    /**
     * ความสัมพันธ์กับ UniquePaymentAmount
     *
     * @return BelongsTo
     */
    public function uniquePaymentAmount(): BelongsTo
    {
        return $this->belongsTo(UniquePaymentAmount::class, 'unique_payment_amount_id');
    }

    /**
     * Scope: ค้นหา reading ที่รอชำระเงินของผู้ใช้
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $facebookUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingPaymentByUser($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId)
            ->where('conversation_status', self::STATUS_PENDING_PAYMENT)
            ->whereNotNull('unique_payment_amount_id');
    }

    /**
     * Scope: ค้นหา reading ที่กำลัง conversation อยู่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $facebookUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveConversation($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId)
            ->whereIn('conversation_status', [
                self::STATUS_BASIC_DONE,
                self::STATUS_COLLECTING_BIRTHDATE,
                self::STATUS_COLLECTING_QUESTIONS,
                self::STATUS_PENDING_PAYMENT,
            ])
            ->latest();
    }

    /**
     * ค้นหา reading ที่กำลัง conversation อยู่สำหรับผู้ใช้
     *
     * @param string $facebookUserId
     * @return self|null
     */
    public static function findActiveConversation(string $facebookUserId): ?self
    {
        return self::activeConversation($facebookUserId)->first();
    }

    /**
     * ค้นหา reading ที่รอชำระเงินโดย unique amount
     *
     * @param float $amount
     * @return self|null
     */
    public static function findByUniqueAmount(float $amount): ?self
    {
        $uniquePayment = UniquePaymentAmount::findMatch($amount);

        if (!$uniquePayment) {
            return null;
        }

        return self::where('unique_payment_amount_id', $uniquePayment->id)
            ->where('conversation_status', self::STATUS_PENDING_PAYMENT)
            ->first();
    }

    /**
     * อัพเดทสถานะ conversation
     *
     * @param string $status
     * @return void
     */
    public function updateConversationStatus(string $status): void
    {
        $this->update(['conversation_status' => $status]);
    }

    /**
     * เก็บข้อมูลใน conversation state
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setConversationState(string $key, $value): void
    {
        $state = $this->conversation_state ?? [];
        $state[$key] = $value;
        $this->update(['conversation_state' => $state]);
    }

    /**
     * ดึงข้อมูลจาก conversation state
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConversationState(string $key, $default = null)
    {
        return $this->conversation_state[$key] ?? $default;
    }

    /**
     * เพิ่มคำถามเข้าไปใน state
     *
     * @param string $question
     * @return int จำนวนคำถามปัจจุบัน
     */
    public function addQuestion(string $question): int
    {
        $questions = $this->getConversationState('collected_questions', []);
        $questions[] = $question;
        $this->setConversationState('collected_questions', $questions);

        return count($questions);
    }

    /**
     * ดึงคำถามที่เก็บไว้ทั้งหมด
     *
     * @return array
     */
    public function getCollectedQuestions(): array
    {
        return $this->getConversationState('collected_questions', []);
    }

    /**
     * ตรวจสอบว่ารอชำระเงินอยู่หรือไม่
     *
     * @return bool
     */
    public function isPendingPayment(): bool
    {
        return $this->conversation_status === self::STATUS_PENDING_PAYMENT;
    }

    /**
     * ตรวจสอบว่าเสร็จสิ้นขั้นตอนพื้นฐานแล้วหรือไม่
     *
     * @return bool
     */
    public function isBasicDone(): bool
    {
        return $this->conversation_status === self::STATUS_BASIC_DONE;
    }

    /**
     * บันทึกคำทำนายพื้นฐานและเปลี่ยนสถานะ
     *
     * @param string $response
     * @param string $provider
     * @param string $model
     * @param int $tokensUsed
     * @return void
     */
    public function saveBasicReading(string $response, string $provider, string $model, int $tokensUsed): void
    {
        $this->update([
            'basic_response' => $response,
            'ai_response' => $response,
            'ai_provider' => $provider,
            'ai_model' => $model,
            'tokens_used' => $tokensUsed,
            'conversation_status' => self::STATUS_BASIC_DONE,
            'responded_at' => now(),
        ]);
    }

    /**
     * บันทึกคำทำนายละเอียดหลังชำระเงิน
     *
     * @param string $response
     * @param string $provider
     * @param string $model
     * @param int $tokensUsed
     * @return void
     */
    public function saveDeepReading(string $response, string $provider, string $model, int $tokensUsed): void
    {
        $this->update([
            'deep_response' => $response,
            'ai_response' => $response,
            'ai_provider' => $provider,
            'ai_model' => $model,
            'tokens_used' => ($this->tokens_used ?? 0) + $tokensUsed,
            'conversation_status' => self::STATUS_COMPLETED,
            'reading_type' => 'deep',
        ]);
    }

    /**
     * ตั้งค่า unique payment amount และเปลี่ยนสถานะเป็นรอชำระ
     *
     * @param UniquePaymentAmount $uniqueAmount
     * @return void
     */
    public function setPendingPayment(UniquePaymentAmount $uniqueAmount): void
    {
        $this->update([
            'unique_payment_amount_id' => $uniqueAmount->id,
            'amount_paid' => $uniqueAmount->unique_amount,
            'conversation_status' => self::STATUS_PENDING_PAYMENT,
        ]);
    }

    /**
     * ยืนยันการชำระเงินและเปลี่ยนสถานะ
     *
     * @param SmsPaymentNotification|null $notification
     * @return void
     */
    public function confirmPayment(?SmsPaymentNotification $notification = null): void
    {
        $updateData = [
            'is_paid' => true,
            'paid_at' => now(),
            'conversation_status' => self::STATUS_PAID,
        ];

        if ($notification) {
            $updateData['sms_notification_id'] = $notification->id;
            $updateData['sender_info'] = $notification->sender_or_receiver;
            $updateData['sender_bank'] = $notification->bank;
        }

        $this->update($updateData);

        // อัพเดท unique payment amount เป็น used
        if ($this->unique_payment_amount_id) {
            UniquePaymentAmount::where('id', $this->unique_payment_amount_id)
                ->update([
                    'status' => 'used',
                    'matched_at' => now(),
                ]);
        }
    }
}
