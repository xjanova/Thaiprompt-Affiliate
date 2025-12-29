<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HomepageSection Model
 *
 * จัดการ sections บนหน้าแรกของเว็บไซต์
 * แต่ละ section สามารถมี elements หลายตัวภายใน
 *
 * @property int $id
 * @property string $name ชื่อ section
 * @property string $type ประเภท: hero, features, stats, testimonials, cta, gallery, custom
 * @property int $order ลำดับการแสดงผล
 * @property bool $is_active สถานะเปิด/ปิด
 * @property bool $is_fullwidth แสดงเต็มความกว้าง
 * @property string $min_height ความสูงขั้นต่ำ
 * @property string|null $max_height ความสูงสูงสุด
 * @property string $padding padding ของ section
 * @property string $background_type ประเภทพื้นหลัง
 * @property string $background_color สีพื้นหลัง
 * @property string $background_color_dark สีพื้นหลัง Dark Mode
 * @property string|null $background_gradient Gradient CSS
 * @property string|null $background_image URL รูปพื้นหลัง
 * @property string|null $background_video URL วิดีโอพื้นหลัง
 * @property string|null $background_overlay Overlay color
 * @property string $background_position ตำแหน่งพื้นหลัง
 * @property string $background_size ขนาดพื้นหลัง
 * @property bool $background_fixed Parallax
 * @property string|null $animation Animation เมื่อ scroll
 * @property int $animation_delay Delay animation
 * @property array|null $settings การตั้งค่าเพิ่มเติม
 * @property array|null $responsive_settings การตั้งค่า responsive
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class HomepageSection extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'homepage_sections';

    /**
     * ประเภท section ที่รองรับ
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'hero' => 'Hero Section',
        'features' => 'Features/Services',
        'stats' => 'Statistics',
        'testimonials' => 'Testimonials',
        'cta' => 'Call to Action',
        'gallery' => 'Gallery',
        'team' => 'Team Members',
        'pricing' => 'Pricing Table',
        'faq' => 'FAQ',
        'contact' => 'Contact Form',
        'custom' => 'Custom Section',
    ];

    /**
     * ประเภทพื้นหลังที่รองรับ
     *
     * @var array<string, string>
     */
    public const BACKGROUND_TYPES = [
        'color' => 'สีเดียว',
        'gradient' => 'ไล่ระดับสี',
        'image' => 'รูปภาพ',
        'video' => 'วิดีโอ',
    ];

    /**
     * Animations ที่รองรับ
     *
     * @var array<string, string>
     */
    public const ANIMATIONS = [
        'none' => 'ไม่มี',
        'fadeIn' => 'Fade In',
        'fadeInUp' => 'Fade In Up',
        'fadeInDown' => 'Fade In Down',
        'fadeInLeft' => 'Fade In Left',
        'fadeInRight' => 'Fade In Right',
        'slideInUp' => 'Slide In Up',
        'slideInDown' => 'Slide In Down',
        'zoomIn' => 'Zoom In',
        'bounceIn' => 'Bounce In',
    ];

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'order',
        'is_active',
        'is_fullwidth',
        'min_height',
        'max_height',
        'padding',
        'background_type',
        'background_color',
        'background_color_dark',
        'background_gradient',
        'background_image',
        'background_video',
        'background_overlay',
        'background_position',
        'background_size',
        'background_fixed',
        'animation',
        'animation_delay',
        'settings',
        'responsive_settings',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_fullwidth' => 'boolean',
        'background_fixed' => 'boolean',
        'animation_delay' => 'integer',
        'order' => 'integer',
        'settings' => 'array',
        'responsive_settings' => 'array',
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
        'type' => 'custom',
        'order' => 0,
        'is_active' => true,
        'is_fullwidth' => true,
        'min_height' => '400px',
        'padding' => '60px 20px',
        'background_type' => 'color',
        'background_color' => '#ffffff',
        'background_color_dark' => '#1f2937',
        'background_position' => 'center center',
        'background_size' => 'cover',
        'background_fixed' => false,
        'animation_delay' => 0,
    ];

    /**
     * ความสัมพันธ์กับ elements ภายใน section
     */
    public function elements(): HasMany
    {
        return $this->hasMany(HomepageElement::class, 'homepage_section_id')
            ->orderBy('order');
    }

    /**
     * ดึงเฉพาะ elements ที่ active
     */
    public function activeElements(): HasMany
    {
        return $this->elements()->where('is_active', true);
    }

    /**
     * Scope: ดึงเฉพาะ sections ที่ active
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม order
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Scope: ดึงตามประเภท
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * สร้าง CSS style string สำหรับ section
     */
    public function getStyleAttribute(bool $isDarkMode = false): string
    {
        $styles = [];

        // ความสูงขั้นต่ำ
        $styles[] = "min-height: {$this->min_height}";

        // ความสูงสูงสุด
        if ($this->max_height) {
            $styles[] = "max-height: {$this->max_height}";
        }

        // Padding
        $styles[] = "padding: {$this->padding}";

        // พื้นหลัง
        $bgColor = $isDarkMode ? $this->background_color_dark : $this->background_color;

        switch ($this->background_type) {
            case 'color':
                $styles[] = "background-color: {$bgColor}";
                break;

            case 'gradient':
                if ($this->background_gradient) {
                    $styles[] = "background: {$this->background_gradient}";
                }
                break;

            case 'image':
                if ($this->background_image) {
                    $styles[] = "background-image: url('{$this->background_image}')";
                    $styles[] = "background-position: {$this->background_position}";
                    $styles[] = "background-size: {$this->background_size}";
                    if ($this->background_fixed) {
                        $styles[] = 'background-attachment: fixed';
                    }
                }
                break;
        }

        return implode('; ', $styles);
    }

    /**
     * แปลงเป็น array สำหรับ API response
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => self::TYPES[$this->type] ?? $this->type,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'is_fullwidth' => $this->is_fullwidth,
            'min_height' => $this->min_height,
            'max_height' => $this->max_height,
            'padding' => $this->padding,
            'background' => [
                'type' => $this->background_type,
                'color' => $this->background_color,
                'color_dark' => $this->background_color_dark,
                'gradient' => $this->background_gradient,
                'image' => $this->background_image,
                'video' => $this->background_video,
                'overlay' => $this->background_overlay,
                'position' => $this->background_position,
                'size' => $this->background_size,
                'fixed' => $this->background_fixed,
            ],
            'animation' => [
                'type' => $this->animation,
                'delay' => $this->animation_delay,
            ],
            'settings' => $this->settings ?? [],
            'responsive_settings' => $this->responsive_settings ?? [],
            'elements' => $this->activeElements->map->toApiArray()->toArray(),
        ];
    }
}
