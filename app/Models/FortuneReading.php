<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const STATUS_BASIC_DONE = 'basic_done';

    public const STATUS_COLLECTING_BIRTHDATE = 'collecting_birthdate';

    public const STATUS_COLLECTING_QUESTIONS = 'collecting_questions';

    public const STATUS_COLLECTING_TAROT = 'collecting_tarot';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_COMPLETED = 'completed';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'bill_reference',
        'user_id',
        'facebook_user_id',
        'facebook_user_name',
        'facebook_comment_id',
        'facebook_post_id',
        'platform',
        'platform_user_id',
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
        'transfer_reported',
        'transfer_reported_at',
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
        'transfer_reported' => 'boolean',
        'transfer_reported_at' => 'datetime',
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
        'platform' => 'facebook',
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ SMS Payment Notification
     */
    public function smsNotification(): BelongsTo
    {
        return $this->belongsTo(SmsPaymentNotification::class, 'sms_notification_id');
    }

    /**
     * Scope: เฉพาะบิลลอย (ยังไม่ระบุตัวตนลูกค้า)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFloating($query)
    {
        return $query->where('is_floating', true);
    }

    /**
     * Scope: เฉพาะที่ชำระผ่าน SMS
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeViaSms($query)
    {
        return $query->whereNotNull('sms_notification_id');
    }

    /**
     * Scope: เฉพาะการทำนายที่ชำระเงินแล้ว
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope: เฉพาะการทำนายฟรี
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFree($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope: เฉพาะของผู้ใช้ Facebook คนใดคนหนึ่ง
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFacebookUser($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId);
    }

    /**
     * Scope: เฉพาะการทำนายวันนี้
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope: เฉพาะการทำนายที่ได้รับการตอบกลับแล้ว
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResponded($query)
    {
        return $query->whereNotNull('responded_at');
    }

    /**
     * Scope: เฉพาะการทำนายเชิงลึก
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeep($query)
    {
        return $query->where('reading_type', 'deep');
    }

    /**
     * Scope: เฉพาะการทำนายพื้นฐาน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBasic($query)
    {
        return $query->where('reading_type', 'basic');
    }

    /**
     * นับจำนวนการทำนายเชิงลึกฟรีของผู้ใช้ Facebook ในวันนี้
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
     * นับจำนวนการทำนายที่สำเร็จของผู้ใช้ Facebook ในวันนี้
     *
     * นับเฉพาะ reading ที่มี AI ตอบกลับแล้ว (responded_at != null)
     * ไม่นับ reading ที่ล้มเหลว (status = 'new') เพื่อไม่ให้หักสิทธิ์ฟรี
     */
    public static function countTodayReadings(string $facebookUserId): int
    {
        return self::byFacebookUser($facebookUserId)
            ->today()
            ->whereNotNull('responded_at')
            ->count();
    }

    /**
     * ตรวจสอบว่าผู้ใช้ใช้งานครบจำนวนฟรีแล้วหรือยัง
     */
    public static function hasReachedFreeLimit(string $facebookUserId, int $maxFreeReadings): bool
    {
        $todayCount = self::countTodayReadings($facebookUserId);

        return $todayCount >= $maxFreeReadings;
    }

    /**
     * เพิ่มจำนวนการดู
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * บันทึกเวลาที่ตอบกลับ
     */
    public function markAsResponded(): void
    {
        $this->update(['responded_at' => now()]);
    }

    /**
     * บันทึกการชำระเงิน
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
     * @param  mixed  $default
     * @return mixed
     */
    public function getProfileData(string $key, $default = null)
    {
        return $this->user_profile[$key] ?? $default;
    }

    /**
     * ตรวจสอบว่ามีคะแนนรีวิวหรือยัง
     */
    public function hasRating(): bool
    {
        return ! is_null($this->rating);
    }

    /**
     * ดึงคะแนนรีวิวเป็นดาว (สำหรับแสดงผล)
     */
    public function getRatingStars(): string
    {
        if (! $this->hasRating()) {
            return '';
        }

        return str_repeat('⭐', $this->rating);
    }

    /**
     * ตรวจสอบว่าเป็นคำทำนายเชิงลึกหรือไม่
     */
    public function isDeep(): bool
    {
        return $this->reading_type === 'deep';
    }

    /**
     * ตรวจสอบว่ามีรูปคำทำนายหรือไม่
     */
    public function hasReadingImage(): bool
    {
        return ! empty($this->reading_image_url);
    }

    /**
     * ตรวจสอบว่าผู้ใช้ส่งรูปมาหรือไม่
     */
    public function hasUserImage(): bool
    {
        return ! empty($this->user_image_url);
    }

    /**
     * ดึงข้อความสรุปประเภทคำทำนาย (สำหรับแสดงผล)
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
     */
    public function uniquePaymentAmount(): BelongsTo
    {
        return $this->belongsTo(UniquePaymentAmount::class, 'unique_payment_amount_id');
    }

    /**
     * Scope: ค้นหา reading ที่รอชำระเงินของผู้ใช้
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingPaymentByUser($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId)
            ->where('conversation_status', self::STATUS_PENDING_PAYMENT)
            ->whereNotNull('unique_payment_amount_id');
    }

    /**
     * ระยะเวลา timeout ของ conversation (นาที)
     *
     * conversation ที่เก่ากว่านี้จะถูกปิดอัตโนมัติ
     * pending_payment ก็ใช้ 30 นาทีเท่ากัน (บิลหมดอายุ 30 นาที)
     */
    public const CONVERSATION_TIMEOUT_MINUTES = 30;

    public const PAYMENT_TIMEOUT_MINUTES = 30;

    /**
     * ระยะเวลา timeout ของ PAID status (นาที)
     *
     * หลังชำระเงินแล้ว AI จะประมวลผลคำทำนาย (~45-60 วินาที)
     * ให้ timeout 5 นาทีเพื่อรอให้ AI ทำงานเสร็จ
     * ถ้าเกิน 5 นาที → ถือว่า AI ล้มเหลว → ปิด conversation อัตโนมัติ
     */
    public const PAID_PROCESSING_TIMEOUT_MINUTES = 5;

    /**
     * Scope: ค้นหา reading ที่กำลัง conversation อยู่
     *
     * เพิ่ม timeout เพื่อป้องกัน conversation ค้างบล็อก conversation ใหม่
     * - conversation ทั่วไป: timeout 30 นาที
     * - pending_payment: timeout 30 นาที (รอโอนเงิน)
     * - paid: timeout 5 นาที (AI กำลังประมวลผลคำทำนาย)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveConversation($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId)
            ->whereIn('conversation_status', [
                self::STATUS_AWAITING_CONFIRMATION,
                self::STATUS_BASIC_DONE,
                self::STATUS_COLLECTING_BIRTHDATE,
                self::STATUS_COLLECTING_QUESTIONS,
                self::STATUS_COLLECTING_TAROT,
                self::STATUS_PENDING_PAYMENT,
                self::STATUS_PAID, // เพิ่ม: ระหว่าง AI กำลังประมวลผลคำทำนาย
            ])
            ->where(function ($q) {
                // awaiting_confirmation + conversation ทั่วไป: timeout 30 นาที
                $q->where(function ($sub) {
                    $sub->whereIn('conversation_status', [
                        self::STATUS_AWAITING_CONFIRMATION,
                        self::STATUS_BASIC_DONE,
                        self::STATUS_COLLECTING_BIRTHDATE,
                        self::STATUS_COLLECTING_QUESTIONS,
                        self::STATUS_COLLECTING_TAROT,
                    ])
                        ->where('updated_at', '>=', now()->subMinutes(self::CONVERSATION_TIMEOUT_MINUTES));
                })
                // pending_payment: timeout 30 นาที (รอโอนเงิน)
                    ->orWhere(function ($sub) {
                        $sub->where('conversation_status', self::STATUS_PENDING_PAYMENT)
                            ->where('updated_at', '>=', now()->subMinutes(self::PAYMENT_TIMEOUT_MINUTES));
                    })
                // paid: timeout 5 นาที (AI ประมวลผล ~45-60 วินาที, ให้ 5 นาทีเพื่อความปลอดภัย)
                    ->orWhere(function ($sub) {
                        $sub->where('conversation_status', self::STATUS_PAID)
                            ->where('updated_at', '>=', now()->subMinutes(self::PAID_PROCESSING_TIMEOUT_MINUTES));
                    });
            })
            ->latest();
    }

    /**
     * ค้นหา reading ที่กำลัง conversation อยู่สำหรับผู้ใช้
     *
     * ถ้าพบ conversation ที่หมดเวลาแล้ว จะปิดอัตโนมัติ
     */
    public static function findActiveConversation(string $facebookUserId): ?self
    {
        // ปิด conversation ที่หมดเวลาอัตโนมัติ
        self::expireOldConversations($facebookUserId);

        return self::activeConversation($facebookUserId)->first();
    }

    /**
     * ปิด conversation ที่หมดเวลาอัตโนมัติ
     *
     * @return int จำนวน conversation ที่ถูกปิด
     */
    public static function expireOldConversations(string $facebookUserId): int
    {
        // ปิด conversation ทั่วไป + pending_payment ที่ค้างเกิน 30 นาที
        $expired = self::where('facebook_user_id', $facebookUserId)
            ->whereIn('conversation_status', [
                self::STATUS_AWAITING_CONFIRMATION,
                self::STATUS_BASIC_DONE,
                self::STATUS_COLLECTING_BIRTHDATE,
                self::STATUS_COLLECTING_QUESTIONS,
                self::STATUS_COLLECTING_TAROT,
                self::STATUS_PENDING_PAYMENT,
            ])
            ->where('updated_at', '<', now()->subMinutes(self::PAYMENT_TIMEOUT_MINUTES))
            ->update(['conversation_status' => self::STATUS_COMPLETED]);

        // ปิด PAID ที่ค้างเกิน 5 นาที (AI processing ล้มเหลว/timeout)
        $expiredPaid = self::where('facebook_user_id', $facebookUserId)
            ->where('conversation_status', self::STATUS_PAID)
            ->where('updated_at', '<', now()->subMinutes(self::PAID_PROCESSING_TIMEOUT_MINUTES))
            ->update(['conversation_status' => self::STATUS_COMPLETED]);

        return $expired + $expiredPaid;
    }

    /**
     * ค้นหา reading ที่รอชำระเงินโดย unique amount
     *
     * กรองเฉพาะ transaction_type = 'fortune_reading' เพื่อแยกบิลดูดวง
     * ไม่ให้ปะปนกับบิลอีคอมเมิร์ซหรือ seller
     */
    public static function findByUniqueAmount(float $amount): ?self
    {
        // กรองเฉพาะ fortune_reading เพื่อไม่ให้ match ข้ามระบบ
        $uniquePayment = UniquePaymentAmount::findMatch($amount, 'fortune_reading');

        if (! $uniquePayment) {
            return null;
        }

        return self::where('unique_payment_amount_id', $uniquePayment->id)
            ->where('conversation_status', self::STATUS_PENDING_PAYMENT)
            ->first();
    }

    /**
     * อัพเดทสถานะ conversation
     */
    public function updateConversationStatus(string $status): void
    {
        $this->update(['conversation_status' => $status]);
    }

    /**
     * เก็บข้อมูลใน conversation state
     *
     * @param  mixed  $value
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
     * @param  mixed  $default
     * @return mixed
     */
    public function getConversationState(string $key, $default = null)
    {
        return $this->conversation_state[$key] ?? $default;
    }

    /**
     * เพิ่มคำถามเข้าไปใน state
     *
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
     */
    public function getCollectedQuestions(): array
    {
        return $this->getConversationState('collected_questions', []);
    }

    /**
     * เพิ่มไพ่ยิปซีที่สุ่มได้เข้าไปใน state (เฉพาะแบบเสียเงิน)
     *
     * @param  int  $questionIndex  ลำดับคำถามที่ไพ่นี้ประกอบ (0-based)
     * @param  int  $cardId  ID ของไพ่จาก TarotCard
     * @param  string  $cardNameTh  ชื่อไพ่ภาษาไทย
     * @param  string  $cardNameEn  ชื่อไพ่ภาษาอังกฤษ
     * @param  bool  $isReversed  ไพ่กลับหัวหรือไม่
     * @param  string  $meaning  ความหมายของไพ่ตามตำแหน่ง
     * @return int จำนวนไพ่ที่เก็บไว้
     */
    public function addTarotCard(int $questionIndex, int $cardId, string $cardNameTh, string $cardNameEn, bool $isReversed, string $meaning, ?string $imageUrl = null): int
    {
        $cards = $this->getConversationState('collected_tarot_cards', []);
        $cards[] = [
            'question_index' => $questionIndex,
            'card_id' => $cardId,
            'card_name_th' => $cardNameTh,
            'card_name_en' => $cardNameEn,
            'is_reversed' => $isReversed,
            'meaning' => $meaning,
            'image_url' => $imageUrl,
        ];
        $this->setConversationState('collected_tarot_cards', $cards);

        return count($cards);
    }

    /**
     * ดึงไพ่ยิปซีที่เก็บไว้ทั้งหมด
     */
    public function getCollectedTarotCards(): array
    {
        return $this->getConversationState('collected_tarot_cards', []);
    }

    /**
     * ดึงไพ่ยิปซีสำหรับคำถามข้อที่ระบุ (0-based index)
     */
    public function getTarotCardForQuestion(int $questionIndex): ?array
    {
        $cards = $this->getCollectedTarotCards();
        foreach ($cards as $card) {
            if (($card['question_index'] ?? -1) === $questionIndex) {
                return $card;
            }
        }

        return null;
    }

    /**
     * ตรวจสอบว่ารอชำระเงินอยู่หรือไม่
     */
    public function isPendingPayment(): bool
    {
        return $this->conversation_status === self::STATUS_PENDING_PAYMENT;
    }

    /**
     * ตรวจสอบว่าเสร็จสิ้นขั้นตอนพื้นฐานแล้วหรือไม่
     */
    public function isBasicDone(): bool
    {
        return $this->conversation_status === self::STATUS_BASIC_DONE;
    }

    /**
     * บันทึกคำทำนายพื้นฐานและเปลี่ยนสถานะ
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
     * ใช้ DB::table query โดยตรงแทน Eloquent update
     * เพราะหลัง AI generation 45-60 วินาที MySQL connection อาจ stale
     * และ Eloquent $this->update() อาจ return false โดยไม่ throw exception
     */
    public function saveDeepReading(string $response, string $provider, string $model, int $tokensUsed): void
    {
        $updateData = [
            'deep_response' => $response,
            'ai_response' => $response,
            'ai_provider' => $provider,
            'ai_model' => $model,
            'tokens_used' => ($this->tokens_used ?? 0) + $tokensUsed,
            'conversation_status' => self::STATUS_COMPLETED,
            'reading_type' => 'deep',
            'updated_at' => now(),
        ];

        // ใช้ DB::table query ตรง — หลีกเลี่ยง Eloquent stale connection
        $affected = \Illuminate\Support\Facades\DB::table($this->table)
            ->where('id', $this->id)
            ->update($updateData);

        if ($affected > 0) {
            // Sync model attributes ให้ตรงกับ DB
            $this->forceFill($updateData)->syncOriginal();
            \Illuminate\Support\Facades\Log::info('Fortune: saveDeepReading สำเร็จ (DB::table)', [
                'reading_id' => $this->id,
                'affected_rows' => $affected,
            ]);
        } else {
            // Fallback: ลอง Eloquent refresh + update
            $this->refresh();
            $result = $this->update($updateData);
            \Illuminate\Support\Facades\Log::warning('Fortune: saveDeepReading fallback to Eloquent', [
                'reading_id' => $this->id,
                'eloquent_result' => $result,
            ]);

            if (! $result) {
                throw new \RuntimeException(
                    "saveDeepReading failed: DB::table affected 0 rows, Eloquent returned false for reading #{$this->id}"
                );
            }
        }
    }

    /**
     * ตั้งค่า unique payment amount และเปลี่ยนสถานะเป็นรอชำระ
     */
    public function setPendingPayment(UniquePaymentAmount $uniqueAmount): void
    {
        $updateData = [
            'unique_payment_amount_id' => $uniqueAmount->id,
            'amount_paid' => $uniqueAmount->unique_amount,
            'conversation_status' => self::STATUS_PENDING_PAYMENT,
        ];

        // Safety net: ถ้ายังไม่มี bill_reference → สร้างให้
        // กรณี reading มาจาก basic→upsell path หรือ boot creating ไม่ได้สร้าง
        if (empty($this->bill_reference)) {
            $updateData['bill_reference'] = self::generateBillReference();
        }

        // ถ้า reading_type ยังเป็น basic → เปลี่ยนเป็น deep (กำลังจะชำระเงิน)
        if ($this->reading_type !== 'deep') {
            $updateData['reading_type'] = 'deep';
        }

        $this->update($updateData);
    }

    /**
     * ยืนยันการชำระเงินและเปลี่ยนสถานะ
     */
    public function confirmPayment(?SmsPaymentNotification $notification = null): void
    {
        // ✅ Idempotent: ถ้าชำระแล้ว ไม่ต้องทำซ้ำ (ป้องกัน paid_at ถูก reset)
        if ($this->is_paid) {
            // อัพเดทเฉพาะ SMS notification info ถ้ายังไม่มี
            if ($notification && empty($this->sms_notification_id)) {
                $this->update([
                    'sms_notification_id' => $notification->id,
                    'sender_info' => $notification->sender_or_receiver,
                    'sender_bank' => $notification->bank,
                ]);
            }

            return;
        }

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

    // ============================================================
    // Bill Reference Number
    // ============================================================

    /**
     * Boot method สำหรับ auto-generate bill_reference
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reading) {
            // สร้าง bill_reference เฉพาะ deep reading (เสียเงิน) เท่านั้น
            // basic reading (ฟรี) ไม่ต้องมีเลขบิล
            if (empty($reading->bill_reference) && $reading->reading_type === 'deep') {
                $reading->bill_reference = self::generateBillReference();
            }
        });
    }

    /**
     * สร้างเลขที่บิลอ้างอิงที่ไม่ซ้ำกัน
     *
     * รูปแบบ: FTU-YYMMDD-AXXXX
     * - FTU = Fortune Reading
     * - YYMMDD = วันที่ (เช่น 260205)
     * - AXXXX = ตัวอักษร 1 ตัว + ลำดับ random 4 หลัก
     *
     * หมายเหตุ: ใช้ตัวอักษรนำหน้า random part เพื่อป้องกัน Facebook
     * detect ตัวเลข "YYMMDD-XXXXX" เป็นเลขบัญชีธนาคาร
     * (Facebook จะสร้าง Payment Card อัตโนมัติจากเลขบัญชีในข้อความ)
     */
    public static function generateBillReference(): string
    {
        $prefix = 'FTU';
        $datePart = now()->format('ymd');
        $maxAttempts = 10;

        // ตัวอักษรสำหรับนำหน้า random part (ไม่ใช้ I, O, L เพื่อไม่สับสนกับตัวเลข)
        $letters = 'ABCDEFGHJKMNPQRSTUVWXYZ';

        for ($i = 0; $i < $maxAttempts; $i++) {
            // สร้าง random: ตัวอักษร 1 ตัว + ตัวเลข 4 หลัก
            $letter = $letters[random_int(0, strlen($letters) - 1)];
            $numPart = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $reference = "{$prefix}-{$datePart}-{$letter}{$numPart}";

            // ตรวจสอบว่าซ้ำหรือไม่
            if (! self::where('bill_reference', $reference)->exists()) {
                return $reference;
            }
        }

        // Fallback: ใช้ microtime
        $uniquePart = substr(md5(microtime()), 0, 5);

        return "{$prefix}-{$datePart}-{$uniquePart}";
    }

    /**
     * ค้นหา reading จากเลขที่บิล
     */
    public static function findByBillReference(string $billReference): ?self
    {
        return self::where('bill_reference', $billReference)->first();
    }
}
