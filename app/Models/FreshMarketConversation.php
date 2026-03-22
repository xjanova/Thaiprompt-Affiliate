<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FreshMarketConversation - ประวัติสนทนา AI ตลาดสด
 *
 * State Machine ใหม่: 13 สถานะแยกละเอียดตาม flow
 * บอทรู้ชัดเจนว่ากำลังทำอะไร ยูสเซอร์กำลังทำอะไร
 *
 * @property int $id
 * @property string $line_user_id
 * @property int|null $user_id
 * @property int|null $seller_id
 * @property string $role buyer|seller
 * @property string $conversation_state
 * @property \Carbon\Carbon|null $state_updated_at
 * @property int $state_step
 * @property int $state_total_steps
 * @property string|null $previous_state
 * @property string|null $last_search_query
 * @property float|null $last_search_latitude
 * @property float|null $last_search_longitude
 * @property float|null $last_search_radius_km
 * @property array|null $preferences
 * @property array|null $context
 * @property int $message_count
 * @property \Carbon\Carbon|null $last_message_at
 */
class FreshMarketConversation extends Model
{
    protected $table = 'fresh_market_conversations';

    // ===== IDLE =====
    public const STATE_IDLE = 'idle';

    // ===== SELLER LISTING FLOW (5 ขั้นตอน) =====
    public const STATE_LISTING_PHOTOS = 'listing_photos';
    public const STATE_LISTING_DETAILS = 'listing_details';
    public const STATE_LISTING_LOCATION = 'listing_location';
    public const STATE_LISTING_REVIEW = 'listing_review';
    public const STATE_LISTING_COMPLETE = 'listing_complete';

    // ===== BUYER SEARCH FLOW (2 ขั้นตอน) =====
    public const STATE_SEARCH_LOCATION = 'search_location';
    public const STATE_SEARCH_BROWSING = 'search_browsing';

    // ===== SELLER VERIFICATION (ก่อนลงขาย) =====
    public const STATE_SELLER_PHONE = 'seller_phone';
    public const STATE_SELLER_OTP = 'seller_otp';

    // ===== BUYER ORDER FLOW (4 ขั้นตอน) =====
    public const STATE_ORDER_SELECT = 'order_select';
    public const STATE_ORDER_QUANTITY = 'order_quantity';
    public const STATE_ORDER_REVIEW = 'order_review';
    public const STATE_ORDER_TRACKING = 'order_tracking';

    // ===== RIDER REGISTRATION FLOW (3 ขั้นตอน) =====
    public const STATE_RIDER_REGISTER = 'rider_register';
    public const STATE_RIDER_CATEGORY = 'rider_category';
    public const STATE_RIDER_COMPLETE = 'rider_complete';

    // ===== Backward compatibility aliases =====
    public const STATE_NEW = 'idle';
    public const STATE_BROWSING = 'search_browsing';
    public const STATE_SEARCHING = 'search_location';
    public const STATE_LISTING = 'listing_photos';
    public const STATE_ORDERING = 'order_quantity';
    public const STATE_CHATTING = 'idle';

    /**
     * Transition ที่อนุญาต: state ปัจจุบัน => [state ที่ไปได้]
     */
    public const VALID_TRANSITIONS = [
        'idle' => ['listing_photos', 'seller_phone', 'search_location', 'search_browsing', 'rider_register', 'idle'],

        // Seller OTP verification
        'seller_phone' => ['seller_otp', 'idle'],
        'seller_otp' => ['listing_photos', 'seller_phone', 'idle'],

        // Seller listing flow
        'listing_photos' => ['listing_details', 'idle'],
        'listing_details' => ['listing_location', 'listing_photos', 'idle'],
        'listing_location' => ['listing_review', 'listing_details', 'idle'],
        'listing_review' => ['listing_complete', 'listing_details', 'idle'],
        'listing_complete' => ['idle', 'listing_photos'],

        // Buyer search flow
        'search_location' => ['search_browsing', 'idle'],
        'search_browsing' => ['order_select', 'search_location', 'idle'],

        // Buyer order flow
        'order_select' => ['order_quantity', 'search_browsing', 'idle'],
        'order_quantity' => ['order_review', 'order_select', 'idle'],
        'order_review' => ['order_tracking', 'order_quantity', 'idle'],
        'order_tracking' => ['idle', 'search_browsing'],

        // Rider registration flow
        'rider_register' => ['rider_category', 'idle'],
        'rider_category' => ['rider_complete', 'rider_register', 'idle'],
        'rider_complete' => ['idle', 'rider_register'],
    ];

    /**
     * Timeout ต่อสถานะ (นาที)
     * ถ้าไม่มีในนี้ = ไม่หมดเวลา
     */
    public const TIMEOUT_MINUTES = [
        'seller_phone' => 10,
        'seller_otp' => 5,
        'listing_photos' => 30,
        'listing_details' => 30,
        'listing_location' => 30,
        'listing_review' => 30,
        'search_location' => 15,
        'search_browsing' => 15,
        'order_select' => 10,
        'order_quantity' => 10,
        'order_review' => 5,
        'order_tracking' => 60, // 1 ชม. แล้วกลับ idle อัตโนมัติ
        'rider_register' => 10,
        'rider_category' => 10,
    ];

