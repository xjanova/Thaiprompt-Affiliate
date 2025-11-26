<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HomepageElement Model
 *
 * จัดการ elements ภายใน section บนหน้าแรก
 * แต่ละ element สามารถลากวางตำแหน่งได้อิสระ
 *
 * @property int $id
 * @property int $homepage_section_id
 * @property string|null $name ชื่อ element
 * @property string $type ประเภท: text, heading, image, button, video, icon, shape, html
 * @property int $order ลำดับ z-index
 * @property bool $is_active สถานะเปิด/ปิด
 * @property string $position_type relative หรือ absolute
 * @property float $position_x ตำแหน่ง X
 * @property float $position_y ตำแหน่ง Y
 * @property string $position_x_unit หน่วย X
 * @property string $position_y_unit หน่วย Y
 * @property string $anchor_x จุดยึด X
 * @property string $anchor_y จุดยึด Y
 * @property string $width ความกว้าง
 * @property string $height ความสูง
 * @property string|null $max_width ความกว้างสูงสุด
 * @property string|null $content เนื้อหาหลัก
 * @property string|null $content_en เนื้อหาภาษาอังกฤษ
 * @property string|null $image_url URL รูปภาพ
 * @property string|null $image_url_dark URL รูปภาพ Dark Mode
 * @property string|null $video_url URL วิดีโอ
 * @property string|null $link_url URL ปลายทาง
 * @property string $link_target _self หรือ _blank
 * @property string|null $icon_class Class ของ icon
 * @property string|null $font_family Font family
 * @property string $font_size ขนาดตัวอักษร
 * @property string $font_weight น้ำหนักตัวอักษร
 * @property string $line_height ระยะห่างบรรทัด
 * @property string $letter_spacing ระยะห่างตัวอักษร
 * @property string $text_align การจัดตำแหน่งข้อความ
 * @property string $text_transform การแปลงตัวอักษร
 * @property string $color สีข้อความ
 * @property string $color_dark สี Dark Mode
 * @property string|null $background_color สีพื้นหลัง
 * @property string|null $background_color_dark สีพื้นหลัง Dark Mode
 * @property string|null $border_color สีขอบ
 * @property string|null $border_color_dark สีขอบ Dark Mode
 * @property string $border_width ความหนาขอบ
 * @property string $border_style รูปแบบขอบ
 * @property string $border_radius ความโค้งมน
 * @property string $padding padding
 * @property string $margin margin
 * @property string|null $box_shadow เงา
 * @property string|null $text_shadow เงาข้อความ
 * @property string|null $animation Animation
 * @property int $animation_delay Delay animation
 * @property int $animation_duration Duration animation
 * @property array|null $hover_effects Effects เมื่อ hover
 * @property array|null $responsive_settings การตั้งค่า responsive
 * @property array|null $settings การตั้งค่าเพิ่มเติม
 */
