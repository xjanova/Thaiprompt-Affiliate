<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneAdminQA Model
 *
 * เก็บคู่ "คำถามลูกค้า ↔ คำตอบแอดมิน" สำหรับระบบ RAG
 *
 * @property int $id
 * @property string $q_text คำถามลูกค้า
 * @property array|null $q_embedding vector embedding 768-dim
 * @property string $a_text คำตอบแอดมิน
 * @property int|null $admin_user_id user id ของแอดมิน (null = FB Page Inbox)
 * @property string $source_platform facebook|line|manual
 * @property string|null $source_user_id ลูกค้าที่ได้รับคำตอบ (FB PSID / LINE userId)
 * @property array|null $context_json บริบทเพิ่มเติม (previous turns, page_id, etc.)
 * @property float|null $similarity_threshold override threshold (null = ใช้ default)
 * @property bool $is_active
 * @property int $hit_count
 * @property \Carbon\Carbon|null $last_hit_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneAdminQA extends Model
{
    /**
     * ชื่อตาราง
     */
    protected $table = 'fortune_admin_qa';

    /**
     * Source platform enums
     */
    public const SOURCE_FACEBOOK = 'facebook';

    public const SOURCE_LINE = 'line';

    public const SOURCE_MANUAL = 'manual';

    /**
     * Default threshold ถ้าไม่ override ใน row
     */
    public const DEFAULT_THRESHOLD = 0.78;

    /**
     * Mass-assignable
     *
     * @var array<string>
     */
    protected $fillable = [
        'q_text',
        'q_embedding',
        'a_text',
        'admin_user_id',
        'source_platform',
        'source_user_id',
        'context_json',
        'similarity_threshold',
        'is_active',
        'hit_count',
        'last_hit_at',
    ];

    /**
     * Casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        'q_embedding' => 'array',
        'context_json' => 'array',
        'similarity_threshold' => 'float',
        'is_active' => 'boolean',
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: admin user
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Scope: active rows only (สำหรับ retrieve)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: has embedding (ใช้ retrieve ได้)
     */
    public function scopeHasEmbedding($query)
    {
        return $query->whereNotNull('q_embedding');
    }

    /**
     * บันทึก hit (ถูก retrieve)
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
        $this->forceFill(['last_hit_at' => now()])->save();
    }
}
