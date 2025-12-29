<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ForumTrophy Model - โทรฟี่/รางวัลในฟอรั่ม
 *
 * รองรับทั้งโทรฟี่ดี (positive) และไม่ดี (negative)
 * โทรฟี่ไม่ดีจะติดตลอดไป (is_permanent = true)
 *
 * @property int $id
 * @property string $name ชื่อโทรฟี่
 * @property string $slug Slug
 * @property string|null $description คำอธิบาย
 * @property string $icon ไอคอน (Font Awesome/emoji)
 * @property string|null $icon_color สีไอคอน
 * @property string|null $name_color สีชื่อผู้ใช้ที่ได้รับโทรฟี่
 * @property string|null $badge_gradient_from สี gradient เริ่มต้น
 * @property string|null $badge_gradient_to สี gradient สิ้นสุด
 * @property string $type ประเภท (positive/negative)
 * @property bool $is_permanent ติดตลอด (ถอดไม่ได้)
 * @property string $requirement_type เงื่อนไขการได้รับ
 * @property int $requirement_value ค่าเงื่อนไข
 * @property int $display_order ลำดับการแสดงผล
 * @property bool $is_active เปิดใช้งาน
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $users
 */
class ForumTrophy extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'forum_trophies';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'icon_color',
        'name_color',
        'badge_gradient_from',
        'badge_gradient_to',
        'type',
        'is_permanent',
        'requirement_type',
        'requirement_value',
        'display_order',
        'is_active',
    ];

    /**
     * การ cast ค่า
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_permanent' => 'boolean',
        'is_active' => 'boolean',
        'requirement_value' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * ความสัมพันธ์กับผู้ใช้ที่ได้รับโทรฟี่
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_forum_trophies', 'trophy_id', 'user_id')
            ->withPivot(['awarded_at', 'awarded_by', 'reason'])
            ->withTimestamps();
    }

    /**
     * Scope: โทรฟี่ที่เปิดใช้งาน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: โทรฟี่ดี
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePositive($query)
    {
        return $query->where('type', 'positive');
    }

    /**
     * Scope: โทรฟี่ไม่ดี
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNegative($query)
    {
        return $query->where('type', 'negative');
    }

    /**
     * Scope: โทรฟี่ที่ให้อัตโนมัติ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutomatic($query)
    {
        return $query->where('requirement_type', '!=', 'manual');
    }

    /**
     * Scope: โทรฟี่ที่ให้ manual
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->where('requirement_type', 'manual');
    }

    /**
     * Scope: เรียงตามลำดับ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีคุณสมบัติได้รับโทรฟี่นี้หรือไม่
     */
    public function isQualified(User $user): bool
    {
        if ($this->requirement_type === 'manual') {
            return false; // ต้องให้ manual เท่านั้น
        }

        $value = match ($this->requirement_type) {
            'likes_received' => $user->forum_likes_received ?? 0,
            'posts_count' => $user->forum_posts_count ?? 0,
            'threads_count' => $user->forum_threads_count ?? 0,
            'best_answers_count' => $user->forum_best_answers_count ?? 0,
            'spam_reports' => $user->forum_spam_reports ?? 0,
            'warnings_count' => $user->forum_warnings_count ?? 0,
            'banned_count' => $user->forum_banned_count ?? 0,
            default => 0,
        };

        return $value >= $this->requirement_value;
    }

    /**
     * มอบโทรฟี่ให้ผู้ใช้
     *
     * @param  User|null  $awardedBy  ผู้มอบ (null = ระบบ)
     * @param  string|null  $reason  เหตุผล
     */
    public function awardTo(User $user, ?User $awardedBy = null, ?string $reason = null): bool
    {
        // ตรวจสอบว่าได้รับแล้วหรือยัง
        if ($this->users()->where('user_id', $user->id)->exists()) {
            return false;
        }

        $this->users()->attach($user->id, [
            'awarded_at' => now(),
            'awarded_by' => $awardedBy?->id,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * ถอนโทรฟี่จากผู้ใช้ (ไม่ได้สำหรับ permanent)
     */
    public function revokeFrom(User $user): bool
    {
        // ถ้าเป็น permanent ถอดไม่ได้
        if ($this->is_permanent) {
            return false;
        }

        $this->users()->detach($user->id);

        return true;
    }

    /**
     * ดึง gradient class สำหรับ badge
     */
    public function getBadgeGradientClassAttribute(): string
    {
        if ($this->badge_gradient_from && $this->badge_gradient_to) {
            return "from-{$this->badge_gradient_from} to-{$this->badge_gradient_to}";
        }

        return $this->type === 'positive'
            ? 'from-yellow-400 to-orange-500'
            : 'from-red-500 to-pink-600';
    }

    /**
     * ดึงสีไอคอนเริ่มต้น
     */
    public function getIconColorClassAttribute(): string
    {
        if ($this->icon_color) {
            return $this->icon_color;
        }

        return $this->type === 'positive' ? 'text-yellow-500' : 'text-red-500';
    }
}