class HomepageElement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'homepage_elements';

    /**
     * ประเภท element ที่รองรับ
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'heading' => 'Heading (H1-H6)',
        'text' => 'Text/Paragraph',
        'image' => 'Image',
        'button' => 'Button',
        'video' => 'Video',
        'icon' => 'Icon',
        'shape' => 'Shape/Divider',
        'html' => 'Custom HTML',
        'spacer' => 'Spacer',
        'container' => 'Container/Box',
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
        'slideInLeft' => 'Slide In Left',
        'slideInRight' => 'Slide In Right',
        'zoomIn' => 'Zoom In',
        'zoomInUp' => 'Zoom In Up',
        'bounceIn' => 'Bounce In',
        'rotateIn' => 'Rotate In',
        'flipInX' => 'Flip In X',
        'flipInY' => 'Flip In Y',
    ];

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'homepage_section_id',
        'name',
        'type',
        'order',
        'is_active',
        'position_type',
        'position_x',
        'position_y',
        'position_x_unit',
        'position_y_unit',
        'anchor_x',
        'anchor_y',
        'width',
        'height',
        'max_width',
        'content',
        'content_en',
        'image_url',
        'image_url_dark',
        'video_url',
        'link_url',
        'link_target',
        'icon_class',
        'font_family',
        'font_size',
        'font_weight',
        'line_height',
        'letter_spacing',
        'text_align',
        'text_transform',
        'color',
        'color_dark',
        'background_color',
        'background_color_dark',
        'border_color',
        'border_color_dark',
        'border_width',
        'border_style',
        'border_radius',
        'padding',
        'margin',
        'box_shadow',
        'text_shadow',
        'animation',
        'animation_delay',
        'animation_duration',
        'hover_effects',
        'responsive_settings',
        'settings',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'homepage_section_id' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'position_x' => 'decimal:2',
        'position_y' => 'decimal:2',
        'animation_delay' => 'integer',
        'animation_duration' => 'integer',
        'hover_effects' => 'array',
        'responsive_settings' => 'array',
        'settings' => 'array',
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
        'type' => 'text',
        'order' => 0,
        'is_active' => true,
        'position_type' => 'relative',
        'position_x' => 0,
        'position_y' => 0,
        'position_x_unit' => '%',
        'position_y_unit' => '%',
        'anchor_x' => 'left',
        'anchor_y' => 'top',
        'width' => 'auto',
        'height' => 'auto',
        'font_size' => '16px',
        'font_weight' => '400',
        'line_height' => '1.5',
        'letter_spacing' => 'normal',
        'text_align' => 'left',
        'text_transform' => 'none',
        'color' => '#000000',
        'color_dark' => '#ffffff',
        'border_width' => '0',
        'border_style' => 'solid',
        'border_radius' => '0',
        'padding' => '0',
        'margin' => '0',
        'animation_delay' => 0,
        'animation_duration' => 500,
        'link_target' => '_self',
    ];

    /**
     * ความสัมพันธ์กับ section
     *
     * @return BelongsTo
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(HomepageSection::class, 'homepage_section_id');
    }

    /**
     * Scope: ดึงเฉพาะ elements ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม order
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Scope: ดึงตามประเภท
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * สร้าง CSS style string สำหรับ element
     *
     * @param bool $isDarkMode
     * @return string
     */
    public function getStyleString(bool $isDarkMode = false): string
    {
        $styles = [];

        // Position
        if ($this->position_type === 'absolute') {
            $styles[] = "position: absolute";

            // Transform based on anchor
            $translateX = $this->anchor_x === 'center' ? '-50%' : ($this->anchor_x === 'right' ? '-100%' : '0');
            $translateY = $this->anchor_y === 'center' ? '-50%' : ($this->anchor_y === 'bottom' ? '-100%' : '0');

            if ($translateX !== '0' || $translateY !== '0') {
                $styles[] = "transform: translate({$translateX}, {$translateY})";
            }

            // Position coordinates
            $xProp = $this->anchor_x === 'right' ? 'right' : 'left';
            $yProp = $this->anchor_y === 'bottom' ? 'bottom' : 'top';
            $styles[] = "{$xProp}: {$this->position_x}{$this->position_x_unit}";
            $styles[] = "{$yProp}: {$this->position_y}{$this->position_y_unit}";
        }

        // Size
        if ($this->width !== 'auto') {
            $styles[] = "width: {$this->width}";
        }
        if ($this->height !== 'auto') {
            $styles[] = "height: {$this->height}";
        }
        if ($this->max_width) {
            $styles[] = "max-width: {$this->max_width}";
        }

        // Typography
        if ($this->font_family) {
            $styles[] = "font-family: {$this->font_family}";
        }
        $styles[] = "font-size: {$this->font_size}";
        $styles[] = "font-weight: {$this->font_weight}";
        $styles[] = "line-height: {$this->line_height}";
        $styles[] = "letter-spacing: {$this->letter_spacing}";
        $styles[] = "text-align: {$this->text_align}";
        $styles[] = "text-transform: {$this->text_transform}";

        // Colors
        $color = $isDarkMode ? $this->color_dark : $this->color;
        $styles[] = "color: {$color}";

        $bgColor = $isDarkMode ? $this->background_color_dark : $this->background_color;
        if ($bgColor) {
            $styles[] = "background-color: {$bgColor}";
        }

        $borderColor = $isDarkMode ? $this->border_color_dark : $this->border_color;
        if ($borderColor && $this->border_width !== '0') {
            $styles[] = "border: {$this->border_width} {$this->border_style} {$borderColor}";
        }

        // Border radius
        if ($this->border_radius !== '0') {
            $styles[] = "border-radius: {$this->border_radius}";
        }

        // Spacing
        if ($this->padding !== '0') {
            $styles[] = "padding: {$this->padding}";
        }
        if ($this->margin !== '0') {
            $styles[] = "margin: {$this->margin}";
        }

        // Shadow
        if ($this->box_shadow) {
            $styles[] = "box-shadow: {$this->box_shadow}";
        }
        if ($this->text_shadow) {
            $styles[] = "text-shadow: {$this->text_shadow}";
        }

        // Z-index
        $styles[] = "z-index: {$this->order}";

        return implode('; ', $styles);
    }

    /**
     * ดึงเนื้อหาตามภาษา
     *
     * @param string $locale
     * @return string|null
     */
    public function getLocalizedContent(string $locale = 'th'): ?string
    {
        if ($locale === 'en' && $this->content_en) {
            return $this->content_en;
        }

        return $this->content;
    }

    /**
     * ดึง URL รูปภาพตาม mode
     *
     * @param bool $isDarkMode
     * @return string|null
     */
    public function getImageUrl(bool $isDarkMode = false): ?string
    {
        if ($isDarkMode && $this->image_url_dark) {
            return $this->image_url_dark;
        }

        return $this->image_url;
    }

    /**
     * แปลงเป็น array สำหรับ API response
     *
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->homepage_section_id,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => self::TYPES[$this->type] ?? $this->type,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'position' => [
                'type' => $this->position_type,
                'x' => $this->position_x,
                'y' => $this->position_y,
                'x_unit' => $this->position_x_unit,
                'y_unit' => $this->position_y_unit,
                'anchor_x' => $this->anchor_x,
                'anchor_y' => $this->anchor_y,
            ],
            'size' => [
                'width' => $this->width,
                'height' => $this->height,
                'max_width' => $this->max_width,
            ],
            'content' => [
                'text' => $this->content,
                'text_en' => $this->content_en,
                'image_url' => $this->image_url,
                'image_url_dark' => $this->image_url_dark,
                'video_url' => $this->video_url,
                'link_url' => $this->link_url,
                'link_target' => $this->link_target,
                'icon_class' => $this->icon_class,
            ],
            'typography' => [
                'font_family' => $this->font_family,
                'font_size' => $this->font_size,
                'font_weight' => $this->font_weight,
                'line_height' => $this->line_height,
                'letter_spacing' => $this->letter_spacing,
                'text_align' => $this->text_align,
                'text_transform' => $this->text_transform,
            ],
            'colors' => [
                'color' => $this->color,
                'color_dark' => $this->color_dark,
                'background_color' => $this->background_color,
                'background_color_dark' => $this->background_color_dark,
                'border_color' => $this->border_color,
                'border_color_dark' => $this->border_color_dark,
            ],
            'border' => [
                'width' => $this->border_width,
                'style' => $this->border_style,
                'radius' => $this->border_radius,
            ],
            'spacing' => [
                'padding' => $this->padding,
                'margin' => $this->margin,
            ],
            'effects' => [
                'box_shadow' => $this->box_shadow,
                'text_shadow' => $this->text_shadow,
            ],
            'animation' => [
                'type' => $this->animation,
                'delay' => $this->animation_delay,
                'duration' => $this->animation_duration,
            ],
            'hover_effects' => $this->hover_effects ?? [],
            'responsive_settings' => $this->responsive_settings ?? [],
            'settings' => $this->settings ?? [],
        ];
    }
}
