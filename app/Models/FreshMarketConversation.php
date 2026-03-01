<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FreshMarketConversation - ประวัติสนทนา AI ตลาดสด
 *
 * @property int $id
 * @property string $line_user_id
 * @property int|null $user_id
 * @property string $role buyer|seller
 * @property string $conversation_state
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

    /** สถานะการสนทนา */
    public const STATE_NEW = 'new';
    public const STATE_BROWSING = 'browsing';
    public const STATE_SEARCHING = 'searching';
    public const STATE_LISTING = 'listing';
    public const STATE_ORDERING = 'ordering';
    public const STATE_CHATTING = 'chatting';

    protected $fillable = [
        'line_user_id',
        'user_id',
        'role',
        'conversation_state',
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
        'last_message_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FreshMarketMessage::class, 'conversation_id');
    }

    // ===== Methods =====

    /**
     * ดึง conversation ล่าสุดของ LINE user หรือสร้างใหม่
     */
    public static function getOrCreate(string $lineUserId, string $role = 'buyer'): self
    {
        return self::firstOrCreate(
            ['line_user_id' => $lineUserId],
            [
                'role' => $role,
                'conversation_state' => self::STATE_NEW,
            ]
        );
    }

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
    public function getContext(int $limit = 10): array
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
     * อัพเดทสถานะ + context
     */
    public function updateState(string $state, array $context = []): void
    {
        $currentContext = $this->context ?? [];
        $mergedContext = array_merge($currentContext, $context);

        $this->update([
            'conversation_state' => $state,
            'context' => $mergedContext,
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
