<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ThemePreset Model
 *
 * ชุด preset สีและการตั้งค่าสำหรับ Arrow X Theme
 *
 * @property int $id
 * @property string $name ชื่อ preset (เช่น Classic, Modern)
 * @property string $display_name ชื่อแสดง (ภาษาไทย)
 * @property string|null $description คำอธิบาย
 * @property string|null $preview_image รูป preview
 * @property array $colors สี theme (JSON)
 * @property bool $is_featured แนะนำหรือไม่
 * @property bool $is_active เปิดใช้งานหรือไม่
 * @property int $usage_count จำนวนครั้งที่ถูกใช้
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ThemePreset extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'theme_presets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'preview_image',
        'colors',
        'is_featured',
        'is_active',
        'usage_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'colors' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_featured' => false,
        'is_active' => true,
        'usage_count' => 0,
    ];

    /**
     * เพิ่มจำนวนการใช้งาน
     *
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * ดึงสีจาก preset
     *
     * @param string $key คีย์สี (เช่น 'primary_start', 'success_color')
     * @param string|null $default ค่า default
     * @return string|null
     */
    public function getColor(string $key, ?string $default = null): ?string
    {
        return $this->colors[$key] ?? $default;
    }

    /**
     * ดึง Gradient CSS
     *
     * @param string $type 'primary' หรือ 'secondary'
     * @return string
     */
    public function getGradientCss(string $type = 'primary'): string
    {
        $direction = $this->getColor('gradient_direction', 'to-right');

        if ($type === 'primary') {
            return sprintf(
                'linear-gradient(%s, %s, %s, %s)',
                $direction,
                $this->getColor('primary_start', '#9333EA'),
                $this->getColor('primary_middle', '#EC4899'),
                $this->getColor('primary_end', '#F97316')
            );
        }

        return sprintf(
            'linear-gradient(%s, %s, %s, %s)',
            $direction,
            $this->getColor('secondary_start', '#3B82F6'),
            $this->getColor('secondary_middle', '#06B6D4'),
            $this->getColor('secondary_end', '#14B8A6')
        );
    }

    /**
     * Apply preset นี้ไปยัง ThemeColor
     *
     * @param \App\Models\ThemeColor $themeColor
     * @return void
     */
    public function applyToThemeColor($themeColor): void
    {
        $themeColor->update([
            'scheme_name' => $this->display_name,
            'primary_start' => $this->getColor('primary_start'),
            'primary_middle' => $this->getColor('primary_middle'),
            'primary_end' => $this->getColor('primary_end'),
            'secondary_start' => $this->getColor('secondary_start'),
            'secondary_middle' => $this->getColor('secondary_middle'),
            'secondary_end' => $this->getColor('secondary_end'),
            'success_color' => $this->getColor('success_color'),
            'warning_color' => $this->getColor('warning_color'),
            'error_color' => $this->getColor('error_color'),
            'info_color' => $this->getColor('info_color'),
            'accent_color' => $this->getColor('accent_color'),
            'gradient_direction' => $this->getColor('gradient_direction'),
        ]);

        // เพิ่มจำนวนการใช้งาน
        $this->incrementUsage();
    }

    /**
     * Scope: Featured presets
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Scope: Active presets
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Popular presets (จาก usage_count)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->where('is_active', true)
            ->orderBy('usage_count', 'desc')
            ->limit($limit);
    }
}