    /**
     * ข้อมูล step/total/label ต่อสถานะ
     */
    public const STEP_INFO = [
        'listing_photos' => ['step' => 1, 'total' => 5, 'label' => 'ส่งรูปสินค้า'],
        'listing_details' => ['step' => 2, 'total' => 5, 'label' => 'รายละเอียดสินค้า'],
        'listing_location' => ['step' => 3, 'total' => 5, 'label' => 'พิกัดร้าน'],
        'listing_review' => ['step' => 4, 'total' => 5, 'label' => 'ตรวจสอบข้อมูล'],
        'listing_complete' => ['step' => 5, 'total' => 5, 'label' => 'สำเร็จ'],
        'search_location' => ['step' => 1, 'total' => 2, 'label' => 'ส่งตำแหน่ง'],
        'search_browsing' => ['step' => 2, 'total' => 2, 'label' => 'ผลค้นหา'],
        'order_select' => ['step' => 1, 'total' => 4, 'label' => 'เลือกสินค้า'],
        'order_quantity' => ['step' => 2, 'total' => 4, 'label' => 'จำนวนและการจัดส่ง'],
        'order_review' => ['step' => 3, 'total' => 4, 'label' => 'ตรวจสอบคำสั่งซื้อ'],
        'order_tracking' => ['step' => 4, 'total' => 4, 'label' => 'ติดตามออเดอร์'],
        'rider_register' => ['step' => 1, 'total' => 3, 'label' => 'เลือกประเภทไรเดอร์'],
        'rider_category' => ['step' => 2, 'total' => 3, 'label' => 'เลือกหมวดหมู่บริการ'],
        'rider_complete' => ['step' => 3, 'total' => 3, 'label' => 'สมัครสำเร็จ'],
    ];

    protected $fillable = [
        'line_user_id',
        'user_id',
        'seller_id',
        'role',
        'conversation_state',
        'state_updated_at',
        'state_step',
        'state_total_steps',
        'previous_state',
        'last_search_query',
        'last_search_latitude',
        'last_search_longitude',
        'last_search_radius_km',
        'preferences',
        'context',
        'message_count',
        'last_message_at',
    ];

    protected $casts = [
        'preferences' => 'array',
        'context' => 'array',
        'last_search_latitude' => 'decimal:8',
        'last_search_longitude' => 'decimal:8',
        'last_search_radius_km' => 'decimal:2',
        'message_count' => 'integer',
        'state_step' => 'integer',
        'state_total_steps' => 'integer',
        'last_message_at' => 'datetime',
        'state_updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(FreshMarketSeller::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FreshMarketMessage::class, 'conversation_id');
    }

    // ===== Factory Methods =====

    /**
     * ดึง conversation ล่าสุดของ LINE user หรือสร้างใหม่
     */
    public static function getOrCreate(string $lineUserId, string $role = 'buyer'): self
    {
        return self::firstOrCreate(
            ['line_user_id' => $lineUserId],
            [
                'role' => $role,
                'conversation_state' => self::STATE_IDLE,
                'state_updated_at' => now(),
            ]
        );
    }

    // ===== State Machine Methods =====

    /**
     * เปลี่ยนสถานะอย่างปลอดภัย (validate transition)
     *
     * @param string $newState สถานะใหม่
     * @param array $contextUpdate ข้อมูลเพิ่มเติมสำหรับ context
     * @return bool สำเร็จหรือไม่
     */
    public function transitionTo(string $newState, array $contextUpdate = []): bool
    {
        $currentState = $this->conversation_state ?? self::STATE_IDLE;
        $validTargets = self::VALID_TRANSITIONS[$currentState] ?? [self::STATE_IDLE];

        // ตรวจสอบว่า transition นี้อนุญาตหรือไม่
        if (! in_array($newState, $validTargets)) {
            // fallback: อนุญาตกลับ idle เสมอ
            if ($newState !== self::STATE_IDLE) {
                return false;
            }
        }

        // ดึงข้อมูล step
        $stepInfo = self::STEP_INFO[$newState] ?? null;

        // Merge context
        $currentContext = $this->context ?? [];
        if (! empty($contextUpdate)) {
            $currentContext = array_merge($currentContext, $contextUpdate);
        }

        $this->update([
            'previous_state' => $currentState,
            'conversation_state' => $newState,
            'state_updated_at' => now(),
            'state_step' => $stepInfo['step'] ?? 0,
            'state_total_steps' => $stepInfo['total'] ?? 0,
            'context' => $currentContext,
        ]);

        return true;
    }

    /**
     * ตรวจสอบว่า session หมดเวลาหรือไม่
     */
    public function isExpired(): bool
    {
        $state = $this->conversation_state ?? self::STATE_IDLE;
        $timeout = self::TIMEOUT_MINUTES[$state] ?? null;

        // ไม่มี timeout (idle, order_tracking, listing_complete)
        if ($timeout === null) {
            return false;
        }

        $stateUpdatedAt = $this->state_updated_at ?? $this->updated_at;

        if (! $stateUpdatedAt) {
            return false;
        }

        return Carbon::parse($stateUpdatedAt)->addMinutes($timeout)->isPast();
    }

