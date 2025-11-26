<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IdCardSetting Model
 *
 * เก็บการตั้งค่า Virtual ID Card สำหรับแต่ละระดับ Rank
 * รวมถึงตำแหน่งขององค์ประกอบต่างๆ บนบัตร
 *
 * @property int $id
 * @property int|null $rank_level ระดับ Rank (1-8, null = ใช้กับทุกระดับ)
 * @property string $name ชื่อการตั้งค่า
 * @property string|null $name_th ชื่อภาษาไทย
 * @property string $background_type ประเภทพื้นหลัง: gradient, image, color
 * @property string|null $background_color สีพื้นหลัง
 * @property array|null $background_gradient ข้อมูล Gradient
 * @property string|null $background_image URL รูปภาพพื้นหลัง
 * @property string|null $overlay_color สี Overlay
 * @property int $overlay_opacity ความโปร่งใส Overlay
 * @property array|null $logo_position ตำแหน่ง Logo
 * @property array|null $profile_position ตำแหน่งรูปโปรไฟล์
 * @property array|null $name_position ตำแหน่งชื่อ
 * @property array|null $rank_badge_position ตำแหน่ง Rank Badge
 * @property array|null $member_number_position ตำแหน่งเลขสมาชิก
 * @property array|null $qr_position ตำแหน่ง QR Code
 * @property array|null $member_since_position ตำแหน่งวันที่สมัคร
 * @property array|null $stars_position ตำแหน่งดาว
 * @property array|null $border_style สไตล์ขอบ
 * @property array|null $shadow_style สไตล์เงา
 * @property array|null $effects เอฟเฟกต์พิเศษ
 * @property string|null $custom_css CSS เพิ่มเติม
 * @property bool $is_active สถานะใช้งาน
 * @property bool $is_default เป็นค่าเริ่มต้น
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class IdCardSetting extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'id_card_settings';

    /**
     * คอลัมน์ที่สามารถกรอกได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'rank_level',
        'name',
        'name_th',
        'background_type',
        'background_color',
        'background_gradient',
        'background_image',
        'overlay_color',
        'overlay_opacity',
        'logo_position',
        'profile_position',
        'name_position',
        'rank_badge_position',
        'member_number_position',
        'qr_position',
        'member_since_position',
        'stars_position',
        'border_style',
        'shadow_style',
        'effects',
        'custom_css',
        'is_active',
        'is_default',
    ];

    /**
     * การ cast คอลัมน์
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rank_level' => 'integer',
        'overlay_opacity' => 'integer',
        'background_gradient' => 'array',
        'logo_position' => 'array',
        'profile_position' => 'array',
        'name_position' => 'array',
        'rank_badge_position' => 'array',
        'member_number_position' => 'array',
        'qr_position' => 'array',
        'member_since_position' => 'array',
        'stars_position' => 'array',
        'border_style' => 'array',
        'shadow_style' => 'array',
        'effects' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'background_type' => 'gradient',
        'overlay_opacity' => 0,
        'is_active' => true,
        'is_default' => false,
    ];

    /**
     * Scope สำหรับดึง settings ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope สำหรับดึง settings ตาม rank level
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $rankLevel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRank($query, ?int $rankLevel)
    {
        return $query->where(function ($q) use ($rankLevel) {
            $q->where('rank_level', $rankLevel)
              ->orWhereNull('rank_level');
        });
    }

    /**
     * ดึงการตั้งค่าที่ active สำหรับ rank ที่ระบุ
     *
     * @param int $rankLevel
     * @return self|null
     */
    public static function getActiveSettingsForRank(int $rankLevel): ?self
    {
        // ลองหาสำหรับ rank นี้โดยเฉพาะ
        $settings = self::active()
            ->where('rank_level', $rankLevel)
            ->first();

        // ถ้าไม่พบ ใช้ค่า default (rank_level = null)
        if (!$settings) {
            $settings = self::active()
                ->whereNull('rank_level')
                ->where('is_default', true)
                ->first();
        }

        return $settings;
    }

    /**
     * ดึงตำแหน่งเริ่มต้นสำหรับองค์ประกอบทั้งหมด
     *
     * @return array
     */
    public static function getDefaultPositions(): array
    {
        return [
            'logo_position' => ['x' => 24, 'y' => 24, 'visible' => true, 'width' => 48, 'height' => 48],
            'profile_position' => ['x' => 24, 'y' => 'bottom-24', 'visible' => true, 'width' => 80, 'height' => 80],
            'name_position' => ['x' => 112, 'y' => 'bottom-64', 'visible' => true],
            'rank_badge_position' => ['x' => 'right-24', 'y' => 24, 'visible' => true],
            'member_number_position' => ['x' => 112, 'y' => 'bottom-32', 'visible' => true],
            'qr_position' => ['x' => 'right-24', 'y' => 'bottom-24', 'visible' => true, 'width' => 56, 'height' => 56],
            'member_since_position' => ['x' => 'right-24', 'y' => 'bottom-64', 'visible' => true],
            'stars_position' => ['x' => 'profile-bottom', 'y' => 'profile-right', 'visible' => true],
        ];
    }

    /**
     * ดึง gradient เริ่มต้นสำหรับแต่ละ rank
     *
     * @param int $rankLevel
     * @return array
     */
    public static function getDefaultGradientForRank(int $rankLevel): array
    {
        $gradients = [
            1 => ['from' => '#B45309', 'via' => '#C2410C', 'to' => '#92400E', 'direction' => 'br'],
            2 => ['from' => '#9CA3AF', 'via' => '#D1D5DB', 'to' => '#6B7280', 'direction' => 'br'],
            3 => ['from' => '#EAB308', 'via' => '#F59E0B', 'to' => '#D97706', 'direction' => 'br'],
            4 => ['from' => '#CBD5E1', 'via' => '#E2E8F0', 'to' => '#94A3B8', 'direction' => 'br'],
            5 => ['from' => '#22D3EE', 'via' => '#0EA5E9', 'to' => '#3B82F6', 'direction' => 'br'],
            6 => ['from' => '#F59E0B', 'via' => '#FBBF24', 'to' => '#F97316', 'direction' => 'br'],
            7 => ['from' => '#9333EA', 'via' => '#8B5CF6', 'to' => '#4F46E5', 'direction' => 'br'],
            8 => ['from' => '#EC4899', 'via' => '#9333EA', 'to' => '#4F46E5', 'direction' => 'br'],
        ];

        return $gradients[$rankLevel] ?? $gradients[1];
    }

    /**
     * รวม settings กับตำแหน่งเริ่มต้น
     *
     * @return array
     */
    public function getMergedPositions(): array
    {
        $defaults = self::getDefaultPositions();

        return [
            'logo' => array_merge($defaults['logo_position'], $this->logo_position ?? []),
            'profile' => array_merge($defaults['profile_position'], $this->profile_position ?? []),
            'name' => array_merge($defaults['name_position'], $this->name_position ?? []),
            'rank_badge' => array_merge($defaults['rank_badge_position'], $this->rank_badge_position ?? []),
            'member_number' => array_merge($defaults['member_number_position'], $this->member_number_position ?? []),
            'qr' => array_merge($defaults['qr_position'], $this->qr_position ?? []),
            'member_since' => array_merge($defaults['member_since_position'], $this->member_since_position ?? []),
            'stars' => array_merge($defaults['stars_position'], $this->stars_position ?? []),
        ];
    }

    /**
     * แปลงเป็น CSS background
     *
     * @return string
     */
    public function getBackgroundCss(): string
    {
        switch ($this->background_type) {
            case 'color':
                return "background-color: {$this->background_color};";

            case 'image':
                $overlay = $this->overlay_color && $this->overlay_opacity > 0
                    ? "linear-gradient({$this->overlay_color}".($this->overlay_opacity / 100).", {$this->overlay_color}".($this->overlay_opacity / 100)."), "
                    : '';
                return "background: {$overlay}url('{$this->background_image}') center/cover no-repeat;";

            case 'gradient':
            default:
                if (!$this->background_gradient) {
                    return 'background: linear-gradient(to bottom right, #8B5CF6, #EC4899);';
                }

                $g = $this->background_gradient;
                $direction = $g['direction'] ?? 'br';
                $directionMap = [
                    'r' => 'to right',
                    'l' => 'to left',
                    't' => 'to top',
                    'b' => 'to bottom',
                    'br' => 'to bottom right',
                    'bl' => 'to bottom left',
                    'tr' => 'to top right',
                    'tl' => 'to top left',
                ];
                $dir = $directionMap[$direction] ?? 'to bottom right';

                $colors = array_filter([$g['from'] ?? null, $g['via'] ?? null, $g['to'] ?? null]);
                return "background: linear-gradient({$dir}, " . implode(', ', $colors) . ");";
        }
    }
}
