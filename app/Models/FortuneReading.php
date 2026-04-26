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
        // Admin Takeover Fields (ระบบเทคโอเวอร์ — แม่หมอ/แอดมินคุยแทน AI)
        'admin_takeover_until',
        'admin_takeover_by',
        'admin_takeover_reason',
        'admin_takeover_started_at',
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
        'admin_takeover_until' => 'datetime',
        'admin_takeover_started_at' => 'datetime',
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
        $profile = $this->user_profile;

        return is_array($profile) ? ($profile[$key] ?? $default) : $default;
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
     * หลังชำระเงินแล้ว AI จะประมวลผลคำทำนาย (~45-90 วินาที + retry)
     * ให้ timeout 10 นาทีเพื่อรอให้ AI ทำงานเสร็จ (รวม retry + ไพ่ยิปซี + throttle delay)
     * ถ้าเกิน 10 นาที → ถือว่า AI ล้มเหลว → ปิด conversation อัตโนมัติ
     */
    public const PAID_PROCESSING_TIMEOUT_MINUTES = 10;

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
     * ปิด conversation ที่หมดเวลาอัตโนมัติ (เฉพาะ user ที่ระบุ)
     *
     * @return int จำนวน conversation ที่ถูกปิด
     */
    public static function expireOldConversations(string $facebookUserId): int
    {
        return self::expireOldConversationsQuery(
            self::where('facebook_user_id', $facebookUserId)
        );
    }

    /**
     * ปิด conversation ที่หมดเวลาทั้งระบบ (global — ใช้จาก scheduled command)
     *
     * @return int จำนวน conversation ที่ถูกปิด
     */
    public static function expireAllOldConversations(): int
    {
        return self::expireOldConversationsQuery(self::query());
    }

    /**
     * 🎯 Phase K — ก่อนยกเลิกบิล ส่ง DM "closing pitch" เพื่อกระตุ้นอีกรอบ
     *
     * ตรรกะ:
     *   - หาบิลที่ค้าง 25 นาที (5 นาทีก่อนหมดอายุ) ที่ยังไม่เคยเตือน
     *   - ส่ง DM message ที่ reframe ราคา (เทียบค่ากาแฟ) + เน้นว่า
     *     การทำนายใช้ดาวเจ้าชนะ + ไพ่ที่พลังจิตลูกค้าเลือกเอง
     *   - mark state `expiry_reminder_sent_at` กันส่งซ้ำ
     *
     * ⚠️ Best-effort: ถ้า platform ส่งไม่สำเร็จ (FB 24hr window หมด) → mark ส่งแล้วอยู่ดี
     *    เพื่อกันวนส่ง
     *
     * @return int  จำนวน reminder ที่ส่งสำเร็จ
     */
    public static function sendExpiryReminders(): int
    {
        // หาบิลอายุ 25-30 นาที (window 5 นาทีก่อนหมด)
        //   เก่าสุด 30 นาที = now - PAYMENT_TIMEOUT_MINUTES  (ใกล้หมด)
        //   ใหม่สุด 25 นาที = now - (PAYMENT_TIMEOUT_MINUTES - 5) (เริ่มเตือน)
        $readings = self::where('conversation_status', self::STATUS_PENDING_PAYMENT)
            ->where('is_paid', false)
            ->whereNotNull('unique_payment_amount_id')
            ->whereBetween('updated_at', [
                now()->subMinutes(self::PAYMENT_TIMEOUT_MINUTES),
                now()->subMinutes(max(1, self::PAYMENT_TIMEOUT_MINUTES - 5)),
            ])
            ->get();

        if ($readings->isEmpty()) {
            return 0;
        }

        $sent = 0;

        $channelManager = null;
        try {
            $channelManager = app(\App\Services\FortuneChannelManager::class);
        } catch (\Throwable $e) {
            \Log::warning('FortuneReading::sendExpiryReminders — channel manager ไม่พร้อม', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        foreach ($readings as $reading) {
            // ข้ามถ้าเคยเตือนแล้ว
            if (! empty($reading->getConversationState('expiry_reminder_sent_at'))) {
                continue;
            }

            $message = self::buildExpiryReminderMessage($reading);
            $platform = $reading->platform ?? 'facebook';
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

            if (empty($userId)) {
                continue;
            }

            try {
                $platformService = $channelManager->getPlatform($platform);
                if ($platformService) {
                    $platformService->sendMessage($userId, $message);
                    $sent++;

                    \Log::info('FortuneReading: ส่ง expiry reminder DM', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'bill_reference' => $reading->bill_reference,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('FortuneReading: expiry reminder DM ล้มเหลว (best-effort)', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'error' => $e->getMessage(),
                ]);
            }

            // ⚠️ mark sent แม้ fail — กันวนส่งซ้ำใน cron tick ถัดไป
            $reading->setConversationState('expiry_reminder_sent_at', now()->toIso8601String());
        }

        return $sent;
    }

    /**
     * 🎯 Phase K — สร้างข้อความ closing pitch (4 variants, rotate ตาม reading ID)
     *
     * ทุก variant สะท้อนสาร 3 อย่าง:
     *   1. ราคาเทียบกับของธรรมดา (ค่ากาแฟ/ที่ปรึกษา)
     *   2. การทำนายอิงดาวเจ้าชนะ (ไม่ใช่คำตอบ generic)
     *   3. ไพ่ที่สุ่มออกมา มาจากพลังจิตของลูกค้าเอง (จิตตั้งมั่น = ไพ่ถึง)
     */
    protected static function buildExpiryReminderMessage(self $reading): string
    {
        $expiresAt = $reading->updated_at->copy()->addMinutes(self::PAYMENT_TIMEOUT_MINUTES);
        $remainingMinutes = (int) max(1, ceil(now()->diffInMinutes($expiresAt, false)));

        // 🎯 Phase L — ราคา: ใช้ amount ของ bill นั้นก่อน (คือราคาที่ลูกค้าเห็น)
        //   ถ้าไม่มี → fallback ไปดึงจาก admin settings (deep_reading_price → reading_price)
        //   ถ้า settings ก็ไม่มี → 39 (ค่าเริ่มต้นจริง ไม่ใช่ 49)
        $price = (int) ($reading->amount_paid ?? 0);
        if ($price <= 0) {
            try {
                $settings = \App\Models\FortuneTellingSetting::getSettings();
                $settingPrice = (float) ($settings->deep_reading_price ?? 0);
                if ($settingPrice <= 0) {
                    $settingPrice = (float) ($settings->reading_price ?? 0);
                }
                $price = (int) ($settingPrice > 0 ? $settingPrice : 39);
            } catch (\Throwable $e) {
                $price = 39;
            }
        }

        $variants = [
            "🔮 บิลดูดวงยังรออยู่นะคะ — อีก {$remainingMinutes} นาทีจะหมดอายุ\n\n"
                ."☕ ค่าครู {$price} บาท น้อยกว่าค่ากาแฟ 1 แก้วเสียอีก\n"
                ."แต่หมอวิเคราะห์จาก **ดาวเจ้าชนะของเจ้าชะตาเอง**\n"
                ."ไพ่ที่เปิดก็มาจากพลังจิตของเจ้าชะตา\n"
                ."ไม่ต่างจากจับไพ่เอง — จิตตั้งมั่น ดาวก็ส่งสัญญาณมาแล้ว ✨\n\n"
                ."ถ้าพร้อม → โอนมาได้เลย 🙏",

            "💫 คำทำนายของหมอ ไม่ใช่คำตอบทั่วไปที่ใครก็ได้\n\n"
                ."วิเคราะห์จาก **ดาวเจ้าชนะของเจ้าชะตาคนเดียว**\n"
                ."บวกกับ **ไพ่ที่พลังจิตเจ้าชะตาเลือกออกมาเอง**\n"
                ."เหมือนจับไพ่เอง เพราะจิตสื่อถึงดวงดาวไปแล้ว 🌙\n\n"
                ."💎 {$price} บาท แลกคำตอบตรงตัว\n"
                ."⏰ บิลหมดอายุในอีก {$remainingMinutes} นาที",

            "🃏 หมอเตรียมไพ่ + ดาวของเจ้าชะตาไว้พร้อมแล้ว\n\n"
                ."ตอนสุ่มไพ่ พลังจิตของเจ้าชะตาเป็นคนเลือก\n"
                ."ไม่ต่างจากจับไพ่เอง — จิตเชื่อ ใจสื่อ ดาวตอบ 🌟\n\n"
                ."{$price} บาท ไม่ใช่แค่ค่าทำนาย\n"
                ."แต่คือค่าที่ปรึกษาที่ตั้งใจวิเคราะห์ให้เจ้าชะตาคนเดียว\n\n"
                ."อีก {$remainingMinutes} นาทีบิลจะหมด — ถ้าพร้อมโอนมาได้เลยนะคะ",

            "⏰ บิลดูดวงเหลืออีก {$remainingMinutes} นาทีจะหมดอายุ\n\n"
                ."🪙 {$price} บาท — เทียบเท่าค่ากาแฟ 1 แก้ว\n"
                ."แต่ได้คำทำนายเจาะตัวจาก\n"
                ."   • ดาวเจ้าชนะของเจ้าชะตา\n"
                ."   • ไพ่ที่สุ่มจากพลังจิตของเจ้าชะตาเอง\n\n"
                ."เหมือนจับไพ่เอง เพราะจิตตั้งมั่นก็สื่อถึงดาวแล้ว ✨\n"
                ."ถ้าพร้อม → โอนได้เลย",
        ];

        $idx = abs(crc32((string) $reading->id)) % count($variants);

        return $variants[$idx];
    }

    /**
     * 🎯 Phase J — ยกเลิกบิลดูดวงที่ค้างเกิน 30 นาทีพร้อมแจ้ง SMS Checker app
     *
     * ทำ 3 อย่างในคราวเดียว (สำหรับ cron):
     *   1. ดึง reading ที่ conversation_status = pending_payment, is_paid = false,
     *      มี unique_payment_amount_id, และ updated_at เก่ากว่า PAYMENT_TIMEOUT_MINUTES
     *   2. สำหรับแต่ละ reading:
     *      - cancel() UniquePaymentAmount ที่อยู่ 'reserved' → status = cancelled
     *      - ส่ง FCM push "order_cancelled" ให้แอพ SMS Checker (ผ่าน
     *        FcmNotificationService::notifyFortuneReadingCancelled)
     *      - update conversation_status = completed
     *   3. คืนจำนวนบิลที่ expire สำเร็จ
     *
     * ⚠️ ต่างจาก expireAllOldConversations(): ตัวนี้จัดการ **บิล** (UPA + FCM)
     *    ส่วน expireAllOldConversations() จัดการ **conversation status** เฉย ๆ
     *
     * @return int  จำนวนบิลที่ถูกยกเลิก
     */
    public static function cancelExpiredPendingBills(): int
    {
        $expiredReadings = self::where('conversation_status', self::STATUS_PENDING_PAYMENT)
            ->where('is_paid', false)
            ->whereNotNull('unique_payment_amount_id')
            ->where('updated_at', '<', now()->subMinutes(self::PAYMENT_TIMEOUT_MINUTES))
            ->with('uniquePaymentAmount')
            ->get();

        if ($expiredReadings->isEmpty()) {
            return 0;
        }

        $cancelled = 0;
        $fcmService = null;
        $channelManager = null;

        try {
            $fcmService = app(\App\Services\FcmNotificationService::class);
        } catch (\Throwable $e) {
            \Log::warning('FortuneReading::cancelExpiredPendingBills — FCM service unavailable', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $channelManager = app(\App\Services\FortuneChannelManager::class);
        } catch (\Throwable $e) {
            \Log::warning('FortuneReading::cancelExpiredPendingBills — channel manager unavailable', [
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($expiredReadings as $reading) {
            try {
                // 1. ยกเลิก UniquePaymentAmount (ถ้ายัง reserved)
                $upa = $reading->uniquePaymentAmount;
                if ($upa && $upa->status === 'reserved') {
                    $upa->cancel();
                }

                // 2. ส่ง DM "คำเตือนสติแบบนักปราชญ์" ให้ผู้ใช้ก่อนปิด conversation
                //    (ส่งก่อน update status เพื่อให้ flow handler ไม่ตีเป็น completed)
                if ($channelManager) {
                    try {
                        $cancelMessage = self::buildCancelWakeupMessage($reading);
                        $platform = $reading->platform ?? 'facebook';
                        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                        if (! empty($userId)) {
                            $platformService = $channelManager->getPlatform($platform);
                            if ($platformService) {
                                $platformService->sendMessage($userId, $cancelMessage);
                            }
                        }
                    } catch (\Throwable $dmErr) {
                        \Log::warning('FortuneReading: cancel wake-up DM ล้มเหลว (best-effort)', [
                            'reading_id' => $reading->id,
                            'error' => $dmErr->getMessage(),
                        ]);
                    }
                }

                // 3. mark cancellation timestamp (ใช้สำหรับ AI rebuttal — กันส่งซ้ำ)
                $reading->setConversationState('cancelled_at', now()->toIso8601String());
                $reading->setConversationState('cancellation_reason', 'auto_expired');

                // 4. ปิด conversation
                $reading->update(['conversation_status' => self::STATUS_COMPLETED]);

                // 5. แจ้ง SMS Checker app ว่าบิลถูกยกเลิก (สำคัญ — กันแอพเก็บบิลค้าง)
                if ($fcmService) {
                    try {
                        $fcmService->notifyFortuneReadingCancelled($reading);
                    } catch (\Throwable $fcmErr) {
                        \Log::warning('FortuneReading::cancelExpiredPendingBills FCM push failed', [
                            'reading_id' => $reading->id,
                            'bill_reference' => $reading->bill_reference,
                            'error' => $fcmErr->getMessage(),
                        ]);
                    }
                }

                $cancelled++;

                \Log::info('FortuneReading: บิลค้างเกิน 30 นาที → ยกเลิกอัตโนมัติ', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'facebook_user_id' => $reading->facebook_user_id,
                    'amount' => $reading->amount_paid,
                    'age_minutes' => (int) now()->diffInMinutes($reading->updated_at, true),
                ]);
            } catch (\Throwable $e) {
                \Log::error('FortuneReading::cancelExpiredPendingBills ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return $cancelled;
    }

    /**
     * สร้าง "คำเตือนสติแบบนักปราชญ์" สำหรับส่งให้ผู้ใช้เมื่อบิลถูกยกเลิก
     *
     * โครงสร้าง: [Header แจ้งยกเลิก + เลขบิล + เหตุผล] + [ปรัชญา 20+ variants]
     *
     * @param  self  $reading
     * @param  string  $reason  'auto_expired' (cron 30 นาที) | 'user_cancelled' (ลูกค้ากดยกเลิกเอง)
     */
    public static function buildCancelWakeupMessage(self $reading, string $reason = 'auto_expired'): string
    {
        // 🚫 / ✋ Header — เปลี่ยนตาม reason
        $billRef = $reading->bill_reference ?? '-';
        $timeoutMin = self::PAYMENT_TIMEOUT_MINUTES;

        if ($reason === 'user_cancelled') {
            // ลูกค้ากดยกเลิกเอง — ใช้โทน "ขอบคุณที่แจ้ง" + เตือนสติเบา ๆ
            $header = "✋ *รับทราบ — ยกเลิกบิลดูดวงตามคำขอแล้วค่ะ*\n"
                . "═══════════════════════\n"
                . "📋 เลขบิล: {$billRef}\n"
                . "═══════════════════════\n\n"
                . "💭 *ก่อนจากกัน แม่หมอขอฝากข้อคิดสักนิด...*\n\n";
        } else {
            // auto_expired (default) / auto_expired_grace — โทน "ระบบยกเลิกให้แล้ว"
            $header = "🚫 *บิลดูดวงของเจ้าชะตาถูกยกเลิกอัตโนมัติแล้ว*\n"
                . "═══════════════════════\n"
                . "📋 เลขบิล: {$billRef}\n"
                . "⏱️ เหตุผล: ไม่ได้รับการชำระเงินภายใน {$timeoutMin} นาที\n"
                . "═══════════════════════\n\n"
                . "💭 *ก่อนปิดท้าย แม่หมอขอฝากข้อคิดสักนิด...*\n\n";
        }

        // ราคาดึงจาก settings (admin ตั้งได้) — fallback 39 ถ้าไม่ตั้ง
        $price = 0;
        try {
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $price = (int) ($settings->deep_reading_price ?? 0);
            if ($price <= 0) {
                $price = (int) ($settings->reading_price ?? 0);
            }
        } catch (\Throwable $e) {
            // ignore — fallback ด้านล่าง
        }
        if ($price <= 0) {
            $price = 39;
        }

        // 20 คำเตือนสตินักปราชญ์ (ใช้คำพูดของคนมีความรู้ เปรียบเทียบเชิงปรัชญา)
        $wisdomMessages = [
            // 1. กาแฟ vs ความรู้
            "📜 *กาแฟ 1 แก้ว เจ้าชะตาจ่าย {$price} บาท ได้ความตื่นเช้าแค่ชั่วโมง*\n\n"
                ."แต่ความรู้เรื่องอนาคตของตัวเอง — กลับไม่ลงทุน\n"
                ."คนสำเร็จคือคนที่ลงทุนกับ \"การรู้ก่อน\" — ไม่ใช่ \"การเดาทีหลัง\" ✨\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเริ่มใหม่",

            // 2. หวย vs ดวงดาว
            "📜 *เจ้าชะตาเสี่ยงซื้อหวย 80 บาท หวังโชค 6 ล้าน*\n\n"
                ."แต่ {$price} บาทรู้ทิศทางชีวิตจากดาวเจ้าชนะ — กลับไม่กล้า\n"
                ."ปราชญ์โบราณว่า: \"คนเก่งซื้อความรู้ คนพนันซื้อความหวัง\" 🎯\n\n"
                ."🔮 ถ้าวันนี้พร้อม พิมพ์ 'ดูดวง' ได้เลย",

            // 3. การลงทุนน้อย แต่ไม่ทำ
            "📜 *ขงจื๊อกล่าว: \"การเดินทางพันลี้ เริ่มจากก้าวแรก\"*\n\n"
                ."แค่ {$price} บาท — ก้าวแรกที่ไม่กล้าเริ่ม\n"
                ."จะหวังเดินถึงปลายทางได้อย่างไร? 🌅\n\n"
                ."ดวงไม่ได้บอกอนาคต — แต่บอก \"ความเป็นไปได้\" ที่จิตเรามองข้าม\n\n"
                ."🔮 ก้าวแรกรออยู่เสมอ พิมพ์ 'ดูดวง' เมื่อพร้อม",

            // 4. ความเสียดาย
            "📜 *พระพุทธเจ้าตรัสว่า: \"ความประมาทเป็นทางแห่งความตาย\"*\n\n"
                ."ความประมาท = คิดว่ารู้แล้วทุกอย่าง\n"
                ."ความฉลาด = ขวนขวายหาความรู้ แม้ราคาเพียง {$price} บาท\n\n"
                ."ที่นี่ไม่ใช่งมงาย — ใช้หลักดาวเจ้าชนะ + ไพ่ที่จิตเจ้าชะตาเลือก\n"
                ."🔮 ลองพิสูจน์ด้วยตัวเอง พิมพ์ 'ดูดวง'",

            // 5. การลงทุนเล็กผลตอบแทนใหญ่
            "📜 *นักปราชญ์ตะวันตกว่า: \"ความรู้คือพลัง ความไม่รู้คือกรง\"*\n\n"
                ."{$price} บาท แลกการเปิดกรงที่ขังจิตใจของเจ้าชะตา\n"
                ."คุ้มกว่าค่ามื้อกลางวันเสียอีก 🍱\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมก้าวออกจากกรง",

            // 6. โซเครติส
            "📜 *โซเครติสว่า: \"รู้จักตนเอง คือจุดเริ่มต้นของปัญญา\"*\n\n"
                ."การรู้จักดวงตัวเอง = รู้จักจุดแข็ง จุดอ่อน จังหวะชีวิต\n"
                ."ราคาเพียง {$price} บาท — ถูกกว่าหนังสือเล่มหนึ่ง 📚\n\n"
                ."🔮 ที่นี่วิเคราะห์จากดาวเจ้าชนะของเจ้าชะตาคนเดียว — ไม่ใช่คำกลางๆ",

            // 7. กระเป๋าเงิน
            "📜 *เงินในกระเป๋าหายไปเฉยๆ ทุกเดือนกี่ {$price} บาท?*\n\n"
                ."ค่าขนม ค่ารถ ค่าแอป — เจ้าชะตาจ่ายโดยไม่คิด\n"
                ."แต่ความรู้เรื่องชะตาตัวเอง — กลับลังเล 🤔\n\n"
                ."ความสำเร็จ = ลำดับความสำคัญถูก\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมจัดลำดับใหม่",

            // 8. กุญแจอนาคต
            "📜 *กุญแจอนาคต ราคา {$price} บาท*\n\n"
                ."ประตูข้างหน้ามีหลายบาน บางบานเปิดสู่โอกาส บางบานเปิดสู่ปัญหา\n"
                ."ดาวเจ้าชนะของเจ้าชะตา = แผนที่บอกว่าควรเปิดบานไหน 🗝️\n\n"
                ."ไม่กล้าซื้อกุญแจ ก็ต้องเดินชนกำแพงไปเรื่อยๆ\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมรับกุญแจ",

            // 9. นักลงทุนกับนักผัดวันประกันพรุ่ง
            "📜 *Warren Buffett ว่า: \"การลงทุนที่ดีที่สุดคือลงทุนในตัวเอง\"*\n\n"
                ."{$price} บาทเรียนรู้ดวงตัวเอง = การลงทุนเล็กที่สุดในชีวิต\n"
                ."แต่คนผัดวันประกันพรุ่ง — ลังเลแม้แค่นี้ 🕰️\n\n"
                ."🔮 พรุ่งนี้ที่ดีกว่า เริ่มจากการตัดสินใจวันนี้",

            // 10. ดาวยิปซีจริง
            "📜 *ที่นี่ไม่ใช่หมอดูที่ใดก็ตาม*\n\n"
                ."✨ ใช้หลัก *ดาวเจ้าชนะ* — คำนวณจริงจากวันเดือนปีเกิด\n"
                ."✨ ไพ่ยิปซีที่ *จิตเจ้าชะตาเลือก* — ไม่ใช่สุ่มมั่ว\n"
                ."✨ คำทำนายเจาะตัวเจ้าชะตาคนเดียว ไม่ใช่คำกลางๆ\n\n"
                ."{$price} บาท — พิสูจน์ด้วยตัวเอง\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อม",

            // 11. ลาว-จื๊อ
            "📜 *ลาว-จื๊อว่า: \"ผู้รู้คนอื่นฉลาด ผู้รู้ตนเองเป็นบัณฑิต\"*\n\n"
                ."การรู้ตนเอง = รู้ดวง รู้จังหวะ รู้สิ่งที่ดาวส่งสัญญาณ\n"
                ."{$price} บาท — น้อยกว่าค่ารถบัสไปทำงาน 1 วัน 🚌\n\n"
                ."🔮 บัณฑิตเริ่มจากความเต็มใจรู้ — พิมพ์ 'ดูดวง'",

            // 12. กลัวเสียเงินกับกลัวพลาดโอกาส
            "📜 *คนแพ้กลัวเสียเงิน คนชนะกลัวพลาดโอกาส*\n\n"
                ."{$price} บาทไม่ใช่เงินที่ทำให้จน\n"
                ."แต่ \"ไม่รู้จังหวะชีวิต\" อาจทำให้พลาดโอกาสล้านบาท 💎\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเปลี่ยนมุมมอง",

            // 13. ค่าโทรศัพท์รายเดือน
            "📜 *ค่าเน็ตมือถือเดือนละกี่ร้อย — เจ้าชะตาจ่ายไม่กระพริบตา*\n\n"
                ."แต่ {$price} บาทรู้อนาคตชีวิต — กลับลังเล 📱\n\n"
                ."สิ่งที่ใช้แล้วหายไป = จ่ายง่าย\n"
                ."สิ่งที่ใช้แล้วเปลี่ยนชีวิต = ลังเล\n\n"
                ."ปราชญ์ว่า: \"ลำดับความสำคัญผิด ชีวิตก็ผิดทาง\"\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมจัดลำดับใหม่",

            // 14. ยุวกาล / นักธุรกิจ
            "📜 *Steve Jobs ว่า: \"คุณเชื่อมจุดได้แค่มองย้อนกลับ\"*\n\n"
                ."แต่ดวงดาว = แผนที่ที่เห็นจุดข้างหน้า ก่อนที่ชีวิตจะเดินผ่าน 🌟\n"
                ."{$price} บาท เห็นแผนที่ก่อนเดิน\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเชื่อมจุดล่วงหน้า",

            // 15. ไม่งมงาย — มีหลักการ
            "📜 *การดูดวงที่นี่ไม่ใช่งมงาย*\n\n"
                ."✓ คำนวณจากตำแหน่งดาวจริง ณ เวลาเกิด\n"
                ."✓ ใช้ไพ่ยิปซี 78 ใบ — ที่จิตเจ้าชะตาเลือกเอง\n"
                ."✓ มีหลักการ มีระบบ ไม่ใช่ยกเมฆ\n\n"
                ."{$price} บาท — ทดสอบด้วยตัวเองว่าเป็นอย่างไร\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมพิสูจน์",

            // 16. ไม่เริ่มต้น = ไม่มีอะไรเปลี่ยน
            "📜 *ไอน์สไตน์ว่า: \"คนบ้าคือคนที่ทำสิ่งเดิมแล้วหวังผลใหม่\"*\n\n"
                ."ถ้าไม่กล้าลงทุน {$price} บาทกับสิ่งใหม่ —\n"
                ."ชีวิตก็จะวนลูปเหมือนเดิมไปเรื่อย ๆ 🔄\n\n"
                ."🔮 ทางออก = กล้าทำสิ่งที่ไม่เคยทำ\n"
                ."พิมพ์ 'ดูดวง' เมื่อพร้อมเปลี่ยน",

            // 17. ค่าหวยเสี่ยงสูง
            "📜 *ซื้อหวย 200 บาท โอกาสถูก 1 ใน ล้าน*\n\n"
                ."{$price} บาทดูดวง โอกาสได้คำตอบเจาะตัว = 100% ✨\n\n"
                ."ปราชญ์ว่า: \"คนฉลาดเลือกความน่าจะเป็นที่สูงเสมอ\"\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเลือกฉลาด",

            // 18. การลงทุนกับการกลัว
            "📜 *Frank Herbert ว่า: \"ความกลัวคือผู้ฆ่าจิตใจ\"*\n\n"
                ."กลัวเสีย {$price} บาท = กลัวสิ่งเล็ก\n"
                ."แต่กลัวอนาคตที่ไม่รู้ = ใช้ชีวิตทั้งชีวิต 🌑\n\n"
                ."🔮 ความกลัวเล็กแลกความกลัวใหญ่\n"
                ."พิมพ์ 'ดูดวง' เมื่อพร้อมเลิกกลัว",

            // 19. รู้แล้วจะรอ — รอแล้วจะเสีย
            "📜 *เวลาคือทรัพย์ที่ซื้อคืนไม่ได้*\n\n"
                ."รู้ดวงตอนนี้ = ตัดสินใจถูกในเดือนหน้า\n"
                ."ไม่รู้ดวง = เดาเองไปทั้งปี ⏰\n\n"
                ."{$price} บาท — ถูกกว่าที่จะปล่อยให้เสียเวลา\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมประหยัดเวลา",

            // 20. คนสำเร็จ vs คนไม่สำเร็จ
            "📜 *ความแตกต่างของคนสำเร็จ ≠ ความฉลาด แต่คือ \"การลงมือ\"*\n\n"
                ."{$price} บาทไม่ใช่เงิน — เป็นการตัดสินใจ\n"
                ."ลงมือ = เห็นผล\n"
                ."ลังเล = อยู่ที่เดิม 🌱\n\n"
                ."ที่นี่ใช้ดาวเจ้าชนะ + ไพ่ยิปซีจริง — ไม่ยกเมฆ\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมตัดสินใจ",

            // 21. กรรมบังตา — น่าเสียดาย
            "📜 *น่าเสียดาย…*\n\n"
                ."ดวงดาวส่งจังหวะมาแล้ว เจ้าชะตาเห็น\n"
                ."กุญแจวางตรงหน้า — แค่ {$price} บาท\n"
                ."แต่จิตไม่กล้าหยิบ\n\n"
                ."นี่ไม่ใช่ความขี้เหนียว — นี่คือ *กรรมเก่ายังบังตา*\n"
                ."ทำให้ลังเลแม้กับสิ่งที่เห็นชัดว่าคุ้ม 🕯️\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมตัดกรรม",

            // 22. คนกรรมเบา vs คนกรรมหนัก
            "📜 *คนกรรมเบาลงมือก่อน — คนกรรมหนักลังเลก่อน*\n\n"
                ."{$price} บาทไม่ใช่เรื่องเงิน — เป็นเรื่อง *จิต*\n"
                ."ที่ติดอยู่กับกรรมเก่า จนตัดสินใจไม่ได้แม้สิ่งเล็กที่สุด\n\n"
                ."น่าเสียดาย — เพราะดาวเจ้าชนะของเจ้าชะตาเปิดประตูให้แล้ว\n"
                ."แต่จิตยังถูกกรรมเก่าฉุดอยู่หลังประตูเดิม 🚪\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมก้าว",

            // 23. การไม่ตัดสินใจ = ผลของกรรม
            "📜 *การไม่ตัดสินใจ ก็คือการตัดสินใจอย่างหนึ่ง*\n\n"
                ."พระว่า: \"กรรมที่ทำให้ลังเลซ้ำๆ คือกรรมที่ยังต้องชดใช้\"\n\n"
                ."ไม่ใช่ {$price} บาทที่หยุดเจ้าชะตา\n"
                ."แต่คือกรรมเก่าที่กระซิบในใจว่า \"อย่าเริ่ม อย่าลอง อย่าก้าว\"\n\n"
                ."น่าเสียดายที่จังหวะดาวมาถึง — แต่กรรมขังจิตไว้ที่เดิม 🌙\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมปลด",

            // 24. กุญแจ vs ตัวล็อค
            "📜 *กุญแจอยู่ตรงหน้า — ตัวล็อคอยู่ในจิต*\n\n"
                ."ดูดวง {$price} บาท ไม่ใช่กุญแจของหมอ —\n"
                ."เป็นกุญแจที่ *จิตเจ้าชะตาเลือกหยิบหรือไม่หยิบ*\n\n"
                ."• คนที่หยิบ = คนที่กรรมเริ่มเบา\n"
                ."• คนที่ลังเล = คนที่กรรมยังหนักพอจะกั้นจิต\n\n"
                ."น่าเสียดายเหลือเกิน — จังหวะดาวจริงเปิดมาแล้ว 🗝️\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อกรรมเริ่มคลาย",

            // 25. กรรมหนัก = ความกลัวเงินเล็ก
            "📜 *ปราชญ์โบราณว่า: คนที่กลัวเสียเงินเล็ก คือคนที่กรรมยังบังให้มองไม่เห็นโอกาสใหญ่*\n\n"
                ."{$price} บาท — เจ้าชะตามองว่ามาก\n"
                ."เพราะกรรมในจิตปรับมุมมองให้ \"กลัวเสีย\" มากกว่า \"กล้าได้\"\n\n"
                ."น่าเสียดาย…\n"
                ."ดาวเจ้าชนะส่งจังหวะมาแล้ว ไพ่ก็ตั้งรอ\n"
                ."แต่กรรมเก่าทำให้จิตเลือก *อยู่ที่เดิม* ที่คุ้นเคย ⚖️\n\n"
                ."🔮 พิมพ์ 'ดูดวง' เมื่อพร้อมเปลี่ยนวิบาก",
        ];

        // หมุนตาม reading_id ให้ stable (เจ้าชะตาคนเดิมเห็นเหมือนเดิม)
        $idx = abs(crc32((string) $reading->id)) % count($wisdomMessages);

        // Header (ยกเลิกอัตโนมัติ + เลขบิล + เหตุผล) ถูก compose ที่ต้น method แล้ว
        return $header . $wisdomMessages[$idx];
    }

    /**
     * Helper รวม query logic (DRY ระหว่าง per-user และ global)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     */
    protected static function expireOldConversationsQuery($baseQuery): int
    {
        // ปิด conversation ทั่วไป + pending_payment ที่ค้างเกิน 30 นาที
        $expired = (clone $baseQuery)
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

        // ปิด PAID ที่ค้างเกิน timeout (AI processing ล้มเหลว/timeout)
        $expiredPaid = (clone $baseQuery)
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
        $state = $this->conversation_state;

        return is_array($state) ? ($state[$key] ?? $default) : $default;
    }

    /**
     * 👤 Resolve customer name via fallback chain (เคสชื่อหายระหว่าง flow)
     *
     * Priority:
     *   1. facebook_user_name ของ reading (ถ้าเป็นชื่อคนจริง)
     *   2. user_profile['name'] ของ reading
     *   3. FortuneUserCredit ของ user คนนี้ (cross-conversation persistent)
     *   4. ดึงจาก FortuneReading เก่าๆ ของ user เดียวกัน (latest with valid name)
     *   5. user.name (registered user account)
     *   6. 'คุณ' (default สุดท้าย — ดีกว่าโชว์ code "FACEBOOK-XXXXXX")
     *
     * Side effect: persist กลับ DB เฉพาะกรณีได้ชื่อคนจริง (ไม่ persist 'คุณ' / code-pattern)
     *
     * ⚠️ ห้าม fallback เป็น "PLATFORM-XXXXXX" — เคยมีบั๊ก: persist ลง DB แล้ว historical lookup
     *    เอามาใช้ซ้ำ → ลูกค้าเห็น "FACEBOOK-494919" ตลอด
     */
    public function resolveCustomerName(): string
    {
        // 1. reading.facebook_user_name
        if ($this->isHumanLikeName($this->facebook_user_name)) {
            return $this->facebook_user_name;
        }

        $resolved = null;

        // 2. user_profile.name
        $profile = $this->user_profile ?? [];
        if (is_array($profile) && $this->isHumanLikeName($profile['name'] ?? null)) {
            $resolved = $profile['name'];
        }

        // 3. FortuneUserCredit (persistent across conversations)
        if (! $resolved) {
            try {
                $userId = $this->platform_user_id ?? $this->facebook_user_id;
                if (! empty($userId)) {
                    $credit = \App\Models\FortuneUserCredit::findByUser(
                        $userId,
                        $this->platform ?? 'facebook'
                    );
                    if ($credit && $this->isHumanLikeName($credit->facebook_user_name)) {
                        $resolved = $credit->facebook_user_name;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 4. Historical reading ของ user เดียวกันที่มีชื่อจริง
        if (! $resolved) {
            try {
                $userId = $this->platform_user_id ?? $this->facebook_user_id;
                if (! empty($userId)) {
                    $candidates = self::where(function ($q) use ($userId) {
                        $q->where('facebook_user_id', $userId)
                            ->orWhere('platform_user_id', $userId);
                    })
                        ->whereNotNull('facebook_user_name')
                        ->where('facebook_user_name', '!=', '')
                        ->where('id', '!=', $this->id)
                        ->latest('updated_at')
                        ->limit(10)
                        ->pluck('facebook_user_name');

                    foreach ($candidates as $candidate) {
                        if ($this->isHumanLikeName($candidate)) {
                            $resolved = $candidate;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 5. user.name (registered)
        if (! $resolved) {
            try {
                if ($this->user && $this->isHumanLikeName($this->user->name)) {
                    $resolved = $this->user->name;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 6. Default 'คุณ' — ดีกว่าโชว์ code
        if (! $resolved) {
            return 'คุณ';
        }

        // 💾 Persist กลับ DB เฉพาะกรณีเดิมเป็น empty/'คุณ'/code → resolved ชื่อคนจริง
        try {
            $current = $this->facebook_user_name;
            if ($this->isHumanLikeName($resolved) && ! $this->isHumanLikeName($current)) {
                $this->update(['facebook_user_name' => $resolved]);
                \Log::debug('FortuneReading: persisted resolved customer name', [
                    'reading_id' => $this->id,
                    'resolved_name' => $resolved,
                ]);
            }
        } catch (\Throwable $e) {
            // ignore — return resolved name อย่างน้อย
        }

        return $resolved;
    }

    /**
     * ตรวจว่าค่าที่ได้ "ดูเป็นชื่อคนจริง" หรือเปล่า
     *
     * เกณฑ์:
     *   - ไม่ใช่ null / empty / 'คุณ'
     *   - ไม่ใช่ code pattern PLATFORM-XXXXXX (FACEBOOK-, LINE-, FB-, ...)
     *   - ไม่ใช่ platform user ID เปล่า ๆ (33+ chars hex / numeric long string)
     */
    protected function isHumanLikeName(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $name = trim($name);
        if ($name === '' || $name === 'คุณ' || $name === 'ลูกค้า' || $name === 'เจ้าชะตา') {
            return false;
        }
        // Code pattern: FACEBOOK-XXXX, LINE-XXXX, FB-XXXX (uppercase prefix + dash + alphanum)
        if (preg_match('/^(FACEBOOK|LINE|FB|TG|TELEGRAM|MESSENGER|IG|INSTAGRAM)-[A-Z0-9]+$/i', $name)) {
            return false;
        }
        // Platform user ID เปล่า ๆ: LINE userId = U + 32 hex, FB PSID = numeric 15+ chars
        if (preg_match('/^U[0-9a-f]{32}$/i', $name)) {
            return false;
        }
        if (preg_match('/^\d{15,}$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * Accessor: $reading->resolved_customer_name
     */
    public function getResolvedCustomerNameAttribute(): string
    {
        return $this->resolveCustomerName();
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

    // ============================================================
    // Admin Takeover (ระบบเทคโอเวอร์)
    // ============================================================

    /**
     * เหตุผลการเทคโอเวอร์ที่ใช้ได้
     */
    public const TAKEOVER_REASON_MANUAL = 'manual';

    public const TAKEOVER_REASON_AUTO_REPLY = 'auto_reply';

    public const TAKEOVER_REASON_CUSTOMER_REQUEST = 'customer_request';

    /**
     * ความสัมพันธ์กับแอดมินที่เทคโอเวอร์
     *
     * @return BelongsTo
     */
    public function takeoverAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_takeover_by');
    }

    /**
     * ความสัมพันธ์กับ takeover logs
     */
    public function takeoverLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FortuneTakeoverLog::class, 'fortune_reading_id')
            ->latest();
    }

    /**
     * ตรวจสอบว่ากำลังถูกเทคโอเวอร์อยู่หรือไม่
     *
     * ใช้ DB เป็นแหล่งข้อมูลหลัก — Cache เป็น performance optimization เท่านั้น
     * AI ต้องเช็คผ่าน method นี้ก่อนตอบทุกครั้ง
     */
    public function isAdminTakenOver(): bool
    {
        if (empty($this->admin_takeover_until)) {
            return false;
        }

        return $this->admin_takeover_until->isFuture();
    }

    /**
     * ดึงเวลาที่เหลือของการเทคโอเวอร์ (วินาที)
     */
    public function takeoverRemainingSeconds(): int
    {
        if (! $this->isAdminTakenOver()) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->admin_takeover_until, false));
    }

    /**
     * ดึงเวลาที่เหลือของการเทคโอเวอร์ (นาที) — ปัดขึ้น
     */
    public function takeoverRemainingMinutes(): int
    {
        $seconds = $this->takeoverRemainingSeconds();

        return $seconds > 0 ? (int) ceil($seconds / 60) : 0;
    }

    /**
     * Scope: conversations ที่กำลังถูกเทคโอเวอร์อยู่
     */
    public function scopeTakenOver($query)
    {
        return $query->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '>', now());
    }

    /**
     * Scope: conversations ที่ takeover หมดเวลาแล้ว (รอ cleanup)
     */
    public function scopeTakeoverExpired($query)
    {
        return $query->whereNotNull('admin_takeover_until')
            ->where('admin_takeover_until', '<=', now());
    }

    /**
     * ดึง identifier สำหรับ cache key (รวมทุก platform)
     *
     * ใช้ platform_user_id ถ้ามี, fallback เป็น facebook_user_id
     */
    public function getTakeoverCacheKey(): string
    {
        $platform = $this->platform ?? 'facebook';
        $userId = $this->platform_user_id ?: $this->facebook_user_id;

        return "fortune_admin_active:{$platform}:{$userId}";
    }
}