    /**
     * รีเซ็ตกลับสู่ idle
     */
    public function resetToIdle(): void
    {
        $this->update([
            'previous_state' => $this->conversation_state,
            'conversation_state' => self::STATE_IDLE,
            'state_updated_at' => now(),
            'state_step' => 0,
            'state_total_steps' => 0,
            'context' => null,
        ]);
    }

    /**
     * ดึง progress text ("📋 ขั้นตอนที่ 2/5: รายละเอียดสินค้า")
     */
    public function getProgressText(): string
    {
        $state = $this->conversation_state ?? self::STATE_IDLE;
        $info = self::STEP_INFO[$state] ?? null;

        if (! $info) {
            return '';
        }

        return "📋 ขั้นตอนที่ {$info['step']}/{$info['total']}: {$info['label']}";
    }

    /**
     * ดึง context สำหรับ flow เฉพาะ (listing/search/order)
     */
    public function getFlowContext(string $flowKey): array
    {
        $context = $this->context ?? [];

        return $context[$flowKey] ?? [];
    }

    /**
     * อัพเดท context สำหรับ flow เฉพาะ (merge ไม่ overwrite)
     */
    public function setFlowContext(string $flowKey, array $data): void
    {
        $context = $this->context ?? [];
        $existing = $context[$flowKey] ?? [];
        $context[$flowKey] = array_merge($existing, $data);

        $this->update([
            'context' => $context,
            'state_updated_at' => now(),
        ]);
    }

    /**
     * ลบ context สำหรับ flow เฉพาะ
     */
    public function clearFlowContext(string $flowKey): void
    {
        $context = $this->context ?? [];
        unset($context[$flowKey]);

        $this->update([
            'context' => $context,
            'state_updated_at' => now(),
        ]);
    }

    /**
     * ตรวจสอบว่าอยู่ใน listing flow หรือไม่
     */
    public function isInListingFlow(): bool
    {
        return in_array($this->conversation_state, [
            self::STATE_LISTING_PHOTOS,
            self::STATE_LISTING_DETAILS,
            self::STATE_LISTING_LOCATION,
            self::STATE_LISTING_REVIEW,
            self::STATE_LISTING_COMPLETE,
        ]);
    }

    /**
     * ตรวจสอบว่าอยู่ใน order flow หรือไม่
     */
    public function isInOrderFlow(): bool
    {
        return in_array($this->conversation_state, [
            self::STATE_ORDER_SELECT,
            self::STATE_ORDER_QUANTITY,
            self::STATE_ORDER_REVIEW,
            self::STATE_ORDER_TRACKING,
        ]);
    }

    /**
     * ตรวจสอบว่าอยู่ใน rider registration flow หรือไม่
     */
    public function isInRiderFlow(): bool
    {
        return in_array($this->conversation_state, [
            self::STATE_RIDER_REGISTER,
            self::STATE_RIDER_CATEGORY,
            self::STATE_RIDER_COMPLETE,
        ]);
    }

    /**
     * ตรวจสอบว่าอยู่ใน search flow หรือไม่
     */
    public function isInSearchFlow(): bool
    {
        return in_array($this->conversation_state, [
            self::STATE_SEARCH_LOCATION,
            self::STATE_SEARCH_BROWSING,
        ]);
    }

    // ===== Message Methods =====

    /**
     * เพิ่มข้อความใหม่
     */
    public function addMessage(string $role, string $content, array $metadata = []): FreshMarketMessage
    {
        $message = $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'tokens_used' => $metadata['tokens_used'] ?? 0,
            'ai_provider' => $metadata['ai_provider'] ?? null,
            'ai_model' => $metadata['ai_model'] ?? null,
        ]);

        $this->update([
            'message_count' => $this->message_count + 1,
            'last_message_at' => now(),
        ]);

        return $message;
    }

    /**
     * ดึงบริบทข้อความล่าสุด (สำหรับส่งให้ AI)
     */
    public function getMessageHistory(int $limit = 10): array
    {
        return $this->messages()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn ($msg) => [
                'role' => $msg->role === 'user' ? 'user' : 'assistant',
                'content' => $msg->content,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Alias สำหรับ backward compatibility
     */
    public function getContext(int $limit = 10): array
    {
        return $this->getMessageHistory($limit);
    }

    /**
     * อัพเดทสถานะ + context (backward compatibility)
     */
    public function updateState(string $state, array $context = []): void
    {
        $currentContext = $this->context ?? [];
        $mergedContext = array_merge($currentContext, $context);

        $this->update([
            'conversation_state' => $state,
            'context' => $mergedContext,
            'state_updated_at' => now(),
        ]);
    }

    /**
     * อัพเดทพิกัดค้นหา
     */
    public function updateSearchLocation(float $lat, float $lng, ?float $radius = null): void
    {
        $this->update([
            'last_search_latitude' => $lat,
            'last_search_longitude' => $lng,
            'last_search_radius_km' => $radius ?? FreshMarketSetting::getSettings()->default_search_radius_km,
        ]);
    }
}
