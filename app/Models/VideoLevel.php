<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'name',
        'icon',
        'stars',
        'star_color',
        'badge_image',
        'title_prefix',
        'required_exp',
        'max_daily_quests',
        'max_weekly_quests',
        'reward_multiplier',
        'coin_bonus',
        'perks',
        'description',
    ];

    protected $casts = [
        'level' => 'integer',
        'stars' => 'integer',
        'required_exp' => 'integer',
        'max_daily_quests' => 'integer',
        'max_weekly_quests' => 'integer',
        'reward_multiplier' => 'decimal:2',
        'coin_bonus' => 'decimal:2',
        'perks' => 'array',
    ];

    /**
     * สีดาวที่รองรับ
     */
    public const STAR_COLORS = [
        'bronze' => ['name' => 'ทองแดง', 'hex' => '#CD7F32', 'gradient' => 'from-amber-600 to-amber-800'],
        'silver' => ['name' => 'เงิน', 'hex' => '#C0C0C0', 'gradient' => 'from-gray-300 to-gray-500'],
        'gold' => ['name' => 'ทอง', 'hex' => '#FFD700', 'gradient' => 'from-yellow-400 to-yellow-600'],
        'platinum' => ['name' => 'แพลตินัม', 'hex' => '#E5E4E2', 'gradient' => 'from-slate-200 to-slate-400'],
        'diamond' => ['name' => 'เพชร', 'hex' => '#B9F2FF', 'gradient' => 'from-cyan-300 to-blue-500'],
    ];

    /**
     * Get users at this level
     */
    public function users()
    {
        return $this->hasMany(UserVideoLevel::class, 'current_level_id');
    }

    /**
     * Get next level
     */
    public function getNextLevelAttribute()
    {
        return static::where('level', $this->level + 1)->first();
    }

    /**
     * Get previous level
     */
    public function getPreviousLevelAttribute()
    {
        return static::where('level', $this->level - 1)->first();
    }

    /**
     * Check if user can level up
     */
    public function canUserLevelUp($currentExp)
    {
        return $currentExp >= $this->required_exp;
    }

    /**
     * Scope for level range
     */
    public function scopeForLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // ==================== STAR ACCESSORS ====================

    /**
     * ดึงข้อมูลสีดาว
     *
     * @return array
     */
    public function getStarColorInfoAttribute(): array
    {
        $color = $this->star_color ?? 'bronze';
        return self::STAR_COLORS[$color] ?? self::STAR_COLORS['bronze'];
    }

    /**
     * ดึงชื่อสีดาวภาษาไทย
     *
     * @return string
     */
    public function getStarColorNameAttribute(): string
    {
        return $this->star_color_info['name'];
    }

    /**
     * ดึงค่า hex ของสีดาว
     *
     * @return string
     */
    public function getStarColorHexAttribute(): string
    {
        return $this->star_color_info['hex'];
    }

    /**
     * ดึง Tailwind gradient class ของสีดาว
     *
     * @return string
     */
    public function getStarGradientClassAttribute(): string
    {
        return $this->star_color_info['gradient'];
    }

    /**
     * ดึงดาวเป็น HTML (สำหรับแสดงใน view)
     *
     * @return string
     */
    public function getStarsHtmlAttribute(): string
    {
        $stars = $this->stars ?? 1;
        $color = $this->star_color_hex;
        $html = '';

        for ($i = 0; $i < $stars; $i++) {
            $html .= '<svg class="w-5 h-5 inline-block" style="color: ' . $color . '" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>';
        }

        return $html;
    }

    /**
     * ดึงชื่อเต็ม (prefix + name)
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        if ($this->title_prefix) {
            return $this->title_prefix . ' ' . $this->name;
        }
        return $this->name;
    }

    /**
     * ดึง badge URL หรือ default
     *
     * @return string
     */
    public function getBadgeUrlAttribute(): string
    {
        if ($this->badge_image) {
            return asset('storage/' . $this->badge_image);
        }

        // Default badge based on star color
        return asset("images/badges/{$this->star_color}_badge.png");
    }
}
