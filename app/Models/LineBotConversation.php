<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LineBotConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'line_user_id',
        'platform',
        'user_id',
        'ai_setting_id',
        'session_id',
        'status',
        'last_message_at',
        'message_count',
        'context',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'message_count' => 'integer',
        'context' => 'array',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($conversation) {
            if (! $conversation->session_id) {
                $conversation->session_id = Str::uuid();
            }
        });
    }

    /**
     * Get user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get AI setting
     */
    public function aiSetting()
    {
        return $this->belongsTo(LineBotAiSetting::class, 'ai_setting_id');
    }

    /**
     * Get messages
     */
    public function messages()
    {
        return $this->hasMany(LineBotMessage::class, 'conversation_id');
    }

    /**
     * Get recent messages
     */
    public function recentMessages(int $limit = 10)
    {
        return $this->hasMany(LineBotMessage::class, 'conversation_id')
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    /**
     * Add message
     */
    public function addMessage(string $role, string $message, array $metadata = []): LineBotMessage
    {
        $msg = $this->messages()->create([
            'role' => $role,
            'message' => $message,
            'metadata' => $metadata,
        ]);

        $this->update([
            'last_message_at' => now(),
            'message_count' => $this->message_count + 1,
        ]);

        return $msg;
    }

    /**
     * Get conversation history for AI
     *
     * 🐛 (2026-05-14) Bug fix: เดิม ORDER BY ASC + LIMIT 10
     *   → ดึง 10 ข้อความ "แรก" ของ conversation (ไม่ใช่ล่าสุด!)
     *   → AI ไม่เห็น context ที่ลูกค้าเพิ่งคุย (recent messages หาย)
     *   ใหม่: ดึง N ข้อความ "ล่าสุด" แล้ว reverse กลับเป็น chronological order
     *   → AI ได้บริบทล่าสุด + เรียงตามเวลา
     */
    public function getHistoryForAI(int $limit = 10): array
    {
        return $this->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->message,
                ];
            })
            ->toArray();
    }

    /**
     * Close conversation
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Archive conversation
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Scope: กรองตาม platform (line, facebook, web, etc.)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $platform  ชื่อ platform
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * ค้นหาหรือสร้าง conversation สำหรับ platform + user
     *
     * - ถ้ามี conversation ที่ active อยู่ (status = active, ยังไม่หมดอายุ) → ใช้ต่อ
     * - ถ้าไม่มี → สร้างใหม่
     * - ปิด conversation ที่ไม่มีข้อความ > 30 นาทีอัตโนมัติ
     *
     * @param  string  $platformUserId  user ID ของ platform (Facebook PSID / LINE user ID)
     * @param  string  $platform  ชื่อ platform (line, facebook)
     * @param  int  $timeoutMinutes  หมดเวลาถ้าไม่มีข้อความ (นาที)
     * @return self
     */
    public static function findOrCreateForPlatform(
        string $platformUserId,
        string $platform = 'line',
        int $timeoutMinutes = 1440 // 🌙 (2026-05-14) default 24hr — แม่หมอจำคุยกับลูกค้าได้ทั้งวัน
    ): self {
        // ปิด conversation ที่หมดอายุก่อน (ไม่มีข้อความ > timeout)
        static::where('line_user_id', $platformUserId)
            ->forPlatform($platform)
            ->where('status', 'active')
            ->where('last_message_at', '<', now()->subMinutes($timeoutMinutes))
            ->update(['status' => 'closed']);

        // ค้นหา conversation ที่ active อยู่
        $conversation = static::where('line_user_id', $platformUserId)
            ->forPlatform($platform)
            ->where('status', 'active')
            ->latest('last_message_at')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // สร้าง conversation ใหม่
        return static::create([
            'line_user_id' => $platformUserId,
            'platform' => $platform,
            'status' => 'active',
            'last_message_at' => now(),
            'message_count' => 0,
        ]);
    }
}
